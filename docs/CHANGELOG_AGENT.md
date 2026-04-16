# CHANGELOG_AGENT.md — Registro de Alterações Documentais

> Histórico de documentação e mudanças estruturais feitas por agentes.
> Atualizar a cada mudança significativa.

---

## [2026-04-15] — Documentação base implantada

**O que foi documentado:**
- `AGENT.md` — ponto central de navegação criado (arquivo novo na raiz)
- `docs/ARCHITECTURE.md` — mapa macro do sistema com todos os módulos identificados
- `docs/FILES_MAP.md` — mapa tático de arquivos críticos com responsabilidades e riscos
- `docs/FLOWS.md` — 10 fluxos reais documentados com sequências e dependências
- `docs/DEPENDENCIES.md` — dependências PHP, JS, integrações externas e pontos frágeis
- `docs/TROUBLESHOOTING.md` — 11 problemas comuns com diagnóstico e correção
- `docs/CONVENTIONS.md` — padrões obrigatórios de código, CSS, logging, deploy
- `docs/CHANGELOG_AGENT.md` — este arquivo

**Fontes analisadas:**
- `routes/web.php` — todas as rotas (~250+)
- `composer.json` + `package.json` — dependências
- `app/Http/Controllers/` — 27 controllers identificados
- `app/Services/` — 8 services principais
- `app/Models/` — 17 models
- `app/Http/Middleware/` — 11 middlewares
- `app/Console/Commands/` — 32 comandos Artisan
- `app/Jobs/` — 1 job identificado
- `database/migrations/` — 45 migrations (últimas de 2025-2026)
- `config/telas.php`, `config/novidades.php`, `config/solicitacoes_bens.php`
- `resources/views/components/` — 32 componentes Blade
- `.github/copilot-instructions.md` — instruções operacionais já existentes

**O que foi inferido (hipóteses não validadas):**
- `AlmoxarifadoController.php` — funcionalidade pouco ou não utilizada (pendente de validação)
- Situações de patrimônio (`EM_USO`, `DISPONIVEL`, etc.) — identificadas por dedução, confirmar em PatrimonioController
- Versão MySQL no KingHost — hipótese: 5.7/8.0
- Driver de fila em produção — hipótese: `sync` ou `database`

**Pendentes para próxima rodada:**
- Documentar `AlmoxarifadoController.php` e verificar se a tela está em uso
- Mapear views individuais por módulo (patrimonios/, solicitacoes/, etc.)
- Documentar a estrutura completa do `SolicitacaoBemController` (é o maior/mais complexo)
- Verificar e documentar o `app/Observers/` e `app/Policies/`
- Documentar `app/Helpers/MenuHelper.php` em detalhe
- Mapear os comandos deprecated em `app/Console/Commands/_deprecated/`
- Validar hipóteses marcadas acima

**Riscos identificados:**
- `SolicitacaoBemFlowService`: nomes/logins de usuários hard-coded em constantes — risco operacional quando colaboradores mudarem
- Sync KingHost via `exec(nohup)`: sem feedback de falha, monitoramento manual necessário
- Jobs de e-mail dependem da fila ser processada — verificar driver em produção
- Tabela `patr` com PK e campos UPPERCASE (legacy): cuidado ao adicionar joins e migrations

---

## Template para próximas entradas

```markdown
## [YYYY-MM-DD] — Descrição curta

**O que foi documentado:**
- Arquivo X: ...
- Arquivo Y: ...

**O que foi inferido:**
- Hipótese A: ... (Pendente de validação)

**Pendentes:**
- Item X para próxima rodada

**Riscos identificados:**
- Risco A: ...
```
