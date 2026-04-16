# FILES_MAP.md — Mapa Tático de Arquivos

> Apenas arquivos com valor operacional real.
> Confirmado no código: 2026-04-15

---

## ROTAS

- `routes/web.php`
  - Responsabilidade: Todas as rotas HTTP da aplicação (~250+ rotas)
  - Quando editar: Ao criar, renomear ou remover rota
  - Riscos: Colisão de rotas, ordem importa para rotas ambíguas (`/api/locais/debug` vs `/api/locais/{id}`)
  - Relacionado a: Todos os controllers

- `routes/auth.php`
  - Responsabilidade: Login, logout, registro, reset de senha (Laravel Breeze)
  - Quando editar: Raramente — apenas se mudar fluxo de autenticação
  - Riscos: Quebrar login/logout de todos os usuários
  - Relacionado a: `app/Http/Controllers/Auth/`

---

## CONTROLLERS

- `app/Http/Controllers/PatrimonioController.php`
  - Responsabilidade: CRUD de patrimônios, APIs de busca/filtro/autocomplete, bulk operations
  - Quando editar: Ao alterar listagem, filtros, criação/edição, APIs de patrimônio
  - Riscos: Controller de alto impacto (rota resource), quebrar a tela principal do sistema
  - Relacionado a: `PatrimonioService`, `PatrimonioBulkController`, Model `Patrimonio`

- `app/Http/Controllers/SolicitacaoBemController.php`
  - Responsabilidade: CRUD + todas as transições de estado do fluxo de solicitações
  - Quando editar: Ao alterar fluxo de aprovação, novos estados, permissões
  - Riscos: Fluxo complexo com estados/transições; erros podem travar solicitações
  - Relacionado a: `SolicitacaoBemFlowService`, `SolicitacaoBemEmailService`, Model `SolicitacaoBem`

- `app/Http/Controllers/UserController.php`
  - Responsabilidade: CRUD de usuários, impersonation, reset de senha, ACL
  - Quando editar: Ao alterar gestão de usuários ou permissões
  - Riscos: Erros podem bloquear acesso de usuários
  - Relacionado a: Model `User`, `AcessoUsuario`, `config/telas.php`

- `app/Http/Controllers/ProjetoController.php`
  - Responsabilidade: CRUD de projetos e locais, APIs de lookup
  - Quando editar: Ao alterar locais, projetos, vínculo local↔projeto
  - Riscos: `locais_projeto` referenciada em patrimônios e solicitações
  - Relacionado a: Models `Tabfant`, `LocalProjeto`

- `app/Http/Controllers/GestaoColaboradoresController.php`
  - Responsabilidade: Listagem e gestão de funcionários, verificação de matrícula
  - Quando editar: Ao alterar tela de colaboradores
  - Riscos: Baixo (tela auxiliar)
  - Relacionado a: Model `Funcionario`, `UserController`

- `app/Http/Controllers/TermoResponsabilidadeController.php`
  - Responsabilidade: Geração e gestão de termos de responsabilidade de patrimônios
  - Quando editar: Ao alterar fluxo de termos
  - Riscos: Médio — relacionado a patrimônios atribuídos
  - Relacionado a: `TermoDocxController`, Models `TermoCodigo`, `TermoResponsabilidadeArquivo`

- `app/Http/Controllers/DashboardController.php`
  - Responsabilidade: Gráficos e indicadores (via AJAX)
  - Quando editar: Ao alterar widgets ou métricas do dashboard
  - Riscos: Baixo
  - Relacionado a: Model `Patrimonio`, `Funcionario`

- `app/Http/Controllers/SolicitacaoEmailController.php`
  - Responsabilidade: Endpoint de criação de solicitação via Power Automate
  - Quando editar: Ao alterar integração com Power Automate
  - Riscos: Integração externa, erros quebram criação via e-mail
  - Relacionado a: `VerifyPowerAutomateToken`, `SolicitacaoBemEmailService`

- `app/Http/Controllers/SyncRemoteController.php`
  - Responsabilidade: Endpoint de sync remoto de tabelas via Power Automate
  - Quando editar: Ao alterar sync via webhook
  - Riscos: Médio — pode sobrescrever dados locais
  - Relacionado a: `VerifyPowerAutomateToken`, Command `SyncRemoteTables`

---

## SERVICES

- `app/Services/PatrimonioService.php`
  - Responsabilidade: Listagem, filtros, consultas de patrimônios; lógica de colunas visíveis
  - Quando editar: Ao alterar busca, ordenação, filtros de patrimônios
  - Riscos: Afeta performance da tela principal (11k+ registros)
  - Relacionado a: `PatrimonioController`, Model `Patrimonio`

- `app/Services/SolicitacaoBemFlowService.php`
  - Responsabilidade: Toda lógica de permissão e fluxo de solicitações (quem pode aprovar, confirmar, etc.)
  - Quando editar: Ao adicionar aprovadores, mudar fluxo TI/padrão
  - Riscos: ALTO — afeta todos os estados de solicitações. Nomes de usuários hard-coded.
  - Relacionado a: `SolicitacaoBemController`, Model `SolicitacaoBem`

- `app/Services/SolicitacaoBemEmailService.php`
  - Responsabilidade: Agendamento e envio de e-mails de notificação de solicitações
  - Quando editar: Ao alterar destinatários ou eventos de notificação
  - Riscos: Médio — erros podem silenciar notificações sem quebrar o fluxo
  - Relacionado a: `SendSolicitacaoBemCriadaEmailJob`, `SolicitacaoBemController`

- `app/Services/SolicitacaoBemPendenciaService.php`
  - Responsabilidade: Verificação de pendências (solicitações não resolvidas)
  - Quando editar: Ao alterar lógica de badge/aviso de pendências
  - Relacionado a: `SolicitacaoBemController`, badge no menu

- `app/Services/FilterService.php`
  - Responsabilidade: Filtros reusáveis para consultas
  - Relacionado a: `PatrimonioService`

- `app/Services/SearchCacheService.php`
  - Responsabilidade: Cache de resultados de busca de funcionários
  - Quando editar: Ao alterar performance de autocomplete
  - Relacionado a: `FuncionarioController`

- `app/Services/SystemNewsService.php`
  - Responsabilidade: Controla exibição das novidades do popup
  - Relacionado a: `config/novidades.php`, `SystemNewsController`

---

## MODELS

- `app/Models/Patrimonio.php` (tabela: `patr`)
  - Responsabilidade: Ativo físico. Campos UPPERCASE (legado KingHost).
  - Relacionado a: `patr`, `locais_projeto`, `tabfant`, `objetopatr`, `funcionarios`, `usuario`

- `app/Models/SolicitacaoBem.php` (tabela: `solicitacoes_bens`)
  - Responsabilidade: Solicitação de compra/separação. Constantes de STATUS_* críticas.
  - Relacionado a: `solicitacoes_bens`, `solicitacao_bens_itens`, `solicitacoes_bens_status_historico`

- `app/Models/User.php` (tabela: `usuario`)
  - Responsabilidade: Autenticação + controle de acesso. Constantes TELA_*.
  - Relacionado a: `usuario`, `acessousuario`

- `app/Models/LocalProjeto.php` (tabela: `locais_projeto`)
  - Responsabilidade: Local interno de um projeto. Campo `fluxo_responsavel` define fluxo TI.
  - Relacionado a: `tabfant`, `patr`, `solicitacoes_bens`

- `app/Models/Tabfant.php` (tabela: `tabfant`)
  - Responsabilidade: Projetos/filiais. `CDPROJETO` é identificador string.
  - Relacionado a: `locais_projeto`

- `app/Models/Funcionario.php` (tabela: `funcionarios`)
  - Responsabilidade: Funcionários sincronizados do KingHost.
  - Relacionado a: `patr` (via `CDMATRFUNCIONARIO`), `usuario`

- `app/Models/ObjetoPatr.php` (tabela: `objetopatr`)
  - Responsabilidade: Tipos de objetos/códigos de patrimônio (~1.208 registros).
  - Relacionado a: `patr` (via `CODOBJETO`)

- `app/Models/HistoricoMovimentacao.php`
  - Responsabilidade: Log de movimentações de patrimônios.
  - Relacionado a: `patr`, `funcionarios`

---

## MIDDLEWARES

- `app/Http/Middleware/CheckTelaAccess.php`
  - Responsabilidade: Valida acesso à tela pelo código NUSEQTELA.
  - Quando editar: Ao mudar lógica de permissão central.
  - Riscos: ALTO — afeta acesso de todos os usuários não-ADM.

- `app/Http/Middleware/EnsureProfileIsComplete.php`
  - Responsabilidade: Redireciona para completar perfil se faltarem dados do usuário.
  - Quando editar: Se campos obrigatórios do perfil mudarem.
  - Riscos: Médio — pode bloquear usuários recém-criados legitimamente.

- `app/Http/Middleware/AutoSyncKinghost.php`
  - Responsabilidade: Dispara sync do KingHost a cada 8h em background (poor man's cron).
  - Quando editar: Se quiser mudar o intervalo ou desligar o sync automático.
  - Riscos: Baixo — falha silenciosa, não bloqueia requisição.

- `app/Http/Middleware/VerifyPowerAutomateToken.php`
  - Responsabilidade: Valida `X-API-KEY` para endpoints do Power Automate.
  - Quando editar: Se mudar integração com Power Automate.
  - Riscos: Crítico — erro aqui bloqueia todas as entradas via webhook.

- `app/Http/Middleware/AdminMiddleware.php`
  - Responsabilidade: Restringe rota a perfil ADM.
  - Riscos: Baixo se bem aplicado.

---

## ARTISAN COMMANDS

- `app/Console/Commands/SyncKinghostData.php`
  - Responsabilidade: Script principal de sync (funcionários, projetos, locais) com KingHost via SSH.
  - Quando usar: Mensal ou quando dados desatualizados.
  - Riscos: ALTO — pode sobrescrever dados locais. Sempre validar com `--dry-run` se disponível.

- `app/Console/Commands/ImportarTodosPatrimonios.php`
  - Responsabilidade: Importação em massa de patrimônios do KingHost.
  - Quando usar: Restauração de emergência ou carga inicial.
  - Riscos: ALTO — operação destrutiva/massiva.

- `app/Console/Commands/BulkUpdatePatrimonios.php`
  - Responsabilidade: Atualização em massa de campos específicos.
  - Riscos: Médio-alto — operar com `--dry-run` primeiro.

- `app/Console/Commands/UnifyDuplicateUsers.php`
  - Responsabilidade: Unificar usuários duplicados no banco.
  - Quando usar: Sob demanda, com backup prévio.
  - Riscos: ALTO — irreversível sem backup.

- `app/Console/Commands/SyncTelasKinghost.php`
  - Responsabilidade: Sync das telas/permissões com KingHost.
  - Riscos: Médio — pode afetar permissões de usuários.

---

## CONFIGURAÇÕES

- `config/telas.php`
  - Responsabilidade: Mapa de telas do sistema (código → nome, rota, ícone, cor).
  - Quando editar: Ao criar nova tela ou renomear/reordenar.
  - Riscos: Afeta exibição do menu e validação de permissões.

- `config/novidades.php`
  - Responsabilidade: Lista de novidades para o popup do sistema.
  - Quando editar: A cada feature nova entregue ao usuário.
  - Riscos: Baixo.

- `config/solicitacoes_bens.php`
  - Responsabilidade: Config de Power Automate token, notificações, queue, e-mails.
  - Quando editar: Ao alterar integração com PA ou configuração de filas.
  - Riscos: Médio — token errado bloqueia integração.

- `config/historico.php`
  - Responsabilidade: Config do módulo de histórico.

---

## COMPONENTES BLADE

- `resources/views/components/patrimonio-form.blade.php`
  - Responsabilidade: Formulário unificado de patrimônio (criar/editar).
  - Quando editar: Ao adicionar novo campo de patrimônio.
  - Riscos: Componente central — testado em claro/escuro após mudanças.

- `resources/views/components/status-badge.blade.php`
  - Responsabilidade: Badge de status com cores automáticas.
  - Uso em: Listagens de patrimônio e solicitações.

- `resources/views/components/action-button.blade.php`
  - Responsabilidade: Botão de ação (edit, delete, view, add, export).
  - Uso em: Tabelas em geral.

- `resources/views/components/patrimonio-table.blade.php`
  - Responsabilidade: Tabela de patrimônios com colunas dinâmicas.
  - Quando editar: Ao adicionar/remover colunas da listagem.

- `resources/views/components/navigation-menu.blade.php`
  - Responsabilidade: Menu principal de navegação.
  - Quando editar: Ao adicionar item de menu ou mudar navegação.
  - Riscos: Alto impacto visual em todas as telas.

- `resources/views/components/employee-autocomplete.blade.php`
  - Responsabilidade: Autocomplete de funcionários (Alpine.js).
  - Quando editar: Ao alterar busca de funcionários.

---

## JOBS / QUEUE

- `app/Jobs/SendSolicitacaoBemCriadaEmailJob.php`
  - Responsabilidade: Envio assíncrono de e-mail sobre eventos de solicitação.
  - Quando editar: Ao adicionar eventos ou mudar destinatários.
  - Riscos: Se a fila não processar, e-mails não chegam. Verificar `queue.failed_jobs`.

---

## BANCO — MIGRATIONS CRÍTICAS

- `2026_01_09_100000_create_solicitacoes_bens_table.php` — criação do módulo de solicitações
- `2026_03_30_180500_add_ti_flow_fields_to_locais_and_solicitacoes.php` — fluxo TI
- `2026_04_07_120000_add_theo_release_authorization_fields_to_solicitacoes_bens.php` — autorização Theo
- `2026_04_15_090000_add_num_mesa_to_patr_table.php` — campo NUMMESA em patrimônios

---

## ARQUIVOS A NÃO MEXER SEM CRITÉRIO

- `public/build/` — assets compilados (regenerar via `npm run build`)
- `vendor/` — dependências Composer
- `storage/backups/` — backups de dados do KingHost
- `archive/backups/` — backups históricos de operações
- `app/Console/Commands/_deprecated/` — comandos obsoletos (não executar)
