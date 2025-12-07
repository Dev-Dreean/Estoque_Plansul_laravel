@echo off
REM Script de Unificacao de Usuarios Duplicados - Plansul
REM Uso: unify_users.bat [--dry-run] [--user=BEATRIZ.SC]
REM Versao: 1.0
REM Data: 2025-12-07

setlocal enabledelayedexpansion

set "DRY_RUN="
set "USER=BEATRIZ.SC"

REM Parse argumentos
:parse_args
if "%~1"=="" goto :start_unify
if "%~1"=="--dry-run" (
    set "DRY_RUN=--dry-run"
    shift
    goto :parse_args
)
if "%~1:~0,7%"=="--user=" (
    set "USER=%~1:~7%"
    shift
    goto :parse_args
)
shift
goto :parse_args

:start_unify
cls
echo.
echo ================================================================
echo.
echo   ^*^*^* UNIFICACAO DE USUARIOS DUPLICADOS - PLANSUL ^*^*^*
echo.
echo ================================================================
echo.
echo Usuario Principal: %USER%
if "%DRY_RUN%"=="" (
    echo Modo: EXECUCAO REAL
) else (
    echo Modo: DRY RUN ^(sem aplicar mudancas^)
)
echo.

if not exist "artisan" (
    echo ERRO: arquivo 'artisan' nao encontrado!
    echo Execute este script do diretorio raiz da aplicacao Laravel.
    pause
    exit /b 1
)

echo Executando: php artisan users:unify --user=%USER% %DRY_RUN%
echo.

php artisan users:unify --user=%USER% %DRY_RUN%

echo.
echo ================================================================
echo Operacao finalizada!
echo ================================================================
echo.

if not "%DRY_RUN%"=="" (
    echo Para executar a consolidacao de verdade, use:
    echo   unify_users.bat --user=%USER%
    echo.
)

pause
endlocal
