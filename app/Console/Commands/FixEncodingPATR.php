<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixEncodingPATR extends Command
{
    protected $signature = 'patr:fix-encoding {--dry-run} {--limit=0}';
    protected $description = 'Corrige mojibake (Windows-1252 → UTF-8) nas colunas de texto da tabela PATR';

    /** Colunas de texto que podem ter mojibake */
    private array $columns = [
        'SITUACAO',
        'MARCA',
        'MODELO',
        'DEHISTORICO',
        'DEPATRIMONIO',
        'TIPO',
        'COR',
        'USUARIO',
    ];

    public function handle()
    {
        $this->info('Iniciando correção de encoding (Windows-1252 → UTF-8) em PATR');

        // 1) Backup das linhas suspeitas (idempotente)
        $this->backupBadRows();

        // 2) Seleciona linhas suspeitas
        $limit = (int) $this->option('limit');

        $query = DB::table('PATR')
            ->select(array_merge(['NUSEQPATR'], $this->columns))
            ->where(function ($q) {
                foreach ($this->columns as $col) {
                    $q->orWhere($col, 'like', '%Ã%')
                        ->orWhere($col, 'like', '%Â%')
                        ->orWhere($col, 'like', '%�%')   // replacement char
                        ->orWhere($col, 'like', '%µ%')   // mu que às vezes veio no lugar de À
                        ->orWhere($col, 'like', '%%');  // U+0080 visível
                }
            })
            ->orderBy('NUSEQPATR', 'asc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $this->info("Linhas suspeitas: " . $rows->count());

        $dry     = (bool) $this->option('dry-run');
        $updated = 0;
        $scanned = 0;

        foreach ($rows as $row) {
            $scanned++;
            $updates = [];

            foreach ($this->columns as $col) {
                $val = $row->{$col};

                $fixed = $this->repairString($val); // aceita null/string, devolve corrigido ou original

                if ($fixed !== $val) {
                    $updates[$col] = $fixed;
                }
            }

            if (!empty($updates)) {
                if ($dry) {
                    $this->line("[dry-run] NUSEQPATR {$row->NUSEQPATR} → " . json_encode($updates, JSON_UNESCAPED_UNICODE));
                } else {
                    DB::table('PATR')
                        ->where('NUSEQPATR', $row->NUSEQPATR)
                        ->update($updates);
                    $updated++;
                }
            }
        }

        $this->line("Linhas varridas: " . ($rows->count()));
        if ($dry) {
            $this->warn('Dry-run: nada foi alterado. Rode sem --dry-run para aplicar.');
        } else {
            $this->info("Atualizações aplicadas: {$updated}");
        }

        $this->info('Concluído.');
        return 0;
    }

    /** Heurística: detecta sinais de mojibake. Aceita null. */
    private function looksBroken(?string $s): bool
    {
        if ($s === null || $s === '') return false;
        // padrões clássicos de mojibake + bytes problemáticos vistos
        return (bool) preg_match('/(Ã.|Â.|�|µ|\x{0080})/u', $s);
    }

    /** Converte para UTF-8 ignorando bytes inválidos (lossy). */
    private function toUtf8Lossy(?string $s): string
    {
        if ($s === null) return '';
        // força retorno em UTF-8 e ignora o que não dá pra converter
        $out = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        return $out === false ? '' : $out;
    }

    /** Correção principal em camadas + dicionário. */
    private function repairString(?string $val): ?string
    {
        if ($val === null || $val === '') return $val;

        $orig = $val;

        // 1) tenta: bytes eram Windows-1252 mas foram lidos como UTF-8
        $fixed = @mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
        if (!$this->looksBroken($fixed)) {
            $fixed = $this->applyDictionary($fixed);
            return $fixed;
        }

        // 2) tenta: bytes eram ISO-8859-1 (latin1)
        $try2 = @mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1');
        if (!$this->looksBroken($try2)) {
            $try2 = $this->applyDictionary($try2);
            return $try2;
        }

        // 3) limpa bytes inválidos e aplica dicionário
        $try3 = $this->toUtf8Lossy($val);
        $try3 = $this->applyDictionary($try3);
        if (!$this->looksBroken($try3)) {
            return $try3;
        }

        // 4) ainda estranho? aplica só o dicionário no original (às vezes resolve)
        $try4 = $this->applyDictionary($val);
        if (!$this->looksBroken($try4)) {
            return $try4;
        }

        // Não conseguiu corrigir com segurança → mantém original
        return $orig;
    }

    /** Aplica mapeamentos manuais + dicionário de termos observados. */
    private function applyDictionary(string $s): string
    {
        // mapa básico comum em pt-BR
        $basic = [
            'Ã ' => 'À',
            'Ã¡' => 'á',
            'Ã¢' => 'â',
            'Ã£' => 'ã',
            'Ã¤' => 'ä',
            'Ã§' => 'ç',
            'Ã¨' => 'è',
            'Ã©' => 'é',
            'Ãª' => 'ê',
            'Ã«' => 'ë',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ã´' => 'ô',
            'Ãµ' => 'õ',
            'Ã¶' => 'ö',
            'Ãº' => 'ú',
            'Ã¼' => 'ü',
            'Ã‡' => 'Ç',
            'Ã‰' => 'É',
            'ÃŠ' => 'Ê',
            'Ã“' => 'Ó',
            'Ã•' => 'Õ',
            'Ãš' => 'Ú',
            'Ã�' => 'Í',
            'Ã€' => 'À',
            'Â«' => '«',
            'Â»' => '»',
            'Âº' => 'º',
            'Âª' => 'ª',
            'Â°' => '°',
            'Â·' => '·',
            'Â'  => '',   // A "grudado" que sobra antes de muitos acentos
            'µ'  => 'À',  // em dumps antigos, µ acabou virando À
            "\xC2\x80" => '', // U+0080 → remove
        ];
        $out = strtr($s, $basic);

        // dicionário de termos reais observados nos seus dados
        $dict = [
            // telefones
            'TELEFâNICO' => 'TELEFÔNICO',
            'TELEFâNICA' => 'TELEFÔNICA',
            'CENTRAL TELEFâNICA' => 'CENTRAL TELEFÔNICA',

            // escritório / cadeiras
            'ESCRITÃ RIO' => 'ESCRITÓRIO',
            'ESCRIÃ TIO'  => 'ESCRITÓRIO',
            'GIRATÃ RIA'  => 'GIRATÓRIA',
            'ERGONÃ¢MICA' => 'ERGONÔMICA',
            'ERGONâMICA'  => 'ERGONÔMICA',
            'BRAÂ€OS'     => 'BRAÇOS',
            'BRAOS'       => 'BRAÇOS',
            'SECRETARIA'  => 'SECRETÁRIA', // no seu dataset, “SECRETARIA” é quase sempre “SECRETÁRIA”

            // apoio / pés
            'APOIO ERGONâMICO' => 'APOIO ERGONÔMICO',
            'PS'              => 'PÉS',

            // acrílico / relógio
            'ACRÃ–LICO' => 'ACRÍLICO',
            'RELÃ GIO'  => 'RELÓGIO',

            // armário / aéreo / divisória / módulo
            'ARMÂµRIO'   => 'ARMÁRIO',
            'AEREO'      => 'AÉREO',
            'AREO'       => 'AÉREO',
            'DIVISÃ RIA' => 'DIVISÓRIA',
            'MÃ DULO'    => 'MÓDULO',

            // incêndio / aço
            'INCãNDIO' => 'INCÊNDIO',
            'A€O'      => 'AÇO',
            ' EM AO'   => ' EM AÇO',

            // outras comuns vistas na amostra
            'TELEVISÇO'        => 'TELEVISÃO',
            'GARATORIAS'       => 'GIRATÓRIAS', // em “CADEIRAS GIRATORIAS”
            'GIRATORIAS'       => 'GIRATÓRIAS',
        ];

        // aplica trocas do dicionário (substituição direta)
        $out = strtr($out, $dict);

        // alguns acertos finos por regex (com borda de palavra quando faz sentido)
        // ex.: “ESCRITÃ(RIO|RIO)” em diferentes separações
        $out = preg_replace('/ESCRITÃ\s*RIO/u', 'ESCRITÓRIO', $out);
        $out = preg_replace('/GIRATÃ\s*RIA/u', 'GIRATÓRIA', $out);
        $out = preg_replace('/DIVISÃ\s*RIA/u', 'DIVISÓRIA', $out);

        return $out;
    }

    /** Cria tabela de backup e copia as linhas suspeitas que ainda não foram copiadas. */
    private function backupBadRows(): void
    {
        // tabela backup com mesma estrutura, vazia
        DB::statement("
            CREATE TABLE IF NOT EXISTS patr_backup_bad AS
            SELECT * FROM PATR WHERE 1=0
        ");

        // copia só as novas linhas suspeitas
        DB::statement("
            INSERT INTO patr_backup_bad
            SELECT p.*
            FROM PATR p
            LEFT JOIN patr_backup_bad b ON b.NUSEQPATR = p.NUSEQPATR
            WHERE b.NUSEQPATR IS NULL
              AND (" . $this->buildWhereLike() . ")
        ");
    }

    /** Constrói o filtro de suspeitos para o backup. */
    private function buildWhereLike(): string
    {
        $parts = [];
        foreach ($this->columns as $col) {
            $colQuoted = 'p.`' . str_replace('`', '``', $col) . '`';
            $parts[] = "{$colQuoted} LIKE '%Ã%'";
            $parts[] = "{$colQuoted} LIKE '%Â%'";
            $parts[] = "{$colQuoted} LIKE '%�%'";
            $parts[] = "{$colQuoted} LIKE '%µ%'";
            $parts[] = "{$colQuoted} LIKE '%%'";
        }
        return implode(' OR ', $parts);
    }
}
