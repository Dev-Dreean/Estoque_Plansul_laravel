# TROUBLESHOOTING.md — Guia de Manutenção Rápida

> Confirmado no código: 2026-04-15
> Base: análise de middlewares, services, configurações e histórico de migrations

---

## PROBLEMA 1 — Usuário sem acesso a nenhuma tela após criação

**Sintoma:** Usuário loga mas o menu aparece vazio ou 403 em todas as rotas.

**Causa provável:** `acessousuario` sem registros vinculados ao usuário.

**Onde olhar:**
- Tabela `acessousuario` → filtrar por user id
- `app/Models/User.php` → método `temAcessoTela()`
- `app/Http/Middleware/CheckTelaAccess.php`

**Correção:**
```sql
-- Ver telas do usuário
SELECT * FROM acessousuario WHERE NUSEQUSUARIO = <id>;

-- Inserir acesso à tela de patrimônio (1000) para o usuário
INSERT INTO acessousuario (NUSEQUSUARIO, NUSEQTELA, INACESSO)
VALUES (<id>, 1000, 'S');
```
Ou usar a interface de Usuários (`/usuarios/<id>/edit`) para liberar telas.

**Validação:** Usuário deve ver o menu e acessar a tela liberada.

---

## PROBLEMA 2 — Sync KingHost falhou (dados desatualizados)

**Sintoma:** Funcionários ou locais não aparecem no autocomplete; dados antigos exibidos.

**Causa provável:** SSH bloqueado, credenciais expiradas, ou erro silencioso no middleware.

**Onde olhar:**
- `storage/logs/sync-kinghost.log`
- `storage/app/sync-kinghost.lock` (timestamp da última execução bem-sucedida)
- `app/Console/Commands/SyncKinghostData.php`

**Correção:**
```bash
# Executar sync manual no servidor
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && php82 artisan sync:kinghost-data"

# Verificar log após execução
ssh plansul@ftp.plansul.info "tail -50 ~/www/estoque-laravel/storage/logs/sync-kinghost.log"
```

**Validação:** Conferir contagem de registros: `funcionarios` ~5.227, `locais_projeto` ~1.939.

---

## PROBLEMA 3 — E-mails de solicitações não chegam

**Sintoma:** Solicitações criadas/aprovadas mas participantes não recebem e-mail.

**Causa provável:**
- Queue worker não está rodando (jobs acumulam sem processar)
- Config SMTP incorreta no `.env`
- `NOTIFICATIONS_ENABLED=false` em `config/solicitacoes_bens.php`

**Onde olhar:**
- `config/solicitacoes_bens.php` → `notificacoes.enabled`
- `.env` → `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- Tabela `jobs` (queue database) → jobs pendentes
- Tabela `failed_jobs` → jobs com erro
- `storage/logs/laravel.log` → erros de envio

**Correção:**
```bash
# Verificar jobs pendentes
php artisan queue:monitor

# Processar fila manualmente
php82 artisan queue:work --once

# Ver failed jobs
php82 artisan queue:failed
```

**Validação:** Criar solicitação de teste e verificar se e-mail chega.

---

## PROBLEMA 4 — Erro "Parse error: unexpected ':'" no KingHost

**Sintoma:** Comando `php artisan` falha com erro de sintaxe PHP.

**Causa:** Usando `php` (versão 5.6) em vez de `php82`.

**Correção:**
```bash
# ERRADO
php artisan migrate

# CORRETO
php82 artisan migrate
```

**Validação:** `php82 --version` deve mostrar PHP 8.2+.

---

## PROBLEMA 5 — Webhook Power Automate retorna 401

**Sintoma:** POST para `/api/solicitacoes/email` ou `/api/sync/remote` retorna 401 Unauthorized.

**Causa provável:**
- Token `POWER_AUTOMATE_TOKEN` diferente entre PA e `.env`
- Header `X-API-KEY` não enviado pelo Power Automate

**Onde olhar:**
- `.env` → `POWER_AUTOMATE_TOKEN`
- `config/solicitacoes_bens.php` → `power_automate_token`
- `app/Http/Middleware/VerifyPowerAutomateToken.php`

**Correção:**
- Sincronizar o token entre o Flow do Power Automate e o `.env` da aplicação
- Após mudar `.env`: `php82 artisan config:clear`

**Validação:** Testar com `curl -H "X-API-KEY: <token>" -X POST <url>`.

---

## PROBLEMA 6 — Tela branca / 500 após migration

**Sintoma:** Aplicação retorna 500 após rodar migrations.

**Causa provável:**
- Migration com erro de SQL
- Column type incompatível com dados existentes
- FK constraint violation

**Onde olhar:**
- `storage/logs/laravel.log`
- `php82 artisan migrate:status`

**Correção:**
```bash
# Ver status das migrations
php82 artisan migrate:status

# Rollback da última migration problemática
php82 artisan migrate:rollback --step=1

# Verificar log
tail -100 storage/logs/laravel.log
```

**Validação:** `php82 artisan migrate:status` deve mostrar todas as migrations como `Ran`.

---

## PROBLEMA 7 — CSS/Estilos quebrados após deploy

**Sintoma:** Tailwind não aplica, botões sem cores, layout quebrado.

**Causa provável:**
- `public/build/` não foi enviado ou está desatualizado
- `npm run build` não foi executado após mudança em `resources/`
- Manifest desatualizado

**Onde olhar:**
- `public/build/manifest.json` → verificar se o hash dos assets é recente
- `resources/css/app.css` e `resources/js/app.js`

**Correção:**
```bash
# Local: recompilar
npm run build

# Enviar para KingHost via git push (ou scp do public/build/)
git add public/build/ && git commit -m "Recompila assets" && git push
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && git pull && php82 artisan view:clear"
```

**Validação:** Inspecionar no browser que os arquivos CSS carregam com hash correto no `src`.

---

## PROBLEMA 8 — Patrimônio sem local ou local incorreto

**Sintoma:** Patrimônio aparece sem local ou com local de outro projeto.

**Causa provável:**
- `CDLOCAL` da tabela `patr` não tem correspondente em `locais_projeto`
- `tabfant_id` em `locais_projeto` aponta para projeto errado

**Onde olhar:**
- `app/Services/PatrimonioService@anexarLocaisCorretos()`
- `app/Models/Patrimonio@getProjetoCorretoAttribute()`
- `locais_projeto` → checar `cdlocal` duplicado entre projetos

**Validação:**
```sql
-- Verificar patrimônios sem local vinculado
SELECT p.NUPATRIMONIO, p.CDLOCAL FROM patr p
LEFT JOIN locais_projeto lp ON lp.cdlocal = p.CDLOCAL
WHERE lp.id IS NULL AND p.CDLOCAL IS NOT NULL;
```

---

## PROBLEMA 9 — Fluxo de solicitação travado em determinado status

**Sintoma:** Botão de ação não aparece para o usuário correto, ou status não avança.

**Causa provável:**
- Usuário não tem a tela de permissão correta (1019, 1020, 1021, etc.)
- Matrícula/login do operador não está nas constantes do `SolicitacaoBemFlowService`
- Fluxo do local (`fluxo_responsavel`) configurado incorretamente

**Onde olhar:**
- `app/Services/SolicitacaoBemFlowService.php` → constantes `FLOW_*_LOGINS` e `FLOW_*_MATRICULAS`
- `acessousuario` → telas 1019, 1020, 1021 do usuário
- `locais_projeto.fluxo_responsavel` → valor esperado: `''` (padrão) ou `'TI'`

**Correção:**
- Verificar se o login/matrícula do operador está nas constantes corretas do FlowService
- Verificar/ajustar permissão de tela via interface de Usuários

**Validação:** Usar `/debug-acessos` (autenticado) para ver permissões do usuário.

---

## PROBLEMA 10 — Login bloqueado (EnsureProfileIsComplete)

**Sintoma:** Usuário loga mas é redirecionado em loop para `/completar-perfil`.

**Causa provável:**
- Campo `CDMATRFUNCIONARIO` vazio ou com valor placeholder (`0`, `1`, prefixo `TMP-`)
- `needs_identity_update=true`
- Funcionário não encontrado na tabela `funcionarios`

**Onde olhar:**
- `app/Http/Middleware/EnsureProfileIsComplete.php`
- `app/Models/User.php` → constantes `MATRICULA_PLACEHOLDERS`, `MATRICULA_PLACEHOLDER_PREFIX`
- Tabela `usuario` → campos do usuário problemático

**Correção:**
- Atualizar manualmente o `CDMATRFUNCIONARIO` para a matrícula real
- Ou vincular via interface de Administrador de Usuários

---

## PROBLEMA 11 — `objetopatr` vazia (0 registros)

**Sintoma:** Campo "Tipo/Objeto" de patrimônio não carrega opções.

**Causa:** Tabela `objetopatr` não sincronizada com KingHost.

**Correção:**
```bash
# Importar do KingHost
ssh plansul@ftp.plansul.info "mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p'<SENHA>' plansul04 -e 'SELECT NUSEQOBJETO as NUSEQOBJ, NUSEQTIPOPATR, DEOBJETO FROM objetopatr;'"
# → Processar resultado e inserir localmente
```
Ver também: seção 14.4 do `.github/copilot-instructions.md` — "Restaurar Objetos (objetopatr)".

**Validação:** `SELECT COUNT(*) FROM objetopatr;` deve retornar ~1.208.
