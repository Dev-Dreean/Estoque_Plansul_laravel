# Padrão Obrigatório de Idioma e Codificação

Este projeto deve seguir, obrigatoriamente, os seguintes padrões:

1. Todo texto de interface, mensagens de erro, avisos e notificações deve estar em **PT-BR**.
2. Todos os arquivos de código e views devem estar em **UTF-8**.
3. Não é permitido deixar caracteres corrompidos (ex.: `Ã¡`, `Ã§`, `â€”`, `ðŸ`, `�`).
4. Evitar mensagens em inglês quando houver equivalente em português.
5. Não usar PT-BR sem acentuação em texto visível ao usuário quando a grafia correta exigir acento.
6. Sempre preferir a grafia correta em interface, por exemplo: `Solicitação`, `cotações`, `medição`, `não`, `você`, `próxima etapa`, `informações`, `descrição`.
7. É proibido concluir tarefa com texto visível sem acento por comodidade, pressa ou “padronização ASCII”.
8. Se houver dúvida entre uma forma acentuada e outra sem acento, considerar a forma sem acento como errada até revisão.
9. Essa exigência vale também para e-mails, notificações HTML, mensagens JSON exibidas na interface, validações, placeholders, títulos, botões e textos de apoio.

## Regra obrigatória para texto visível

- Sempre que alterar texto visível ao usuário, revisar acentuação antes de concluir.
- Se houver dúvida, considerar incorreto deixar a palavra sem acento.
- Essa revisão vale para Blade, PHP, e-mails, mensagens JSON, validações, logs funcionais exibidos em tela e notificações.
- Antes de encerrar qualquer tarefa com alteração textual, revisar explicitamente palavras comuns de erro: `não`, `informações`, `solicitação`, `medição`, `cotação`, `próxima`, `código`, `descrição`, `observação`, `liberação`, `concluída`, `você`.

## Validação obrigatória

Antes de publicar, executar:

```bash
composer text:check
```

Se o comando falhar, corrigir os arquivos apontados antes do deploy.

Se o comando passar, mas ainda houver texto visível sem acentuação correta, a tarefa continua incorreta e deve ser ajustada manualmente.

## Frontend e CSS

Este projeto usa **um único entrypoint de CSS** em `resources/css/app.css`.
Esse arquivo apenas organiza imports e o build do Tailwind/Vite.

### Arquitetura oficial

- `resources/css/foundation/*`
  Contém tokens, temas e regras base.
- `resources/css/components/*`
  Contém componentes reutilizáveis como botões, campos, tabelas, painéis e modais.
- `resources/css/screens/*`
  Contém regras específicas por tela, sem duplicar componente genérico.
- `resources/css/legacy/compat.css`
  Camada temporária de compatibilidade visual. Só deve receber override legado necessário para manter o visual atual.

### Regras obrigatórias para alterar frontend

1. Não criar novo arquivo CSS fora dessa arquitetura sem justificativa técnica clara.
2. Não criar uma segunda fonte de verdade para tema, cor ou componente visual.
3. Não usar `style=""` para cor, borda, padding, background ou hover recorrente.
4. `style=""` só pode ser usado para valor realmente dinâmico, por exemplo largura calculada em runtime ou cor vinda do banco.
5. Antes de criar classe nova, verificar se já existe token, componente ou classe de tela apropriada.
6. Não alterar o visual aprovado das telas principais sem necessidade explícita.
7. Não resolver conflito visual com override global ou `!important` sem registrar como compatibilidade temporária.
8. Se tocar em `resources/css/*`, `resources/js/*` ou introduzir classe Tailwind nova em Blade, rodar `npm run build`.

### Regra de build

- Mudança só em Blade/PHP, sem nova classe Tailwind e sem mexer em `resources/css/*`: **não precisa** `npm run build`.
- Mudança em `resources/css/*`: **precisa** `npm run build`.
- Mudança em `resources/js/*`: **precisa** `npm run build`.
- Mudança em Blade com classe Tailwind nova: **precisa** `npm run build`.

### Regra de deploy frontend

Ao publicar mudanças de frontend, sempre conferir:

1. `composer text:check`
2. `npm run build` quando aplicável
3. `public/build/manifest.json`
4. assets novos referenciados pelo manifest
5. remoção de assets antigos que deixaram de ser usados
6. limpeza de cache Laravel no servidor

### Regra de resposta do agente

O agente não deve responder "subiu" sem confirmar, no mínimo:

1. arquivo Blade/CSS remoto atualizado
2. `manifest.json` remoto apontando para o asset correto
3. cache Laravel limpo

### Objetivo de manutenção

O foco do projeto é manter o visual atual bonito e consistente, reorganizando a base para facilitar manutenção futura, sem redesign desnecessário.

## Regra obrigatória de novidades do sistema

Toda novidade implementada por completo e que impacte o uso do sistema deve ser registrada na comunicação de novidades para os usuários.

### Critérios obrigatórios

1. Se a entrega for uma funcionalidade nova, melhoria relevante de fluxo ou mudança importante de comportamento, ela deve entrar em `config/novidades.php`.
2. O texto da novidade deve ser escrito em linguagem de UX, explicando claramente o que mudou, para quem a novidade é importante, qual benefício prático ela traz e como o usuário deve usar a novidade.
3. Não registrar como novidade mudanças técnicas internas sem impacto real para o usuário final.
4. Não publicar novidade incompleta, vaga ou sem contexto de uso.
5. Se a entrega tiver impacto amplo, a descrição deve ser completa o suficiente para o usuário entender a mudança sem precisar pedir orientação adicional.
6. Toda novidade nova deve ficar apta a aparecer no popup de novidades para os usuários elegíveis assim que a entrega estiver pronta.
