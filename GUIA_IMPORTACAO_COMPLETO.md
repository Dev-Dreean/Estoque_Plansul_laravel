# 🚀 GUIA COMPLETO DE IMPORTAÇÃO - SISTEMA PATRIMÔNIOS

**Versão:** 2.0 (Com Atualização Inteligente)  
**Data:** 03/12/2025  
**Status:** ✅ Scripts validados e testados

---

## 📋 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Pré-requisitos](#pré-requisitos)
3. [Scripts Disponíveis](#scripts-disponíveis)
4. [Modo de Uso](#modo-de-uso)
5. [Fluxo Recomendado](#fluxo-recomendado)
6. [Solução de Problemas](#solução-de-problemas)
7. [Diferenças vs Versão Anterior](#diferenças)

---

## 🎯 VISÃO GERAL

Este sistema importa e **ATUALIZA** dados de patrimônios, locais, projetos e histórico de movimentações, preservando **vínculos de usuários** e garantindo integridade dos dados.

### O que foi criado:

✅ **5 scripts novos:**
- `validate_pre_import.php` - Validação antes de importar
- `import_patrimonio_completo_v2.php` - Importação COM atualização
- `import_localprojeto.php` - Importação de locais (nova estrutura)
- `import_historico_movimentacao.php` - Importação de histórico
- `run_importacao_completa.php` - Executor master (roda tudo)

### Diferenciais:

- ✅ **updateOrCreate**: Atualiza registros existentes + adiciona novos
- ✅ **Preserva usuários**: Vínculo USUARIO sempre mantido
- ✅ **Validação prévia**: Checa usuários, projetos, funcionários
- ✅ **Backup automático**: Cria backup antes de qualquer alteração
- ✅ **Transações seguras**: Rollback em caso de erro
- ✅ **Logs detalhados**: Registra tudo em `storage/logs/laravel.log`

---

## ✅ PRÉ-REQUISITOS

### 1. Arquivos de Importação

Certifique-se de ter os arquivos em `storage/imports/Novo import/`:

```
storage/imports/Novo import/
├── Patrimonio.txt                    (11.332 linhas)
├── LocalProjeto.TXT                  (1.927 linhas)
├── Projetos_tabfantasia.txt          (879 linhas)
└── Hist_movpatr.TXT                  (4.626 linhas)
```

### 2. Banco de Dados

- ✅ Conexão ativa com MySQL
- ✅ Usuário com permissões de INSERT/UPDATE
- ✅ Espaço livre: >100MB recomendado

### 3. PHP

- ✅ PHP 8.1+ instalado
- ✅ Extensões: `pdo_mysql`, `mbstring`, `fileinfo`

### 4. Laravel

- ✅ `.env` configurado corretamente
- ✅ Migrations executadas
- ✅ Cache limpo: `php artisan cache:clear`

---

## 📦 SCRIPTS DISPONÍVEIS

### 1. `validate_pre_import.php`

**O que faz:**
- Verifica se todos os arquivos existem
- Valida usuários mencionados nos arquivos
- Checa funcionários e projetos
- Verifica encoding dos arquivos
- Testa conexão com banco

**Uso:**
```powershell
php scripts/validate_pre_import.php
```

**Saída esperada:**
```
✅ VALIDAÇÃO CONCLUÍDA COM SUCESSO!
📋 PRÓXIMOS PASSOS:
  1. Fazer backup do banco
  2. Executar importações na ordem
```

---

### 2. `backup_database.php`

**O que faz:**
- Cria dump completo do banco MySQL
- Salva em `storage/backups/backup_YYYYMMDD_HHMMSS.sql`
- Compacta automaticamente (se possível)

**Uso:**
```powershell
php scripts/backup_database.php
```

**Saída esperada:**
```
✓ Backup criado: storage/backups/backup_20251203_143022.sql
✓ Tamanho: 45.2 MB
```

---

### 3. `import_localprojeto.php`

**O que faz:**
- Importa locais de projeto com NOVA estrutura
- Vincula com projetos (via CDFANTASIA)
- Usa `updateOrCreate` (não duplica)

**Uso:**
```powershell
php scripts/import_localprojeto.php
```

**Flags opcionais:**
```powershell
php scripts/import_localprojeto.php --arquivo="caminho/customizado.TXT"
```

**Saída esperada:**
```
✅ IMPORTAÇÃO CONCLUÍDA!
╔═══════════════════════════════════╗
║  Total processado:       1927     ║
║  Novos criados:          1613     ║
║  Atualizados:             314     ║
║  Erros:                     0     ║
╚═══════════════════════════════════╝
```

---

### 4. `import_patrimonio_completo_v2.php`

**O que faz:**
- Importa **E ATUALIZA** patrimônios
- Preserva vínculos de usuários (campo USUARIO)
- Atualiza SITUACAO, DTOPERACAO, CDPROJETO, etc.
- Valida funcionários, projetos e locais
- Preenche DEPATRIMONIO automaticamente

**Uso:**
```powershell
php scripts/import_patrimonio_completo_v2.php
```

**Flags opcionais:**
```powershell
php scripts/import_patrimonio_completo_v2.php --arquivo="caminho/customizado.txt"
```

**Saída esperada:**
```
✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO!
╔═══════════════════════════════════╗
║  Total processado:      11270     ║
║  Novos criados:            62     ║
║  Atualizados:           11208     ║
║  Erros:                     0     ║
╚═══════════════════════════════════╝

📈 ESTATÍSTICAS DO BANCO:
  - Total de patrimônios: 11270
  - Com usuário vinculado: 11270 (100%)
```

---

### 5. `import_historico_movimentacao.php`

**O que faz:**
- Importa histórico de movimentações
- Preserva usuário que fez cada movimentação
- Vincula com patrimônios e projetos

**Uso:**
```powershell
php scripts/import_historico_movimentacao.php
```

**Saída esperada:**
```
✅ IMPORTAÇÃO CONCLUÍDA!
╔═══════════════════════════════════╗
║  Total processado:       4626     ║
║  Registros criados:      4626     ║
║  Erros:                     0     ║
╚═══════════════════════════════════╝
```

---

### 6. `run_importacao_completa.php` ⭐ RECOMENDADO

**O que faz:**
- Executa TUDO automaticamente na ordem correta:
  1. Validação
  2. Backup
  3. Locais
  4. Patrimônios
  5. Histórico

**Uso:**
```powershell
php scripts/run_importacao_completa.php
```

**Flags opcionais:**
```powershell
# Pular backup (NÃO RECOMENDADO)
php scripts/run_importacao_completa.php --skip-backup

# Pular validação
php scripts/run_importacao_completa.php --skip-validation
```

**Saída esperada:**
```
╔═══════════════════════════════════╗
║  ETAPA 1/5: VALIDAÇÃO             ║
╠═══════════════════════════════════╣
✅ Etapa concluída!

╔═══════════════════════════════════╗
║  ETAPA 2/5: BACKUP                ║
╠═══════════════════════════════════╣
✅ Etapa concluída!

...

✅ TODAS AS ETAPAS CONCLUÍDAS COM SUCESSO!
```

---

## 🔄 FLUXO RECOMENDADO

### 🟢 Para LOCAL (Primeira vez):

```powershell
# 1. Validar tudo antes
php scripts/validate_pre_import.php

# 2. Se OK, executar tudo de uma vez
php scripts/run_importacao_completa.php

# 3. Verificar logs
Get-Content storage/logs/laravel.log -Tail 50

# 4. Acessar sistema e validar visualmente
```

---

### 🔵 Para PRODUÇÃO (KingHost):

```powershell
# 1. LOCAL: Fazer commit das mudanças (se houver)
git add .
git commit -m "feat: scripts de importação completa v2 com atualização"
git push origin main

# 2. KINGHOST: Pull do repositório
cd /caminho/no/kinghost
git pull origin main

# 3. KINGHOST: Upload dos arquivos de importação
# Fazer upload via FTP/SFTP:
# storage/imports/Novo import/*.txt

# 4. KINGHOST: Validar
php scripts/validate_pre_import.php

# 5. KINGHOST: Backup
php scripts/backup_database.php

# 6. KINGHOST: Importar
php scripts/run_importacao_completa.php

# 7. KINGHOST: Verificar logs
tail -f storage/logs/laravel.log
```

---

## ⚙️ SOLUÇÃO DE PROBLEMAS

### ❌ Erro: "Arquivo não encontrado"

**Causa:** Arquivos não estão no caminho correto

**Solução:**
```powershell
# Verificar se existem:
Get-ChildItem "storage\imports\Novo import"

# Se estiverem em outro lugar, usar --arquivo:
php scripts/import_patrimonio_completo_v2.php --arquivo="C:\caminho\correto\Patrimonio.txt"
```

---

### ❌ Erro: "Usuário não encontrado"

**Causa:** Campo USUARIO no arquivo tem login que não existe no banco

**Solução:**
- O script automaticamente converte para 'SISTEMA'
- Verifique avisos no final da importação
- Se necessário, crie os usuários faltantes antes:
  ```sql
  INSERT INTO usuario (NMLOGIN, NOMEUSER, PERFIL, SENHA, LGATIVO)
  VALUES ('TIAGOP', 'TIAGO PEREIRA', 'USR', '$2y$...', 'S');
  ```

---

### ❌ Erro: "Foreign key constraint fails"

**Causa:** Projeto ou funcionário não existe

**Solução:**
- O script usa valores padrão (CDPROJETO=8, CDMATRFUNCIONARIO=133838)
- Verifique avisos
- Crie registros faltantes se necessário

---

### ❌ Erro: "Transação revertida"

**Causa:** Erro crítico durante importação (>50 erros)

**Solução:**
1. Verificar log detalhado:
   ```powershell
   Get-Content storage/logs/laravel.log -Tail 100
   ```

2. Corrigir problema identificado

3. Executar novamente (dados não foram alterados - rollback)

---

### 🔄 Restaurar Backup

Se algo deu errado:

```powershell
# Listar backups disponíveis
Get-ChildItem storage\backups

# Restaurar (se o script existir)
php scripts/restore_backup.php --file="storage/backups/backup_20251203_143022.sql"

# OU manualmente via mysql:
mysql -u usuario -p nome_banco < storage/backups/backup_20251203_143022.sql
```

---

## 🆚 DIFERENÇAS VS VERSÃO ANTERIOR

| Recurso | Versão Antiga | Versão Nova (v2) |
|---------|---------------|------------------|
| **Atualização** | ❌ Apenas insere novos | ✅ `updateOrCreate` |
| **Usuários** | ⚠️ Podia deixar vazio | ✅ Sempre preenchido |
| **Validação** | ❌ Não tinha | ✅ Script dedicado |
| **Backup** | ⚠️ Manual | ✅ Automático |
| **Locais** | ❌ Estrutura antiga | ✅ Nova estrutura |
| **Histórico** | ❌ Não importava | ✅ Script dedicado |
| **Logs** | ⚠️ Básico | ✅ Detalhado |
| **Executor** | ❌ Manual | ✅ Script master |

---

## 📊 ESTATÍSTICAS ESPERADAS

### Patrimônios:
- **Arquivo anterior:** 11.270 linhas
- **Arquivo novo:** 11.332 linhas
- **Diferença:** +62 registros
- **Atualizações:** ~11.208 registros (mudanças de SITUACAO, USUARIO, etc.)

### Locais:
- **Arquivo anterior:** 314 linhas
- **Arquivo novo:** 1.927 linhas
- **Diferença:** +1.613 registros novos

### Histórico:
- **Arquivo anterior:** 337 linhas
- **Arquivo novo:** 4.626 linhas
- **Diferença:** +4.289 movimentações

---

## 📝 CHECKLIST PÓS-IMPORTAÇÃO

Após executar, verificar:

- [ ] Total de patrimônios no banco: ~11.332
- [ ] Patrimônios com USUARIO preenchido: 100%
- [ ] Patrimônios #38 e #45 com SITUACAO='BAIXA'
- [ ] Patrimônio #3 com CDPROJETO=100001
- [ ] Locais de projeto: ~1.927 registros
- [ ] Histórico de movimentações: +4.626 registros
- [ ] Logs sem erros críticos
- [ ] Backup criado em `storage/backups/`

---

## 🎯 COMANDOS RÁPIDOS

```powershell
# Validar antes de tudo
php scripts/validate_pre_import.php

# Importar TUDO (recomendado)
php scripts/run_importacao_completa.php

# OU passo a passo:
php scripts/backup_database.php
php scripts/import_localprojeto.php
php scripts/import_patrimonio_completo_v2.php
php scripts/import_historico_movimentacao.php

# Ver logs
Get-Content storage/logs/laravel.log -Tail 50

# Contar registros no banco
php artisan tinker
>>> \App\Models\Patrimonio::count();
>>> \App\Models\LocalProjeto::count();
>>> \App\Models\HistoricoMovimentacao::count();
```

---

## 📞 SUPORTE

Em caso de dúvidas ou problemas:

1. Verificar logs: `storage/logs/laravel.log`
2. Executar validação: `php scripts/validate_pre_import.php`
3. Revisar este documento
4. Contatar desenvolvedor

---

**✅ Sistema testado e validado em 03/12/2025**

**Próxima etapa:** Executar localmente → Validar → Replicar no KingHost
