# Regras de Trabalho do Agente no Projeto Plansul

Antes de propor qualquer alteração, você DEVE:

1. **Ler contexto**  
   - Leia o `docs/agent-brief.md` e os arquivos anexados no contexto (composer.json, package.json, routes, views, providers).
   - Mostre quais arquivos foram lidos e um resumo em 3–5 linhas do que encontrou.

2. **Planejar**  
   - Liste quais arquivos serão alterados e explique em 2–3 frases o motivo.

3. **Executar**  
   - Forneça patches em formato unified diff (`--- a/arquivo` / `+++ b/arquivo`).  
   - Nunca reescreva arquivos inteiros.  
   - Não crie arquivos novos sem permissão explícita.  
   - Use apenas Blade + Tailwind. Sem jQuery novo, sem CSS inline.

4. **Testar**  
   - Indique os comandos para validar:  
     ```bash
     php artisan view:clear
     npm run dev
     ```  
   - Liste as rotas/telas a verificar.

5. **Critérios de aceite obrigatórios**  
   - Layout responsivo (360, 768, 1024, 1440).  
   - Paginação visível e não coberta pelo footer.  
   - Não alterar traduções.  
   - Manter HTTPS forçado em produção.

---

## Saída esperada
1. Resumo do que mudou.  
2. Diffs por arquivo.  
3. Passos de teste.  
4. Como reverter se necessário.
