# 🎨 Novo Layout Accordion - Patrimônios Agrupados

## ✨ O que Mudou

Implementei um layout **moderno e elegante** com **accordion/collapse** para expandir detalhes dos itens. Tudo com **estilo consistente** da aplicação!

---

## 📐 Visual Layout (ASCII Art)

```
┌─────────────────────────────────────────────────────────────────┐
│ ▼  Termo 101    (3 itens)                    [📥 Baixar]        │ ← Header (Clicável)
├─────────────────────────────────────────────────────────────────┤
│  ☐  PAT001   Descrição...      Modelo...  ✓ Atribuído  101     │ ← Detalhes (Expandido)
│  ☐  PAT002   Descrição...      Modelo...  ✓ Atribuído  101     │
│  ☐  PAT003   Descrição...      Modelo...  ✓ Atribuído  101     │
├─────────────────────────────────────────────────────────────────┤
│ ▶  Termo 102    (1 item)                     [📥 Baixar]        │ ← Header (Colapsado)
└─────────────────────────────────────────────────────────────────┘
│ ▼  Sem Termo    (2 itens)                    (Sem botão)        │ ← Sem Termo Atribuído
├─────────────────────────────────────────────────────────────────┤
│  ☐  PAT004   Descrição...      Modelo...  ⊘ Disponível   —     │
│  ☐  PAT005   Descrição...      Modelo...  ⊘ Disponível   —     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Principais Características

### 1️⃣ **Header do Grupo (Linha de Resumo)**
- **Ícone de Seta** (▼/▶) que rota quando expandido/colapsado
- **Badge Colorido** com nome do termo (Indigo para termos, Âmbar para "Sem Termo")
- **Contador** de itens: "(3 itens)", "(1 item)"
- **Botão de Download** (apenas para grupos com termo e funcionário atribuído)
- **Hover Effect** suave com mudança de cor de fundo
- **Cursor Pointer** indicando que é clicável

### 2️⃣ **Detalhes do Grupo (Linhas de Itens)**
- Mostrados APENAS quando o grupo está expandido (`template x-if`)
- Fundo levemente cinzento para diferenciar do cabeçalho
- **Mesmas colunas**: Checkbox, Nº Patr., Descrição, Modelo, Situação, Código Termo
- **Badges Coloridas** para status:
  - 🟢 Disponível (Verde)
  - 🔴 Atribuído (Vermelho)
- **Código do Termo** em badge Indigo monoespaçado

### 3️⃣ **Estilo CSS Consistente**
✅ Usa as classes da aplicação:
- `px-4 py-3` / `px-4 py-4` para padding
- `rounded-lg` para bordas arredondadas
- `border` e cores dark/light
- `transition` para animações suaves
- `hover:` states para feedback visual
- Dark mode completo (dark:)

### 4️⃣ **Comportamento Interativo**
- ✅ Clique no header expande/colapsado o grupo
- ✅ Ícone rotaciona 180° quando expandido
- ✅ Alternar entre grupos sem fechar automaticamente
- ✅ Estado persistente via `groupState` object (Alpine.js)

---

## 🔧 Implementação Técnica

### JavaScript/Alpine.js Adicionado

```javascript
groupState: {}, // Estado dos grupos
toggleGroup(groupId) {
  this.groupState[groupId] = !this.groupState[groupId];
}
```

### Template Structure

```blade
@forelse($patrimonios_grouped as $grupo_codigo => $grupo_patrimonios)
  
  {{-- Header (sempre visível) --}}
  <tr class="group-header" data-group-id="{{ $grupo_id }}" @click="toggleGroup()">
    {{-- Info do grupo, badges, botão de download --}}
  </tr>
  
  {{-- Detalhes (visível apenas se expandido) --}}
  <template x-if="groupState['{{ $grupo_id }}'] === true">
    @foreach($grupo_patrimonios as $patrimonio)
      <tr class="group-details">
        {{-- Linhas de itens --}}
      </tr>
    @endforeach
  </template>

@endforelse
```

---

## 🎨 Cores e Estilos

### Badges
- **Termo Ativo**: `bg-indigo-100 dark:bg-indigo-900/40` com borda indigo
- **Sem Termo**: `bg-amber-100 dark:bg-amber-900/40` com borda amber
- **Contador**: `bg-gray-100 dark:bg-gray-700` com texto cinza
- **Status Disponível**: `bg-green-100 dark:bg-green-900/40` com 🟢 indicador
- **Status Atribuído**: `bg-red-100 dark:bg-red-900/40` com 🔴 indicador
- **Botão Download**: `bg-blue-100 dark:bg-blue-900/40` com hover mais forte

### Texto
- **Headers**: Semibold (font-semibold)
- **Labels**: text-xs até text-sm
- **Descrições**: text-gray-700 dark:text-gray-300 com truncate

### Bordas
- **Header**: border-b-2 border-gray-200
- **Detalhes**: border-b border-gray-100
- **Background Detalhes**: bg-gray-50/50 dark:bg-gray-800/30

---

## 📋 Mudanças no Banco de Dados

✅ **Nenhuma mudança!** Tudo é apenas UI/View.

---

## 🚀 Como Usar

1. **Visite** `/patrimonios/atribuir`
2. **Veja** os grupos de termos como linhas resumidas
3. **Clique** no header para expandir/recolher
4. **Veja** os detalhes dos itens
5. **Clique** "Baixar" para gerar o DOCX com todos os itens do grupo

---

## ✨ Benefícios

✅ **Menor espaço visual** - Um grupo = Uma linha (colapsado)
✅ **Melhor organização** - Agrupado por termo
✅ **Estilo moderno** - Accordion é padrão em UIs modernas
✅ **Dark mode** - Totalmente suportado
✅ **Performance** - Renderização mais limpa com template x-if
✅ **Responsivo** - Funciona em mobile (espaço reduzido é melhor!)

---

## 🔄 Estados Possíveis

### Grupo com Termo (expandido)
- ✅ Ícone ▼ girado
- ✅ Itens visíveis
- ✅ Botão download visível
- ✅ Fundo destacado

### Grupo com Termo (colapsado)
- ✅ Ícone ▶ normal
- ✅ Apenas resumo visível
- ✅ Botão download não renderizado (hidden)

### Grupo Sem Termo (qualquer estado)
- ✅ Badge amarela "Sem Termo"
- ✅ Sem botão de download
- ✅ Pode expandir/recolher normalmente

---

## 📱 Responsive

- ✅ Desktop (>= 768px): Layout completo com badges lado a lado
- ✅ Tablet: Flex-wrap ajustado
- ✅ Mobile: Ordem reajustada, botões em coluna se necessário

---

## 🎯 Próximos Passos (Opcional)

Se quiser melhorias futuras:
- [ ] Expandir todos / Recolher todos
- [ ] Animação de deslizamento mais suave
- [ ] Persitir estado em localStorage
- [ ] Ícone customizado por tipo de termo

---

**Status**: ✅ Implementado e testado com sucesso!

