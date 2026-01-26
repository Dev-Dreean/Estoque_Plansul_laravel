<?php

namespace App\Http\Controllers;

use App\Models\SolicitacaoBem;
use App\Models\Tabfant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SolicitacaoEmailController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        $subject = $this->stringValue($payload['subject'] ?? '');
        $body = $this->extractBody($payload);
        $from = $this->normalizeFrom($payload['from'] ?? null);

        if ($subject === '' && $body === '') {
            return response()->json([
                'success' => false,
                'message' => 'Empty payload.',
            ], 422);
        }

        $parsed = $this->parseEmail($body);

        // DEBUG: Log detalhado do parsing
        Log::info('🔍 [DEBUG parseEmail] Payload received', [
            'payload_keys' => array_keys($payload),
            'subject_value' => $payload['subject'] ?? 'VAZIO',
            'from_value' => $payload['from'] ?? 'VAZIO',
            'body_length' => strlen($payload['body'] ?? ''),
            'bodyPreview_exists' => isset($payload['bodyPreview']),
            'parsed_result' => $parsed,
        ]);

        $emailOrigem = $this->cleanValue($from['email'] ?? null, 200);
        $emailAssunto = $this->cleanValue($subject, 200);

        $solicitanteNome = $this->cleanValue(
            $parsed['solicitante_nome'] ?? $from['name'] ?? $from['email'] ?? null,
            120
        );
        $solicitanteMatricula = $this->cleanValue($parsed['solicitante_matricula'] ?? null, 20);
        $uf = $this->formatUf($parsed['uf'] ?? null);
        $setor = $this->cleanValue($parsed['setor'] ?? null, 120);
        $localDestino = $this->cleanValue($parsed['local_destino'] ?? null, 150);

        $projetoValue = $parsed['projeto'] ?? $this->extractProjetoFromSubject($subject);
        $projetoId = $this->resolveProjetoId($projetoValue);

        $user = $this->resolveUser($from['email'] ?? null, $solicitanteMatricula);
        $solicitanteId = null;
        if ($user) {
            $solicitanteId = $user->getAuthIdentifier();
            if ($solicitanteNome === null) {
                $solicitanteNome = $this->cleanValue($user->NOMEUSER ?? $user->NMLOGIN ?? null, 120);
            }
            if ($solicitanteMatricula === null
                && !User::isPlaceholderMatriculaValue($user->CDMATRFUNCIONARIO ?? null)) {
                $solicitanteMatricula = $this->cleanValue($user->CDMATRFUNCIONARIO ?? null, 20);
            }
            if ($uf === null) {
                $uf = $this->formatUf($user->UF ?? null);
            }
        }

        $itens = $this->sanitizeItems($parsed['itens'] ?? []);

        $missing = [];
        if ($solicitanteNome === null) {
            $missing[] = 'solicitante_nome';
        }
        if ($projetoId === null) {
            $missing[] = 'projeto_id';
        }
        if ($localDestino === null) {
            $missing[] = 'local_destino';
        }
        if (empty($itens)) {
            $missing[] = 'itens';
        }

        if (!empty($missing)) {
            Log::warning('Email solicitacao missing fields.', [
                'missing' => $missing,
                'from' => $from,
                'subject' => $subject,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Missing required fields.',
                'missing' => $missing,
            ], 422);
        }

        $observacao = $this->buildObservacao($parsed['observacao'] ?? null, $from, $subject, $body);

        $solicitacao = null;
        DB::transaction(function () use (
            $solicitanteId,
            $solicitanteNome,
            $solicitanteMatricula,
            $projetoId,
            $uf,
            $setor,
            $localDestino,
            $observacao,
            $itens,
            &$solicitacao
        ) {
            $solicitacao = SolicitacaoBem::create([
                'solicitante_id' => $solicitanteId,
                'solicitante_nome' => $solicitanteNome,
                'solicitante_matricula' => $solicitanteMatricula,
                'email_origem' => $emailOrigem,
                'email_assunto' => $emailAssunto,
                'projeto_id' => $projetoId,
                'uf' => $uf,
                'setor' => $setor,
                'local_destino' => $localDestino,
                'observacao' => $observacao,
                'status' => SolicitacaoBem::STATUS_PENDENTE,
            ]);

            $solicitacao->itens()->createMany($itens);
        });

        if ($solicitacao) {
            $this->sendConfirmacaoEmail($solicitacao);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitacao registrada com sucesso.',
            'solicitacao_id' => $solicitacao?->id,
        ]);
    }

    private function extractBody(array $payload): string
    {
        $body = $payload['body'] ?? '';

        if (is_array($body)) {
            $body = $body['content'] ?? $body['body'] ?? '';
        }

        if ($body === '' && isset($payload['bodyPreview'])) {
            $body = $payload['bodyPreview'];
        }

        if ($body === '' && isset($payload['body_preview'])) {
            $body = $payload['body_preview'];
        }

        if ($body === '' && isset($payload['bodyPreviewText'])) {
            $body = $payload['bodyPreviewText'];
        }

        $body = $this->stringValue($body);
        if ($body === '') {
            return '';
        }

        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Converter <br>, <br/>, <br /> em quebras de linha ANTES de remover tags
        $body = preg_replace('/<br\s*\/?>/i', "\n", $body);
        // Remover tags HTML
        $body = strip_tags($body);
        // Normalizar quebras de linha
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        // Remover múltiplas quebras de linha consecutivas
        $body = preg_replace('/\n+/', "\n", $body);

        return trim($body);
    }

    private function normalizeFrom($from): array
    {
        $email = null;
        $name = null;

        if (is_object($from)) {
            $from = (array) $from;
        }

        if (is_array($from)) {
            if (isset($from['emailAddress'])) {
                $email = $from['emailAddress']['address'] ?? null;
                $name = $from['emailAddress']['name'] ?? null;
            } else {
                $email = $from['address'] ?? $from['email'] ?? null;
                $name = $from['name'] ?? null;
            }
        } elseif (is_string($from)) {
            $from = trim($from);
            if (preg_match('/^(.*)<(.+)>$/', $from, $matches)) {
                $name = trim($matches[1], " \"");
                $email = trim($matches[2]);
            } else {
                $email = $from;
            }
        }

        $email = $this->cleanValue($email, 200);
        if ($email !== null) {
            $email = strtolower($email);
        }

        return [
            'email' => $email,
            'name' => $this->cleanValue($name, 120),
        ];
    }

    private function parseEmail(string $body): array
    {
        $result = [
            'solicitante_nome' => null,
            'solicitante_matricula' => null,
            'projeto' => null,
            'uf' => null,
            'setor' => null,
            'local_destino' => null,
            'observacao' => null,
            'itens' => [],
        ];

        $lines = preg_split('/\n+/', $body);
        $inItems = false;
        $pendingField = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $pendingField = null;
                continue;
            }

            if (preg_match('/^itens?\s*[:\-]\s*(.+)$/i', $line, $matches)) {
                $inItems = true;
                $item = $this->parseItemLine($matches[1]);
                if ($item) {
                    $result['itens'][] = $item;
                }
                continue;
            }

            if (preg_match('/^itens?$/i', $line)) {
                $inItems = true;
                continue;
            }

            if ($inItems) {
                $item = $this->parseItemLine($line);
                if ($item) {
                    $result['itens'][] = $item;
                }
                continue;
            }

            if (preg_match('/^(.+?)\s*[:=]\s*(.*)$/', $line, $matches)) {
                $key = $this->mapKey($matches[1]);
                $value = trim($matches[2]);

                if ($key === 'itens') {
                    $inItems = true;
                    $item = $this->parseItemLine($value);
                    if ($item) {
                        $result['itens'][] = $item;
                    }
                    continue;
                }

                if ($key === 'observacao' && $value === '') {
                    $pendingField = 'observacao';
                    continue;
                }

                if ($key !== null) {
                    $result[$key] = $value;
                    continue;
                }
            }

            if ($pendingField === 'observacao') {
                $result['observacao'] = trim(($result['observacao'] ?? '') . "\n" . $line);
                continue;
            }

            if ($this->looksLikeItemLine($line)) {
                $item = $this->parseItemLine($line);
                if ($item) {
                    $result['itens'][] = $item;
                }
            }
        }

        return $result;
    }

    private function mapKey(string $rawKey): ?string
    {
        $key = $this->normalizeKey($rawKey);

        return match ($key) {
            'solicitante',
            'solicitante nome',
            'nome',
            'nome solicitante' => 'solicitante_nome',
            'matricula',
            'matricula solicitante' => 'solicitante_matricula',
            'projeto',
            'projeto id',
            'codigo projeto',
            'cdprojeto' => 'projeto',
            'uf',
            'estado' => 'uf',
            'setor' => 'setor',
            'local',
            'local destino',
            'localdestino',
            'destino' => 'local_destino',
            'observacao',
            'observacoes',
            'obs' => 'observacao',
            'itens',
            'items',
            'item' => 'itens',
            default => null,
        };
    }

    private function normalizeKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        $key = mb_strtolower($key, 'UTF-8');
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $key);
        if ($transliterated !== false) {
            $key = $transliterated;
        }

        $key = preg_replace('/[^a-z0-9]+/', ' ', $key);
        return trim($key);
    }

    private function parseItemLine(string $line): ?array
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        $line = preg_replace('/^[^[:alnum:]]+/u', '', $line);
        if ($line === '') {
            return null;
        }

        $descricao = '';
        $quantidade = 1;
        $unidade = null;
        $observacao = null;

        if (preg_match('/^(\d+)\s*[xX]\s*(.+)$/', $line, $matches)) {
            $quantidade = (int) $matches[1];
            $descricao = trim($matches[2]);
        } elseif (preg_match('/^(.+?)\s*[xX]\s*(\d+)$/', $line, $matches)) {
            $descricao = trim($matches[1]);
            $quantidade = (int) $matches[2];
        } else {
            $parts = preg_split('/[;|]/', $line);
            if ($parts && count($parts) > 1) {
                $parts = array_map('trim', $parts);
                $first = $parts[0] ?? '';
                $second = $parts[1] ?? '';

                if ($this->isNumericValue($first) && $second !== '') {
                    $quantidade = (int) $first;
                    $descricao = $second;
                    $unidade = $parts[2] ?? null;
                    $observacao = $parts[3] ?? null;
                } else {
                    $descricao = $first;
                    if ($this->isNumericValue($second)) {
                        $quantidade = (int) $second;
                    } else {
                        $observacao = $second ?: null;
                    }
                    $unidade = $parts[2] ?? null;
                    if ($observacao === null) {
                        $observacao = $parts[3] ?? null;
                    }
                }
            } else {
                $descricao = $line;
            }
        }

        $descricao = $this->cleanValue($descricao, 200);
        if ($descricao === null) {
            return null;
        }

        $quantidade = max(1, (int) $quantidade);

        return [
            'descricao' => $descricao,
            'quantidade' => $quantidade,
            'unidade' => $this->cleanValue($unidade, 20),
            'observacao' => $this->cleanValue($observacao, 500),
        ];
    }

    private function looksLikeItemLine(string $line): bool
    {
        if (str_contains($line, ';') || str_contains($line, '|')) {
            return true;
        }

        return (bool) preg_match('/^(\s*[\-\*]|\d+\s*[xX]\s+)/', $line);
    }

    private function sanitizeItems(array $items): array
    {
        $sanitized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $descricao = $this->cleanValue($item['descricao'] ?? null, 200);
            if ($descricao === null) {
                continue;
            }

            $quantidade = max(1, (int) ($item['quantidade'] ?? 1));
            $sanitized[] = [
                'descricao' => $descricao,
                'quantidade' => $quantidade,
                'unidade' => $this->cleanValue($item['unidade'] ?? null, 20),
                'observacao' => $this->cleanValue($item['observacao'] ?? null, 500),
            ];
        }

        return $sanitized;
    }

    private function resolveProjetoId(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $candidates = [$value];
        if (str_contains($value, '-')) {
            $parts = array_map('trim', explode('-', $value, 2));
            $candidates = array_merge($candidates, array_filter($parts));
        }

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (ctype_digit($candidate)) {
                $project = Tabfant::where('id', (int) $candidate)->first();
                if ($project) {
                    return (int) $project->id;
                }

                $project = Tabfant::where('CDPROJETO', $candidate)->first();
                if ($project) {
                    return (int) $project->id;
                }
            }

            $project = Tabfant::where('CDPROJETO', $candidate)->first();
            if ($project) {
                return (int) $project->id;
            }

            $project = Tabfant::where('NOMEPROJETO', $candidate)->first();
            if ($project) {
                return (int) $project->id;
            }
        }

        $project = Tabfant::where('NOMEPROJETO', 'like', '%' . $value . '%')->first();
        if ($project) {
            return (int) $project->id;
        }

        return null;
    }

    private function resolveUser(?string $email, ?string $matricula): ?User
    {
        $matricula = $this->cleanValue($matricula, 20);
        if ($matricula !== null) {
            $user = User::where('CDMATRFUNCIONARIO', $matricula)->first();
            if ($user) {
                return $user;
            }
        }

        // Não buscar por email pois a tabela usuario não tem coluna 'email'
        // Se quiser, poderia buscar por NMLOGIN extraindo do email
        // Por enquanto, apenas retornar null se não encontrou por matrícula

        return null;
    }

    private function extractProjetoFromSubject(string $subject): ?string
    {
        if (preg_match('/projeto\s*[:\-]\s*(.+)$/i', $subject, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function formatUf(?string $uf): ?string
    {
        $uf = $this->cleanValue($uf, 2);
        if ($uf === null) {
            return null;
        }

        return strtoupper($uf);
    }

    private function buildObservacao(
        ?string $observacao,
        array $from,
        string $subject,
        string $body
    ): ?string {
        $parts = [];

        if ($observacao !== null && $observacao !== '') {
            $parts[] = $observacao;
        }

        $meta = [];
        if (!empty($from['email'])) {
            $meta[] = 'Email origem: ' . $from['email'];
        }
        if (!empty($from['name'])) {
            $meta[] = 'Nome origem: ' . $from['name'];
        }
        if ($subject !== '') {
            $meta[] = 'Assunto: ' . $subject;
        }

        if (!empty($meta)) {
            $parts[] = '---';
            $parts = array_merge($parts, $meta);
        }

        if ($body !== '') {
            $parts[] = 'Mensagem:';
            $parts[] = $this->truncate($body, 2000);
        }

        $text = trim(implode("\n", array_filter($parts, fn ($value) => $value !== null && $value !== '')));
        return $text !== '' ? $text : null;
    }

    private function truncate(string $value, int $limit): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit);
    }

    private function cleanValue(?string $value, int $limit): ?string
    {
        $value = $this->stringValue($value);
        if ($value === '') {
            return null;
        }

        if (strlen($value) > $limit) {
            $value = substr($value, 0, $limit);
        }

        return $value;
    }

    private function stringValue($value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_numeric($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function isNumericValue(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = trim($value);
        return $value !== '' && ctype_digit($value);
    }

    private function sendConfirmacaoEmail(SolicitacaoBem $solicitacao): void
    {
        $to = trim((string) config('solicitacoes_bens.email_to'));
        if ($to === '') {
            return;
        }

        $subject = 'Solicitacao de bens recebida #' . $solicitacao->id;
        $body = implode("\n", [
            'Uma nova solicitacao de bens foi registrada.',
            'Numero: ' . $solicitacao->id,
            'Solicitante: ' . ($solicitacao->solicitante_nome ?? '-'),
            'Matricula: ' . ($solicitacao->solicitante_matricula ?? '-'),
            'Setor: ' . ($solicitacao->setor ?? '-'),
            'UF: ' . ($solicitacao->uf ?? '-'),
            'Local destino: ' . ($solicitacao->local_destino ?? '-'),
            'Status: ' . ($solicitacao->status ?? '-'),
        ]);

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            $solicitacao->email_confirmacao_enviado_em = now();
            $solicitacao->save();
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar email de solicitacao de bens', [
                'solicitacao_id' => $solicitacao->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
