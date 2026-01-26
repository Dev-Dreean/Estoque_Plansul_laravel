# 🔧 CONFIGURAÇÃO POWER AUTOMATE - MÉTODO CORRETO

## ⚠️ ERRO COMUM QUE VOCÊ PODE ESTAR COMETENDO

**❌ ERRADO:** Escrever manualmente as expressions no JSON:
```json
{"from":"@{triggerOutputs()?['body/from']}","subject":"..."}
```
☝️ **Isso NÃO funciona! O Power Automate não vai interpretar as expressions!**

**✅ CORRETO:** Usar o modo visual e mapear campos dinâmicos.

---

## 📋 PASSO A PASSO CORRETO

### **1️⃣ Configurar o Trigger**

1. Trigger: **"Quando um novo email é recebido (V3)"**
2. Pasta: **Inbox** (ou pasta específica que você monitora)
3. **NÃO** use filtro de assunto (pode causar problemas)

---

### **2️⃣ Configurar a Ação HTTP**

1. Adicione ação: **HTTP**
2. Configure:
   - **Method:** POST
   - **URI:** `https://plansul.info/api/solicitacoes/email`

3. **Headers** (adicione 2 headers):
   ```
   Content-Type: application/json
   X-API-KEY: 3a7f9e2c5b8d1a4f7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e
   ```

---

### **3️⃣ Configurar o Body (CRÍTICO - SIGA EXATAMENTE)**

**MÉTODO 1 - Recomendado (Modo Visual):**

1. No campo **Body**, clique no ícone de **{}** (Adicionar conteúdo dinâmico)
2. Clique em **"Expression"** (no topo do painel)
3. Cole esta expression:

```javascript
json(concat('{"from":"', replace(triggerOutputs()?['body/from'], '"', '\\"'), '","subject":"', replace(triggerOutputs()?['body/subject'], '"', '\\"'), '","body":"', replace(replace(triggerOutputs()?['body/body'], '"', '\\"'), char(10), '\\n'), '"}'))
```

4. Clique em **OK**

**MÉTODO 2 - Alternativo (Estrutura simples):**

Se o Método 1 der erro, use esta estrutura simples:

1. No campo **Body**, digite:
```
{
```
2. Pressione ENTER
3. Digite: `"from": "`
4. Clique no ícone **⚡** (Conteúdo dinâmico)
5. Selecione: **De** (do trigger)
6. Continue digitando: `",`
7. ENTER e digite: `"subject": "`
8. Clique **⚡** e selecione: **Assunto**
9. Continue: `",`
10. ENTER e digite: `"body": "`
11. Clique **⚡** e selecione: **Corpo**
12. Finalize: `"`
13. ENTER e digite: `}`

O resultado deve parecer com:
```json
{
"from": [De - ícone dinâmico],
"subject": [Assunto - ícone dinâmico],
"body": [Corpo - ícone dinâmico]
}
```

---

## 📋 Checklist de Configuração

- [ ] Trigger: "Quando um novo email é recebido (V3)"
- [ ] Pasta monitorada: **Inbox** ou pasta específica
- [ ] Ação HTTP configurada com:
  - [ ] Method: **POST**
  - [ ] URI: **https://plansul.info/api/solicitacoes/email**
  - [ ] Headers:
    - [ ] `Content-Type`: `application/json`
    - [ ] `X-API-KEY`: `3a7f9e2c5b8d1a4f7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e`
  - [ ] Body: JSON com campos dinâmicos mapeados (conforme acima)

---

---

### **4️⃣ VERIFICAR SE ESTÁ CORRETO**

Após configurar, clique em **"Código"** (View code) na ação HTTP.

**✅ DEVE parecer com isto:**
```json
{
  "inputs": {
    "method": "POST",
    "uri": "https://plansul.info/api/solicitacoes/email",
    "headers": {
      "Content-Type": "application/json",
      "X-API-KEY": "3a7f9e2c5b8d1a4f7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e"
    },
    "body": {
      "from": "@{triggerOutputs()?['body/from']}",
      "subject": "@{triggerOutputs()?['body/subject']}",
      "body": "@{triggerOutputs()?['body/body']}"
    }
  }
}
```
---

## 🐛 TROUBLESHOOTING

### ❌ **Erro: "Campos vazios" (from="", subject="", body="")**

**Causa:** Conteúdos dinâmicos não foram mapeados

**Solução:**
1. Delete a ação HTTP e crie novamente
2. Use o **Método 2** (estrutura simples) descrito acima
3. Certifique-se de clicar no ⚡ para adicionar campos dinâmicos
4. NÃO digite manualmente `@{triggerOutputs()...}` - isso não funciona!

---

### ❌ **Erro: 401 Unauthorized**

**Causa:** Token incorreto

**Solução:**
- Copie o token novamente (pode ter espaço extra):
  ```
  3a7f9e2c5b8d1a4f7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e
  ```

---

### ❌ **Erro: 422 "Missing required fields"**

**Causa:** Dados do email estão incompletos ou projeto não existe

**Solução:**
1. Verifique se o projeto com o código informado EXISTE no banco
2. Use um CDPROJETO válido (não use 1234 se não existir)
3. Verifique se "Local destino" está preenchido

**Como verificar projeto válido:**
```bash
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && php82 artisan db 'SELECT id, CDPROJETO, NOMEPROJETO FROM tabfant LIMIT 10;'"
```

---

### ❌ **Flow não executa automaticamente**

**Causa:** Email não chegou na pasta monitorada

**Solução:**
1. Verifique se o email está na pasta **Inbox**
2. Aguarde até 5 minutos (Power Automate tem delay)
3. Execute manualmente: Clique em "Testar" → "Manualmente

Itens:
- Monitor 24"; 1; UN; Teste
- Mouse; 1; UN; Teste
```

### **Passo 2: Verificar Execução**

1. Aguarde 30-60 segundos
2. Abra o Power Automate
3. Vá em **"Meus fluxos"**
4. Clique no seu flow
5. Clique na última execução

### **Passo 3: Verificar os Inputs**

Na execução, expanda a ação **HTTP** e veja os **Inputs**:

**✅ CORRETO - Deve mostrar:**
```json
{
  "from": "seuemail@empresa.com",
  "subject": "Solicitacao de Bem",
  "body": "Solicitante: João Silva Teste\nMatricula: 99999..."
}
```

**❌ ERRADO - Se mostrar:**
```json
{
  "from": "",
  "subject": "",
  "body": ""
}
```
☝️ Significa que os campos dinâmicos não foram mapeados!

---

## 🐛 Troubleshooting

### Erro: "Missing required fields"

**Causa:** Campos `from`, `subject` ou `body` ainda estão vazios

**Solução:**
1. Verifique se os conteúdos dinâmicos foram mapeados corretamente
2. Teste o trigger enviando um email simples
3. Clique em "Executar novamente" e verifique os **Inputs** da ação HTTP

### Erro: "Unauthorized" (401)

**Causa:** Token incorreto no header X-API-KEY

**Solução:**
- Verifique se o token é exatamente: `3a7f9e2c5b8d1a4f7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e`

### Erro: "Empty payload"

**Causa:** Nenhum campo chegou no servidor

**Solução:**
1. Verifique se o **Content-Type** está como `application/json`
2. Verifique se o Body está no formato JSON válido
3. Não use "Corpo (HTML)" - use apenas "Corpo"

---

## 📊 Verificar Logs no Servidor

Para ver o que está chegando no servidor:

```bash
ssh plansul@ftp.plansul.info "tail -100 ~/www/estoque-laravel/storage/logs/laravel.log | grep POWER_AUTOMATE"
```

Os logs vão mostrar:
- 🚀 **Requisição recebida** com todos os campos
- 📧 **Campos extraídos** do payload
- ✅ **Solicitação criada com sucesso** (se tudo funcionou)

---

## ✅ Sucesso!

Se tudo estiver correto, você verá:

```json
{
  "success": true,
  "message": "Solicitacao registrada com sucesso.",
  "solicitacao_id": 123
}
```

E a solicitação aparecerá em: https://plansul.info/solicitacoes

---

**Data:** 26/01/2026
**Status:** ✅ Pronto para configuração
