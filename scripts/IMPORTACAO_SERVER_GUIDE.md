# 📋 IMPORTAÇÃO DE PATRIMÔNIOS - GUIA DE DEPLOYMENT

## 🚀 Resumo Executivo

Este guia fornece instruções passo a passo para executar a importação de patrimônios no servidor de produção (Kinghost).

**Status Atual:**
- ✅ Script testado localmente: 11.236 patrimônios importados com sucesso
- ✅ Zero erros, 74.2% com descrição preenchida
- ✅ Backup automático criado antes da importação

---

## 📁 Estrutura de Arquivos

```
scripts/
├── import_patrimonio_completo.php      ← Script principal de importação
├── backup_database.php                  ← Script de backup (executado automaticamente)
├── config-import.php                    ← Configurações de caminho (NOVO)
├── PathDetector.php                     ← Detecção automática de caminho (NOVO)
├── IMPORTACAO_SERVER_GUIDE.md          ← Este arquivo
├── test_atribuir.php                    ← Verificação pós-importação
└── README_RUN_CHECKS.md                ← Verificações de saúde
```

---

## 🖥️ INSTRUÇÕES PARA SERVIDOR KINGHOST

### Pré-requisitos

- ✅ Acesso SSH ao servidor (usuário: `plansul`)
- ✅ PHP 8.0+ com extensões: `mb_string`, `json`, `pdo_mysql`
- ✅ Composer instalado
- ✅ Arquivo `patrimonio.TXT` pronto para upload
- ✅ Backup do banco de dados existente

### Passo 1: Preparar o Servidor

#### 1.1 Conectar via SSH
```bash
ssh plansul@ftp.plansul.info
# Ou via seu cliente SSH preferido
```

#### 1.2 Navegar até o projeto
```bash
cd /home/plansul/public_html  # ou seu caminho específico
cd plansul                      # Ou o diretório do projeto
```

#### 1.3 Criar diretório para arquivos de importação
```bash
mkdir -p "Subir arquivos Kinghost"
chmod 755 "Subir arquivos Kinghost"
```

### Passo 2: Enviar Arquivo patrimonio.TXT

#### Opção A: Via SFTP (Recomendado)

Usando WinSCP, Filezilla, ou similar:
```
Servidor: ftp.plansul.info
Usuário: plansul
Caminho remoto: /home/plansul/public_html/plansul/Subir arquivos Kinghost/
Arquivo: patrimonio.TXT
```

#### Opção B: Via SCP (Linha de Comando)

Do seu PC (PowerShell):
```powershell
$arquivo = "C:\Users\marketing\Desktop\Subir arquivos Kinghost\patrimonio.TXT"
$destino = "plansul@ftp.plansul.info:/home/plansul/public_html/plansul/Subir arquivos Kinghost/"

scp -r $arquivo $destino
```

#### Opção C: Via SSH (Upload direto)

No servidor:
```bash
cd "Subir arquivos Kinghost"
nano patrimonio.TXT  # Cole o conteúdo do arquivo
# Ctrl+X, Y, Enter para salvar
```

### Passo 3: Verificar Caminho (Importante!)

#### No servidor, confirme o caminho:
```bash
ls -lah "Subir arquivos Kinghost/patrimonio.TXT"
```

Você deve ver:
```
-rw-r--r-- 1 plansul plansul 10.2M Nov 28 12:00 patrimonio.TXT
```

#### Se o arquivo está em outro local, edite config-import.php:

```bash
nano scripts/config-import.php
```

Localize a seção `'source_paths' => ['server' => [...]` e adicione seu caminho real, por exemplo:

```php
'server' => [
    '/home/plansul/patrimonio.TXT',  // ← Seu caminho real
    '/home/plansul/public_html/plansul/Subir arquivos Kinghost/patrimonio.TXT',
]
```

### Passo 4: Criar Backup (Crítico!)

```bash
php artisan tinker
> DB::table('PATR')->count();
// Deve mostrar o total de patrimônios atuais

> exit
```

Executar backup manualmente:
```bash
php scripts/backup_database.php
```

Você verá:
```
=== BACKUP DO BANCO DE DADOS ===
✅ Backup criado em: storage/backups/patrimonio_backup_2025_11_28_120000.json
📊 Registros exportados: 952
💾 Tamanho do arquivo: 0.8 MB
```

### Passo 5: Executar Importação

```bash
php scripts/import_patrimonio_completo.php
```

#### Saída esperada:
```
=== IMPORTAÇÃO COMPLETA DE PATRIMÔNIOS ===
Data: 28/11/2025 12:05:30

🔍 Detectando ambiente...
📍 Ambiente: SERVER
🗂️  Procurando arquivo patrimonio.TXT...

   [1] Testando: /home/plansul/Subir arquivos Kinghost/patrimonio.TXT
✅ ARQUIVO ENCONTRADO!
   Caminho: /home/plansul/Subir arquivos Kinghost/patrimonio.TXT
   Tamanho: 10.2 MB

📄 Arquivo encontrado
📊 Analisando arquivo...
🔄 Convertendo encoding de ISO-8859-1 para UTF-8...

✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO!
   Total: 11.236 patrimônios
   Com Descrição: 8.336 (74.2%)
   Disponíveis: 11.236 (100%)
   Erros: 0
   
💾 Backup: storage/backups/patrimonio_backup_2025_11_28_120530.json
```

### Passo 6: Limpar Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Passo 7: Verificação Final

```bash
php scripts/test_atribuir.php
```

Esperado:
```
✅ Total de patrimônios disponíveis: 11.236
✅ Patrimônios 17483, 6817, 22502: ENCONTRADOS ✓
```

---

## ⚙️ CONFIGURAÇÃO AUTOMÁTICA DE CAMINHO

O script detecta automaticamente o ambiente através de:

1. **Variável de ambiente** `IMPORT_ENV`
   ```bash
   export IMPORT_ENV=server
   php scripts/import_patrimonio_completo.php
   ```

2. **Detecção automática** baseada no caminho do projeto
   - Se em `/home/plansul` → Ambiente: SERVER
   - Se em `C:\Users` → Ambiente: LOCAL

3. **Argumento de linha de comando** (prioridade máxima)
   ```bash
   php scripts/import_patrimonio_completo.php --arquivo="/caminho/completo/patrimonio.TXT"
   ```

---

## 🔄 Se Algo Deu Errado

### Erro: "Arquivo não encontrado"

```
❌ ERRO: Arquivo não encontrado: /home/plansul/Subir arquivos Kinghost/patrimonio.TXT
```

**Solução:**
```bash
# 1. Verificar onde o arquivo realmente está
find /home/plansul -name "patrimonio.TXT" -type f

# 2. Editar config-import.php com o caminho correto
nano scripts/config-import.php

# 3. Ou executar com argumento
php scripts/import_patrimonio_completo.php --arquivo="/caminho/encontrado/patrimonio.TXT"
```

### Erro: "Encoding" ou "Caracteres especiais"

```
Incorrect string value: '\xC1 DISP...'
```

**Solução:** O script detecta automaticamente, mas se persistir:
```bash
# Converter arquivo para UTF-8 manualmente
iconv -f ISO-8859-1 -t UTF-8 "patrimonio.TXT" > "patrimonio_utf8.TXT"
mv "patrimonio_utf8.TXT" "patrimonio.TXT"
```

### Erro: "Banco de dados travou"

**Solução:** Aguardar alguns minutos, depois reiniciar MySQL:
```bash
# Contato com suporte Kinghost para reiniciar MySQL
# Ou execute de novo
php scripts/import_patrimonio_completo.php
```

### Rollback (Desfazer Importação)

Se detectar problemas após importação:

```bash
# 1. Listar backups disponíveis
ls -lah storage/backups/

# 2. Restaurar backup
php scripts/restore_backup.php --file="patrimonio_backup_2025_11_28_120530.json"
```

---

## 📊 Monitoramento Pós-Importação

### Verificar Patrimônios no Banco

```bash
php artisan tinker
> App\Models\Patrimonio::count();
// Deve retornar: 11236

> App\Models\Patrimonio::where('DEPATRIMONIO', '<>', '')->count();
// Deve retornar: ~8336

> App\Models\Patrimonio::whereIn('NUPATRIMONIO', [17483, 6817, 22502])->get();
// Deve encontrar os 3 patrimônios
```

### Acessar Tela de Atribuição

No navegador:
```
https://seu-dominio.com.br/patrimonios/atribuir
```

Verificar:
- ✅ Todos os ~11k patrimônios listados (com paginação)
- ✅ Patrimônios 17483, 6817, 22502 localizáveis via busca
- ✅ Descrição exibida corretamente (74.2% preenchidas)

---

## 🔐 Segurança & Boas Práticas

### ✅ Fazer Sempre

- [ ] Executar backup ANTES de importar
- [ ] Testar em ambiente de staging primeiro
- [ ] Validar arquivo patrimonio.TXT antes
- [ ] Criar ponto de recuperação no banco

### ❌ Nunca

- Editar config-import.php sem backup
- Executar múltiplas importações sem verificar duplicatas
- Deletar arquivo patrimonio.TXT até confirmar sucesso
- Executar durante horário de pico de acesso

---

## 📞 Suporte & Contato

Se encontrar problemas:

1. **Verificar logs:**
   ```bash
   tail -100 storage/logs/laravel.log
   tail -100 storage/logs/imports/*
   ```

2. **Enviar para suporte:**
   - Arquivo: `storage/logs/laravel.log` (últimas 50 linhas)
   - Arquivo: `storage/backups/` (backup mais recente)
   - Screenshot do erro

---

## 📝 Histórico de Execução

Cada importação fica registrada em:
```
storage/backups/patrimonio_backup_YYYY_MM_DD_HHMMSS.json
storage/logs/imports/patrimonio_import_YYYY_MM_DD.log
```

Consultar histórico:
```bash
ls -lah storage/backups/
ls -lah storage/logs/imports/
```

---

**Última atualização:** 28 de Novembro de 2025  
**Versão:** 2.0 (Com detecção automática de caminho)  
**Status:** ✅ Pronto para Produção

