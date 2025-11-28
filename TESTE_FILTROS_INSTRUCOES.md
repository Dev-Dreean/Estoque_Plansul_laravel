# 🔍 Instruções para Testar Filtros com Logs

## ✅ Modificações Realizadas

Foi adicionado logging detalhado em **TODOS** os filtros do controller `PatrimonioController.php`:

- **Log no início**: mostra all request params
- **Log antes de cada filtro**: mostra qual filtro está sendo testado
- **Log de sucesso**: ✅ quando o filtro é aplicado
- **Log de warning**: ⚠️  quando o valor está vazio
- **Log de SQL**: mostra a query SQL gerada com seus bindings
- **Log no index()**: mostra total de resultados, página atual, etc.

## 🚀 Passos para Testar

### 1️⃣ Abra PowerShell e monitore o log em tempo real

```powershell
cd "c:\Users\marketing\Desktop\MATRIZ - TRABALHOS\Projeto - Matriz\plansul"
Get-Content .\storage\logs\laravel.log -Wait -Tail 100
```

⚠️  **DEIXE ESSE POWERSHELL ABERTO E MONITORE**

### 2️⃣ Em outra aba/janela do PowerShell, execute um teste

**Teste 1: Filtrar por Nº Patrimônio = 2522**
```powershell
cd "c:\Users\marketing\Desktop\MATRIZ - TRABALHOS\Projeto - Matriz\plansul"
curl "http://127.0.0.1:8000/patrimonios?nupatrimonio=2522" -UseBasicParsing | Select-Object -ExpandProperty StatusCode
```

Ou abra no navegador: `http://127.0.0.1:8000/patrimonios?nupatrimonio=2522`

### 3️⃣ Volte ao PowerShell que monitora o log

Você deverá ver linhas como:

```
[2025-11-28 XX:XX:XX] local.INFO: 🏠 [INDEX] Iniciado
[2025-11-28 XX:XX:XX] local.INFO: 📍 [getPatrimoniosQuery] INICIADO
[2025-11-28 XX:XX:XX] local.INFO: 📊 [FILTROS] Antes de aplicar filtros
[2025-11-28 XX:XX:XX] local.INFO: ✅ [FILTRO] nupatrimonio aplicado (INT)
[2025-11-28 XX:XX:XX] local.INFO: 📊 [QUERY] SQL gerada
[2025-11-28 XX:XX:XX] local.INFO: 📈 [INDEX] Resultado
```

### 4️⃣ Cole aqui os logs que aparecerem

Copie as linhas do log e cole na conversa para que eu possa analisar.

---

## 📋 Testes Recomendados (na ordem)

| # | Filtro | URL |
|---|--------|-----|
| 1 | Nº Patrimônio | `http://127.0.0.1:8000/patrimonios?nupatrimonio=2522` |
| 2 | Projeto | `http://127.0.0.1:8000/patrimonios?cdprojeto=1` |
| 3 | Descrição | `http://127.0.0.1:8000/patrimonios?descricao=monitor` |
| 4 | Situação | `http://127.0.0.1:8000/patrimonios?situacao=EM%20USO` |
| 5 | Modelo | `http://127.0.0.1:8000/patrimonios?modelo=dell` |
| 6 | Termo | `http://127.0.0.1:8000/patrimonios?nmplanta=123` |
| 7 | Responsável | `http://127.0.0.1:8000/patrimonios?matr_responsavel=6817` |
| 8 | Cadastrador | `http://127.0.0.1:8000/patrimonios?cadastrado_por=AOliveira` |

---

## 🔧 Se o servidor não estiver rodando

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

---

## 💡 O que procurar nos logs

- ✅ Se vir `✅ [FILTRO]`, o filtro foi aplicado
- ⚠️  Se vir `⚠️  [FILTRO]`, o valor estava vazio
- 📊 Se vir `📊 [QUERY]`, você vê a SQL gerada
- 📈 Se vir `📈 [INDEX] Resultado`, você vê quantos itens foram retornados

Se um filtro não está funcionando, você verá linhas que mostram se o parâmetro chegou, se foi aplicado, e qual foi a SQL gerada.

---

## ⚡ Atalho: Limpar log e testar de novo

```powershell
# Vazia o log
"" | Out-File .\storage\logs\laravel.log

# Depois faça o teste novamente
```

---

**Aguardando seus logs! 👀**
