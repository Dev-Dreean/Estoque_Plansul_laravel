<?php

namespace App\Services;

use App\Jobs\SendSolicitacaoBemCriadaEmailJob;
use App\Models\SolicitacaoBem;
use App\Models\SolicitacaoBemNotificacaoUsuario;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Centraliza o envio de notificações por e-mail de Solicitações de Bens.
 */
class SolicitacaoBemEmailService
{
    public function agendarConfirmacaoCriacao(SolicitacaoBem $solicitacao): void
    {
        $this->agendarNotificacaoFluxo($solicitacao, 'criada');
    }

    public function agendarNotificacaoFluxo(SolicitacaoBem $solicitacao, string $evento): void
    {
        if (!$this->notificacoesHabilitadas()) {
            Log::info('[SOLICITACOES_EMAIL] Notificações desabilitadas para evento do fluxo.', [
                'solicitacao_id' => $solicitacao->id,
                'status' => $solicitacao->status,
                'evento' => $evento,
            ]);

            return;
        }

        $job = SendSolicitacaoBemCriadaEmailJob::dispatch($solicitacao->id, $evento);

        $queueConnection = trim((string) config('solicitacoes_bens.notificacoes.queue_connection', ''));
        $queueName = trim((string) config('solicitacoes_bens.notificacoes.queue_name', ''));

        if ($queueConnection !== '') {
            $job->onConnection($queueConnection);
        }

        if ($queueName !== '') {
            $job->onQueue($queueName);
        }

        Log::info('[SOLICITACOES_EMAIL] Envio de notificação agendado.', [
            'solicitacao_id' => $solicitacao->id,
            'status' => $solicitacao->status,
            'evento' => $evento,
            'queue_connection' => $queueConnection !== '' ? $queueConnection : config('queue.default'),
            'queue_name' => $queueName !== '' ? $queueName : 'default',
        ]);
    }

    public function enviarNotificacaoFluxo(int $solicitacaoId, string $evento): void
    {
        $relations = ['itens', 'projeto'];
        if (method_exists(SolicitacaoBem::class, 'destinoLocalProjeto')) {
            $relations[] = 'destinoLocalProjeto';
        }

        $solicitacao = SolicitacaoBem::query()
            ->with($relations)
            ->find($solicitacaoId);

        if (!$solicitacao) {
            Log::warning('[SOLICITACOES_EMAIL] Solicitação não encontrada para envio de notificação.', [
                'solicitacao_id' => $solicitacaoId,
                'evento' => $evento,
            ]);

            return;
        }

        $notificacao = [
            'assunto' => '(não definido)',
            'destinatarios' => [],
        ];

        try {
            $notificacao = $this->montarNotificacao($solicitacao, $evento);
            if ($notificacao === null) {
                Log::info('[SOLICITACOES_EMAIL] Evento sem notificação configurada.', [
                    'solicitacao_id' => $solicitacao->id,
                    'evento' => $evento,
                ]);

                return;
            }

            if ($notificacao['destinatarios'] === []) {
                Log::warning('[SOLICITACOES_EMAIL] Evento sem destinatários válidos.', [
                    'solicitacao_id' => $solicitacao->id,
                    'evento' => $evento,
                    'status' => $solicitacao->status,
                ]);

                return;
            }

            if ($this->usaPowerAutomate()) {
                $this->enviarViaPowerAutomate($solicitacao, $evento, $notificacao);
            } else {
                $this->enviarViaMailer($solicitacao, $evento, $notificacao);
            }

            if ($evento === 'criada') {
                $solicitacao->forceFill([
                    'email_confirmacao_enviado_em' => now(),
                ])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('[SOLICITACOES_EMAIL] Falha ao enviar notificação de fluxo.', [
                'solicitacao_id' => $solicitacao->id,
                'evento' => $evento,
                'assunto' => $notificacao['assunto'],
                'destinatarios' => $notificacao['destinatarios'],
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function montarNotificacao(SolicitacaoBem $solicitacao, string $evento): ?array
    {
        $contexto = $this->resolverContextoEvento($solicitacao, $evento);
        if ($contexto === null) {
            return null;
        }

        $destinatarios = $this->aplicarDestinatarioForcado($contexto['destinatarios']);
        $assunto = $this->montarAssunto($solicitacao, $contexto);
        $corpoTexto = $this->montarCorpo($solicitacao, $contexto);

        return [
            'assunto' => $assunto,
            'corpo' => $corpoTexto,
            'corpo_html' => $this->montarCorpoHtml($solicitacao, $contexto),
            'destinatarios' => array_values(array_map(static fn (array $destinatario) => $destinatario['email'], $destinatarios)),
            'destinatarios_contatos' => $destinatarios,
            'destinatarios_linha' => implode(';', array_values(array_map(static fn (array $destinatario) => $destinatario['email'], $destinatarios))),
            'titulo' => $contexto['titulo'],
            'mensagem' => $contexto['mensagem'],
            'proxima_etapa' => $contexto['proxima_etapa'],
            'link_modal' => route('solicitacoes-bens.index', ['open_modal' => $solicitacao->id]),
            'contexto' => $contexto,
        ];
    }

    private function notificacoesHabilitadas(): bool
    {
        return (bool) config('solicitacoes_bens.notificacoes.enabled', true);
    }

    private function usaPowerAutomate(): bool
    {
        return trim((string) config('solicitacoes_bens.power_automate.webhook_url', '')) !== '';
    }

    private function enviarViaPowerAutomate(SolicitacaoBem $solicitacao, string $evento, array $notificacao): void
    {
        $webhookUrl = trim((string) config('solicitacoes_bens.power_automate.webhook_url', ''));
        $webhookToken = trim((string) config('solicitacoes_bens.power_automate.webhook_token', ''));
        $timeout = max(5, (int) config('solicitacoes_bens.power_automate.timeout', 15));
        $verifySsl = (bool) config('solicitacoes_bens.power_automate.verify_ssl', true);

        $request = Http::timeout($timeout)
            ->acceptJson()
            ->contentType('application/json');

        if (!$verifySsl) {
            $request = $request->withoutVerifying();
        }

        if ($webhookToken !== '') {
            $request = $request->withHeaders([
                'X-API-KEY' => $webhookToken,
            ]);
        }

        foreach ($notificacao['destinatarios_contatos'] as $destinatario) {
            $corpoTexto = $this->montarCorpo($solicitacao, $notificacao['contexto'], $destinatario);
            $corpoHtml = $this->montarCorpoHtml($solicitacao, $notificacao['contexto'], $destinatario);

            $payload = [
                'solicitacao_id' => $solicitacao->id,
                'evento' => $evento,
                'status' => $solicitacao->status,
                'titulo' => $notificacao['titulo'],
                'mensagem' => $notificacao['mensagem'],
                'proxima_etapa' => $notificacao['proxima_etapa'],
                'to' => $destinatario['email'],
                'subject' => $notificacao['assunto'],
                'body' => $corpoHtml,
                'assunto' => $notificacao['assunto'],
                'destinatarios' => [$destinatario['email']],
                'destinatarios_linha' => $destinatario['email'],
                'destinatario_principal' => $destinatario['email'],
                'corpo_texto' => $corpoTexto,
                'corpo_html' => $corpoHtml,
                'link_modal' => $notificacao['link_modal'],
                'destinatario_nome' => $destinatario['nome'] ?? null,
            ];

            $response = $request->post($webhookUrl, $payload);
            $response->throw();

            Log::info('[SOLICITACOES_EMAIL] Notificacao enviada via Power Automate.', [
                'solicitacao_id' => $solicitacao->id,
                'evento' => $evento,
                'destinatario' => $destinatario['email'],
                'status_http' => $response->status(),
            ]);
        }
    }

    private function enviarViaMailer(SolicitacaoBem $solicitacao, string $evento, array $notificacao): void
    {
        foreach ($notificacao['destinatarios_contatos'] as $destinatario) {
            $corpoHtml = $this->montarCorpoHtml($solicitacao, $notificacao['contexto'], $destinatario);

            Mail::html($corpoHtml, function ($message) use ($destinatario, $notificacao) {
                $message->to($destinatario['email'])->subject($notificacao['assunto']);
            });

            Log::info('[SOLICITACOES_EMAIL] Notificacao enviada via mailer.', [
                'solicitacao_id' => $solicitacao->id,
                'destinatario' => $destinatario['email'],
                'status' => $solicitacao->status,
                'evento' => $evento,
            ]);
        }
    }

    private function montarCorpoHtml(SolicitacaoBem $solicitacao, array $contexto, ?array $destinatario = null): string
    {
        $nomeProjeto = trim((string) ($solicitacao->projeto->NOMEPROJETO ?? ''));
        $codigoProjeto = trim((string) ($solicitacao->projeto->CDPROJETO ?? ''));
        $identificacaoProjeto = $nomeProjeto !== '' ? $nomeProjeto : '-';
        if ($codigoProjeto !== '') {
            $identificacaoProjeto .= ' (' . $codigoProjeto . ')';
        }

        $status = trim((string) ($solicitacao->status ?? '-'));
        $corStatus = $this->corStatusHtml($status);
        $itensHtml = $this->montarItensHtml($solicitacao);
        $detalhesHtml = $this->montarDetalhesComplementaresHtml($solicitacao, $contexto['evento'] ?? null);
        $linkModal = route('solicitacoes-bens.index', ['open_modal' => $solicitacao->id]);
        $nomeDestinatario = $destinatario['nome'] ?? null;

        $resumo = array_filter([
            'Número' => (string) $solicitacao->id,
            'Próxima etapa' => $contexto['proxima_etapa'] ?: null,
            'Projeto' => $identificacaoProjeto,
            'Local de destino' => $solicitacao->local_destino ?: '-',
            'Fluxo responsável' => $solicitacao->fluxo_responsavel_label ?: 'Padrão',
            'Recebedor' => $solicitacao->nome_recebedor ?: '-',
        ], static fn ($valor) => $valor !== null && $valor !== '');

        $resumoHtml = '';
        foreach ($resumo as $label => $valor) {
            $resumoHtml .=
                '<tr>'
                . '<td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;font-weight:600;width:180px;">' . e($label) . '</td>'
                . '<td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;">' . e((string) $valor) . '</td>'
                . '</tr>';
        }

        $cabecalhoInternoHtml =
            '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">'
            . '<tr>'
            . '<td style="vertical-align:top;">'
            . '<div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:10px;">Sistema de Solicitações de Bens</div>'
            . '<div style="font-size:28px;line-height:1.2;font-weight:700;margin-bottom:8px;color:#ffffff;">' . e($contexto['titulo']) . '</div>'
            . '<div style="font-size:15px;line-height:1.55;max-width:560px;opacity:0.95;color:#e5e7eb;">' . e($contexto['mensagem']) . '</div>'
            . ($nomeDestinatario ? '<div style="margin-top:12px;font-size:14px;line-height:1.5;opacity:0.92;">Olá, <strong>' . e($nomeDestinatario) . '</strong>.</div>' : '')
            . '<div style="margin-top:16px;">'
            . '<span style="display:inline-block;padding:8px 12px;border-radius:999px;background:' . e($corStatus) . ';color:#ffffff;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;">' . e($status) . '</span>'
            . '</div>'
            . '</td>'
            . '</tr>'
            . '</table>';

        return
            '<div style="margin:0;padding:24px;background-color:#f3f4f6;">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:760px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;font-family:Segoe UI,Arial,sans-serif;">'
            . '<tr>'
            . '<td bgcolor="#1d4ed8" style="padding:0;background-color:#1d4ed8;color:#ffffff;">'
            . '<!--[if mso]>'
            . '<v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:760px;height:200px;">'
            . '<v:fill type="gradient" color="#1d4ed8" color2="#f97316" angle="0" />'
            . '<v:textbox inset="0,0,0,0">'
            . '<div style="padding:24px 28px;">'
            . $cabecalhoInternoHtml
            . '</div>'
            . '</v:textbox>'
            . '</v:rect>'
            . '<![endif]-->'
            . '<!--[if !mso]><!-->'
            . '<div style="padding:24px 28px;background-color:#1d4ed8;background-image:linear-gradient(120deg,#1d4ed8 0%, #2563eb 42%, #f97316 100%);">'
            . $cabecalhoInternoHtml
            . '</div>'
            . '<!--<![endif]-->'
            . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="padding:28px;">'
            . ($detalhesHtml !== '' ? '<div style="margin-bottom:22px;"><div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:12px;">Informações novas desta etapa</div>' . $detalhesHtml . '</div>' : '')
            . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">'
            . '<tr>'
            . '<td style="font-size:18px;font-weight:700;color:#111827;padding-bottom:4px;">Resumo rápido</td>'
            . '</tr>'
            . '</table>'
            . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;background:#ffffff;">'
            . $resumoHtml
            . '</table>'
            . ($contexto['proxima_etapa']
                ? '<div style="margin-top:24px;padding:20px;border:1px solid #dbeafe;background:#eff6ff;border-radius:14px;">'
                    . '<div style="font-size:16px;font-weight:700;color:#1e3a8a;margin-bottom:6px;">Próxima etapa</div>'
                    . '<div style="font-size:14px;line-height:1.6;color:#1f2937;">' . e($contexto['proxima_etapa']) . '</div>'
                    . '</div>'
                : '')
            . '<div style="margin-top:24px;">'
            . '<div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:12px;">Itens solicitados</div>'
            . $itensHtml
            . '</div>'
            . '<div style="margin-top:28px;text-align:center;">'
            . '<a href="' . e($linkModal) . '" style="display:inline-block;padding:14px 22px;border-radius:12px;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">Abrir solicitação na plataforma</a>'
            . '</div>'
            . '<div style="margin-top:22px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">'
            . 'Mensagem automática do fluxo de Solicitações de Bens.'
            . '</div>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</div>';
    }

    private function corStatusHtml(string $status): string
    {
        return match ($status) {
            SolicitacaoBem::STATUS_PENDENTE => '#f59e0b',
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO => '#2563eb',
            SolicitacaoBem::STATUS_LIBERACAO => '#7c3aed',
            SolicitacaoBem::STATUS_CONFIRMADO => '#059669',
            SolicitacaoBem::STATUS_NAO_ENVIADO,
            SolicitacaoBem::STATUS_NAO_RECEBIDO,
            SolicitacaoBem::STATUS_CANCELADO => '#dc2626',
            SolicitacaoBem::STATUS_RECEBIDO => '#16a34a',
            default => '#475569',
        };
    }

    private function resolverContextoEvento(SolicitacaoBem $solicitacao, string $evento): ?array
    {
        $destinatariosSolicitante = $this->resolverDestinatariosSolicitante($solicitacao);
        $destinatariosEtapaAtual = $this->resolverDestinatariosEtapaAtual($solicitacao);
        $destinatariosCriacao = $this->resolverDestinatariosCriacao($solicitacao);

        return match ($evento) {
            'criada' => [
                'evento' => $evento,
                'titulo' => 'Nova solicitação aberta',
                'mensagem' => 'Uma nova solicitação foi aberta e já está aguardando a triagem inicial.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $destinatariosCriacao,
            ],
            'triagem_concluida' => [
                'evento' => $evento,
                'titulo' => 'Triagem concluída',
                'mensagem' => 'A solicitação foi validada e agora segue para medição e separação.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'medidas_registradas' => [
                'evento' => $evento,
                'titulo' => 'Medição concluída',
                'mensagem' => 'As medidas e o peso foram registrados. A solicitação está pronta para cotação.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'cotacoes_registradas' => [
                'evento' => $evento,
                'titulo' => 'Cotações registradas',
                'mensagem' => 'As cotações foram informadas e a solicitação aguarda autorização do Theo.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'autorizacao_liberacao' => [
                'evento' => $evento,
                'titulo' => 'Autorização registrada',
                'mensagem' => 'Theo autorizou a solicitação. Agora ela segue para a liberação final do Bruno.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'liberacao_aprovada' => [
                'evento' => $evento,
                'titulo' => 'Liberação aprovada',
                'mensagem' => 'A liberação final do Bruno foi aprovada e a solicitação já pode seguir para o envio.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'envio_registrado', 'pedido_enviado' => [
                'evento' => $evento,
                'titulo' => 'Envio registrado',
                'mensagem' => 'O envio foi registrado e a solicitação aguarda confirmação de recebimento.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'pedido_nao_recebido' => [
                'evento' => $evento,
                'titulo' => 'Pedido marcado como não recebido',
                'mensagem' => 'O solicitante informou que o pedido não foi recebido. A solicitação precisa de tratativa.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'retorno_analise', 'contestacao_nao_recebido' => [
                'evento' => $evento,
                'titulo' => 'Solicitação retornada para uma etapa anterior',
                'mensagem' => 'A solicitação voltou no fluxo e já está disponível para a próxima ação necessária.',
                'proxima_etapa' => $this->descricaoEtapaAtual($solicitacao),
                'destinatarios' => $this->mesclarContatos($destinatariosEtapaAtual, $destinatariosSolicitante),
            ],
            'cotacao_recusada', 'pedido_nao_enviado', 'solicitacao_cancelada', 'pedido_recebido' => [
                'evento' => $evento,
                'titulo' => $this->tituloEventoFinal($evento),
                'mensagem' => $this->mensagemEventoFinal($evento),
                'proxima_etapa' => null,
                'destinatarios' => $destinatariosSolicitante,
            ],
            default => null,
        };
    }

    private function montarAssunto(SolicitacaoBem $solicitacao, array $contexto): string
    {
        $prefixo = trim((string) config('solicitacoes_bens.notificacoes.subject_prefix', 'Solicitação de bens'));

        return $prefixo . ' #' . $solicitacao->id . ' - ' . $contexto['titulo'];
    }

    private function montarCorpo(SolicitacaoBem $solicitacao, array $contexto, ?array $destinatario = null): string
    {
        $nomeProjeto = trim((string) ($solicitacao->projeto->NOMEPROJETO ?? ''));
        $codigoProjeto = trim((string) ($solicitacao->projeto->CDPROJETO ?? ''));
        $identificacaoProjeto = $nomeProjeto !== '' ? $nomeProjeto : '-';

        if ($codigoProjeto !== '') {
            $identificacaoProjeto .= ' (' . $codigoProjeto . ')';
        }

        $detalhesComplementares = $this->montarDetalhesComplementares($solicitacao, $contexto['evento'] ?? null);
        $saudacao = $destinatario['nome'] ?? null;

        return implode("\n", array_filter([
            $saudacao ? 'Olá, ' . $saudacao . '.' : null,
            $contexto['titulo'],
            $contexto['mensagem'],
            '',
            $contexto['proxima_etapa'] ? 'Nova etapa: ' . $contexto['proxima_etapa'] : null,
            $contexto['proxima_etapa'] ? '' : null,
            $detalhesComplementares !== '' ? 'Informações novas:' : null,
            $detalhesComplementares !== '' ? $detalhesComplementares : null,
            $detalhesComplementares !== '' ? '' : null,
            'Número: ' . $solicitacao->id,
            'Projeto: ' . $identificacaoProjeto,
            'Local de destino: ' . ($solicitacao->local_destino ?: '-'),
            'Fluxo responsável: ' . ($solicitacao->fluxo_responsavel_label ?: 'Padrão'),
            'Recebedor: ' . ($solicitacao->nome_recebedor ?: '-'),
            '',
            'Itens da solicitação:',
            $this->montarResumoItens($solicitacao),
            '',
            route('solicitacoes-bens.index', ['open_modal' => $solicitacao->id]),
        ]));
    }

    private function montarResumoItens(SolicitacaoBem $solicitacao): string
    {
        $linhas = $solicitacao->itens
            ->map(function ($item, int $index) {
                $descricao = trim((string) ($item->descricao ?? 'Item sem descrição'));
                $quantidade = (int) ($item->quantidade ?? 0);
                $unidade = trim((string) ($item->unidade ?? ''));
                $observacao = trim((string) ($item->observacao ?? ''));

                $linha = ($index + 1) . '. ' . $descricao . ' | Qtd: ' . $quantidade;
                if ($unidade !== '') {
                    $linha .= ' ' . $unidade;
                }
                if ($observacao !== '') {
                    $linha .= ' | Obs.: ' . $observacao;
                }

                return $linha;
            })
            ->all();

        return $linhas !== [] ? implode("\n", $linhas) : '- Nenhum item informado';
    }

    private function montarItensHtml(SolicitacaoBem $solicitacao): string
    {
        if ($solicitacao->itens->isEmpty()) {
            return '<div style="padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#f9fafb;color:#6b7280;font-size:14px;">Nenhum item informado.</div>';
        }

        $html = '';

        foreach ($solicitacao->itens as $index => $item) {
            $descricao = trim((string) ($item->descricao ?? 'Item sem descrição'));
            $quantidade = (int) ($item->quantidade ?? 0);
            $unidade = trim((string) ($item->unidade ?? ''));
            $observacao = trim((string) ($item->observacao ?? ''));
            $quantidadeLabel = 'Qtd: ' . $quantidade . ($unidade !== '' ? ' ' . $unidade : '');

            $html .=
                '<div style="margin-bottom:12px;padding:16px 18px;border:1px solid #e5e7eb;border-radius:14px;background:#ffffff;">'
                . '<div style="font-size:12px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;color:#6b7280;margin-bottom:8px;">Item ' . ($index + 1) . '</div>'
                . '<div style="font-size:16px;font-weight:700;color:#111827;margin-bottom:8px;">' . e($descricao) . '</div>'
                . '<div style="font-size:14px;color:#374151;">' . e($quantidadeLabel) . '</div>'
                . ($observacao !== '' ? '<div style="margin-top:10px;font-size:13px;line-height:1.6;color:#4b5563;"><strong>Observação:</strong> ' . e($observacao) . '</div>' : '')
                . '</div>';
        }

        return $html;
    }

    private function montarDetalhesComplementares(SolicitacaoBem $solicitacao, ?string $evento = null): string
    {
        return implode("\n", array_map(
            static fn (array $detalhe) => $detalhe['titulo'] . ': ' . $detalhe['valor'],
            $this->detalhesComplementaresLista($solicitacao, $evento)
        ));
    }

    private function montarDetalhesComplementaresHtml(SolicitacaoBem $solicitacao, ?string $evento = null): string
    {
        $linhas = $this->detalhesComplementaresLista($solicitacao, $evento);

        if ($linhas === []) {
            return '';
        }

        $html = '<div style="padding:8px 0;">';

        foreach ($linhas as $linha) {
            $corBorda = $linha['cor_borda'] ?? '#e5e7eb';
            $corFundo = $linha['cor_fundo'] ?? '#f9fafb';
            $corTitulo = $linha['cor_titulo'] ?? '#111827';

            $html .=
                '<div style="margin-bottom:12px;padding:16px 18px;border:1px solid ' . e($corBorda) . ';border-radius:14px;background:' . e($corFundo) . ';">'
                . '<div style="font-size:12px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;color:' . e($corTitulo) . ';margin-bottom:8px;">' . e($linha['titulo']) . '</div>'
                . '<div style="font-size:15px;line-height:1.65;color:#1f2937;">' . e($linha['valor']) . '</div>'
                . '</div>';
        }

        return $html . '</div>';
    }

    private function detalhesComplementaresLista(SolicitacaoBem $solicitacao, ?string $evento = null): array
    {
        $linhas = [];

        if ($solicitacao->hasLogisticsData()) {
            $partesMedicao = [];

            if ($solicitacao->logistics_volume_count !== null) {
                $partesMedicao[] = sprintf('Volumes: %d', (int) $solicitacao->logistics_volume_count);
            }

            $partesMedicao[] = sprintf(
                'Altura: %s cm | Largura: %s cm | Comprimento: %s cm | Peso: %s kg',
                $this->valorOuHifen($solicitacao->logistics_height_cm),
                $this->valorOuHifen($solicitacao->logistics_width_cm),
                $this->valorOuHifen($solicitacao->logistics_length_cm),
                $this->valorOuHifen($solicitacao->logistics_weight_kg),
            );

            $linhas[] = [
                'chave' => 'medidas_peso',
                'titulo' => 'Medidas e peso',
                'valor' => implode(' | ', $partesMedicao),
                'cor_borda' => '#93c5fd',
                'cor_fundo' => '#eff6ff',
                'cor_titulo' => '#1d4ed8',
            ];

            if (trim((string) ($solicitacao->logistics_asset_number ?? '')) !== '') {
                $linhas[] = [
                    'chave' => 'patrimonio_logistica',
                    'titulo' => 'Patrimônio informado',
                    'valor' => trim((string) $solicitacao->logistics_asset_number),
                    'cor_borda' => '#bfdbfe',
                    'cor_fundo' => '#f8fbff',
                    'cor_titulo' => '#2563eb',
                ];
            }

            if (trim((string) ($solicitacao->logistics_notes ?? '')) !== '') {
                $linhas[] = [
                    'chave' => 'observacao_medicao',
                    'titulo' => 'Observação da medição',
                    'valor' => trim((string) $solicitacao->logistics_notes),
                    'cor_borda' => '#bfdbfe',
                    'cor_fundo' => '#f8fbff',
                    'cor_titulo' => '#2563eb',
                ];
            }
        }

        if ($solicitacao->hasQuoteOptions()) {
            foreach ($solicitacao->quoteOptions() as $index => $quote) {
                $descricaoCotacao = sprintf(
                    'Transportadora: %s | Valor: %s | Prazo: %s | Tipo de rastreio: %s',
                    $quote['transporter'] ?? '-',
                    isset($quote['amount']) ? 'R$ ' . number_format((float) $quote['amount'], 2, ',', '.') : '-',
                    $quote['deadline'] ?? '-',
                    $quote['tracking_type'] ?? '-',
                );

                if (!empty($quote['notes'])) {
                    $descricaoCotacao .= ' | Observação: ' . $quote['notes'];
                }

                $linhas[] = [
                    'chave' => 'cotacao_' . ($index + 1),
                    'titulo' => 'Cotação ' . ($index + 1),
                    'valor' => $descricaoCotacao,
                    'cor_borda' => '#fcd34d',
                    'cor_fundo' => '#fffbeb',
                    'cor_titulo' => '#b45309',
                ];
            }
        }

        $selectedQuote = $solicitacao->selectedQuote();
        if ($selectedQuote) {
            $linhas[] = [
                'chave' => 'cotacao_aprovada',
                'titulo' => 'Cotação aprovada',
                'valor' => sprintf(
                    'Transportadora: %s | Valor: %s | Prazo: %s',
                    $selectedQuote['transporter'] ?? '-',
                    isset($selectedQuote['amount']) ? 'R$ ' . number_format((float) $selectedQuote['amount'], 2, ',', '.') : '-',
                    $selectedQuote['deadline'] ?? '-',
                ),
                'cor_borda' => '#86efac',
                'cor_fundo' => '#f0fdf4',
                'cor_titulo' => '#15803d',
            ];

            $previsaoChegada = $this->montarPrevisaoChegada($solicitacao, $selectedQuote);
            if ($previsaoChegada !== null) {
                $linhas[] = [
                    'chave' => 'previsao_chegada',
                    'titulo' => 'Previsão de chegada',
                    'valor' => $previsaoChegada,
                    'cor_borda' => '#fdba74',
                    'cor_fundo' => '#fff7ed',
                    'cor_titulo' => '#c2410c',
                ];
            }
        }

        if ($solicitacao->tracking_code) {
            $linhas[] = [
                'chave' => 'codigo_rastreio',
                'titulo' => 'Código de rastreio',
                'valor' => $solicitacao->tracking_code,
                'cor_borda' => '#c4b5fd',
                'cor_fundo' => '#f5f3ff',
                'cor_titulo' => '#6d28d9',
            ];
        }

        if ($solicitacao->invoice_number) {
            $linhas[] = [
                'chave' => 'nota_fiscal',
                'titulo' => 'Nota fiscal',
                'valor' => $solicitacao->invoice_number,
                'cor_borda' => '#a7f3d0',
                'cor_fundo' => '#ecfdf5',
                'cor_titulo' => '#047857',
            ];
        }

        if ($solicitacao->justificativa_cancelamento) {
            $linhas[] = [
                'chave' => 'justificativa',
                'titulo' => 'Justificativa',
                'valor' => trim((string) $solicitacao->justificativa_cancelamento),
                'cor_borda' => '#fca5a5',
                'cor_fundo' => '#fef2f2',
                'cor_titulo' => '#b91c1c',
            ];
        }

        if ($solicitacao->observacao) {
            $linhas[] = [
                'chave' => 'observacao_solicitante',
                'titulo' => 'Observação do solicitante',
                'valor' => trim((string) $solicitacao->observacao),
                'cor_borda' => '#d1d5db',
                'cor_fundo' => '#f9fafb',
                'cor_titulo' => '#4b5563',
            ];
        }

        if ($solicitacao->observacao_controle) {
            $linhas[] = [
                'chave' => 'observacao_interna',
                'titulo' => 'Observação interna',
                'valor' => trim((string) $solicitacao->observacao_controle),
                'cor_borda' => '#d1d5db',
                'cor_fundo' => '#f3f4f6',
                'cor_titulo' => '#374151',
            ];
        }

        $permitidos = $this->detalhesPermitidosPorEvento($evento);
        if ($permitidos === []) {
            return [];
        }

        return array_values(array_filter($linhas, static function (array $linha) use ($permitidos) {
            return in_array($linha['chave'] ?? '', $permitidos, true);
        }));
    }

    private function detalhesPermitidosPorEvento(?string $evento): array
    {
        return match ($evento) {
            'criada' => [
                'observacao_solicitante',
            ],
            'triagem_concluida' => [
                'observacao_solicitante',
                'observacao_interna',
            ],
            'medidas_registradas' => [
                'medidas_peso',
                'observacao_medicao',
                'observacao_solicitante',
            ],
            'cotacoes_registradas' => [
                'cotacao_1',
                'cotacao_2',
                'cotacao_3',
                'observacao_solicitante',
            ],
            'liberacao_aprovada' => [
                'cotacao_aprovada',
                'observacao_solicitante',
                'observacao_interna',
            ],
            'envio_registrado', 'pedido_enviado' => [
                'cotacao_aprovada',
                'previsao_chegada',
                'codigo_rastreio',
                'nota_fiscal',
                'observacao_interna',
            ],
            'pedido_nao_recebido' => [
                'previsao_chegada',
                'codigo_rastreio',
                'nota_fiscal',
                'observacao_solicitante',
                'observacao_interna',
            ],
            'retorno_analise', 'contestacao_nao_recebido' => [
                'justificativa',
                'observacao_solicitante',
                'observacao_interna',
            ],
            'cotacao_recusada' => [
                'cotacao_1',
                'cotacao_2',
                'cotacao_3',
                'justificativa',
                'observacao_interna',
            ],
            'pedido_nao_enviado', 'solicitacao_cancelada' => [
                'justificativa',
                'observacao_interna',
                'observacao_solicitante',
            ],
            'pedido_recebido' => [
                'previsao_chegada',
            ],
            default => [
                'observacao_solicitante',
                'observacao_interna',
            ],
        };
    }

    private function montarPrevisaoChegada(SolicitacaoBem $solicitacao, array $selectedQuote): ?string
    {
        $prazoTexto = trim((string) ($selectedQuote['deadline'] ?? ''));
        if ($prazoTexto === '') {
            return null;
        }

        if (!preg_match('/\d+/', $prazoTexto, $matches)) {
            return 'Prazo informado: ' . $prazoTexto . '.';
        }

        $diasUteis = (int) $matches[0];
        if ($diasUteis <= 0) {
            return 'Prazo informado: ' . $prazoTexto . '.';
        }

        $dataBase = $solicitacao->shipped_at instanceof Carbon
            ? $solicitacao->shipped_at->copy()
            : now();

        $previsao = $dataBase->copy()->addWeekdays($diasUteis);

        return sprintf(
            'Até %s, considerando %d dia(s) útil(eis) a partir de %s.',
            $previsao->format('d/m/Y'),
            $diasUteis,
            $dataBase->format('d/m/Y')
        );
    }

    private function tituloEventoFinal(string $evento): string
    {
        return match ($evento) {
            'cotacao_recusada' => 'Cotação recusada',
            'pedido_nao_enviado' => 'Pedido marcado como não enviado',
            'solicitacao_cancelada' => 'Solicitação cancelada',
            'pedido_recebido' => 'Pedido recebido',
            default => 'Atualização da solicitação',
        };
    }

    private function mensagemEventoFinal(string $evento): string
    {
        return match ($evento) {
            'cotacao_recusada' => 'A cotação foi recusada e a solicitação foi encerrada como não enviada.',
            'pedido_nao_enviado' => 'A solicitação foi encerrada como não enviada.',
            'solicitacao_cancelada' => 'A solicitação foi cancelada durante o fluxo.',
            'pedido_recebido' => 'O recebimento foi confirmado pelo solicitante e o fluxo foi concluído.',
            default => 'A solicitação recebeu uma atualização final.',
        };
    }

    private function descricaoEtapaAtual(SolicitacaoBem $solicitacao): string
    {
        return match ($solicitacao->status) {
            SolicitacaoBem::STATUS_PENDENTE => 'Triagem inicial',
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO => $solicitacao->hasLogisticsData()
                ? 'Registro de cotações'
                : 'Medição e separação',
            SolicitacaoBem::STATUS_LIBERACAO => $solicitacao->isAwaitingTheoAuthorization()
                ? 'Autorização do Theo'
                : 'Liberação final do Bruno',
            SolicitacaoBem::STATUS_CONFIRMADO => $solicitacao->hasShipmentData()
                ? 'Confirmação de recebimento do solicitante'
                : 'Envio do pedido',
            SolicitacaoBem::STATUS_NAO_RECEBIDO => 'Tratativa de não recebimento (triagem)',
            SolicitacaoBem::STATUS_RECEBIDO => 'Fluxo concluído com recebimento confirmado',
            SolicitacaoBem::STATUS_NAO_ENVIADO => 'Fluxo encerrado como não enviado',
            SolicitacaoBem::STATUS_CANCELADO => 'Fluxo cancelado',
            default => 'Acompanhamento da solicitação',
        };
    }

    private function resolverDestinatariosEtapaAtual(SolicitacaoBem $solicitacao): array
    {
        $fluxo = $this->resolverFluxoResponsavel($solicitacao);

        return match ($solicitacao->status) {
            SolicitacaoBem::STATUS_PENDENTE => $this->resolverContatosPorPapel('triagem', $fluxo),
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO => $solicitacao->hasLogisticsData()
                ? $this->resolverContatosPorPapel('cotacao', $fluxo)
                : $this->resolverContatosPorPapel('medicao', $fluxo),
            SolicitacaoBem::STATUS_LIBERACAO => $this->resolverContatosPorPapel('liberacao', $fluxo),
            SolicitacaoBem::STATUS_CONFIRMADO => $solicitacao->hasShipmentData()
                ? $this->resolverDestinatariosSolicitante($solicitacao)
                : $this->resolverContatosPorPapel('envio', $fluxo),
            SolicitacaoBem::STATUS_NAO_RECEBIDO => $this->resolverContatosPorPapel('triagem', $fluxo),
            default => [],
        };
    }

    private function resolverDestinatariosCriacao(SolicitacaoBem $solicitacao): array
    {
        $fluxo = $this->resolverFluxoResponsavel($solicitacao);

        return $this->mesclarContatos(
            $this->resolverContatosPorPapel('triagem', $fluxo),
            $this->resolverContatosPorPapel('medicao', $fluxo),
            $this->resolverContatosPorPapel('cotacao', $fluxo),
            $this->resolverContatosPorPapel('liberacao', $fluxo),
            $this->resolverDestinatariosSolicitante($solicitacao),
        );
    }

    private function resolverContatosPorPapel(string $papel, ?string $fluxo = null): array
    {
        $contatosPorCadastro = $this->resolverContatosPorCadastro($papel);
        $contatosDiretos = array_map(fn (string $email) => $this->criarContato($email), $this->resolverListaConfiguracao($this->resolverChaveConfigPapel($papel, 'emails', $fluxo)));
        $contatosPorLogin = $this->resolverContatosPorLogins(
            $this->resolverListaConfiguracao($this->resolverChaveConfigPapel($papel, 'logins', $fluxo)),
        );

        return $this->mesclarContatos($contatosPorCadastro, $contatosDiretos, $contatosPorLogin);
    }

    private function resolverFluxoResponsavel(SolicitacaoBem $solicitacao): string
    {
        return $solicitacao->fluxo_responsavel_normalizado;
    }

    private function resolverChaveConfigPapel(string $papel, string $tipo, ?string $fluxo = null): string
    {
        $fluxo = mb_strtoupper(trim((string) $fluxo), 'UTF-8');

        if ($fluxo !== '' && $fluxo !== 'PADRAO') {
            $chaveFluxo = "solicitacoes_bens.notificacoes.flows.{$fluxo}.roles.{$papel}.{$tipo}";
            if ($this->resolverListaConfiguracao($chaveFluxo) !== []) {
                return $chaveFluxo;
            }
        }

        return "solicitacoes_bens.notificacoes.roles.{$papel}.{$tipo}";
    }

    private function resolverContatosPorCadastro(string $papel): array
    {
        try {
            $usuariosIds = SolicitacaoBemNotificacaoUsuario::query()
                ->where('papel', $papel)
                ->pluck('usuario_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->all();
        } catch (\Throwable) {
            return [];
        }

        if ($usuariosIds === []) {
            return [];
        }

        $contatos = [];
        $usuarios = User::query()
            ->whereIn('NUSEQUSUARIO', $usuariosIds)
            ->get();

        foreach ($usuarios as $usuario) {
            if (!$this->usuarioPodeReceberPapel($usuario, $papel)) {
                continue;
            }

            $email = $this->resolverEmailUsuario($usuario);
            if ($email !== null) {
                $contatos[] = $this->criarContato($email, $this->resolverNomeUsuario($usuario));
            }
        }

        return $this->mesclarContatos($contatos);
    }

    private function usuarioPodeReceberPapel(User $usuario, string $papel): bool
    {
        if ($usuario->isAdmin()) {
            return true;
        }

        return match ($papel) {
            'triagem' => $usuario->temAcessoTela(User::TELA_SOLICITACOES_TRIAGEM_INICIAL),
            'medicao', 'cotacao' => $usuario->temAcessoTela(User::TELA_SOLICITACOES_ATUALIZAR),
            'liberacao' => $usuario->temAcessoTela(User::TELA_SOLICITACOES_LIBERACAO_ENVIO)
                || $usuario->temAcessoTela(User::TELA_SOLICITACOES_AUTORIZACAO_LIBERACAO),
            'envio' => $usuario->temAcessoTela(User::TELA_SOLICITACOES_APROVAR),
            default => false,
        };
    }

    private function resolverDestinatariosSolicitante(SolicitacaoBem $solicitacao): array
    {
        $destinatario = $this->resolverContatoSolicitante($solicitacao);

        return $destinatario !== null ? [$destinatario] : [];
    }

    private function resolverContatoSolicitante(SolicitacaoBem $solicitacao): ?array
    {
        $emailOrigem = $this->normalizarEmail($solicitacao->email_origem);
        if ($emailOrigem !== null) {
            return $this->criarContato($emailOrigem, $solicitacao->solicitante_nome ?: null);
        }

        $user = $this->resolverUsuarioSolicitante($solicitacao);
        if ($user) {
            $emailUsuario = $this->resolverEmailUsuario($user);
            if ($emailUsuario !== null) {
                return $this->criarContato($emailUsuario, $this->resolverNomeUsuario($user));
            }
        }

        $fallback = $this->normalizarEmail(config('solicitacoes_bens.notificacoes.fallback_to'));

        return $fallback !== null ? $this->criarContato($fallback) : null;
    }

    private function resolverUsuarioSolicitante(SolicitacaoBem $solicitacao): ?User
    {
        if ($solicitacao->solicitante_id === null) {
            return null;
        }

        return User::query()->find($solicitacao->solicitante_id);
    }

    private function resolverEmailUsuario(User $user): ?string
    {
        $emailColumn = $this->resolverColunaEmailUsuario($user);
        if ($emailColumn !== null) {
            $email = $this->normalizarEmail($user->getAttribute($emailColumn));
            if ($email !== null) {
                return $email;
            }
        }

        $dominio = trim((string) config('solicitacoes_bens.notificacoes.login_email_domain', ''));
        $login = trim((string) ($user->NMLOGIN ?? ''));

        if ($dominio !== '' && $login !== '') {
            return $this->normalizarEmail(strtolower($login) . '@' . ltrim($dominio, '@'));
        }

        return null;
    }

    private function resolverColunaEmailUsuario(User $user): ?string
    {
        $table = $user->getTable();

        foreach (['email', 'EMAIL'] as $column) {
            if ($this->tabelaTemColuna($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function tabelaTemColuna(string $table, string $column): bool
    {
        try {
            $columns = DB::select('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
            $column = mb_strtolower($column, 'UTF-8');

            foreach ($columns as $columnData) {
                $field = $columnData->Field ?? null;
                if (is_string($field) && mb_strtolower($field, 'UTF-8') === $column) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('[SOLICITACOES_EMAIL] Falha ao verificar coluna de e-mail do usuário.', [
                'tabela' => $table,
                'coluna' => $column,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function resolverContatosPorLogins(array $logins): array
    {
        $logins = array_values(array_unique(array_filter(array_map(function ($login) {
            $login = mb_strtoupper(trim((string) $login), 'UTF-8');

            return $login !== '' ? $login : null;
        }, $logins))));

        if ($logins === []) {
            return [];
        }

        $contatos = [];
        $users = User::query()
            ->whereIn(DB::raw('UPPER(NMLOGIN)'), $logins)
            ->get();

        foreach ($users as $user) {
            $email = $this->resolverEmailUsuario($user);
            if ($email !== null) {
                $contatos[] = $this->criarContato($email, $this->resolverNomeUsuario($user));
            }
        }

        $dominio = trim((string) config('solicitacoes_bens.notificacoes.login_email_domain', ''));
        if ($dominio !== '') {
            foreach ($logins as $login) {
                $contatos[] = $this->criarContato(strtolower($login) . '@' . ltrim($dominio, '@'), $login);
            }
        }

        return $this->mesclarContatos($contatos);
    }

    private function resolverListaConfiguracao(string $chave): array
    {
        $valor = config($chave, []);
        if (is_string($valor)) {
            $valor = explode(',', $valor);
        }

        if (!is_array($valor)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item) {
            $item = trim((string) $item);

            return $item !== '' ? $item : null;
        }, $valor)));
    }

    private function aplicarDestinatarioForcado(array $destinatariosOriginais): array
    {
        $destinatarioForcado = $this->normalizarEmail((string) config('solicitacoes_bens.email_to', ''));
        if ($destinatarioForcado === null) {
            return $destinatariosOriginais;
        }

        Log::info('[SOLICITACOES_EMAIL] Destinatário de teste ativo; sobrescrevendo destinatários reais.', [
            'destinatario_teste' => $destinatarioForcado,
            'destinatarios_originais' => array_values(array_map(static fn (array $destinatario) => $destinatario['email'], $destinatariosOriginais)),
        ]);

        return [$this->criarContato($destinatarioForcado, 'Destinatário de teste')];
    }

    private function mesclarContatos(array ...$listas): array
    {
        $destinatarios = [];

        foreach ($listas as $lista) {
            foreach ($lista as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $email = $this->normalizarEmail($item['email'] ?? null);
                if ($email !== null) {
                    $destinatarios[$email] = [
                        'email' => $email,
                        'nome' => $this->normalizarNomeContato($item['nome'] ?? null),
                    ];
                }
            }
        }

        return array_values($destinatarios);
    }

    private function criarContato(?string $email, ?string $nome = null): array
    {
        return [
            'email' => (string) $email,
            'nome' => $this->normalizarNomeContato($nome),
        ];
    }

    private function resolverNomeUsuario(User $user): ?string
    {
        return $this->normalizarNomeContato($user->NOMEUSER ?? $user->NMLOGIN ?? null);
    }

    private function normalizarNomeContato(mixed $nome): ?string
    {
        if (!is_string($nome)) {
            return null;
        }

        $nome = trim($nome);

        return $nome !== '' ? $nome : null;
    }

    private function valorOuHifen(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return (string) $value;
    }

    private function normalizarEmail(mixed $email): ?string
    {
        if (!is_string($email)) {
            return null;
        }

        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }
}
