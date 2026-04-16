# AGENT.md — Ponto Central de Navegação

> Arquivo primário para agentes de IA e mantenedores humanos.
> Leia este arquivo ANTES de abrir qualquer outro.
> Última atualização: 2026-04-15

---

## Objetivo do repositório

Sistema Laravel de **gestão de patrimônio e solicitações de bens** da empresa Plansul.

- Gerencia ~11.394 ativos físicos distribuídos em filiais/projetos
- Controla fluxo de compras/solicitações de bens com aprovações em cascata
- Integrado ao sistema legado KingHost (banco de dados remoto como source of truth)
- Hospedado em: KingHost (SSH: `plansul@ftp.plansul.info`, app em `~/www/estoque-laravel`)

---

## Estrutura principal

```
app/
  Http/Controllers/   → Controladores (roteamento + resposta HTTP)
  Services/           → Lógica de negócio (usar SEMPRE que possível)
  Models/             → Eloquent ORM
  Console/Commands/   → Artisan commands (import, sync, fix, one-offs)
  Http/Middleware/    → Autenticação, permissões, auto-sync, Power Automate
  Jobs/               → Filas assíncronas (e-mail de solicitações)
  Helpers/            → MenuHelper, funções utilitárias
config/
  telas.php           → MAPA DE TELAS/PERMISSÕES — ler antes de mexer em acesso
  novidades.php       → Novidades exibidas no popup ao usuário
  solicitacoes_bens.php → Configurações do fluxo de solicitações
routes/
  web.php             → TODAS as rotas — leia antes de criar nova rota
  auth.php            → Login, logout, reset de senha
database/
  migrations/         → Schema evolution (NÃO editar banco direto)
resources/views/
  components/         → Componentes Blade reutilizáveis (verificar antes de criar HTML)
  patrimonios/        → Telas de patrimônio
  solicitacoes/       → Telas de solicitações
  layouts/            → Layouts base
```

---

## Onde ficam os fluxos críticos

| Fluxo | Arquivo principal |
|---|---|
| Autenticação | `routes/auth.php` + `app/Http/Controllers/Auth/` |
| Controle de acesso por tela | `app/Http/Middleware/CheckTelaAccess.php` + `config/telas.php` |
| Listagem/busca de patrimônios | `app/Services/PatrimonioService.php` |
| CRUD de patrimônios | `app/Http/Controllers/PatrimonioController.php` |
| Fluxo de solicitações de bens | `app/Services/SolicitacaoBemFlowService.php` |
| Aprovações de solicitações | `app/Http/Controllers/SolicitacaoBemController.php` |
| Notificações por e-mail | `app/Services/SolicitacaoBemEmailService.php` + `app/Jobs/SendSolicitacaoBemCriadaEmailJob.php` |
| Sync com KingHost | `app/Console/Commands/SyncKinghostData.php` + middleware `AutoSyncKinghost` |
| Webhook Power Automate | `app/Http/Controllers/SolicitacaoEmailController.php` + middleware `VerifyPowerAutomateToken` |
| Termos de responsabilidade | `app/Http/Controllers/TermoResponsabilidadeController.php` + DOCX: `TermoDocxController` |

---

## Arquivos de entrada prioritários

Leia nesta ordem ao iniciar uma tarefa:

1. `routes/web.php` — entender rotas disponíveis e middlewares aplicados
2. `config/telas.php` — entender sistema de permissões (códigos 1000–1021)
3. `app/Services/PatrimonioService.php` — lógica de listagem/filtros de patrimônios
4. `app/Services/SolicitacaoBemFlowService.php` — lógica de fluxo de solicitações
5. `app/Models/Patrimonio.php` — campos, casts, relacionamentos
6. `app/Models/SolicitacaoBem.php` — constantes de status, campos do fluxo
7. `app/Models/User.php` — constantes de telas (TELA_*), perfis (ADM/USR/C)

---

## Estratégia de investigação

**Antes de abrir um arquivo de view/controller:**
1. Buscar a rota em `routes/web.php` → encontrar o controller+método
2. Verificar se existe Service para a lógica → `app/Services/`
3. Verificar se existe componente reutilizável → `resources/views/components/`
4. Ler o model para entender os campos → `app/Models/`

**Busca eficiente:**
- Para permissões: olhar `config/telas.php` + constantes em `User.php`
- Para campos do banco: olhar model (`$fillable`, `$casts`) + migrations
- Para fluxo de e-mail: `SolicitacaoBemEmailService` + `SendSolicitacaoBemCriadaEmailJob`
- Para sync KingHost: `app/Console/Commands/SyncKinghostData.php`

**NÃO explorar sem critério:**
- `vendor/` — não é código do projeto
- `public/build/` — assets compilados, modificar apenas via `resources/`
- `node_modules/` — dependências JS
- `storage/backups/` — backups de dados, não código

---

## Regras antes de editar código

1. **Verificar se existe Service** antes de colocar lógica em controller
2. **Verificar se existe componente Blade** antes de criar HTML inline repetido
3. **NUNCA editar banco diretamente** — criar migration
4. **NUNCA fazer `git push` sem autorização explícita** do mantenedor
5. **Criar backup** em `archive/backups/` antes de operações destrutivas
6. **Rodar `php -l arquivo.php`** após qualquer edição PHP
7. **Rodar `npm run build`** se alterar classes Tailwind ou arquivos em `resources/css/` ou `resources/js/`
8. **Todo CSS deve ser Tailwind** — PROIBIDO CSS customizado fora da arquitetura em `resources/css/`
9. **Cada cor Tailwind DEVE ter variante `dark:`** correspondente

---

## Convenções do projeto

- Idioma: **PT-BR** obrigatório em TODO texto visível ao usuário
- CSS: Tailwind exclusivamente, com dark mode em TODOS os elementos
- Logs em services: usar emojis padrão (`🚀 ➕ ✏️ 🗑️ ✅ ⚠️ ❌ 🔍 📊`)
- Scripts one-off: avisar no chat → executar → documentar → **remover** o script
- Perfis de usuário: `ADM` (admin), `USR` (usuário padrão), `C` (consultor)
- Permissões de tela: NUSEQTELA codes em `config/telas.php` e constantes `User::TELA_*`
- PHP 8.2+ obrigatório (`php82` no KingHost)
- Nomenclatura SQL: UPPERCASE (`NUPATRIMONIO`, `SITUACAO`, etc.)

---

## Documentos obrigatórios para consulta

| Documento | Quando ler |
|---|---|
| `docs/ARCHITECTURE.md` | Antes de criar módulo/feature nova |
| `docs/FILES_MAP.md` | Antes de mexer em arquivo crítico |
| `docs/FLOWS.md` | Antes de alterar fluxos de negócio |
| `docs/DEPENDENCIES.md` | Antes de atualizar dependências |
| `docs/TROUBLESHOOTING.md` | Quando algo quebrar |
| `docs/CONVENTIONS.md` | Antes de criar novo código |
| `.github/copilot-instructions.md` | Regras gerais operacionais do projeto |
| `AGENTS.md` | Regras de PT-BR e CSS |

---

## Como atualizar esta documentação

Após qualquer mudança significativa:
- Novo módulo → atualizar `ARCHITECTURE.md` + `FILES_MAP.md`
- Novo fluxo ou alteração de fluxo → atualizar `FLOWS.md`
- Nova dependência → atualizar `DEPENDENCIES.md`
- Problema novo encontrado → adicionar em `TROUBLESHOOTING.md`
- Toda mudança documentada → registrar entrada em `docs/CHANGELOG_AGENT.md`

---

## Checklist de segurança para manutenção

- [ ] Li `docs/FILES_MAP.md` e identifico o arquivo correto?
- [ ] Existe Service para esta lógica? Estou usando ele?
- [ ] Backup criado se alterar dados em massa?
- [ ] Migration criada se alterar schema?
- [ ] `php -l` passou em todos arquivos PHP editados?
- [ ] `npm run build` rodado se alterar Tailwind/CSS/JS?
- [ ] Testado em tema claro E escuro?
- [ ] Todo texto visível está em PT-BR com acentuação correta?
- [ ] `git push` só com autorização explícita do mantenedor?
- [ ] `CHANGELOG_AGENT.md` atualizado?
