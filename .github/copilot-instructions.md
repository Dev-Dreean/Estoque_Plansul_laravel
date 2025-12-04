# Copilot / AI agent instructions for Estoque_Plansul_laravel

Resumo rápido
- Projeto: aplicação Laravel de gerenciamento de patrimônio (backend em PHP + Blade views + scripts auxiliares).
- Objetivo deste arquivo: dar instruções concisas e específicas para agentes de código (Copilot/AI) serem imediatamente produtivos.

Arquitetura e fluxo principal
- Backend Laravel clássico: rotas em `routes/` → controllers em `app/Http/Controllers/` → modelos em `app/Models/` → views em `resources/views/`.
- Importação e manutenção de dados: existem comandos Artisan em `app/Console/Commands/` (ex.: `ImportarTodosPatrimonios.php`, `ImportKinghost*`), e arquivos de dados em `database/seeders/data/`.
- Backups e dados históricos: cópias JSON/DTs e backups ficam em `storage/backups/` e `archive/backups/` — operações destrutivas devem sempre preservar esses caminhos.

Padrões e convenções do projeto
- Scripts utilitários e one-offs ficam em `scripts/` (nem sempre parte do deploy). Antes de alterar/excluir, crie backup em `archive/backups/`.
- Arquivos de seeders de dados: `database/seeders/data/*` contêm arquivos TXT/JSON usados por seeders; evite removê-los sem confirmar.
- Migrations usam nomeação temporal (ex.: `YYYY_MM_DD_*`) na pasta `database/migrations/` — mudanças de schema devem ser feitas via migrations, não edits diretos no DB.

Fluxo de desenvolvimento e comandos úteis
- Instalação das dependências PHP/JS:
  - `composer install`
  - `npm install`
  - `npm run build` (ou `npm run dev` para desenvolvimento com Vite)
- Rodar servidor local: `php artisan serve` — verifique `.env` e `DB_*` antes.
- Comandos úteis do projeto (exemplos):
  - `php artisan migrate --seed` — aplicar migrations e seeders (use com cuidado em produção)
  - `php artisan <command>` — ver `app/Console/Commands/` para comandos customizados (importação, verificação, limpeza)
  - `php -l <file.php>` — verificação rápida de sintaxe em PHP
  - `composer dump-autoload` — atualizar autoloader após mover/renomear classes

Padrões de código e pontos de atenção
- Controllers são grandes e contêm lógica de listagem/pesquisa; ao alterar consultas, verifique uso de índices em `database/migrations/` e caches (`app/Services/` contém serviços de busca/otimização).
- Filtragem de listagens (ex.: `PatrimonioController`) usa parâmetros de request; preserve compatibilidade com front-end (Blade + pequenos scripts JS em `resources/js/`).
- Evitar alterar arquivos em `public/build/` — são assets compilados; modifique fontes em `resources/` e recompile.

Integrações e pontos externos
- Banco: MySQL/Postgres via configuração em `config/database.php` e `.env`.
- Export/import com Kinghost/terceiros: há scripts e comandos específicos (procure `Kinghost` em `app/Console/Commands/` e `scripts/`).
- Backups automáticos e exportações são colocados em `storage/backups/kinghost/...` — não limpar sem backup central.

Como o agente deve agir (regras operacionais)
1. Antes de qualquer remoção/movimentação em massa: avisar no chat e criar backup ZIP em `archive/backups/` contendo os itens a remover.
2. Evitar criar `.md` automaticamente — caso de documentação ser necessária, usar `.txt` (o repositório do mantenedor prefere `.txt`).
3. Não executar `git push` sem autorização explícita; `git commit` local pode ser feito se solicitado.
4. Preferir aplicar mudanças pequenas e testáveis (ex.: mover um script e rodar `php -l`), reportar resultados imediatos.

Arquivos/chaves para inspeção rápida
- `app/Console/Commands/ImportarTodosPatrimonios.php` — lógica de importação em lote
- `app/Http/Controllers/PatrimonioController.php` — pesquisa, filtros e paginação de patrimônios
- `database/seeders/data/` — arquivos TXT/JSON usados em seeders
- `storage/backups/` e `archive/backups/` — backups e exportações
- `resources/views/patrimonios/index.blade.php` — exemplo de filtro multi-select do frontend

Exemplos de tarefas e como abordá-las
- Atualizar filtro que retorna por data mais recente:
  1) localizar `PatrimonioController::getPatrimoniosQuery`
  2) adaptar `orderBy('DTOPERACAO', 'desc')` e testar com dabatase local
  3) rodar `php -l` e compartilhar resultado
- Remover arquivos não essenciais:
  1) listar arquivos candidatos
  2) criar `archive/backups/pre_cleanup_<timestamp>.zip` com os candidatos
  3) remover e reportar resultados (paths removidos e backup criado)

Perguntas frequentes para o mantenedor
- Preferência de formato de documentação: `.txt` em vez de `.md`? (respeitar explicitamente)
- Procedimento para `git push` automático ou preferem revisão manual?

Fim — peça feedback se algum tópico está incompleto ou se quer que eu adicione exemplos de comandos específicos do ambiente de deploy.

*** Arquivo gerado automaticamente: 2025-12-04 ***

---

ADENDO: instruções operacionais obrigatórias (integrado do `ASSISTANT_INSTRUCTIONS.txt`)

- Não crie múltiplos `.md` automaticamente. Preferir `.txt` para documentação gerada pelo assistente.
- Antes de qualquer operação que modifique ou exclua arquivos em massa: 1) aviso curto no chat; 2) listar os arquivos; 3) criar backup em `archive/backups/pre_action_<YYYY-MM-DD_HHMM>.zip`.
- Formato de respostas do agente neste repositório:
  - 1 frase inicial (o que farei);
  - bullets com ações realizadas e arquivos afetados;
  - opções claras de próximo passo (commit/push/restore).
- Operações git: nunca executar `git push` sem autorização explícita; commits locais podem ser feitos se solicitado e descritos.
- Quando mover/renomear classes, rodar `composer dump-autoload`.
- Validar PHP com `php -l` após mover arquivos PHP.

Se for necessário alterar este guia, peça aqui no chat. O assistente atualizará este arquivo (ou criará/atualizará um `.txt`) conforme sua orientação.
