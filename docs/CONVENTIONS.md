# CONVENTIONS.md — Padrões do Projeto

> Última atualização: 2026-04-15
> Baseado em: copilot-instructions.md, AGENTS.md, análise do código existente

---

## 1. Idioma e Texto Visível

- **Todo texto visível ao usuário: PT-BR com acentuação correta**
- Proibido: `Solicitacao`, `informacoes`, `nao` — usar sempre: `Solicitação`, `informações`, `não`
- Vale para: Blade views, mensagens de validação, JSON de resposta, e-mails, botões, tooltips, placeholders
- Antes de concluir qualquer tarefa com texto: revisar palavras comuns → `não`, `informações`, `solicitação`, `medição`, `cotação`, `código`, `descrição`
- Validação: `composer text:check` (obrigatório antes de deploy)

---

## 2. CSS e Estilo

- **Apenas Tailwind CSS** — proibido CSS customizado fora de `resources/css/`
- Proibido: `style=""` para cor, borda, padding, background estáticos
- Permitido: `style=""` apenas para valores computados em runtime
- Cada cor Tailwind **DEVE** ter variante `dark:` correspondente

**Padrão obrigatório:**
```html
<!-- CORRETO -->
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">

<!-- ERRADO -->
<div class="bg-white text-gray-900">   <!-- sem dark mode -->
<div style="background: white;">       <!-- style inline proibido -->
```

**Tokens de cor padrão:**
- Background: `bg-white dark:bg-gray-800`
- Texto: `text-gray-900 dark:text-gray-100`
- Borda: `border-gray-200 dark:border-gray-700`
- Input: `border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200`
- Hover: `hover:bg-gray-100 dark:hover:bg-gray-700`

**Arquitetura CSS:**
```
resources/css/
  app.css              → entrypoint único
  foundation/          → tokens, temas, base
  components/          → botões, campos, tabelas, painéis, modais
  screens/             → regras específicas por tela
  legacy/compat.css    → overrides legados temporários
```

**Regra de build:**
- Alteração só em Blade sem nova classe Tailwind → sem `npm run build`
- Alteração em `resources/css/` ou `resources/js/` → **obrigatório `npm run build`**
- Nova classe Tailwind em Blade → **obrigatório `npm run build`**

---

## 3. Nomenclatura de Arquivos e Classes

**Controllers:**
- Formato: `PascalCase` + sufixo `Controller`
- Exemplo: `PatrimonioController`, `SolicitacaoBemController`
- Localização: `app/Http/Controllers/`

**Services:**
- Formato: `PascalCase` + sufixo `Service`
- Exemplo: `PatrimonioService`, `SolicitacaoBemFlowService`
- Localização: `app/Services/`

**Models:**
- Formato: `PascalCase` singular
- Exemplo: `Patrimonio`, `SolicitacaoBem`, `LocalProjeto`
- Localização: `app/Models/`

**Migrations:**
- Formato: `YYYY_MM_DD_HHMMSS_acao_em_tabela.php`
- Exemplo: `2026_04_15_090000_add_num_mesa_to_patr_table.php`

**Componentes Blade:**
- Formato: `kebab-case.blade.php`
- Exemplo: `patrimonio-form.blade.php`, `status-badge.blade.php`
- Localização: `resources/views/components/`

**Colunas de banco (tabelas legadas):**
- Formato: UPPERCASE
- Exemplo: `NUPATRIMONIO`, `SITUACAO`, `CDMATRFUNCIONARIO`

**Colunas de banco (tabelas novas):**
- Formato: `snake_case`
- Exemplo: `solicitante_id`, `fluxo_responsavel`, `created_at`

---

## 4. Service Layer

- **Controllers fazem:** roteamento, validação de request, resposta HTTP
- **Services fazem:** lógica de negócio, consultas complexas, transações
- **NUNCA** colocar query complexa direto no controller se o service existe
- Services disponíveis → verificar `app/Services/` antes de criar novo
- Novo service: criar PHPDoc com propósito + exemplo de uso

---

## 5. Logging em Services

Usar `Log::info/warning/error` com emojis padrão:

```php
Log::info('🚀 [CONTEXTO] Inicializando', ['param' => $valor]);
Log::info('📋 [CONTEXTO] Listagem iniciada', ['user' => $user->NMLOGIN]);
Log::info('➕ [CONTEXTO] Criando registro', ['dados' => $dados]);
Log::info('✏️ [CONTEXTO] Atualizando registro', ['id' => $id]);
Log::info('🗑️ [CONTEXTO] Deletando registro', ['id' => $id]);
Log::info('✅ [CONTEXTO] Operação concluída', ['total' => $count]);
Log::warning('⚠️ [CONTEXTO] Aviso', ['detalhe' => $msg]);
Log::error('❌ [CONTEXTO] Erro', ['erro' => $e->getMessage()]);
Log::info('🔍 [CONTEXTO] Busca', ['termo' => $termo]);
Log::info('📊 [CONTEXTO] Estatísticas', ['dados' => $stats]);
```

Formato de contexto: `[NOME_SERVICO]` em maiúsculas, ex: `[PatrimonioService]`, `[SOLICITACOES_EMAIL]`

---

## 6. Tratamento de Erros

- Validação de input: `$request->validate([...])`  no controller
- Erros de negócio: usar exceções customizadas ou retornar JSON com status adequado
- Erros de produção: `storage/logs/laravel.log` centraliza erros do Laravel
- Em jobs: falha vai para `failed_jobs`, não quebra silenciosamente

---

## 7. Componentes Blade — Regra de Criação

**Antes de criar HTML repetido:** verificar `resources/views/components/`.

Componentes existentes:
- `<x-action-button>` — botões de ação (edit, delete, view, add, export)
- `<x-status-badge>` — badge de status com cores automáticas
- `<x-table-header>` — cabeçalho de tabela com ordenação
- `<x-patrimonio-form>` — formulário completo de patrimônio
- `<x-employee-autocomplete>` — autocomplete de funcionários

Documentação inline obrigatória no topo do componente:
```blade
{{--
  Componente: nome-componente
  @props: prop1 (tipo), prop2 (tipo)
  Exemplo: <x-nome-componente :prop1="$val" prop2="texto" />
--}}
```

---

## 8. JavaScript

- Reatividade: Alpine.js (já carregado globalmente)
- Módulos JS: `public/js/` com padrão IIFE
- Módulo disponível: `PatrimonioActions` (CRUD de patrimônios)
- Evitar JavaScript inline em views; usar `data-attributes` para vincular ações

---

## 9. Banco de Dados

- **NUNCA** editar banco diretamente — criar migration
- Migrations em `database/migrations/` com nomenclatura temporal
- Novas colunas em tabelas críticas (`patr`, `usuario`): SEMPRE `nullable()` + `comment()`
- Campos booleanos em tabelas legadas: `CHAR(1)` com valores `'S'`/`'N'` (padrão KingHost)
- Colunas novas em tabelas novas: `snake_case`; em tabelas legadas: UPPERCASE

---

## 10. Segurança

- Autenticação: Laravel session (nunca expor tokens de sessão)
- API endpoints públicos (Power Automate): proteger com `VerifyPowerAutomateToken` (`X-API-KEY`)
- Permissões por tela: usar middleware `tela.access:NNNN` nas rotas
- Nunca expor credenciais de banco nos logs
- Validar TODOS os inputs via `$request->validate()` antes de persistir
- Impersonation restrita a ADM ou ambiente local (verificar no controller)
- Rotas de debug (`/debug-acessos`): proteger com `app()->environment('local')` ou `isAdmin()`

---

## 11. Deploy e Versionamento

- `git push` apenas com autorização explícita do mantenedor
- `git commit` local pode ser feito conforme necessário
- Antes de push: rodar `composer text:check` e `npm run build` se necessário
- Deploy no KingHost: `git pull` + `php82 artisan config:clear` + `php82 artisan view:clear`
- Backup antes de operações destrutivas: `archive/backups/pre_action_<YYYY-MM-DD_HHMM>.zip`

---

## 12. Scripts One-Off

Scripts temporários para diagnóstico/correção pontual:

1. Marcar como `// one-off` na primeira linha do arquivo
2. Avisar no chat que é one-off antes de executar
3. Preferir `--dry-run` na primeira execução
4. Gerar log em `storage/logs/`
5. Documentar o que foi feito
6. **Remover o script** após uso (exceto se mantenedor pedir preservação)

---

## 13. Novidades do Sistema

Toda feature nova entregue ao usuário final **DEVE** ter entrada em `config/novidades.php`:

```php
[
    'key' => 'YYYY-MM-DD-descricao-curta',   // único, kebab-case
    'title' => 'Título da novidade',
    'summary' => 'Resumo em texto plano',
    'summary_html' => 'Resumo com highlights HTML',
    'highlight' => 'Como usar na prática',
    'details' => ['detalhe 1', 'detalhe 2'],
    'released_at' => 'YYYY-MM-DD 00:00:00',
    'active' => true,
]
```

Critério: feature nova, melhoria relevante de fluxo ou mudança de comportamento perceptível pelo usuário.
NÃO registrar: refatorações internas, ajustes de performance, correções de bug silenciosas.
