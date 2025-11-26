# Sistema de Gestão de Acessos - Plansul

## 📋 Visão Geral

Este sistema gerencia de forma integrada as permissões de acesso às telas, controlando tanto a **visibilidade** quanto a **funcionalidade** de cada área do sistema.

## 🎯 Hierarquia de Permissões

### 1. **Super Admin (SUP)** 🛡️
- **Acesso Total**: Tem acesso a TODAS as telas do sistema
- **God Mode**: Ignora todas as verificações de permissão
- **Pode Excluir**: Único perfil autorizado a deletar registros
- **Não precisa de liberação**: Acessa tudo automaticamente

### 2. **Administrador (ADM)** 👨‍💼
- **Acesso Controlado**: Tem acesso às telas liberadas por Super Admin
- **Sem Exclusão**: Não pode deletar registros
- **Gerencia Usuários**: Pode liberar acessos para usuários comuns
- **Visibilidade**: Vê telas com `NIVEL_VISIBILIDADE = 'TODOS'` ou `'ADM'`

### 3. **Usuário (USR)** 👤
- **Acesso Limitado**: Apenas telas explicitamente liberadas
- **Sem Privilégios**: Não pode gerenciar outros usuários
- **Baseado em Permissão**: Precisa ter acesso concedido na tabela `acessousuario`
- **Visibilidade**: Vê apenas telas com `NIVEL_VISIBILIDADE = 'TODOS'`

## 🔐 Sistema de Telas

### Telas Obrigatórias (Sempre Ativas)

#### **1006 - Relatórios** 📊
- **Sempre Ativo**: Todos os usuários autenticados têm acesso
- **Não Aparece no Menu Principal**: Funcionalidade ativada por botões específicos
- **Motivo**: É uma função de geração de documentos, não uma tela navegável
- **Uso**: Botão "Gerar Relatório" no Controle de Patrimônio

#### **1007 - Histórico de Movimentações** 🕐
- **Sempre Ativo**: Todos os usuários autenticados têm acesso
- **Aparece no Submenu**: Dentro do Controle de Patrimônio
- **Obrigatório**: Faz parte do fluxo natural do controle de patrimônio
- **Contexto**: Igual a "Atribuir Cód. Termo" e "Patrimônios"

### Telas Controláveis (Requerem Permissão)

As seguintes telas são controladas por ADM/SUPER ADM:

#### **1000 - Controle de Patrimônio** 📦
- Menu principal
- Submenu: Patrimônios, Atribuir Cód. Termo, Relatório de Bens, Histórico

#### **1001 - Dashboard - Gráficos** 📈
- Indicadores e gráficos consolidados

#### **1002 - Cadastro de Locais** 📍
- Cadastro de plantas, locais e vínculos

#### **1003 - Cadastro de Usuários** 👥
- Gestão de contas, perfis e permissões

#### **1004 - Cadastro de Telas** 🖥️
- Liberação e registro de novas telas

#### **1005 - Gerenciar Acessos** 🔑
- Gestão de regras de acesso por usuário

#### **1008 - Configurações de Tema** 🎨
- Preferências visuais e aparência

## 🛠️ Implementação Técnica

### Classes Principais

#### **MenuHelper** (`app/Helpers/MenuHelper.php`)
```php
// Principais métodos:
MenuHelper::getTelasParaMenu()        // Retorna telas que devem aparecer no menu
MenuHelper::getTelasComAcesso()       // Retorna telas que o usuário pode acessar
MenuHelper::temAcessoTela($nuseqtela) // Verifica se tem acesso a uma tela específica
MenuHelper::isTelaObrigatoria($id)    // Verifica se é tela obrigatória
MenuHelper::getSubmenuPatrimonio()    // Retorna itens do submenu patrimônio
```

#### **CheckTelaAccess** (`app/Http/Middleware/CheckTelaAccess.php`)
Middleware que protege rotas:
```php
Route::get('/patrimonios', ...)->middleware('tela.access:1000');
```

**Lógica de verificação:**
1. Verifica se usuário está autenticado
2. Super Admin passa automaticamente
3. Verifica se é tela obrigatória → permite acesso
4. Verifica permissão do usuário na tabela `acessousuario`
5. Nega acesso se não atender critérios

#### **User Model** (`app/Models/User.php`)
```php
// Métodos de verificação:
$user->isGod()                 // É Super Admin?
$user->isSuperAdmin()          // É Super Admin? (alias)
$user->isAdmin()               // É Admin ou Super Admin?
$user->isUsuario()             // É usuário comum?
$user->podeExcluir()           // Pode deletar registros?
$user->temAcessoTela($id)      // Tem acesso à tela?
$user->telaVisivel($id)        // Tela é visível para o perfil?
$user->telasComAcesso()        // Array com todas as telas acessíveis
```

### Component Blade

#### **navigation-menu** (`resources/views/components/navigation-menu.blade.php`)
Component reutilizável que gera o menu dinamicamente:
```blade
<x-navigation-menu />
```

**Características:**
- Mostra apenas telas com permissão
- Estilização automática por cor
- Ícones personalizados
- Desabilita telas sem rota definida
- Mensagem quando não há acessos

### Fluxo de Concessão de Acesso

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Super Admin/Admin acessa "Gerenciar Acessos" (Tela 1005)│
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Seleciona o usuário que receberá a permissão            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Marca as telas que o usuário poderá acessar             │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Sistema insere registro na tabela `acessousuario`:      │
│    - NUSEQTELA: ID da tela                                  │
│    - CDMATRFUNCIONARIO: Matrícula do funcionário            │
│    - INACESSO: 'S' (liberado) ou 'N' (bloqueado)           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Usuário passa a ver a tela no menu e pode acessá-la     │
└─────────────────────────────────────────────────────────────┘
```

## 📊 Estrutura do Banco de Dados

### Tabela: `acessotela`
Define as telas disponíveis no sistema:
```sql
- NUSEQTELA (varchar)        # ID único da tela
- NMTELA (varchar)           # Nome da tela
- DESTELA (varchar)          # Descrição
- FLACESSO (char)            # 'S' = ativa, 'N' = desativada
- NIVEL_VISIBILIDADE (enum)  # 'TODOS', 'ADM', 'SUP'
```

### Tabela: `acessousuario`
Define quais usuários têm acesso a quais telas:
```sql
- NUSEQTELA (varchar)           # ID da tela (FK)
- CDMATRFUNCIONARIO (varchar)   # Matrícula do funcionário (FK)
- INACESSO (char)               # 'S' = permitido, 'N' = bloqueado
```

**Chave Primária Composta**: `(NUSEQTELA, CDMATRFUNCIONARIO)`

## 🔍 Verificação de Acesso

### Ordem de Verificação

```php
1. Usuário está autenticado?
   └─ NÃO → Redireciona para login

2. Usuário é Super Admin (GOD MODE)?
   └─ SIM → ACESSO CONCEDIDO

3. Tela é obrigatória (1006 ou 1007)?
   └─ SIM → ACESSO CONCEDIDO

4. Tela está ativa (FLACESSO = 'S')?
   └─ NÃO → ACESSO NEGADO

5. Tela é visível para o perfil do usuário?
   └─ NÃO → ACESSO NEGADO

6. Usuário tem permissão na tabela acessousuario?
   └─ NÃO → ACESSO NEGADO

7. ACESSO CONCEDIDO ✅
```

## 🎨 Submenu de Patrimônio

O submenu do Controle de Patrimônio contém itens **sempre obrigatórios**:

### Itens Fixos (Não Controláveis)
1. **Patrimônios** - Listagem principal
2. **Atribuir Cód. Termo** - Geração de termos
3. **Relatório de Bens** - Tipos e bens
4. **Histórico de Movimentações** - Auditoria (Tela 1007)

Esses itens aparecem automaticamente quando o usuário tem acesso à tela 1000 (Controle de Patrimônio).

## 📝 Exemplos de Uso

### Exemplo 1: Verificar se usuário tem acesso
```php
use App\Helpers\MenuHelper;

if (MenuHelper::temAcessoTela('1002')) {
    // Usuário tem acesso ao Cadastro de Locais
}
```

### Exemplo 2: Obter telas do menu
```php
use App\Helpers\MenuHelper;

$telasMenu = MenuHelper::getTelasParaMenu();
// Retorna apenas telas que o usuário pode acessar e devem aparecer no menu
```

### Exemplo 3: Proteger uma rota
```php
Route::get('/minha-tela', [MyController::class, 'index'])
    ->middleware('tela.access:1009');
```

### Exemplo 4: Usar component de navegação
```blade
@auth
    <x-navigation-menu class="my-custom-class" />
@endauth
```

## 🚀 Como Adicionar Nova Tela

### 1. Adicionar em `config/telas.php`
```php
'1009' => [
    'nome' => 'Minha Nova Tela',
    'descricao' => 'Descrição da funcionalidade',
    'route' => 'minha-tela.index',
    'icone' => 'fa-star',
    'cor' => 'purple',
    'ordem' => 10,
],
```

### 2. Adicionar registro no banco
```sql
INSERT INTO acessotela (NUSEQTELA, NMTELA, DESTELA, FLACESSO, NIVEL_VISIBILIDADE)
VALUES ('1009', 'Minha Nova Tela', 'Descrição', 'S', 'TODOS');
```

### 3. Proteger a rota
```php
Route::get('/minha-tela', [MyController::class, 'index'])
    ->middleware(['auth', 'tela.access:1009']);
```

### 4. Conceder permissão aos usuários
O ADM/SUPER ADM deve acessar "Gerenciar Acessos" e liberar a tela para os usuários desejados.

## ⚠️ Importante

1. **Telas Obrigatórias**: Relatórios (1006) e Histórico (1007) são sempre acessíveis
2. **Super Admin**: Ignora todas as verificações de permissão
3. **Middleware**: Sempre usar `tela.access:XXXX` nas rotas protegidas
4. **Visibilidade**: Controla se a tela aparece no menu para determinados perfis
5. **Permissão**: Controla se o usuário pode realmente acessar a funcionalidade

## 🔄 Atualização Dinâmica

O sistema é totalmente dinâmico:
- Menu atualiza automaticamente quando permissões mudam
- Não precisa reiniciar aplicação
- Middleware verifica permissões em tempo real
- Cache pode ser usado para otimização (implementação futura)

## 📞 Suporte

Para dúvidas sobre o sistema de acessos:
1. Verificar este documento
2. Consultar código dos Helpers
3. Revisar middleware CheckTelaAccess
4. Analisar Model User

---

**Última Atualização**: 25/11/2025
**Versão**: 1.0
**Desenvolvedor**: Sistema Plansul
