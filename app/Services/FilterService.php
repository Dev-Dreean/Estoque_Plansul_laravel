<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Serviço de Filtros Inteligentes (Versão 2.0 - Melhorada)
 * 
 * Centraliza a lógica de busca e ordenação com sistema de scoring aprimorado.
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
     * Realizar busca inteligente com scoring aprimorado
     * 
     * @param array $items Items para buscar
     * @param string $termo Termo de busca (suporta múltiplos termos separados por vírgula)
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

        // Dividir múltiplos termos por vírgula
        $termos = array_map('trim', explode(',', $termo));
        $termos = array_filter($termos); // Remove vazios

        // Calcular score para cada item
        $itemsComScore = collect($items)
            ->map(function ($item) use ($termos, $searchFields, $fieldTypes) {
                $score = self::calcularScore(
                    $item,
                    $termos,
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
     * Normalizar string para busca (remove acentos, espaços extras, etc)
     */
    private static function normalizar(string $str): string
    {
        // Remove acentos
        $str = preg_replace('/[áàâãä]/i', 'a', $str);
        $str = preg_replace('/[éèêë]/i', 'e', $str);
        $str = preg_replace('/[íìîï]/i', 'i', $str);
        $str = preg_replace('/[óòôõö]/i', 'o', $str);
        $str = preg_replace('/[úùûü]/i', 'u', $str);
        $str = preg_replace('/[ç]/i', 'c', $str);

        // Remove espaços extras
        $str = trim(preg_replace('/\s+/', ' ', $str));

        return strtolower($str);
    }

    /**
     * Calcular score de um item em relação a múltiplos termos
     * Todos os termos devem estar presentes (busca AND)
     */
    private static function calcularScore(
        $item,
        array $termos,
        array $searchFields,
        array $fieldTypes
    ): int {
        // Se não há campos de busca, retornar score padrão
        if (empty($searchFields)) {
            return PHP_INT_MAX;
        }

        $scoreTotal = 0;

        // Cada termo deve estar presente no item
        foreach ($termos as $termo) {
            $termo = self::normalizar($termo);

            $melhorScore = PHP_INT_MAX;

            foreach ($searchFields as $field) {
                $valor = self::obterValor($item, $field);
                $tipo = $fieldTypes[$field] ?? 'texto';

                $score = self::calcularScoreCampo($valor, $termo, $tipo);
                $melhorScore = min($melhorScore, $score);

                // Se encontrou match exato, parar a busca neste campo
                if ($score === 0) {
                    break;
                }
            }

            // Se um termo não foi encontrado, eliminar o item
            if ($melhorScore === PHP_INT_MAX) {
                return PHP_INT_MAX;
            }

            $scoreTotal += $melhorScore;
        }

        return $scoreTotal;
    }

    /**
     * Calcular score de um campo específico (versão melhorada)
     * 
     * Sistema de pontuação:
     * - 0: Match exato
     * - 5-15: Começa com termo (números têm penalidade menor)
     * - 20-50: Contém termo (com posição considerada)
     * - 100-200: Distância de Levenshtein pequena
     * - PHP_INT_MAX: Não encontrado
     * 
     * @param mixed $valor Valor do campo
     * @param string $termo Termo normalizado
     * @param string $tipo Tipo do campo: 'número' ou 'texto'
     * @return int Score
     */
    private static function calcularScoreCampo($valor, string $termo, string $tipo): int
    {
        if ($valor === null || $valor === '') {
            return PHP_INT_MAX;
        }

        $valorNorm = self::normalizar((string) $valor);

        // 🥇 Match exato
        if ($valorNorm === $termo) {
            return 0;
        }

        // 🥈 Começa com o termo (muito prioritário)
        if (str_starts_with($valorNorm, $termo)) {
            // Para números, dar prioridade aos que começam
            if ($tipo === 'número' && is_numeric($valorNorm)) {
                $score = 5 + strlen($valorNorm) + intval($valorNorm) / 1000;
            } else {
                $score = 10 + strlen($valorNorm);
            }
            return (int) $score;
        }

        // 🥉 Contém o termo
        if (str_contains($valorNorm, $termo)) {
            $posicao = strpos($valorNorm, $termo);
            // Quanto mais perto do início, melhor o score
            $score = 20 + $posicao + (strlen($valorNorm) / 2);
            return (int) $score;
        }

        // ❓ Distância de Levenshtein (similaridade fuzzy)
        $distancia = self::distanciaLevenshtein($valorNorm, $termo);

        // Aceitar matches muito similares (até 2 caracteres de diferença)
        if ($distancia <= 2) {
            return 100 + ($distancia * 10);
        }

        // Se o termo é muito curto, ser mais permissivo
        if (strlen($termo) <= 2 && $distancia <= 3) {
            return 150 + ($distancia * 15);
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
