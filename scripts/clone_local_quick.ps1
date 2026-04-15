<#
.SYNOPSIS
    Script PowerShell para clonar e preparar rapidamente o projeto Plansul localmente.

.USAGE
    # Interativo:
    .\scripts\clone_local_quick.ps1

    # Com parâmetros:
    .\scripts\clone_local_quick.ps1 -RepoUrl "https://github.com/usuario/repositorio.git" -TargetDir "plansul" -Branch "main"

NOTAS
    - Requer Git instalado. Recomenda Composer, PHP e Node.js para executar instalação de dependências.
    - Não executa migrations automaticamente (por segurança).
#>
Param(
    [string]$RepoUrl,
    [string]$TargetDir,
    [string]$Branch = "main",
    [switch]$SkipInstall
)

function CommandExists {
    param([string]$name)
    return $null -ne (Get-Command $name -ErrorAction SilentlyContinue)
}

if (-not $RepoUrl) {
    $RepoUrl = Read-Host "URL do repositório (ex.: https://github.com/usuario/repositorio.git)"
}
if (-not $TargetDir) {
    $TargetDir = ($RepoUrl.TrimEnd('/') -split '/')[ -1 ] -replace '\.git$',''
}

Write-Host "Clonando $RepoUrl → $TargetDir (branch: $Branch)" -ForegroundColor Cyan

if (Test-Path $TargetDir) {
    $ans = Read-Host "Diretório '$TargetDir' já existe. Remover e continuar? (s/n)"
    if ($ans -notin @('s','S','y','Y')) { Write-Host "Abortando."; exit 1 }
    Remove-Item -Recurse -Force $TargetDir
}

if (-not (CommandExists 'git')) { Write-Host "git não encontrado. Instale Git antes de prosseguir."; exit 1 }

$gitArgs = @('clone', $RepoUrl, $TargetDir, '--depth', '1')
if ($Branch) { $gitArgs += @('-b', $Branch) }
git @gitArgs
if ($LASTEXITCODE -ne 0) { Write-Host "git clone falhou."; exit 1 }

Set-Location $TargetDir

if (Test-Path .env) { Write-Host ".env existe, pulando cópia." }
elseif (Test-Path .env.example) { Copy-Item .env.example .env; Write-Host "Criado .env a partir de .env.example" }
else { Write-Host "Nenhum .env.example encontrado — crie .env manualmente." }

if (-not $SkipInstall) {
    if (CommandExists 'composer') {
        Write-Host "Instalando dependências PHP (composer)..." -ForegroundColor Green
        composer install --prefer-dist --no-interaction
    } else {
        Write-Host "Composer não encontrado — execute 'composer install' manualmente após instalar Composer." -ForegroundColor Yellow
    }

    if (CommandExists 'npm') {
        Write-Host "Instalando pacotes NPM..." -ForegroundColor Green
        npm install
    } else {
        Write-Host "npm não encontrado — instale Node.js para rodar o frontend." -ForegroundColor Yellow
    }

    if (CommandExists 'php') {
        Write-Host "Gerando APP_KEY..." -ForegroundColor Green
        php artisan key:generate
    } else {
        Write-Host "php não encontrado — instale PHP para rodar comandos artisan." -ForegroundColor Yellow
    }
}

Write-Host ""; Write-Host "Próximos passos rápidos:" -ForegroundColor Cyan
Write-Host "1) Abra $TargetDir/.env e ajuste DB_*, MAIL_* e demais variáveis." -ForegroundColor White
Write-Host "2) Rode 'php artisan migrate --seed' após configurar o DB." -ForegroundColor White
Write-Host "3) Para desenvolvimento: 'npm run dev' e 'php artisan serve'." -ForegroundColor White

Write-Host ""; Write-Host "Script finalizado." -ForegroundColor Green
