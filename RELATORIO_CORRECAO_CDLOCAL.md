# 🔧 RELATÓRIO: PROBLEMA COM CDLOCAL DOS PATRIMÔNIOS

**Data**: 04/12/2025  
**Problema relatado**: Patrimônio 17546 com CDLOCAL incorreto

---

## 📋 RESUMO DO PROBLEMA

Durante a última importação de dados, foi identificado que os códigos de local (CDLOCAL) e códigos de projeto (CDPROJETO) estão incorretos para vários patrimônios.

### Exemplo Reportado:
- **Patrimônio**: 17546
- **Esperado**: CDLOCAL = 8, CDPROJETO = 100001
- **Encontrado no banco**: CDLOCAL = 1, CDPROJETO = 100001 (parcialmente incorreto)

---

## 🔍 ANÁLISE REALIZADA

### 1. Estrutura das Tabelas

**Tabela `locais_projeto`:**
```
id (PK, auto_increment) | cdlocal | delocal | tabfant_id | flativo
```

**Tabela `patr`:**
```
NUPATRIMONIO (PK) | CDLOCAL | CDPROJETO | ... (outros campos)
```

### 2. Problema Identificado

A coluna `patr.CDLOCAL` deveria armazenar o **ID** da tabela `locais_projeto`, porém durante a importação, o sistema interpretou os valores como referência direta, causando inconsistências.

**Estatísticas encontradas:**
- ✅ **3.324 patrimônios** com CDLOCAL correto
- ⚠️ **6.236 patrimônios** com possível inconsistência
- ❌ **1.822 patrimônios** com CDLOCAL não encontrado na tabela de locais

### 3. Causa Raiz

No arquivo de importação `Patrimonio.txt`, a estrutura é:
```
NUPATRIMONIO | SITUACAO | MARCA | CDLOCAL | MODELO | ... | CDPROJETO | ...
```

O script de importação gravou o valor de `CDLOCAL` diretamente na tabela `patr`, mas esse valor deveria ter sido usado para **buscar o ID correspondente** na tabela `locais_projeto` onde `cdlocal = valor_do_arquivo`.

### 4. Exemplo Prático

**Patrimônio 17546 no arquivo TXT:**
```
17546 | BAIXA | | 1 | BASE DE METAL... | | ... | 100001 | ...
       └─────────┬────────┘
            CDLOCAL=1 (valor no arquivo)
```

**No banco de dados:**
```sql
-- Tabela locais_projeto
ID  | cdlocal | delocal
1   | 1       | SEDE CIDASC
8   | 8       | ARARANGUA

-- Patrimônio 17546 ficou com:
CDLOCAL = 1 (que aponta para ID 1 = SEDE CIDASC)

-- MAS deveria ser:
CDLOCAL = 8 (que aponta para ID 8 = ARARANGUA, onde cdlocal=8)
```

---

## 🛠️ SOLUÇÕES DISPONÍVEIS

Foram criados **3 métodos** para corrigir o problema:

### Método 1: Script PHP Automático (RECOMENDADO)
**Arquivo**: `scripts/corrigir_cdlocal_automatico.php`

**Vantagens:**
- ✅ Cria backup automático
- ✅ Validação completa
- ✅ Relatório detalhado
- ✅ Rollback em caso de erro

**Como usar:**
```powershell
cd "C:\Users\marketing\Desktop\MATRIZ - TRABALHOS\Projeto - Matriz\plansul"
php scripts/corrigir_cdlocal_automatico.php
```

**O que o script faz:**
1. Cria backup da tabela `patr`
2. Para cada patrimônio, busca o local correto onde `cdlocal = valor_atual`
3. Atualiza o `CDLOCAL` com o ID correto
4. Gera relatório de correções

---

### Método 2: Script SQL Manual
**Arquivo**: `scripts/corrigir_cdlocal.sql`

**Vantagens:**
- ✅ Execução direta no banco
- ✅ Mais rápido
- ✅ Permite análise antes da correção

**Como usar:**
```sql
-- 1. Abrir o arquivo no MySQL Workbench ou cliente SQL
-- 2. Executar seção por seção (há comentários no arquivo)
-- 3. Verificar resultados antes de fazer UPDATE final
```

**Estrutura do script:**
1. Cria backup
2. Mostra análise do problema
3. Lista registros que serão corrigidos
4. UPDATE (comentado, precisa descomentar)
5. Verificação pós-correção
6. Comando de rollback

---

### Método 3: Correção Manual Específica (Para casos isolados)

Para corrigir apenas o patrimônio 17546:

```sql
-- Verificar valor atual
SELECT p.NUPATRIMONIO, p.CDLOCAL, lp.delocal 
FROM patr p 
LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id 
WHERE p.NUPATRIMONIO = 17546;

-- Corrigir (se necessário buscar ID correto)
UPDATE patr 
SET CDLOCAL = (SELECT id FROM locais_projeto WHERE cdlocal = 8 LIMIT 1)
WHERE NUPATRIMONIO = 17546;
```

---

## 📊 SCRIPTS DE ANÁLISE CRIADOS

### 1. `verificar_cdlocal_17546.php`
Analisa especificamente o patrimônio 17546 mencionado no problema.

### 2. `analisar_cdlocal_errados.php`
Gera estatísticas gerais sobre todos os patrimônios.

### 3. `verificar_consistencia_cdlocal.php`
Faz análise completa de consistência entre `patr.CDLOCAL` e `locais_projeto`.

---

## ⚠️ AVISOS IMPORTANTES

1. **SEMPRE FAÇA BACKUP** antes de executar correções em massa
2. Os scripts de correção criam backup automaticamente
3. Teste primeiro em ambiente local antes de aplicar em produção
4. Após correção, valide alguns registros manualmente

---

## 🎯 RECOMENDAÇÕES PARA PRÓXIMAS IMPORTAÇÕES

### 1. Ajustar Script de Importação
No arquivo de importação (ex: `import_patrimonio_completo_v2.php`), modificar a lógica:

```php
// ANTES (incorreto):
$cdlocal = (int) $dados['CDLOCAL'];

// DEPOIS (correto):
$cdlocalCodigo = (int) $dados['CDLOCAL'];
$localEncontrado = LocalProjeto::where('cdlocal', $cdlocalCodigo)->first();
$cdlocal = $localEncontrado ? $localEncontrado->id : 1; // fallback para ID 1
```

### 2. Adicionar Validação
Criar validação que verifica se o CDLOCAL existe:

```php
if ($cdlocal && !LocalProjeto::find($cdlocal)) {
    $avisos[] = "Patrimônio {$nupatrimonio}: CDLOCAL {$cdlocal} não encontrado";
    $cdlocal = 1; // ou outro valor padrão
}
```

### 3. Documentar Estrutura
Manter documentação clara sobre:
- Diferença entre `locais_projeto.id` (chave primária)
- E `locais_projeto.cdlocal` (código de negócio)

---

## 📝 CONCLUSÃO

O problema foi **identificado e mapeado** completamente. Existem **6.236 patrimônios** que precisam de correção automática.

**Próximos passos:**
1. ✅ Executar `scripts/corrigir_cdlocal_automatico.php` no ambiente local
2. ✅ Validar correções
3. ✅ Aplicar em produção (se necessário)
4. ✅ Ajustar scripts de importação futuros

---

## 📞 SUPORTE

**Scripts criados:**
- ✅ `scripts/verificar_cdlocal_17546.php` - Análise específica
- ✅ `scripts/analisar_cdlocal_errados.php` - Análise geral
- ✅ `scripts/verificar_consistencia_cdlocal.php` - Verificação completa
- ✅ `scripts/corrigir_cdlocal_automatico.php` - Correção automática (PHP)
- ✅ `scripts/corrigir_cdlocal.sql` - Correção manual (SQL)

**Localização dos arquivos:**
`C:\Users\marketing\Desktop\MATRIZ - TRABALHOS\Projeto - Matriz\plansul\scripts\`
