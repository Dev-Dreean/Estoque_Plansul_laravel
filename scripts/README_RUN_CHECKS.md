# Run health checks

Estes scripts executam verificações básicas do ambiente e do projeto Laravel para identificar problemas comuns antes de rodar em produção.

Arquivos:
- `scripts/run_health_checks.sh` — script para Linux (bash). Executar via `bash scripts/run_health_checks.sh` ou em servidor Linux.
- `scripts/run_health_checks.ps1` — script para PowerShell (Windows). Executar via PowerShell: `.\	emplates\run_health_checks.ps1` (ou `powershell -File .\\scripts\\run_health_checks.ps1`).

O que cada script faz (resumo):
- Checa versão do PHP
- Verifica algumas extensões PHP críticas (openssl, pdo_mysql, mbstring, tokenizer, xml, ctype, json, fileinfo)
- Checa disponibilidade do `composer`
- Executa `php artisan --version` (valida ambiente Laravel)
- Verifica existência do `.env` e algumas chaves importantes (`APP_KEY`, `DB_CONNECTION`)
- Lista permissões de `storage` e `bootstrap/cache`
- Exibe as últimas 50 linhas de `storage/logs/laravel.log`

Observações:
- Os scripts são não intrusivos — não alteram o banco nem o código. São seguros para rodar em produção como diagnóstico.
- Alguns comandos (composer, php, php artisan) devem estar disponíveis no PATH do usuário que executar o script.
- Se desejar, posso adicionar verificações extras (migrações pendentes, testes unitários, composer validate, lint, etc.).

Como rodar localmente (Windows PowerShell):
```powershell
cd path\to\repo\plansul
powershell -ExecutionPolicy Bypass -File .\scripts\run_health_checks.ps1
```

Como rodar no servidor (Linux):
```bash
cd /home/plansul/www/estoque-laravel
bash scripts/run_health_checks.sh
```

Quer que eu rode os scripts aqui (workspace) agora e cole a saída? Ou prefere que eu só gere os arquivos e você execute no servidor? Se quiser, eu também adiciono uma task `npm`/`composer` para automatizar em CI.
