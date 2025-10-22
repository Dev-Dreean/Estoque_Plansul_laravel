# Teste da Tela de Edição de Patrimônio

## Alterações Realizadas

### 1. Descrição do Objeto (DEOBJETO)
- **Problema**: Na tela de edição, o campo "Descrição do Objeto" não estava sendo preenchido automaticamente com o valor armazenado
- **Solução**: Adicionada lógica na inicialização (`init()`) para buscar a descrição do objeto via API `/api/codigos/buscar/{NUSEQOBJ}`
- **Campo de teste**: Campo "Descrição do Objeto" no formulário

### 2. Código do Local e Nome do Local
- **Problema**: Os campos de código e nome do local não estavam sendo sincronizados corretamente com o novo sistema de dropdown
- **Solução**: Atualizada a inicialização para:
  - Preencher `codigoLocalDigitado` (código do local)
  - Preencher `nomeLocalBusca` (nome do local visível)
  - Sincronizar `projetoSearch` com o novo dropdown de projetos

### 3. Matrícula do Responsável
- **Problema**: Campo exibia dados extras como datas e números (ex: "133838 - RODRIGO BEDA GUALDA                                              01/02/2022    0")
- **Solução**: Refatorada função `selecionarUsuario()` para:
  - Remover datas no padrão dd/mm/yyyy
  - Remover números soltos ao final
  - Remover múltiplos espaços
  - Manter apenas letras, acentos e espaço

## Checklist de Testes

### Teste 1: Abrir Edição de Patrimônio
- [ ] Abrir um patrimônio existente no modo edição
- [ ] Verificar se TODOS os campos são preenchidos:
  - [ ] Número do Patrimônio
  - [ ] Número da Ordem de Compra
  - [ ] **Descrição do Objeto** ← NOVO
  - [ ] Código do Objeto
  - [ ] Observações
  - [ ] Projeto Associado
  - [ ] **Código do Local** ← VERIFICAR
  - [ ] **Nome do Local** ← VERIFICAR (deve mostrar automaticamente)
  - [ ] Código do Termo
  - [ ] Marca
  - [ ] Modelo
  - [ ] Situação do Patrimônio
  - [ ] **Matrícula do Responsável** ← VERIFICAR (sem datas/números extras)
  - [ ] Data de Aquisição
  - [ ] Data de Baixa

### Teste 2: Validar Descrição do Objeto
- [ ] Abrir edição de patrimônio que tenha NUSEQOBJ preenchido
- [ ] Verificar se o campo "Descrição do Objeto" mostra a descrição corretamente
- [ ] Confirmar que a descrição está visível no dropdown

### Teste 3: Validar Código e Nome do Local
- [ ] Verificar se o código do local (CDLOCAL) aparece no campo "Código do Local"
- [ ] Verificar se o nome do local aparece no campo readonly "Nome do Local"
- [ ] Verificar se o projeto associado aparece preenchido automaticamente

### Teste 4: Validar Matrícula do Responsável
- [ ] Verificar se o campo exibe apenas "matrícula - nome" (ex: "133838 - RODRIGO BEDA GUALDA")
- [ ] Confirmar que NÃO apareça datas ou números extras
- [ ] Se o responsável foi alterado, verificar se o novo nome aparece corretamente

### Teste 5: Salvar Alterações
- [ ] Fazer uma pequena alteração (ex: adicionar observação)
- [ ] Clicar em "Atualizar Patrimônio"
- [ ] Verificar se salva sem erros
- [ ] Reabrir para confirmar que todas as alterações foram preservadas

### Teste 6: Comparar com Tela de Criação
- [ ] Verificar que a tela de edição está idêntica à tela de criação em termos de layout
- [ ] Confirmar que todos os campos seguem o mesmo padrão de preenchimento

## Campos que Devem Estar Visíveis e Preenchidos

```
┌─────────────────────────────────────────┐
│ EDITAR PATRIMÔNIO: [DESCRIÇÃO DO PATR]  │
├─────────────────────────────────────────┤
│ Número do Patrimônio *: [12345      ]   │
│ Número da Ordem de Compra: [OC123   ]   │
│ Descrição do Objeto *: [Descrição   ] ✓ │ ← NOVO
│ Código do Objeto *: [123            ]   │
│                                         │
│ Observações: [Texto...            ]   │
│                                         │
│ Projeto Associado *: [Projeto 001]  ✓ │
│ Código do Local *: [LOC-01      ]   ✓ │
│ Nome do Local: [Almoxarifado... ] ✓ │
│ Código do Termo: [5000          ]   │
│                                         │
│ Marca: [Marca ABC          ]            │
│ Modelo: [Modelo XYZ       ]            │
│ Situação do Patrimônio *: [EM USO ▼] │
│                                         │
│ Matrícula do Responsável *: [133838 - R│
│                              RODRIGO ▼]✓│
│ Data de Aquisição: [2022-01-15    ]    │
│ Data de Baixa: [                  ]    │
│                                         │
│        [Cancelar] [Atualizar Patrimônio]│
└─────────────────────────────────────────┘
```

## Notas Importantes

1. A descrição do objeto (DEOBJETO) é buscada via API na inicialização, não deve ser deixada vazia
2. O nome do local (NOMELOCAL) é um campo readonly que se preenche automaticamente
3. A matrícula do responsável deve mostrar APENAS "matrícula - nome", sem dados extras
4. O projeto associado e código do local devem estar sincronizados corretamente
