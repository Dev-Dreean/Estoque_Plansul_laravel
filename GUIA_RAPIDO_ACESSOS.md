# 🚀 Guia Rápido - Sistema de Acessos Implementado

## ✅ O que foi implementado

### 1. **MenuHelper** - Gerenciamento Centralizado
📁 `app/Helpers/MenuHelper.php`

**Funcionalidades:**
- ✅ Identifica telas obrigatórias (Relatórios e Histórico)
- ✅ Filtra telas por permissão do usuário
- ✅ Gera menu dinâmico baseado em acessos
- ✅ Controla visibilidade no menu principal
- ✅ Gerencia submenu de Patrimônio

### 2. **Middleware Atualizado** - CheckTelaAccess
📁 `app/Http/Middleware/CheckTelaAccess.php`

**Melhorias:**
- ✅ Verifica telas obrigatórias (sempre acessíveis)
- ✅ God Mode para Super Admin
- ✅ Validação de permissões em tempo real
- ✅ Redirecionamento inteligente

### 3. **Component Blade** - navigation-menu
📁 `resources/views/components/navigation-menu.blade.php`

**Características:**
- ✅ Menu 100% dinâmico
- ✅ Estilização automática por tipo de tela
- ✅ Ícones personalizados
- ✅ Mensagem quando não há acessos

### 4. **Documentação Completa**
📁 `SISTEMA_GESTAO_ACESSOS.md`

**Conteúdo:**
- ✅ Hierarquia de permissões detalhada
- ✅ Fluxograma de verificação de acesso
- ✅ Exemplos práticos de uso
- ✅ Guia para adicionar novas telas

### 5. **Exemplo de Integração**
📁 `resources/views/menu/exemplo-navegacao-dinamica.blade.php`

**Demonstra:**
- ✅ Uso do MenuHelper
- ✅ Integração do component
- ✅ Verificação de acessos
- ✅ UI moderna e responsiva

---

## 🎯 Como Funciona Agora

### **Telas Obrigatórias (Sempre Ativas)**

#### 1. Relatórios (1006)
```php
// ✅ SEMPRE ativo para todos os usuários autenticados
// ❌ NÃO aparece no menu principal
// 💡 Usado por botões específicos
MenuHelper::isTelaObrigatoria('1006'); // true
```

#### 2. Histórico de Movimentações (1007)
```php
// ✅ SEMPRE ativo para todos os usuários autenticados
// ✅ Aparece no SUBMENU de Patrimônio
// 💡 Parte obrigatória do controle de patrimônio
MenuHelper::isTelaObrigatoria('1007'); // true
```

### **Telas Controladas (Requerem Permissão)**

Todas as outras telas precisam de liberação por ADM/SUPER ADM:
- 1000 - Controle de Patrimônio
- 1001 - Dashboard
- 1002 - Cadastro de Locais
- 1003 - Cadastro de Usuários
- 1004 - Cadastro de Telas
- 1005 - Gerenciar Acessos
- 1008 - Configurações de Tema

---

## 📋 Como Usar

### **1. Verificar se usuário tem acesso**
```php
use App\Helpers\MenuHelper;

// Verificar acesso individual
if (MenuHelper::temAcessoTela('1002')) {
    // Usuário tem acesso ao Cadastro de Locais
    echo "Acesso permitido!";
}

// Obter todas as telas com acesso
$telasComAcesso = MenuHelper::getTelasComAcesso();
// Retorna: ['1000', '1001', '1006', '1007', ...]
```

### **2. Gerar menu dinâmico**
```php
use App\Helpers\MenuHelper;

// Obter telas para o menu (filtra telas obrigatórias que não devem aparecer)
$telasMenu = MenuHelper::getTelasParaMenu();

// Obter submenu de Patrimônio
$submenu = MenuHelper::getSubmenuPatrimonio();
```

### **3. Usar component de navegação**
```blade
{{-- Em qualquer view --}}
@auth
    <x-navigation-menu class="my-custom-class" />
@endauth
```

### **4. Proteger rotas**
```php
// Em routes/web.php
Route::get('/minha-tela', [MyController::class, 'index'])
    ->middleware(['auth', 'tela.access:1009']);
```

### **5. Verificar no Controller**
```php
use App\Helpers\MenuHelper;

class MyController extends Controller
{
    public function index()
    {
        // Verificação manual (opcional, middleware já faz isso)
        if (!MenuHelper::temAcessoTela('1009')) {
            abort(403, 'Sem permissão');
        }
        
        // Sua lógica aqui
    }
}
```

### **6. Verificar na View**
```blade
@if(App\Helpers\MenuHelper::temAcessoTela('1002'))
    <a href="{{ route('projetos.index') }}">Cadastro de Locais</a>
@endif

{{-- Ou verificar no usuário diretamente --}}
@if(Auth::user()->temAcessoTela('1002'))
    <a href="{{ route('projetos.index') }}">Cadastro de Locais</a>
@endif
```

---

## 🔐 Hierarquia de Permissões

### **Super Admin (SUP)** 🛡️
```php
Auth::user()->isGod();        // true
Auth::user()->isSuperAdmin(); // true
Auth::user()->podeExcluir();  // true

// Acessa TUDO automaticamente
MenuHelper::temAcessoTela('XXXX'); // sempre true
```

### **Administrador (ADM)** 👨‍💼
```php
Auth::user()->isAdmin();      // true
Auth::user()->podeExcluir();  // false

// Acessa telas liberadas por Super Admin
// Não pode deletar registros
```

### **Usuário (USR)** 👤
```php
Auth::user()->isUsuario();    // true
Auth::user()->podeExcluir();  // false

// Acessa apenas telas explicitamente liberadas
// Precisa de registro na tabela acessousuario
```

---

## 🎨 Submenu de Patrimônio

O submenu é **sempre fixo** quando o usuário tem acesso à tela 1000:

```php
$submenu = MenuHelper::getSubmenuPatrimonio();

// Retorna:
[
    '1000' => ['nome' => 'Patrimônios', 'obrigatoria' => true],
    '1007' => ['nome' => 'Histórico de Movimentações', 'obrigatoria' => true],
]
```

**Itens fixos (não controláveis):**
1. ✅ Patrimônios (listagem)
2. ✅ Atribuir Cód. Termo
3. ✅ Relatório de Bens
4. ✅ Histórico de Movimentações (Tela 1007)

---

## 🧪 Testes Manuais

### **Teste 1: Super Admin**
1. Login como Super Admin (PERFIL = 'SUP')
2. ✅ Deve ver TODAS as telas no menu
3. ✅ Deve acessar qualquer tela sem restrição
4. ✅ Pode excluir registros

### **Teste 2: Administrador**
1. Login como Admin (PERFIL = 'ADM')
2. ✅ Deve ver telas com NIVEL_VISIBILIDADE = 'TODOS' ou 'ADM'
3. ✅ Não pode excluir registros
4. ✅ Pode gerenciar acessos de usuários

### **Teste 3: Usuário Comum**
1. Login como Usuário (PERFIL = 'USR')
2. ✅ Deve ver apenas telas liberadas
3. ✅ Telas 1006 e 1007 sempre acessíveis (obrigatórias)
4. ❌ Tenta acessar tela sem permissão → redirecionado

### **Teste 4: Telas Obrigatórias**
1. Login com qualquer perfil (não Super Admin)
2. ✅ Pode acessar `/relatorios` (1006) mesmo sem liberação
3. ✅ Pode acessar `/historico` (1007) mesmo sem liberação
4. ✅ Relatórios NÃO aparece no menu principal
5. ✅ Histórico aparece no submenu de Patrimônio

---

## 🛠️ Resolução de Problemas

### **Problema: Usuário não vê nenhuma tela**
**Solução:**
1. Verificar se está autenticado
2. Verificar registros na tabela `acessousuario`
3. Verificar se `INACESSO = 'S'` nas permissões
4. Verificar se telas estão ativas (`acessotela.FLACESSO = 'S'`)

### **Problema: Super Admin não acessa tudo**
**Solução:**
1. Verificar se `PERFIL = 'SUP'` no banco
2. Verificar método `isGod()` no model User
3. Limpar cache: `php artisan cache:clear`

### **Problema: Tela liberada mas usuário não acessa**
**Solução:**
1. Verificar middleware na rota
2. Verificar `NIVEL_VISIBILIDADE` da tela
3. Verificar se rota existe: `MenuHelper::rotaExiste('route.name')`
4. Verificar logs: `storage/logs/laravel.log`

### **Problema: Menu não atualiza**
**Solução:**
1. Fazer logout e login novamente
2. Limpar cache: `php artisan cache:clear`
3. Limpar view cache: `php artisan view:clear`
4. Verificar sessão do usuário

---

## 📝 Checklist de Implementação

### Para cada nova tela:
- [ ] Adicionar em `config/telas.php`
- [ ] Inserir registro na tabela `acessotela`
- [ ] Criar rota com middleware `tela.access:XXXX`
- [ ] Adicionar controller e view
- [ ] Testar com cada perfil (SUP, ADM, USR)
- [ ] Documentar funcionalidade

---

## 🎓 Exemplos Completos

### **Exemplo 1: Controller com MenuHelper**
```php
<?php

namespace App\Http\Controllers;

use App\Helpers\MenuHelper;
use Illuminate\Http\Request;

class MinhaTelaController extends Controller
{
    public function index()
    {
        // Obter telas do menu
        $telasMenu = MenuHelper::getTelasParaMenu();
        
        // Verificar acesso específico
        $temAcessoLocais = MenuHelper::temAcessoTela('1002');
        
        return view('minha-tela.index', [
            'telasMenu' => $telasMenu,
            'temAcessoLocais' => $temAcessoLocais,
        ]);
    }
}
```

### **Exemplo 2: View com navegação dinâmica**
```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Minha Tela</h1>
    
    {{-- Menu dinâmico --}}
    <x-navigation-menu />
    
    {{-- Verificação condicional --}}
    @if(App\Helpers\MenuHelper::temAcessoTela('1002'))
        <a href="{{ route('projetos.index') }}">
            Ir para Cadastro de Locais
        </a>
    @endif
</div>
@endsection
```

---

## 📞 Suporte Adicional

**Arquivos importantes:**
- `app/Helpers/MenuHelper.php` - Lógica principal
- `app/Http/Middleware/CheckTelaAccess.php` - Proteção de rotas
- `app/Models/User.php` - Métodos de verificação
- `config/telas.php` - Configuração de telas
- `SISTEMA_GESTAO_ACESSOS.md` - Documentação detalhada

**Comandos úteis:**
```bash
# Limpar caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Ver rotas
php artisan route:list

# Ver logs
tail -f storage/logs/laravel.log
```

---

**✅ Sistema 100% funcional e pronto para uso!**

**Última atualização:** 25/11/2025
