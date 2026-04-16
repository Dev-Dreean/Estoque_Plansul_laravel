# FLOWS.md — Fluxos Reais do Sistema

> Confirmado no código: 2026-04-15
> Fontes: routes/web.php, SolicitacaoBemFlowService, PatrimonioService, middlewares

---

## FLUXO 1 — Autenticação e Acesso

**Objetivo:** Autenticar usuário e garantir que tenha perfil e permissões corretos.

**Ponto de entrada:** `GET /login`

**Arquivos envolvidos:**
- `routes/auth.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Middleware/EnsureProfileIsComplete.php`
- `app/Http/Middleware/CheckTelaAccess.php`
- `app/Helpers/MenuHelper.php`
- `config/telas.php`
- `app/Models/User.php`
- `app/Models/AcessoUsuario.php`

**Sequência:**
```
1. POST /login → valida email+senha
2. Laravel cria sessão autenticada
3. Redireciona para /menu
4. EnsureProfileIsComplete verifica: matrícula preenchida + senha não expirada
   └─ Se incompleto → redireciona para /completar-perfil
5. /menu renderiza MenuController::index
6. MenuHelper::getTelasParaMenu() monta itens visíveis conforme acessousuario
7. Usuário clica em item → middleware tela.access:NNNN verifica AcessoUsuario.INACESSO='S'
   └─ ADM: acesso total
   └─ USR/C: somente telas liberadas
```

**Riscos comuns:**
- Usuário sem acessousuario vinculado → menu vazio
- `needs_identity_update=true` → pode forçar re-completar perfil
- Senha `must_change_password=true` → redireciona para troca

**Pontos de quebra:**
- `EnsureProfileIsComplete` com lógica incorreta pode bloquear usuários válidos
- Tabela `acessousuario` vazia para usuário → sem acesso a nada

---

## FLUXO 2 — Listagem e Busca de Patrimônios

**Objetivo:** Exibir lista paginada de patrimônios com filtros avançados.

**Ponto de entrada:** `GET /patrimonios`

**Arquivos envolvidos:**
- `app/Http/Controllers/PatrimonioController@index`
- `app/Services/PatrimonioService@listarParaIndex`
- `app/Services/PatrimonioService@montarConsultaFiltrada`
- `app/Models/Patrimonio.php`
- `resources/views/patrimonios/index.blade.php`
- `resources/views/components/patrimonio-table.blade.php`

**Sequência:**
```
1. GET /patrimonios (com parâmetros de filtro no query string)
2. Middleware tela.access:1000 → verifica permissão
3. PatrimonioController::index() → chama PatrimonioService::listarParaIndex()
4. PatrimonioService::montarConsultaFiltrada():
   - Aplica filtro de cadastradores (se não admin, pode filtrar por si mesmo)
   - Aplica filtros principais (projeto, local, situação, funcionário, UF, datas, etc.)
   - Aplica ordenação
5. Paginação (30 por página padrão, máx 500)
6. anexarLocaisCorretos() corrige locais via eager loading
7. detectarColunasVisiveis() determina quais colunas ter dados preenchidos
8. Retorna view com patrimonios paginados + colunas visíveis
```

**Filtros disponíveis (query string):**
- `projeto`, `local`, `situacao`, `funcionario`, `uf`, `verificado`
- `cadastrador`, `data_inicio`, `data_fim`, `busca` (texto livre)

**Riscos comuns:**
- Consulta sem índice pode ser lenta com 11k registros
- `detectarColunasVisiveis()` itera todos os itens da página — custo O(n)

---

## FLUXO 3 — Criação de Patrimônio

**Objetivo:** Registrar novo ativo físico.

**Ponto de entrada:** `GET /patrimonios/create` → `POST /patrimonios`

**Arquivos envolvidos:**
- `app/Http/Controllers/PatrimonioController@create`, `@store`
- `resources/views/patrimonios/create.blade.php`
- `resources/views/components/patrimonio-form.blade.php`
- `app/Models/Patrimonio.php`
- `app/Observers/` (se existir observer de criação)

**Sequência:**
```
1. Carrega formulário com dados de contexto (projetos, locais, funcionários, tipos)
2. Usuário preenche campos no patrimonio-form.blade.php (Alpine.js)
3. POST /patrimonios → PatrimonioController::store()
4. Validação de campos obrigatórios
5. Cria registro em tabela patr
6. Registra histórico de movimentação
7. Redireciona para show com mensagem de sucesso
```

**Riscos comuns:**
- `NUMMESA` único por patrimônio `EM_USO` — validação no backend
- Campos uppercase: valores não normalizados gerarão inconsistências

---

## FLUXO 4 — Solicitação de Bens: Criação

**Objetivo:** Iniciar pedido de compra/separação de item.

**Ponto de entrada:** `GET /solicitacoes-bens/create` → `POST /solicitacoes-bens`

**Arquivos envolvidos:**
- `app/Http/Controllers/SolicitacaoBemController@create`, `@store`
- `app/Services/SolicitacaoBemFlowService@normalizeFlow`
- `app/Services/SolicitacaoBemEmailService@agendarConfirmacaoCriacao`
- `app/Jobs/SendSolicitacaoBemCriadaEmailJob`
- `app/Models/SolicitacaoBem.php`
- `resources/views/solicitacoes/create.blade.php`

**Sequência:**
```
1. Usuário preenche solicitação (destino, local, itens, observação)
2. Sistema determina fluxo_responsavel com base no local selecionado:
   └─ Local com fluxo_responsavel='TI' → fluxo TI (Bruno)
   └─ Demais → fluxo padrão (Tiago/Beatriz)
3. POST → SolicitacaoBemController::store()
4. Cria SolicitacaoBem com status=PENDENTE
5. Cria SolicitacaoBemItens associados
6. SolicitacaoBemEmailService::agendarConfirmacaoCriacao()
   → Despacha SendSolicitacaoBemCriadaEmailJob na fila
7. Redireciona para index com mensagem de sucesso
```

**Integração Power Automate (alternativa):**
```
Power Automate → POST /api/solicitacoes/email (X-API-KEY)
→ VerifyPowerAutomateToken
→ SolicitacaoEmailController::store()
→ Mesmo fluxo de criação
```

---

## FLUXO 5 — Solicitação de Bens: Fluxo Padrão

**Objetivo:** Processar pedido pelo fluxo padrão (Tiago/Beatriz).

**Sequência de estados:**
```
PENDENTE
  → [triagem inicial - Tiago ou Beatriz - TELA:1019]
  AGUARDANDO_CONFIRMACAO
  → [encaminhamento para liberação]
  LIBERACAO
  → [confirmado com medidas/cotações]
  CONFIRMADO
  → [envio]
  NAO_ENVIADO ou RECEBIDO ou NAO_RECEBIDO
```

**Transições de estado → métodos do controller:**

| Ação | Método | Quem pode |
|---|---|---|
| Triagem inicial | `confirm()` | Tiago, Beatriz (tela 1019) |
| Encaminhar liberação | `forwardToLiberacao()` | Tiago, Beatriz |
| Liberar (com cotação) | `release()` | Beatriz ou admin (tela 1020) |
| Aprovar cotação | `approveQuote()` | Aprovador (tela 1014) |
| Enviar | `send()` | Liberador (tela 1020) |
| Receber | `receive()` | Recebedor |
| Não recebido | `notReceived()` | — |
| Cancelar | `cancel()` | Tela 1015 |

---

## FLUXO 6 — Solicitação de Bens: Fluxo TI

**Objetivo:** Processar pedido do estoque da TI pelo fluxo Bruno → Theo.

**Ativado quando:** `local_projeto.fluxo_responsavel = 'TI'`

**Sequência:**
```
PENDENTE
  → [triagem inicial - Bruno - TELA:1019]
  AGUARDANDO_CONFIRMACAO
  → [encaminhamento para liberação]
  LIBERACAO  
  → [medição/cotação por Bruno]
  CONFIRMADO (com cotação aprovada)
  → [autorização de liberação - Theo - TELA:1021]
  release_authorized_by + release_authorized_at preenchidos
  → [envio - Bruno - TELA:1020]
  NAO_ENVIADO ou RECEBIDO
```

**Diferença do fluxo padrão:** Etapa adicional de autorização por Theo antes do envio.

**Arquivos críticos:**
- `SolicitacaoBemFlowService::canAuthorizeRelease()` — quem pode autorizar
- `SolicitacaoBemFlowService::isBrunoFlowOperator()` — identifica Bruno
- Nomes hard-coded em constantes: `FLOW_BRUNO_LOGINS`, `FLOW_THEO_LOGINS`, etc.

---

## FLUXO 7 — Sync com KingHost

**Objetivo:** Manter dados locais atualizados com o banco de produção KingHost.

**Ponto de entrada (automático):** Middleware `AutoSyncKinghost` (a cada 8h)
**Ponto de entrada (manual):** `php82 artisan sync:kinghost-data`

**Arquivos envolvidos:**
- `app/Http/Middleware/AutoSyncKinghost.php`
- `app/Console/Commands/SyncKinghostData.php`
- `storage/app/sync-kinghost.lock` (timestamp da última execução)
- `storage/logs/sync-kinghost.log`

**Sequência:**
```
1. AutoSyncKinghost verifica timestamp em sync-kinghost.lock
2. Se > 8h desde última execução:
   └─ Atualiza timestamp
   └─ exec("nohup php82 artisan sync:kinghost-data >> log 2>&1 &")
3. SyncKinghostData conecta via SSH: plansul@ftp.plansul.info
4. Executa queries MySQL no banco remoto
5. Faz upsert/insert nas tabelas locais:
   └─ funcionarios (~5.227)
   └─ tabfant (~877)
   └─ locais_projeto (~1.939)
6. Registra log em storage/logs/sync-kinghost.log
```

**Riscos comuns:**
- SSH bloqueado → sync falha silenciosamente (middleware não bloqueia requisição)
- Dados divergentes → verificar contagens em auditoria (docs/ARCHITECTURE.md §14.5)
- `php82` não disponível no servidor → usar caminho absoluto

---

## FLUXO 8 — Termos de Responsabilidade

**Objetivo:** Gerar documento formal atribuindo patrimônio a funcionário.

**Ponto de entrada:** View de patrimônio → botão "Gerar Termo"

**Arquivos envolvidos:**
- `app/Http/Controllers/TermoResponsabilidadeController.php`
- `app/Http/Controllers/TermoDocxController.php`
- `app/Models/TermoCodigo.php`
- `app/Models/TermoResponsabilidadeArquivo.php`
- `vendor/phpoffice/phpword` (DOCX)
- `vendor/barryvdh/laravel-dompdf` (PDF)

**Sequência:**
```
1. Usuário seleciona patrimônios na tela de atribuição
2. GET /patrimonios/atribuir/termo → formulário de dados do termo
3. POST → TermoResponsabilidadeController::store()
4. Cria TermoCodigo e TermoResponsabilidadeArquivo
5. Gera DOCX via phpword OU PDF via dompdf
6. Armazena em storage e oferece download
```

---

## FLUXO 9 — Controle de Acesso por Tela

**Objetivo:** Restringir acesso a módulos conforme perfil do usuário.

**Ponto de entrada:** Qualquer rota com `->middleware('tela.access:NNNN')`

**Sequência:**
```
1. Request chega com auth session válida
2. CheckTelaAccess::handle($request, $next, $nuseqtela)
3. Se user->isAdmin() → acesso total
4. Se não: user->temAcessoTela($nuseqtela)
   └─ Consulta AcessoUsuario where NUSEQTELA=$nuseqtela AND INACESSO='S'
5. Não tem acesso:
   └─ JSON request → 403 JSON
   └─ HTML request → view errors/403
6. Tem acesso → $next($request)
```

**Configuração de telas:** `config/telas.php`
**Constantes de tela:** `User::TELA_*` em `app/Models/User.php`

---

## FLUXO 10 — Importação em Massa via Planilha

**Objetivo:** Atualizar campos de múltiplos patrimônios via upload de arquivo.

**Ponto de entrada:** `POST /patrimonios/bulk-update/import`

**Arquivos envolvidos:**
- `app/Http/Controllers/PatrimonioBulkController.php`
- `vendor/spatie/simple-excel`
- Template: `GET /patrimonios/bulk-update/template/{tipo}`

**Sequência:**
```
1. Usuário faz download do template CSV/XLSX
2. Preenche campos desejados
3. POST upload → PatrimonioBulkController::import()
4. Lê arquivo com spatie/simple-excel
5. Para cada linha: localiza patrimônio por NUPATRIMONIO
6. Atualiza campos permitidos
7. Gera log de resultado (sucesso/erro por linha)
8. Retorna JSON com resumo
```
