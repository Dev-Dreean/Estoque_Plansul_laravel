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

**6a) Acesso SSH com PHP 8.2+ no KingHost (REFERÊNCIA RÁPIDA)**
⚠️ **IMPORTANTE:** O KingHost possui múltiplas versões PHP (5.6, 7.0-7.4, 8.0-8.2). A aplicação requer **PHP 8.2+**.

**Diretório da aplicação no KingHost:**
```
SSH: ssh plansul@ftp.plansul.info
App: ~/www/estoque-laravel (não /home/plansul/public_html/)
```

**Versões PHP disponíveis:**
- `php` = PHP 5.6 (padrão, não usar para Laravel 11)
- `php82` = PHP 8.2 ✅ (usar este)
- `php81` = PHP 8.1 (não compatível com composer.lock)
- `php80`, `php74`, etc. = versões anteriores

**Fluxo padrão de SSH (quando solicitado):**

1) **Pull do repositório:**
```bash
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && git pull origin main && git log --oneline -1"
```

2) **Verificação pré-comando:**
```bash
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && php82 --version && ls -la storage/backups/ | tail -3"
```

3) **Executar comando Artisan (exemplo: dry-run):**
```bash
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && php82 artisan users:unify --user=BEATRIZ.SC --dry-run"
```

4) **Executar comando Artisan (produção, com confirmação automática):**
```bash
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && echo 'yes' | php82 artisan users:unify --user=BEATRIZ.SC 2>&1"
```

5) **Verificar backup foi criado:**
```bash
ssh plansul@ftp.plansul.info "ls -lah ~/www/estoque-laravel/storage/backups/user_unify_backup*.json | tail -1"
```

**Troubleshooting comum:**
- ❌ "Parse error: unexpected ':'" → Usar `php82` em vez de `php`
- ❌ "root composer.json requires php ^8.2" → Usar `php82 ~/composer.phar install`
- ❌ "/home/plansul/public_html/plansul: No such file" → App está em `~/www/estoque-laravel`, não public_html
- ✅ Se pull falhar com "untracked files", fazer `git stash` antes

**Procedimento para agent (quando usuário solicita SSH):**
1. Verificar se é operação de **leitura** (git pull, check) ou **escrita** (data modifications)
2. Se read-only: executar sem confirmação adicional
3. Se write: sempre fazer `--dry-run` primeiro, mostrar resultado, pedir confirmação
4. Após execução: verificar backup foi criado (se aplicável) e reportar sucesso
5. **NUNCA** executar SSH sem autorização explícita do usuário (a menos que seja para ler status)

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

13) Princípios de Manutenibilidade e Arquitetura Limpa (OBRIGATÓRIO)

**⚠️ REGRA FUNDAMENTAL: Todo código deve ser manutenível, escalável e de fácil evolução**

Princípios obrigatórios a seguir em TODAS as implementações:

a) **Separação de Responsabilidades**
   - Controllers: apenas roteamento e resposta HTTP
   - Services (`app/Services/`): lógica de negócio e transações
   - Models: relacionamentos e escopos
   - Components (`resources/views/components/`): UI reutilizável

b) **Reutilização de Código (DRY)**
   - UI repetida DEVE virar componente Blade
   - Lógica repetida DEVE ir para Service Layer
   - JavaScript repetido DEVE ser modularizado
   - Sempre verificar se já existe componente/service antes de criar novo

c) **Componentes Blade Reutilizáveis**
   - Localização: `resources/views/components/`
   - Componentes disponíveis:
     * `<x-action-button>` - Botões de ação (edit, delete, view, add, export)
     * `<x-status-badge>` - Badges de status com cores automáticas
     * `<x-table-header>` - Cabeçalhos de tabela com ordenação
   - Antes de criar HTML inline, verificar se componente existente atende
   - Novos componentes devem ter documentação inline no topo do arquivo

d) **JavaScript Modular**
   - Módulos em `public/js/` com padrão IIFE
   - API pública exposta via `window.NomeModulo`
   - Módulos disponíveis:
     * `PatrimonioActions` - CRUD de patrimônios (delete, rebind, configure)
   - Evitar JavaScript inline em views; preferir módulos reutilizáveis
   - Usar data-attributes para vincular ações (ex: `data-delete-patrimonio`)

e) **Service Layer para Lógica de Negócio**
   - Services em `app/Services/`
   - Services disponíveis:
     * `PatrimonioService` - listar, buscarPorId, criar, atualizar, deletar, estatisticas
   - Controllers DEVEM usar Services para operações complexas
   - Services facilitam testes e reutilização

f) **Logs Padronizados com Emojis**
   - Formato: `Log::info('emoji [CONTEXTO] mensagem', ['dados' => $valor]);`
   - Emojis padrão:
     * 🚀 Inicialização | 📋 Listagem | ➕ Criação | ✏️ Atualização
     * 🗑️ Deleção | ✅ Sucesso | ⚠️ Aviso | ❌ Erro
     * 🔍 Busca | 📊 Estatísticas | 📡 HTTP Request | 📥 HTTP Response
   - Todo método de Service DEVE ter logs de entrada/saída

g) **Estilos e CSS (OBRIGATÓRIO - SOMENTE TAILWIND)**
   - ❌ PROIBIDO: CSS customizado, inline styles, ou classes personalizadas
   - ✅ OBRIGATÓRIO: Usar APENAS classes Tailwind do `tailwind.config.js`
   - ✅ Dark mode: Usar `dark:` prefix (ex: `dark:bg-gray-900`, `dark:text-gray-200`)
   - ✅ Componentes Blade: Manter consistência visual com página
   - **CRÍTICO:** Toda classe de cor DEVE ter o prefixo `dark:` correspondente
     * CORRETO: `bg-white dark:bg-gray-900` 
     * ERRADO: `bg-white` (ficará branco em ambos temas)
     * ERRADO: `bg-blue-500` sem `dark:bg-blue-700` (ficará errado no modo escuro)
   - Cores padrão do projeto: `gray`, `blue`, `red` (padrão Tailwind)
   - Borders: `border-gray-200 dark:border-gray-700`
   - Texto: `text-gray-900 dark:text-gray-100`
   - Backgrounds: `bg-white dark:bg-gray-800` ou `bg-gray-100 dark:bg-gray-700`
   - Spinner/Loading: `text-gray-600 dark:text-gray-400`
   - Hover: `hover:bg-gray-100 dark:hover:bg-gray-700`
   - Inputs: `border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200`
   - **NUNCA** usar `bg-blue-500` ou cores saturadas para backgrounds; apenas para highlights
   - Se precisar de estilo não disponível em Tailwind, solicitar adição em `tailwind.config.js` ANTES de implementar

h) **Documentação Inline**
   - Services: bloco PHPDoc com propósito, benefícios, exemplo de uso
   - Components: comentário Blade no topo com @props e exemplo
   - Módulos JS: comentário JSDoc com propósito, API pública, exemplo

i) **Antes de Implementar Qualquer Feature**
   1. Verificar se existe componente/service/módulo reutilizável
   2. Se não existe mas é reutilizável, criar como componente
   3. Se existe mas não atende, avaliar extensão vs criar novo
   4. Documentar inline se criar algo novo
   5. Adicionar logs padronizados
   6. Verificar compatibilidade dark mode (Tailwind) - **testar em ambos os temas**
   7. Validar que TODO CSS vem de classes Tailwind
   8. **Copiar padrão visual de componentes existentes** (não inovar em estilo)

j) **Checklist de Código Limpo (verificar antes de commit)**
   - [ ] Lógica complexa está em Service?
   - [ ] HTML repetido virou componente?
   - [ ] JavaScript está modularizado?
   - [ ] Logs usam emojis padronizados?
   - [ ] Nomes descritivos em português?
   - [ ] Sem código comentado desnecessário?
   - [ ] Tratamento de erros adequado?
   - [ ] Documentação inline quando necessário?
   - [ ] TODOS os estilos são Tailwind?
   - [ ] Cada cor tem seu `dark:` correspondente?
   - [ ] Testado em tema claro E tema escuro?
   - [ ] Segue padrão visual dos outros componentes?

k) **Documentação de Referência**
   - Arquitetura completa: `docs/ARQUITETURA_MANUTENCAO.md`
   - Guia rápido de componentes: `docs/COMPONENTES_GUIA_RAPIDO.md`
   - Tailwind: https://tailwindcss.com/docs
   - Ler ANTES de implementar features complexas

l) **Quando Refatorar Código Legado**
   - NÃO reescrever tudo de uma vez
   - Refatorar incrementalmente (um método/view por vez)
   - Extrair para Service primeiro
   - Criar componentes depois
   - Manter funcionalidade existente
   - Testar após cada mudança

Data da última atualização: 2025-12-04

*** FIM — consolidado e expandido em 2025-12-04 ***
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
