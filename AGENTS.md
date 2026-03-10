# Padrão Obrigatório de Idioma e Codificação

Este projeto deve seguir, obrigatoriamente, os seguintes padrões:

1. Todo texto de interface, mensagens de erro, avisos e notificações deve estar em **PT-BR**.
2. Todos os arquivos de código e views devem estar em **UTF-8**.
3. Não é permitido deixar caracteres corrompidos (ex.: `Ã¡`, `Ã§`, `â€”`, `ðŸ`, `�`).
4. Evitar mensagens em inglês quando houver equivalente em português.

## Validação obrigatória

Antes de publicar, executar:

```bash
composer text:check
```

Se o comando falhar, corrigir os arquivos apontados antes do deploy.
