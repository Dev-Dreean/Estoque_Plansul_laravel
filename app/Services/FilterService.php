<?php

namespace App\Services;

/**
 * Serviço de Filtros Inteligentes
 * 
 * Centraliza a lógica de busca e ordenação com sistema de scoring.
 * Funciona com qualquer array de items e qualquer estrutura de dados.
 * 
 * @example
 * $filtrados = FilterService::filtrar(
 *     $items,
 *     $termo,
 *     ['codigo', 'nome'],  // campos onde buscar
 *     ['codigo' => 'número', 'nome' => 'texto']  // tipos
 * );
 */
class FilterService
{
    /**
     * Realizar busca inteligente com scoring
     * 
     * @param array $items Items para buscar
     * @param string $termo Termo de busca
     * @param array $searchFields Campos onde buscar: ['codigo', 'nome', ...]
     * @param array $fieldTypes Tipos de campos: ['codigo' => 'número', 'nome' => 'texto']
     * @param int $limit Limite de resultados
     * @return array Items filtrados e ordenados
     */
    public static function filtrar(
        $items,
        string $termo,
        array $searchFields = [],
        array $fieldTypes = [],
        int $limit = 100
    ): array {
        $termo = trim($termo);

        // Se não há termo, retornar os primeiros N items
        if ($termo === '') {
            return array_slice($items, 0, $limit);
        }

        $termoLower = strtolower($termo);

        // Calcular score para cada item
        $itemsComScore = collect($items)
            ->map(function ($item) use ($termo, $termoLower, $searchFields, $fieldTypes) {
                $score = self::calcularScore(
                    $item,
                    $termoLower,
                    $searchFields,
                    $fieldTypes
                );

                return [
                    'item' => $item,
                    'score' => $score,
                ];
            })
            ->toArray();

        // Filtrar apenas itens com score finito (encontrados)
        $itemsComScore = array_filter($itemsComScore, function ($x) {
            return $x['score'] < PHP_INT_MAX;
        });

        // Ordenar por score
        usort($itemsComScore, function ($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        // Retornar apenas os items com limite
        return array_slice(
            array_map(fn($x) => $x['item'], $itemsComScore),
            0,
            $limit
        );
    }

    /**
     * Calcular score de um item em relação a um termo
     * 
     * @param mixed $item Item para avaliar
     * @param string $termoLower Termo em lowercase
     * @param array $searchFields Campos onde buscar
     * @param array $fieldTypes Tipos de campos
     * @return int Score (menor = mais relevante)
     */
    private static function calcularScore(
        $item,
        string $termoLower,
        array $searchFields,
        array $fieldTypes
    ): int {
        // Se não há campos de busca, retornar score padrão
        if (empty($searchFields)) {
            return PHP_INT_MAX;
        }

        $melhorScore = PHP_INT_MAX;

        foreach ($searchFields as $field) {
            $valor = self::obterValor($item, $field);
            $tipo = $fieldTypes[$field] ?? 'texto';

            $score = self::calcularScoreCampo($valor, $termoLower, $tipo);
            $melhorScore = min($melhorScore, $score);

            // Se encontrou match exato, parar a busca
            if ($score === 0) {
                break;
            }
        }

        return $melhorScore;
    }

    /**
     * Calcular score de um campo específico
     * 
     * Sistema de pontuação:
     * - 0: Match exato
     * - 10-99: Começa com termo
     * - 50-199: Contém termo
     * - 100-299: Nome começa/contém (para campos secundários)
     * - 500+: Distância de Levenshtein
     * 
     * @param mixed $valor Valor do campo
     * @param string $termo Termo em lowercase
     * @param string $tipo Tipo do campo: 'número' ou 'texto'
     * @return int Score
     */
    private static function calcularScoreCampo($valor, string $termo, string $tipo): int
    {
        if ($valor === null || $valor === '') {
            return PHP_INT_MAX;
        }

        $valorLower = strtolower(trim((string) $valor));

        // 🥇 Match exato
        if ($valorLower === $termo) {
            return 0;
        }

        // 🥈 Começa com o termo
        if (str_starts_with($valorLower, $termo)) {
            $score = 10 + strlen($valorLower);

            // Se for número, penalizar menos para manter números no topo
            if ($tipo === 'número' && is_numeric($valorLower)) {
                $score = 5 + (int) $valorLower;
            }

            return $score;
        }

        // 🥉 Contém o termo
        if (str_contains($valorLower, $termo)) {
            $posicao = strpos($valorLower, $termo);
            $score = 50 + $posicao + strlen($valorLower);
            return $score;
        }

        // ❓ Distância de Levenshtein (similaridade)
        $distancia = self::distanciaLevenshtein($valorLower, $termo);
        if ($distancia <= 2) {  // Muito similar
            return 100 + $distancia;
        }
        if ($distancia <= 5) {  // Razoavelmente similar
            return 200 + $distancia;
        }

        return PHP_INT_MAX;  // Não encontrado
    }

    /**
     * Obter valor de um campo (suporta notação com ponto)
     * 
     * @param mixed $item Item ou array
     * @param string $field Campo: 'nome' ou 'relacionamento.nome'
     * @return mixed Valor do campo
     */
    private static function obterValor($item, string $field)
    {
        $partes = explode('.', $field);
        $valor = $item;

        foreach ($partes as $parte) {
            if (is_array($valor)) {
                $valor = $valor[$parte] ?? null;
            } elseif (is_object($valor)) {
                $valor = $valor->{$parte} ?? null;
            } else {
                return null;
            }
        }

        return $valor;
    }

    /**
     * Calcular distância de Levenshtein entre duas strings
     * 
     * @param string $s1 Primeira string
     * @param string $s2 Segunda string
     * @return int Distância
     */
    private static function distanciaLevenshtein(string $s1, string $s2): int
    {
        $len1 = strlen($s1);
        $len2 = strlen($s2);

        if ($len1 === 0) return $len2;
        if ($len2 === 0) return $len1;

        // Criar matriz
        $matriz = [];
        for ($i = 0; $i <= $len2; $i++) {
            $matriz[0][$i] = $i;
        }
        for ($j = 0; $j <= $len1; $j++) {
            $matriz[$j][0] = $j;
        }

        // Preencher matriz
        for ($j = 1; $j <= $len1; $j++) {
            for ($i = 1; $i <= $len2; $i++) {
                $custo = $s1[$j - 1] === $s2[$i - 1] ? 0 : 1;
                $matriz[$j][$i] = min(
                    $matriz[$j][$i - 1] + 1,      // deleção
                    $matriz[$j - 1][$i] + 1,      // inserção
                    $matriz[$j - 1][$i - 1] + $custo  // substituição
                );
            }
        }

        return $matriz[$len1][$len2];
    }

    /**
     * Ordenar array por múltiplos campos com suporte a tipo de dado
     * 
     * @param array $items Items para ordenar
     * @param array $campos Campos para ordenar: ['codigo' => 'asc', 'nome' => 'asc']
     * @return array Items ordenados
     */
    public static function ordenar(array $items, array $campos): array
    {
        $items = collect($items)->toArray();

        usort($items, function ($a, $b) use ($campos) {
            foreach ($campos as $campo => $direcao) {
                $valorA = self::obterValor($a, $campo);
                $valorB = self::obterValor($b, $campo);

                if ($valorA === $valorB) {
                    continue;
                }

                $resultado = 0;
                if (is_numeric($valorA) && is_numeric($valorB)) {
                    $resultado = $valorA - $valorB;
                } else {
                    $resultado = strcmp(strtolower((string) $valorA), strtolower((string) $valorB));
                }

                return $direcao === 'desc' ? -$resultado : $resultado;
            }
            return 0;
        });

        return $items;
    }
}
