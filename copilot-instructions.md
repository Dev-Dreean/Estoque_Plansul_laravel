Objetivo do projeto
Sistema de Estoque/Patrimônio em Laravel 11 + Blade + Tailwind + Vite. Priorizar clareza, segurança e compatibilidade.

Padrões obrigatórios

Não altere schema diretamente: sempre migrations; seeders opcionais.

Validações em Form Requests (não no controller).

Services/Actions para regras de negócio; Eloquent limpo (scopes, casts, accessors/mutators).

Nomes em pt-BR, consistentes e no singular para Model (ex.: Patrimonio) e tabelas no plural.

Autorização com Policies/Gates; Nunca exponha mass assignment (use $fillable).

Respostas de controller padronizadas (redirect + with() em POST; view clara em GET).

Logs com Log::... em fluxos críticos; trate exceções com try/catch e report().

NUNCA mudar rotas existentes sem dizer o impacto; adicione novas de forma backward-compatible.

Front: Blade componentizado; Tailwind utilitário; JS mínimo (Alpine) quando necessário.

Qualidade & testes (sempre que tocar em código)

Rodar e/ou propor Pint (formatação), Larastan nível 5+, Pest unitário/feature.

Sugerir Rector apenas para refactors seguros (sem side-effects).

Gerar/esboçar testes Pest ao criar controllers/services.

Como responder

Explique rapidamente a intenção, liste arquivos que vai tocar, mostre diff unificado pequeno.

Cite riscos, fallback e como rodar testes/linters.

Se faltar contexto, pergunte antes de alterar comportamento.

Do’s

Preferir Route::resource/Route::controller quando fizer sentido.

Usar Scopes (->active(), etc.) e DTOs simples quando a request ficar complexa.

Documentar com PHPDoc onde a inferência do tipo não for óbvia.

Don’ts

Criar dependências pesadas sem justificar.

Silenciar exceções.

Query “n+1”: sempre com with() pontual.

Comandos úteis p/ mim (VS Code Copilot Chat)

/fix no arquivo aberto para correções seguras.

/tests para sugerir Pest do arquivo atual.

@workspace perguntas sobre o projeto inteiro.

“Faça refactor seguro mantendo comportamento e explique riscos”.

Referências
Laravel docs oficiais; Pint, Larastan/PHPStan, Pest, Rector.

Nota: Esse arquivo é lido automaticamente pelo Copilot Chat e pelo “coding agent” no contexto do repo. 
GitHub Docs