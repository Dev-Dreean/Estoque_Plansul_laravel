$stage = Join-Path $PSScriptRoot '..\.codex_deploy_stage'
$zip = Join-Path $PSScriptRoot '..\deploy_solicitacoes_email_20260325.zip'

if (Test-Path $stage) {
    Remove-Item $stage -Recurse -Force
}

if (Test-Path $zip) {
    Remove-Item $zip -Force
}

$files = @(
    'app/Http/Controllers/Auth/AuthenticatedSessionController.php',
    'app/Http/Controllers/ProfileController.php',
    'app/Http/Controllers/SolicitacaoBemController.php',
    'app/Http/Controllers/SolicitacaoEmailController.php',
    'app/Http/Controllers/UserController.php',
    'app/Http/Middleware/EnsureProfileIsComplete.php',
    'app/Models/User.php',
    'app/View/Components/AppLayout.php',
    'app/Jobs/SendSolicitacaoBemCriadaEmailJob.php',
    'app/Services/SolicitacaoBemEmailService.php',
    'config/solicitacoes_bens.php',
    'database/migrations/2026_03_24_000000_add_email_to_usuario_table.php',
    'public/build/manifest.json',
    'public/build/assets/app-D39jYA1c.css',
    'resources/views/layouts/app.blade.php',
    'resources/views/profile/complete.blade.php',
    'resources/views/profile/partials/update-profile-information-form.blade.php',
    'resources/views/solicitacoes/index.blade.php',
    'resources/views/solicitacoes/partials/show-content.blade.php',
    'resources/views/usuarios/partials/form.blade.php',
    'routes/web.php'
)

foreach ($file in $files) {
    $source = Join-Path $PSScriptRoot "..\\$file"
    $target = Join-Path $stage $file
    $dir = Split-Path $target -Parent
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    Copy-Item $source $target -Force
}

Compress-Archive -Force -Path (Join-Path $stage '*') -DestinationPath $zip
Write-Output $zip
