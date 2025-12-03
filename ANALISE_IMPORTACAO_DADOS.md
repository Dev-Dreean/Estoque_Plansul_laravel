# 📋 ANÁLISE DE IMPORTAÇÃO - PATRIMÔNIOS PLANSUL

**Data da Análise:** 03/12/2025  
**Objetivo:** Importar apenas registros NOVOS sem sobrescrever dados existentes

---

## 📊 RESUMO DAS DIFERENÇAS

### Arquivos Comparados

| Arquivo | Linhas Antigas | Linhas Novas | Diferença | Status |
|---------|----------------|--------------|-----------|---------|
| **Patrimonio.txt** | 11.270 | 11.332 | +62 | ✓ Estrutura OK |
| **LocalProjeto.TXT** | 314 | 1.927 | +1.613 | ⚠️ GRANDE AUMENTO |
| **Projetos_tabfantasia.txt** | 877 | 879 | +2 | ✓ OK |
| **Hist_movpatr.TXT** | 337 | 4.626 | +4.289 | ⚠️ GRANDE AUMENTO |

---

## ⚠️ ATENÇÃO - MUDANÇAS IMPORTANTES DETECTADAS

### 1. Patrimônios (62 registros novos)

**Tipo de mudança:** Não são apenas registros novos — há **ATUALIZAÇÕES** de registros existentes:

#### Exemplo #3 (AUDITÓRIO):
- **ANTES:** USUARIO=RYAN, DTOPERACAO=03/10/2024, DEHISTORICO="AUDITÓRIO"
- **AGORA:** USUARIO=BEA.SC, DTOPERACAO=03/12/2025, DEHISTORICO=(vazio)
- **MUDANÇA:** Projeto mudou de 8 → 100001

#### Exemplo #38 e #45 (PRATELEIRAS):
- **ANTES:** SITUACAO="EM USO", USUARIO=TEIXEIRA (2019)
- **AGORA:** SITUACAO="BAIXA", USUARIO=BRUNO (02/12/2025)
- **INTERPRETAÇÃO:** Itens foram dados baixa recentemente

#### Padrão Geral:
- Todos os registros com `USUARIO=<null>` agora aparecem como `USUARIO=BEA.SC`
- Isso indica que houve **padronização/normalização** no banco de origem

### 2. Locais (+1.613 registros!)

**ANTES:** 314 locais  
**AGORA:** 1.927 locais  

**Estrutura mudou:**
- **Arquivo antigo:** `CDFANTASIA | DEFANTASIA | CDFILIAL | UFPROJ`
- **Arquivo novo:** `NUSEQLOCALPROJ | CDLOCAL | DELOCAL | CDFANTASIA | FLATIVO`

⚠️ **PROBLEMA:** As colunas são DIFERENTES! O script atual espera a estrutura antiga.

### 3. Histórico de Movimentações (+4.289 registros)

Crescimento de 337 → 4.626 indica muita atividade (atribuições, movimentações).

---

## 🚨 PROBLEMAS IDENTIFICADOS

### Problema 1: Script não suporta ATUALIZAÇÃO
**Status atual:** O script `import_patrimonio_completo.php`:
```php
// Linha 258: Verifica se já existe
if (Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->exists()) {
    continue; // Pular duplicatas
}
```

**Impacto:**
- ✅ Não sobrescreve registros existentes (BOM)
- ❌ Ignora atualizações de SITUACAO, USUARIO, DTOPERACAO (RUIM)
- ❌ Dos 62 registros "novos", muitos são na verdade ATUALIZAÇÕES que serão IGNORADAS

### Problema 2: Estrutura de LocalProjeto mudou
- Script espera: `CDFANTASIA, DEFANTASIA, CDFILIAL, UFPROJ`
- Arquivo tem: `NUSEQLOCALPROJ, CDLOCAL, DELOCAL, CDFANTASIA, FLATIVO`
- **Resultado:** Importação de locais vai FALHAR

### Problema 3: Não há script para histórico
- Arquivo `Hist_movpatr.TXT` tem 4.289 novos registros
- Não existe script de importação para `HistoricoMovimentacao`

---

## ✅ RECOMENDAÇÕES

### Opção 1: IMPORTAÇÃO APENAS NOVOS (Mais Segura)
**O que faz:**
- Adiciona apenas patrimônios com `NUPATRIMONIO` inexistente no banco
- Ignora atualizações de registros existentes
- Preserva 100% dos dados atuais

**Vantagens:**
- Sem risco de sobrescrever dados
- Script já pronto (`import_patrimonio_completo.php`)

**Desvantagens:**
- Perde atualizações (ex.: baixas dos patrimônios #38 e #45)
- Perde normalização de usuários (<null> → BEA.SC)

**Comando:**
```powershell
php scripts/import_patrimonio_completo.php --arquivo="storage/imports/Novo import/Patrimonio.txt"
```

---

### Opção 2: IMPORTAÇÃO COM ATUALIZAÇÃO (Recomendada)
**O que faz:**
- Adiciona patrimônios novos
- Atualiza patrimônios existentes que mudaram

**Requer:**
- Modificar script para adicionar lógica `updateOrCreate`
- Backup obrigatório antes
- Teste em ambiente local

**Lógica sugerida:**
```php
// Substituir linha 258-260 por:
$patrimonio = Patrimonio::updateOrCreate(
    ['NUPATRIMONIO' => $nupatrimonio], // Chave
    $dados // Atualiza estes campos
);
```

---

### Opção 3: IMPORTAÇÃO SELETIVA (Mais Controle)
**O que faz:**
1. Importa apenas patrimônios completamente novos
2. Gera relatório de registros que mudaram
3. Você decide manualmente quais atualizar

---

## 📝 CHECKLIST PRÉ-IMPORTAÇÃO

### Obrigatório (local):
- [ ] Fazer backup do banco de dados
  ```powershell
  php scripts/backup_database.php
  ```
- [ ] Verificar se todos os usuários existem:
  ```powershell
  php artisan tinker
  User::whereIn('NMLOGIN', ['BEA.SC', 'RYAN', 'BRUNO', 'TEIXEIRA'])->pluck('NMLOGIN');
  ```
- [ ] Verificar funcionários (matrículas 133838, 884, 80441, etc.)
- [ ] Verificar projetos (8, 100001, 522, 523)

### Recomendado:
- [ ] Criar script de dry-run (simula sem gravar)
- [ ] Testar primeiro localmente
- [ ] Validar encoding dos arquivos (UTF-8 vs ISO-8859-1)

---

## 🔧 CORREÇÕES NECESSÁRIAS NO SCRIPT

### 1. Corrigir importação de LocalProjeto
**Arquivo:** Criar `scripts/import_localprojeto.php`

**Estrutura esperada:**
```
NUSEQLOCALPROJ | CDLOCAL | DELOCAL | CDFANTASIA | FLATIVO
```

### 2. Adicionar importação de Histórico
**Arquivo:** Criar `scripts/import_historico.php`

### 3. Adicionar opção de atualização
**Arquivo:** Modificar `scripts/import_patrimonio_completo.php`
- Adicionar flag `--update` para permitir atualizações
- Por padrão manter comportamento atual (apenas novos)

---

## 🎯 PLANO DE AÇÃO SUGERIDO

### Fase 1: Local (Teste Seguro)
1. ✅ Backup do banco local
2. ✅ Executar importação apenas novos
3. ✅ Validar resultado (conferir totais)
4. ✅ Se OK, prosseguir para Fase 2

### Fase 2: Local (Atualização)
5. ⚠️ Criar script de atualização seletiva
6. ⚠️ Executar dry-run
7. ⚠️ Revisar lista de atualizações
8. ⚠️ Aplicar atualizações aprovadas

### Fase 3: Produção (KingHost)
9. 🚨 Backup do banco KingHost
10. 🚨 Upload dos arquivos de importação
11. 🚨 Executar script via SSH/Cron
12. 🚨 Validar resultado

---

## 📞 PRÓXIMOS PASSOS

**Responda qual abordagem prefere:**

**A)** "Apenas novos" (mais seguro, perde atualizações)  
   → Eu preparo o comando e você executa

**B)** "Com atualização" (mais completo, requer modificação no script)  
   → Eu crio/modifico os scripts necessários

**C)** "Quero revisar" (gero relatório detalhado das diferenças)  
   → Eu crio um CSV com todos os registros que mudaram

---

## 📌 NOTAS IMPORTANTES

1. **LocalProjeto:** Script atual NÃO funciona com arquivo novo (estrutura diferente)
2. **Histórico:** Não há script para importar (precisa criar)
3. **Usuários:** Validar se TODOS os usuários no arquivo existem no banco
4. **Encoding:** Arquivo tem caracteres especiais (ã, ô, ç) — verificar se UTF-8

---

**Status:** ⏸️ AGUARDANDO SUA DECISÃO (A, B ou C)
