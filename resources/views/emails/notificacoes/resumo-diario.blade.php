<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo diário de pendências importantes</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Segoe UI,Arial,sans-serif;color:#111827;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;">
        <tr>
            <td style="padding:24px 28px;background:linear-gradient(120deg,#1d4ed8 0%, #2563eb 42%, #f97316 100%);color:#ffffff;">
                <div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.92;margin-bottom:10px;">Sistema Plansul</div>
                <div style="font-size:28px;line-height:1.2;font-weight:700;margin-bottom:8px;">Resumo diário de pendências</div>
                <div style="font-size:15px;line-height:1.55;max-width:560px;opacity:0.96;">
                    Olá, <strong>{{ $usuario->NOMEUSER ?? $usuario->NMLOGIN ?? 'usuário' }}</strong>. Você tem
                    <strong>{{ $totalCount }}</strong> pendência(s) importante(s) aguardando sua atenção.
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;">
                @foreach($grouped as $modulo => $itemsDoModulo)
                    <div style="margin-bottom:24px;">
                        <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:12px;">{{ $modulo }}</div>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;background:#ffffff;">
                            @foreach($itemsDoModulo as $item)
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #e5e7eb;">
                                        <div style="font-size:15px;font-weight:700;color:#111827;">{{ $item['titulo'] }}</div>
                                        <div style="margin-top:4px;font-size:13px;line-height:1.5;color:#4b5563;">{{ $item['descricao'] }}</div>
                                        <div style="margin-top:8px;font-size:12px;color:#6b7280;">Atualizado em {{ $item['occurred_at_label'] ?? '-' }}</div>
                                        <div style="margin-top:12px;">
                                            <a href="{{ $item['url'] }}" style="display:inline-block;padding:10px 16px;border-radius:10px;background:#2563eb;color:#ffffff;text-decoration:none;font-size:13px;font-weight:700;">
                                                {{ $item['acao_label'] }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach

                <div style="padding-top:12px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
                    Mensagem automática do sistema de pendências importantes.
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
