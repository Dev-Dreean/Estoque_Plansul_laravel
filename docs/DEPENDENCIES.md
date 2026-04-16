# DEPENDENCIES.md — Dependências e Integrações

> Confirmado no código: 2026-04-15
> Fontes: composer.json, package.json, config/, middlewares

---

## Dependências PHP (Composer)

### Produção

| Pacote | Versão | Uso |
|---|---|---|
| `laravel/framework` | ^11.0 | Framework base |
| `php` | ^8.2 | Runtime — no KingHost usar `php82` |
| `barryvdh/laravel-dompdf` | ^3.1 | Geração de PDF (Termos de Responsabilidade, Relatórios) |
| `blade-ui-kit/blade-heroicons` | ^2.6 | Ícones SVG via componente Blade |
| `laravel/tinker` | ^2.10 | REPL para debug local |
| `phpoffice/phpword` | ^1.3 | Geração de documentos DOCX (Termos de Responsabilidade) |
| `spatie/simple-excel` | ^3.6 | Leitura/escrita de CSV/XLSX (bulk import de patrimônios) |

### Desenvolvimento

| Pacote | Versão | Uso |
|---|---|---|
| `laravel/breeze` | ^2.3 | Scaffolding de autenticação (já aplicado) |
| `pestphp/pest` | ^3.8 | Framework de testes |
| `laravel/pint` | ^1.13 | Code style PHP (PSR-12) |
| `laradumps/laradumps` | ^4.6 | Debug visual |
| `laravel/sail` | ^1.41 | Ambiente Docker para dev |

**Risco de atualização:**
- `laravel/framework` 11→12: breaking changes em providers, middleware, routing
- `barryvdh/laravel-dompdf`: mudanças de API podem quebrar geração de PDF
- PHP 8.2→8.3: geralmente compatível, mas validar no KingHost disponibilidade

---

## Dependências JS (npm / Vite)

| Pacote | Versão | Uso |
|---|---|---|
| `vite` | — | Build frontend |
| `tailwindcss` | — | CSS utility-first (OBRIGATÓRIO para todo CSS) |
| `alpinejs` | — | Reatividade JavaScript nas views Blade |
| `@tailwindcss/forms` | — | Reset de estilos de formulários |
| `postcss` | — | Processamento CSS |
| `autoprefixer` | — | Prefixos CSS automáticos |

**Entrypoint frontend:** `resources/css/app.css` + `resources/js/app.js`
**Output compilado:** `public/build/` (via Vite manifest)

**Risco:** `public/build/` nunca deve ser editado manualmente. Regenerar via `npm run build`.

---

## Integrações Externas

### KingHost (Banco de Produção)

- **Tipo:** MySQL remoto (source of truth para patrimônios, funcionários, projetos)
- **Host:** `mysql07-farm10.kinghost.net`
- **Acesso:** Via SSH `plansul@ftp.plansul.info` + credenciais em `.env` (`DB_HOST_KINGHOST`, etc.)
- **Sync:** `app/Console/Commands/SyncKinghostData.php` + `AutoSyncKinghost` middleware
- **Frequência:** A cada 8h automaticamente
- **Risco:** Se KingHost mudar estrutura de tabelas, sync quebra silenciosamente. Validar com auditoria periódica.

### Power Automate (Microsoft)

- **Tipo:** Webhook HTTP
- **Endpoints:**
  - `POST /api/solicitacoes/email` — criação de solicitação via e-mail
  - `POST /api/sync/remote` — sync remoto de tabelas
- **Autenticação:** Header `X-API-KEY` (token em `config/solicitacoes_bens.power_automate_token`)
- **Config:** `config/solicitacoes_bens.php`
- **Risco:** Token incorreto/expirado bloqueia integração. Verificar variável `POWER_AUTOMATE_TOKEN` no `.env`.

### Serviço de E-mail (SMTP)

- **Tipo:** SMTP configurável
- **Driver:** definido em `.env` (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, etc.)
- **Uso:** Notificações de solicitações de bens
- **Job:** `SendSolicitacaoBemCriadaEmailJob` (assíncrono via fila)
- **Fila:** configurável em `config/solicitacoes_bens.notificacoes.queue_connection`
- **Risco:** Se fila não processar (queue worker parado), e-mails ficam pendentes sem falha visível

### Sistema de Filas (Queue)

- **Driver:** configurado em `.env` (`QUEUE_CONNECTION`)
- **KingHost (produção):** `sync` ou `database` (não tem Redis/Beanstalkd)
- **Jobs:** `SendSolicitacaoBemCriadaEmailJob`
- **Risco:** Em produção sem worker rodando, fila `database` acumula jobs sem processar

---

## Serviços de Infraestrutura

### KingHost (Hospedagem)

- **Tipo:** Hospedagem compartilhada
- **SSH:** `plansul@ftp.plansul.info`
- **App path:** `~/www/estoque-laravel`
- **PHP disponível:** `php82` (usar), `php` = PHP 5.6 (não usar)
- **Composer:** `php82 ~/composer.phar`
- **Crontab:** BLOQUEADO — auto-sync via middleware como workaround

### MySQL

- **Local:** `localhost` (desenvolvimento)
- **Produção:** KingHost MySQL (mesmo servidor, banco separado)
- **Versão:** Hipótese: MySQL 5.7/8.0 — validar com `SELECT VERSION()`

---

## Pontos Frágeis Identificados

1. **Nomes hard-coded em SolicitacaoBemFlowService** — matrículas, logins e nomes de operadores (Bruno, Tiago, Beatriz, Theo) estão em constantes PHP. Qualquer troca de colaborador exige deploy de código.

2. **Sync KingHost via `exec(nohup)`** — não há retry, monitoramento ou alertas de falha. Verificar `storage/logs/sync-kinghost.log` manualmente.

3. **Fila de e-mails** — sem worker dedicado no KingHost, dependente do driver `sync` (síncrono, pode impactar performance) ou jobs acumulam.

4. **Token Power Automate em `.env`** — se o `.env` for rollback ou resetado, integração quebra silenciosamente.

5. **Tabela `patr` com schema legado** — colunas UPPERCASE, sem `timestamps`, PK customizada (`NUSEQPATR`). Adicionar novas colunas exige cuidado com migrations e compatibilidade com KingHost.
