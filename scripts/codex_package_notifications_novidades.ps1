$ErrorActionPreference = 'Stop'

$stage = Join-Path $PSScriptRoot '..\.codex_deploy_notifications_stage'
$zip = Join-Path $PSScriptRoot '..\deploy_notifications_novidades_20260325.zip'

if (Test-Path $stage) {
    Remove-Item $stage -Recurse -Force
}

if (Test-Path $zip) {
    Remove-Item $zip -Force
}

$files = @(
    'app/Console/Commands/SendDailyImportantNotificationsSummary.php',
    'app/Contracts/ImportantNotificationProvider.php',
    'app/Http/Controllers/ImportantNotificationController.php',
    'app/Http/Controllers/SystemNewsController.php',
    'app/Http/Controllers/RemovidosController.php',
    'app/Providers/AppServiceProvider.php',
    'app/Services/ImportantNotifications/SolicitacoesImportantNotificationProvider.php',
    'app/Services/ImportantNotifications/RemovidosImportantNotificationProvider.php',
    'app/Services/ImportantNotificationsService.php',
    'app/Services/SolicitacaoBemPendenciaService.php',
    'app/Services/SystemNewsService.php',
    'config/notificacoes.php',
    'config/novidades.php',
    'database/migrations/2026_03_25_113000_create_solicitacoes_bens_notificacao_usuarios_table.php',
    'database/migrations/2026_03_25_140000_add_logistics_volume_count_to_solicitacoes_bens.php',
    'database/migrations/2026_03_25_170000_create_novidades_sistema_visualizacoes_table.php',
    'resources/views/emails/notificacoes/resumo-diario.blade.php',
    'resources/views/layouts/navigation.blade.php',
    'resources/views/layouts/app.blade.php',
    'resources/views/removidos/index.blade.php',
    'resources/views/solicitacoes/index.blade.php',
    'resources/views/solicitacoes/partials/show-content.blade.php',
    'resources/js/app.js',
    'resources/css/app.css',
    'resources/css/components/notifications.css',
    'resources/css/components/system-news.css',
    'routes/web.php',
    'routes/console.php',
    'public/build/manifest.json',
    'public/build/assets/app-BaBnB5WQ.js',
    'public/build/assets/app-Dq9qyc34.css'
)

foreach ($file in $files) {
    $source = Join-Path $PSScriptRoot "..\\$file"
    if (-not (Test-Path $source)) {
        throw "Arquivo ausente: $file"
    }

    $target = Join-Path $stage $file
    $dir = Split-Path $target -Parent
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    Copy-Item $source $target -Force
}

Compress-Archive -Force -Path (Join-Path $stage '*') -DestinationPath $zip
Write-Output $zip
