<?php
// One-off script - Simular o payload exato do Power Automate

// Copiar exatamente do captura: Body section
$payload = [
    "id" => "AAMkADE4YTUwZTIwLThkY2QtNGY2NS1hODMyLWQ2YTFkODc2YTVmNQBGAAAAAABMvukI-pE8TalD-gdopp3PBwDqN1AVpTiYSIaW_cMVeqmFAAAAAAEMAADqN1AVpTiYSIaW_cMVeqmFAABuVlCXAAA=",
    "receivedDateTime" => "2026-01-26T19:34:13+00:00",
    "hasAttachments" => false,
    "internetMessageId" => "<CAMkpxHRDeHYkFm3aq89vCrRb3RZ6LCN6jW0Z1g+DZJiwce-xfg@mail.gmail.com>",
    "subject" => "Solicitacao de Bem",
    "bodyPreview" => "Solicitante: João Silva Teste\r\nMatricula: 99999\r\nProjeto: 1234 - Sistema Plansul\r\nUF: SP\r\nSetor: TI\r\nLocal destino: Sala de Testes\r\nObservacao: Teste final da integração\r\n\r\nItens:\r\n- Monitor 24\"; 1; UN; Teste monitor\r\n- Mouse; 1; UN; Teste mouse",
    "importance" => "normal",
    "conversationId" => "AAQkADE4YTUwZTIwLThkY2QtNGY2NS1hODMyLWQ2YTFkODc2YTVmNQAQAN1w3WPJTnxMlgrORfHPW-8=",
    "isRead" => false,
    "isHtml" => true,
    "body" => "<html><head>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><div dir=\"ltr\"><div class=\"gmail_default\" style=\"font-family:comic sans ms,sans-serif\">Solicitante: João Silva Teste<br>Matricula: 99999<br>Projeto: 1234 - Sistema Plansul<br>UF: SP<br>Setor: TI<br>Local destino: Sala de Testes<br>Observacao: Teste final da integração<br><br>Itens:<br>- Monitor 24&quot;; 1; UN; Teste monitor<br>- Mouse; 1; UN; Teste mouse</div></div></body></html>",
    "from" => "andrelucasbueno@gmail.com",
    "toRecipients" => "suporte.dev@plansul.com.br",
    "attachments" => []
];

echo "=== INSPECIONAR PAYLOAD ===\n";
echo "subject: " . ($payload['subject'] ?? 'VAZIO') . "\n";
echo "from: " . ($payload['from'] ?? 'VAZIO') . "\n";
echo "body (primeiros 100 chars): " . substr($payload['body'] ?? 'VAZIO', 0, 100) . "\n";

// Agora simular stringValue
function stringValue($value) {
    if ($value === null || $value === '') {
        return '';
    }
    if (is_string($value)) {
        return trim($value);
    }
    if (is_numeric($value)) {
        return (string) $value;
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_array($value) || is_object($value)) {
        return '';
    }
    return trim((string) $value);
}

echo "\n=== NORMALIZAR ===\n";
$subject = stringValue($payload['subject'] ?? '');
echo "subject normalizado: '$subject'\n";

$from = $payload['from'] ?? null;
echo "from: '$from'\n";

// Agora fazer extractBody
function extractBody($payload) {
    $body = $payload['body'] ?? '';

    if (is_array($body)) {
        $body = $body['content'] ?? $body['body'] ?? '';
    }

    if ($body === '' && isset($payload['bodyPreview'])) {
        $body = $payload['bodyPreview'];
        echo "[DEBUG] Usando bodyPreview\n";
    }

    if ($body === '' && isset($payload['body_preview'])) {
        $body = $payload['body_preview'];
        echo "[DEBUG] Usando body_preview\n";
    }

    if ($body === '' && isset($payload['bodyPreviewText'])) {
        $body = $payload['bodyPreviewText'];
        echo "[DEBUG] Usando bodyPreviewText\n";
    }

    $body = stringValue($body);
    if ($body === '') {
        return '';
    }

    $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $body = preg_replace('/<br\s*\/?>/i', "\n", $body);
    $body = strip_tags($body);
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = preg_replace('/\n+/', "\n", $body);

    return trim($body);
}

$body = extractBody($payload);
echo "\nBody extraído (primeiros 200 chars):\n" . substr($body, 0, 200) . "\n";

// Agora fazer normalizeFrom
function normalizeFrom($from) {
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

    $email_clean = $email ? strtolower(trim($email)) : null;

    return [
        'email' => $email_clean,
        'name' => $name ? trim($name) : null,
    ];
}

$from_normalized = normalizeFrom($from);
echo "\nFrom normalizado:\n";
echo "  email: " . ($from_normalized['email'] ?? 'null') . "\n";
echo "  name: " . ($from_normalized['name'] ?? 'null') . "\n";

echo "\n✅ Fim do teste\n";
?>
