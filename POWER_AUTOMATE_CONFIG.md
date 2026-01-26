# 🔧 CONFIGURAÇÃO CORRETA DO POWER AUTOMATE

## ❌ Problema Identificado

O Power Automate está enviando campos VAZIOS:
```json
{
  "from": "",  ← VAZIO
  "subject": "",  ← VAZIO  
  "body": "corrupted data"
}
```

## ✅ Solução: Configurar Corretamente os Campos

### Passo 1: Editar a Ação HTTP no Power Automate

1. Abra seu Flow "Email para Solicitacao - Plansul"
2. Clique na ação **HTTP** (POST para https://plansul.info/api/solicitacoes/email)
3. Clique em **"Mostrar opções avançadas"**

### Passo 2: Configurar os Campos do Body

No campo **Body**, use este JSON (clique em "Alternar para modo de entrada de texto"):

```json
{
  "from": "Remetente",
  "subject": "Assunto",
  "body": "Corpo"
}
```

### Passo 3: Mapear os Conteúdos Dinâmicos

Após colar o JSON acima:

1. **Clique** no valor `"Remetente"` (mantenha as aspas!)
2. No menu de **Conteúdo dinâmico**, selecione:
   - **De** (campo From do trigger "Quando um novo email é recebido")

3. **Clique** no valor `"Assunto"`  
4. Selecione:
   - **Assunto** (campo Subject do trigger)

5. **Clique** no valor `"Corpo"`
6. Selecione:
   - **Corpo** (campo Body do trigger)

### ✅ Resultado Final Esperado

O JSON deve ficar assim (com os campos dinâmicos mapeados):

```json
{
  "from": @{triggerOutputs()?['body/from']},
  "subject": @{triggerOutputs()?['body/subject']},
  "body": @{triggerOutputs()?['body/body']}
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

## 🧪 Como Testar

1. Salve o Flow
2. Envie um email para a caixa monitorada com este conteúdo:

```
Solicitante: João Silva Teste
Matricula: 99999
Projeto: 100 - Nome do Projeto Real
UF: SP
Setor: TI
Local destino: Almoxarifado
Observacao: Teste de integração

Itens:
- Monitor 24"; 1; UN; Teste
- Mouse; 1; UN; Teste
```

3. Aguarde 30 segundos
4. Verifique se o Flow executou com sucesso (Status: **Succeeded**)

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
