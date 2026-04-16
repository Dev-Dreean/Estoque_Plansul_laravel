# ARCHITECTURE.md — Mapa Macro do Sistema

> Confirmado no código: 2026-04-15
> Base: análise de routes/web.php, controllers, services, models, migrations, middlewares

---

## Visão Geral

Sistema Laravel 11 monolítico com Blade views. Backend PHP + Frontend Alpine.js/Tailwind.

```
Usuário → Browser (Blade + Alpine.js + Tailwind)
         → Laravel App (Controllers → Services → Models → MySQL)
         ← Banco local (mirror do KingHost)
         ← KingHost MySQL (source of truth, sync periódico)
```

---

## Módulos do Sistema

### 1. Controle de Patrimônio (TELA: 1000) — CRÍTICO

**Responsabilidade:** CRUD completo de ativos físicos (~11.394 registros).

- Controller: `app/Http/Controllers/PatrimonioController.php`
- Service: `app/Services/PatrimonioService.php`
- Model: `app/Models/Patrimonio.php` (tabela: `patr`)
- Bulk: `app/Http/Controllers/PatrimonioBulkController.php`
- Views: `resources/views/patrimonios/`
- Componentes: `resources/views/components/patrimonio-form.blade.php`, `patrimonio-table.blade.php`

**Funcionalidades:**
- Listagem com filtros avançados (projeto, local, situação, funcionário, UF, verificação, cadastrador)
- CRUD: criar, editar, visualizar, deletar
- Operações em massa: bulk situação, bulk verificar, bulk delete, bulk update via planilha
- Geração de Termos de Responsabilidade (PDF/DOCX)
- Vínculo de códigos (CODIGOS → CODOBJETO em objetopatr)
- Atribuição de funcionários e gerentes

**Dados críticos:**
- Situação: `EM_USO`, `DISPONIVEL`, `BAIXADO`, `MANUTENCAO` (Hipótese — validar em PatrimonioController)
- Verificação: campo `FLCONFERIDO`
- Campos físicos: `PESO`, `TAMANHO`, `VOLTAGEM`, `NUMMESA`

---

### 2. Solicitações de Bens (TELA: 1010) — CRÍTICO

**Responsabilidade:** Fluxo de compras/separação de itens com aprovações em cascata.

- Controller: `app/Http/Controllers/SolicitacaoBemController.php`
- Service (fluxo): `app/Services/SolicitacaoBemFlowService.php`
- Service (e-mail): `app/Services/SolicitacaoBemEmailService.php`
- Service (pendência): `app/Services/SolicitacaoBemPendenciaService.php`
- Model: `app/Models/SolicitacaoBem.php` (tabela: `solicitacoes_bens`)
- Job: `app/Jobs/SendSolicitacaoBemCriadaEmailJob.php`
- Views: `resources/views/solicitacoes/`

**Fluxo Padrão (Tiago/Beatriz):**
```
PENDENTE → AGUARDANDO_CONFIRMACAO → CONFIRMADO → LIBERACAO → (enviado/não enviado) → RECEBIDO
```

**Fluxo TI (Bruno):**
```
PENDENTE → AGUARDANDO_CONFIRMACAO → CONFIRMADO → LIBERACAO → [autorização Theo] → (enviado) → RECEBIDO
```

**Status possíveis:** `PENDENTE`, `AGUARDANDO_CONFIRMACAO`, `LIBERACAO`, `CONFIRMADO`, `NAO_ENVIADO`, `NAO_RECEBIDO`, `RECEBIDO`, `CANCELADO`, `ARQUIVADO`

**Telas de permissão para solicitações:**
- 1010: acessar módulo
- 1011: ver todas (Gestão de Colaboradores)
- 1012: atualizar
- 1013: criar
- 1014: aprovar
- 1015: cancelar
- 1016: histórico
- 1017: gerenciar visibilidade
- 1018: visualização restrita
- 1019: triagem inicial
- 1020: liberação de envio
- 1021: autorização de liberação

**Integrações:**
- Power Automate: POST `/api/solicitacoes/email` → `SolicitacaoEmailController`
- E-mail automático via job: `SendSolicitacaoBemCriadaEmailJob`

---

### 3. Projetos e Locais (TELA: 1002)

**Responsabilidade:** Cadastro e vínculo de projetos (filiais) e seus locais internos.

- Controller: `app/Http/Controllers/ProjetoController.php`
- Models: `app/Models/Tabfant.php` (tabela: `tabfant`, projetos), `app/Models/LocalProjeto.php` (tabela: `locais_projeto`)
- Views: `resources/views/projetos/`

**Dados críticos:**
- `tabfant`: projetos/filiais (~877 registros). ID=10000002 = SEDE.
- `locais_projeto`: locais por projeto (~1.939 registros), campo `tabfant_id` como FK
- Campo `fluxo_responsavel` em `locais_projeto` define se solicitações daquele local vão pelo fluxo TI ou padrão

---

### 4. Usuários (TELA: 1003)

**Responsabilidade:** Gestão de contas, perfis e permissões.

- Controller: `app/Http/Controllers/UserController.php`
- Model: `app/Models/User.php` (tabela: `usuario`)
- Perfis: `ADM` (admin), `USR` (padrão), `C` (consultor)
- Permissões: tabela `acessousuario` (FK → `acessotela`) via `app/Models/AcessoUsuario.php`

---

### 5. Gestão de Colaboradores (TELA: 1011)

**Responsabilidade:** Ver/gerenciar funcionários e seus vínculos de acesso.

- Controller: `app/Http/Controllers/GestaoColaboradoresController.php`
- Model: `app/Models/Funcionario.php` (tabela: `funcionarios`)
- Views: `resources/views/colaboradores/`

---

### 6. Dashboard / Gráficos (TELA: 1001)

- Controller: `app/Http/Controllers/DashboardController.php`
- Views: `resources/views/dashboard.blade.php`

---

### 7. Histórico (TELA: 1007)

**Responsabilidade:** Auditoria de movimentações de patrimônios.

- Controllers: `HistoricoController.php`, `HistoricoMovimentacaoController.php`
- Model: `app/Models/HistoricoMovimentacao.php`
- Views: `resources/views/historico/`

---

### 8. Relatórios (TELA: 1006)

- Controllers: `RelatorioController.php`, `RelatorioBensController.php`, `RelatorioDownloadController.php`
- Views: `resources/views/relatorios/`
- Dependências: `barryvdh/laravel-dompdf` (PDF), `phpoffice/phpword` (DOCX), `spatie/simple-excel` (Excel)

---

### 9. Termos de Responsabilidade

- Controllers: `TermoController.php`, `TermoDocxController.php`, `TermoResponsabilidadeController.php`
- Models: `TermoCodigo.php`, `TermoResponsabilidadeArquivo.php`, `TermoResponsabilidadeArquivoItem.php`
- Views: `resources/views/termos/`

---

### 10. Menu Principal / Navegação

- Controller: `app/Http/Controllers/MenuController.php`
- Helper: `app/Helpers/MenuHelper.php`
- View: `resources/views/menu/`
- Rota raiz `/` redireciona para `/menu`

---

### 11. Notificações Importantes

- Controller: `ImportantNotificationController.php`
- Service: `app/Services/ImportantNotifications/ImportantNotificationsService.php`
- API: GET `/api/notificacoes/importantes`

---

### 12. Novidades do Sistema

- Controller: `SystemNewsController.php`
- Service: `SystemNewsService.php`
- Config: `config/novidades.php` — lista de novidades com data de lançamento
- Popup automático para usuários que ainda não viram

---

## Infraestrutura e Integrações

### Banco de dados

- **Conexão local:** MySQL (`DB_CONNECTION=mysql`, localhost)
- **Fonte de verdade:** KingHost MySQL (`mysql07-farm10.kinghost.net`)
- **Sync:** `app/Console/Commands/SyncKinghostData.php` (via SSH + exec MySQL) + Middleware `AutoSyncKinghost` (a cada 8h, como poor man's cron)

**Tabelas críticas:**

| Tabela | Model | Registros esperados |
|---|---|---|
| `patr` | Patrimonio | ~11.394 |
| `usuario` | User | — |
| `tabfant` | Tabfant | ~877 |
| `locais_projeto` | LocalProjeto | ~1.939 |
| `funcionarios` | Funcionario | ~5.227 |
| `objetopatr` | ObjetoPatr | ~1.208 |
| `solicitacoes_bens` | SolicitacaoBem | crescente |

### Autenticação

- Laravel Breeze (email/senha)
- Middleware: `EnsureProfileIsComplete` exige matrícula e dados mínimos antes de usar o sistema
- Sessão: banco ou arquivo (conforme `.env`)

### Controle de Acesso

Sistema duplo:
1. Perfil (`ADM`/`USR`/`C`)
2. Telas liberadas por usuário (tabela `acessousuario` com `INACESSO=S`)

Middleware `CheckTelaAccess` intercepta rotas com `->middleware('tela.access:NNNN')`.

Configuração em `config/telas.php` — código numérico de 4 dígitos (1000–1021+).

### Power Automate

- Endpoint: `POST /api/solicitacoes/email` (criação de solicitação via e-mail)
- Endpoint: `POST /api/sync/remote` (sync remoto de tabelas)
- Autenticação: header `X-API-KEY` verificado por `VerifyPowerAutomateToken`
- Token configurado em: `config/solicitacoes_bens.php` via env `POWER_AUTOMATE_TOKEN`

### E-mail

- Driver: configurado em `.env` (`MAIL_MAILER`)
- Job assíncrono: `SendSolicitacaoBemCriadaEmailJob`
- Queue: configurável via `config/solicitacoes_bens.php`

### KingHost Deploy

- SSH: `plansul@ftp.plansul.info`
- App dir: `~/www/estoque-laravel`
- PHP: usar `php82` (não `php` que é 5.6)
- Deploy manual: `git pull` + `php82 artisan config:clear`

---

## O que é legado / sensível / ativo

**Ativo e crítico:**
- Módulo de patrimônios (core do sistema)
- Módulo de solicitações (em evolução ativa, migrations frequentes em 2026)
- Sync KingHost

**Sensível (mexer com cuidado):**
- Tabela `patr` — dados legados do sistema antigo KingHost, colunas em UPPERCASE
- Sistema de permissões via `acessousuario` (erros deixam usuários sem acesso)
- `SolicitacaoBemFlowService` — lógica de fluxo com nomes hard-coded (Bruno, Tiago, Beatriz, Theo)

**Legado / Hipótese:**
- `app/Console/Commands/_deprecated/` — comandos obsoletos
- Tabela `patr` com colunas que existem no KingHost mas algumas não sincronizadas
- `AlmoxarifadoController.php` — Hipótese: funcionalidade em desenvolvimento ou pouco usada (validar)
