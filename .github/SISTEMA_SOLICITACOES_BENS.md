# Sistema de Solicitações de Bens - Documentação

## Versão em Desenvolvimento (2026-01-08)

### O que foi implementado

✅ **Sistema de solicitações de bens** (compras cria, controle separa/conclui)
- Telas: listagem, criação e detalhe/atualização com auto-preenchimento do nome pela matrícula
- Status controlados: `PENDENTE`, `SEPARADO`, `CONCLUÍDO`, `CANCELADO`
- Email de confirmação opcional via `SOLICITACOES_BENS_EMAIL_TO`
- Acesso e menu: nova tela 1010 "Solicitações de Bens"

### Arquivos principais alterados/criados

#### Models
- `app/Models/SolicitacaoBem.php` - Modelo principal de solicitação
- `app/Models/SolicitacaoBemItem.php` - Modelo de itens da solicitação

#### Controller
- `app/Http/Controllers/SolicitacaoBemController.php` - Lógica de CRUD

#### Rotas
- `routes/web.php` - Rotas do sistema

#### Views
- `resources/views/solicitacoes/index.blade.php` - Listagem
- `resources/views/solicitacoes/create.blade.php` - Criação
- `resources/views/solicitacoes/show.blade.php` - Detalhe/Atualização

#### Configuração
- `config/solicitacoes_bens.php` - Configurações do sistema
- `.env.example` - Variável `SOLICITACOES_BENS_EMAIL_TO`

#### Telas/Menus
- `app/Http/Controllers/CadastroTelaController.php` - Atualização de menus
- `config/telas.php` - Mapeamento de telas

#### Migrations
- `database/migrations/2026_01_09_100000_create_solicitacoes_bens_table.php`
- `database/migrations/2026_01_09_100100_create_solicitacao_bens_itens_table.php`
- `database/migrations/2026_01_09_100200_add_tela_solicitacoes_bens.php`

### Status da Implementação

- ✅ Estrutura de banco criada
- ✅ Models e Controllers implementados
- ✅ Views criadas
- ✅ Tela 1010 adicionada ao acessotela
- ✅ Testado localmente
- ⏳ Aguardando deploy no KingHost

### Próximas Etapas

1. Deploy no KingHost
2. Teste de fluxo completo (criar → separar → concluir)
3. Integração com sistema de email
4. Testes de permissão e visibilidade

---
Data: 2026-01-08
Versão: dev (em desenvolvimento)
