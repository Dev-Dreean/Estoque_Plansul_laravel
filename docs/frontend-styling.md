# Frontend Styling

## Objetivo

Este guia existe para evitar retrabalho em front, divergencia entre local e KingHost e criacao de CSS paralelo.
O objetivo e manter o visual atual do sistema e facilitar manutencao futura.

## Mapa da arquitetura

- `resources/css/app.css`
  Entry point unico do Vite. So organiza imports e diretivas do Tailwind.
- `resources/css/foundation/tokens.css`
  Variaveis de tema para `light` e `dark`.
- `resources/css/foundation/base.css`
  Helpers base como background, texto, borda, foco e utilitarios globais.
- `resources/css/components/*`
  Componentes reutilizaveis.
- `resources/css/screens/*`
  Regras especificas por tela.
- `resources/css/legacy/compat.css`
  Overrides legados temporarios para preservar o visual atual durante a migracao.

## Como escolher a abordagem

### Use token

Quando a mudanca for de cor, superficie, borda, foco ou estado semantico do sistema.

Exemplos:
- cor primaria do sistema
- cor de painel
- cor de status de fluxo

### Use componente

Quando o padrao se repete em mais de uma tela.

Exemplos:
- botao
- badge
- campo
- tabela
- modal
- painel

### Use screen css

Quando o estilo e especifico de uma tela e nao faz sentido virar componente global.

Exemplos:
- switch de Solicitacoes / Historico
- card expansivel do Historico
- botoes de fluxo do modal de solicitacao

### Use Tailwind utilitario

Quando o ajuste e pequeno, local e nao cria um novo padrao visual.

Exemplos:
- `mt-4`
- `flex`
- `items-center`
- `gap-2`

## Quando usar style=""

Permitido:
- largura calculada em runtime
- porcentagem dinamica
- cor vinda de dado externo
- `display:none` necessario para integracao com Alpine quando nao houver classe padrao

Nao permitido:
- cor fixa de botao
- borda fixa repetida
- hover recorrente
- background recorrente
- espacamento recorrente

## Classes principais

### Botoes

- `.btn`
- `.btn-primary`
- `.btn-secondary`
- `.btn-danger`
- `.btn-warning`
- `.btn-outlined`

### Campos

- `.field`
- `.field-sm`
- `.field-disabled`

### Tabelas

- `.table-app`
- `.table-clean`
- `.table-wrap`
- `.table-head-cell`
- `.table-cell`

### Paineis e badges

- `.panel`
- `.panel-header`
- `.panel-muted`
- `.badge`
- `.badge-accent`

### Solicitações

- `.sol-subnav*`
- `.sol-history-*`
- `.sol-index-*`
- `.sol-flow-action*`

## Regra de build

Rode `npm run build` quando:
- alterar `resources/css/*`
- alterar `resources/js/*`
- introduzir nova classe Tailwind em Blade

Nao precisa build quando:
- a mudanca for so logica Blade/PHP
- nao houver nova classe Tailwind
- nao houver alteracao em CSS/JS

## Checklist de deploy frontend

1. Rodar `composer text:check`
2. Rodar `npm run build` quando aplicavel
3. Confirmar `public/build/manifest.json`
4. Subir manifest e assets novos
5. Remover assets antigos que sairam do manifest
6. Limpar cache Laravel no servidor
7. Conferir arquivo remoto e manifest remoto antes de dizer que publicou

## Regra de manutencao

Se uma solucao exigir override global com `!important`, ela deve entrar em `resources/css/legacy/compat.css` com justificativa clara e ser tratada como divida tecnica temporaria.
