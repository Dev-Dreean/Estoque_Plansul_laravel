@echo off
REM Script para abrir porta 8000 no Firewall (execute como ADMIN)
REM Click direito > Executar como administrador

echo.
echo ========================================
echo Abrindo porta 8000 no Firewall...
echo ========================================
echo.

REM Remover regra anterior se existir
netsh advfirewall firewall delete rule name="Laravel Port 8000" >nul 2>&1

REM Criar nova regra
netsh advfirewall firewall add rule name="Laravel Port 8000" dir=in action=allow protocol=tcp localport=8000 profile=any

echo.
echo ========================================
echo ✅ Porta 8000 aberta com sucesso!
echo ========================================
echo.
echo Agora execute no PowerShell:
echo php artisan serve
echo.
echo O script scripts\start_laravel_server.ps1 mostra a URL da rede automaticamente.
echo Se preferir abrir manualmente, use o IPv4 atual desta maquina na porta 8000.
echo.
pause
