# Copilot / AI agent instructions for Estoque_Plansul_laravel

Resumo rápido
- Projeto: aplicação Laravel de gerenciamento de patrimônio (backend em PHP + Blade views + scripts auxiliares).
- Objetivo: instruções concisas e acionáveis para agentes de código serem imediatamente produtivos neste repositório.

1) Arquitetura e fluxo (alto nível)
- Laravel clássico: `routes/` → `app/Http/Controllers/` → `app/Models/` → `resources/views/`.
- Tarefas em lote e importações: `app/Console/Commands/` e `scripts/`.
- Backups/data exports: `storage/backups/` e `archive/backups/`.

2) Comandos e verificações rápidas
- Instalação: `composer install`, `npm install`, `npm run build` (ou `npm run dev`).
- Dev server: `php artisan serve` (verifique `.env`).
- Lint PHP: `php -l <file.php>`; atualizar autoload: `composer dump-autoload`.

3) Regras operacionais obrigatórias
- Backups: sempre criar `archive/backups/pre_action_<YYYY-MM-DD_HHMM>.zip` antes de alterações em massa.
- Git: nunca execute `git push` sem autorização explícita; commits locais podem ser feitos se solicitado.
- Documentação gerada: NÃO criar múltiplos `.md` automaticamente; use `.txt` para documentação auxiliar. Esta exceção é o arquivo `.github/copilot-instructions.md`.

4) Scripts one-off (diagnóstico/execução pontual)
- Marcar scripts temporários como `one-off` (comentário na primeira linha).
- Procedimento: avisar no chat que o script é one-off → executar (preferir `--dry-run`) → gerar LOG → documentar o que foi feito → REMOVER o script automaticamente após uso, a menos que mantenedor peça preservação.

5) Logs e rastreabilidade (obrigatório)
- Todo script que modifique dados ou importe/exporte deve gerar logs em `storage/logs/` (ou `storage/app/logs/`).
- Formato mínimo de log: `[YYYY-MM-DD HH:MM:SS] LEVEL contexto: mensagem` (ex.: `[2025-12-04 15:04:05] INFO import_patr: processou 123 registros; erros=2`).
- Oferecer flags `--dry-run` e `--log-path` quando aplicável; implementar rotação/remoção configurável.

6) Implantação alvo — KingHost
- Ambiente alvo: KingHost (SSH: `plansul@ftp.plansul.info`). Desenvolver considerando compatibilidade local ↔ KingHost (versões PHP, permissões, paths).
- Pré-deploy: confirmar `php -v`, `composer` e permissões de `storage/` e `bootstrap/cache`.
- Paths: prefira `__DIR__` ou variáveis de ambiente em vez de caminhos hard-coded.
- Fornecer exemplos de comandos para PowerShell e Bash quando aplicável.

7) Organização e mudanças de estrutura
- Antes de mover/renomear arquivos, proponha um mapa de reorganização (origem -> destino) no chat e aguarde confirmação.
- Mantenha diretórios essenciais intactos (`app/`, `config/`, `public/`, `resources/`, `routes/`, `database/migrations/`, `vendor/`) a menos que haja plano e testes.

8) Análise completa antes de correções
- Antes de alterar um trecho solicitado, analisar todo o fluxo relacionado (controllers, services, models, views, migrations) para garantir que a correção seja suficiente.
- Se houver múltiplos problemas interdependentes, apresentar um plano com todas as mudanças necessárias e impacto estimado.

9) Leia arquivos auxiliares criados
- Sempre que um arquivo auxiliar for criado (ex.: `.txt`, scripts de diagnóstico, addendums), o agente deve publicar no chat uma instrução curta indicando que o arquivo existe e deve ser LIDO antes de executar ações baseadas nele.
- Exemplo de mensagem automática: "Arquivo criado: `.github/copilot-addendum.txt`. Leia antes de executar scripts relacionados — contém opções de deploy, logs e instruções one-off."

10) Formato das respostas do agente
- Respostas em `pt-br`, objetivas e simples.
- Estrutura padrão:
  1) Uma frase inicial — o que farei.
  2) Bullets (3–6) com ações/arquivos/comandos essenciais.
  3) Comandos/código prontos para copiar (PowerShell e Bash quando necessário).
  4) Pergunta final: próximo passo (commit/push/restore).

11) Auditoria e rollback
- Preserve backups em `archive/backups/` antes de mudanças destrutivas.
- O assistente pode restaurar/extrair arquivos do backup mediante solicitação.

12) Observações finais e exemplos rápidos
- Exemplo de log: `[2025-12-04 15:04:05] INFO import_patr: processou 123 registros; erros=2`.
- Comando para checar PHP e permissões no servidor (Bash):
```
ssh plansul@ftp.plansul.info
php -v; composer --version; ls -ld storage bootstrap/cache
```

Data da última atualização: 2025-12-04

*** FIM — consolidado em 2025-12-04 ***
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
5. Scripts ou utilitários criados para análise/execução pontual: se o script for feito apenas para executar uma tarefa investigativa ou uma correção pontual e **não** for destinado a permanecer como ferramenta reutilizável, o agente deve:
  - avisar no chat que o script é one-off antes de executá-lo;
  - executar e documentar o que foi feito;
  - remover (deletar) o script automaticamente após o uso, a menos que o mantenedor peça explicitamente para preservá-lo.

6. Organização e estrutura: manter código e pastas sempre bem organizados. Antes de mover/renomear arquivos, o agente deve propor um mapa de reorganização (paths de origem -> destino) e só aplicar após confirmação do mantenedor.

7. Análise completa antes de agir: antes de modificar um trecho solicitado para correção, o agente deve analisar todo o fluxo relacionado (funções chamadas, controllers, serviços, migrations, views envolvidas) para avaliar se a correção local resolve o problema global. Se detectar múltiplos problemas interdependentes, reportar um plano com todas as correções necessárias em vez de aplicar apenas a primeira alteração.

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
