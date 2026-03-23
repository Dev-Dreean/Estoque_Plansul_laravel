# Padrao Obrigatorio de Idioma e Codificacao

Este projeto deve seguir, obrigatoriamente, os seguintes padroes:

1. Todo texto de interface, mensagens de erro, avisos e notificacoes deve estar em **PT-BR**.
2. Todos os arquivos de codigo e views devem estar em **UTF-8**.
3. Nao e permitido deixar caracteres corrompidos (ex.: `Ã¡`, `Ã§`, `â€”`, `ðŸ`, `�`).
4. Evitar mensagens em ingles quando houver equivalente em portugues.

## Validacao obrigatoria

Antes de publicar, executar:

```bash
composer text:check
```

Se o comando falhar, corrigir os arquivos apontados antes do deploy.

## Frontend e CSS

Este projeto usa **um unico entrypoint de CSS** em `resources/css/app.css`.
Esse arquivo apenas organiza imports e o build do Tailwind/Vite.

### Arquitetura oficial

- `resources/css/foundation/*`
  Contem tokens, temas e regras base.
- `resources/css/components/*`
  Contem componentes reutilizaveis como botoes, campos, tabelas, paineis e modais.
- `resources/css/screens/*`
  Contem regras especificas por tela, sem duplicar componente generico.
- `resources/css/legacy/compat.css`
  Camada temporaria de compatibilidade visual. So deve receber override legado necessario para manter o visual atual.

### Regras obrigatorias para alterar frontend

1. Nao criar novo arquivo CSS fora dessa arquitetura sem justificativa tecnica clara.
2. Nao criar uma segunda fonte de verdade para tema, cor ou componente visual.
3. Nao usar `style=""` para cor, borda, padding, background ou hover recorrente.
4. `style=""` so pode ser usado para valor realmente dinamico, por exemplo largura calculada em runtime ou cor vinda do banco.
5. Antes de criar classe nova, verificar se ja existe token, componente ou classe de tela apropriada.
6. Nao alterar o visual aprovado das telas principais sem necessidade explicita.
7. Nao resolver conflito visual com override global ou `!important` sem registrar como compatibilidade temporaria.
8. Se tocar em `resources/css/*`, `resources/js/*` ou introduzir classe Tailwind nova em Blade, rodar `npm run build`.

### Regra de build

- Mudanca so em Blade/PHP, sem nova classe Tailwind e sem mexer em `resources/css/*`: **nao precisa** `npm run build`.
- Mudanca em `resources/css/*`: **precisa** `npm run build`.
- Mudanca em `resources/js/*`: **precisa** `npm run build`.
- Mudanca em Blade com classe Tailwind nova: **precisa** `npm run build`.

### Regra de deploy frontend

Ao publicar mudancas de frontend, sempre conferir:

1. `composer text:check`
2. `npm run build` quando aplicavel
3. `public/build/manifest.json`
4. assets novos referenciados pelo manifest
5. remocao de assets antigos que deixaram de ser usados
6. limpeza de cache Laravel no servidor

### Regra de resposta do agente

O agente nao deve responder "subiu" sem confirmar, no minimo:

1. arquivo Blade/CSS remoto atualizado
2. `manifest.json` remoto apontando para o asset correto
3. cache Laravel limpo

### Objetivo de manutencao

O foco do projeto e manter o visual atual bonito e consistente, reorganizando a base para facilitar manutencao futura, sem redesign desnecessario.
