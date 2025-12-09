# 📋 INSTRUÇÕES PARA EXECUTAR NO KINGHOST

## Resumo da Correção
- **Problema**: 2.908 patrimonios foram importados com CDLOCAL=3 (Rio Fortuna) incorretamente
- **Solução**: Mover 2.891 não-veículos para LOCAL 70 (Escritório SC)
- **Veículos mantidos em LOCAL 3**: 17 (Honda, Fiat, Volkswagen, KIA, Renault)
- **Status LOCAL**: Correção JÁ foi aplicada localmente e está validada

## 🔐 Procedimento Seguro para KingHost

### ETAPA 1: Preparação (SEM MUDANÇAS NO BANCO)

```bash
# 1. Conectar ao servidor
ssh plansul@ftp.plansul.info

# 2. Navegar para a aplicação
cd ~/www/estoque-laravel

# 3. Fazer PULL do código atualizado
php82 git pull origin main

# 4. Verificar que o script existe
ls -la scripts/producao_correcao_veiculos.php

# 5. Verificar conexão com banco
php82 artisan tinker
# Dentro do tinker:
# >>> DB::table('patr')->where('CDLOCAL', 3)->count()
# >>> exit
```

### ETAPA 2: Testar em DRY-RUN (SIMULA, NÃO ALTERA)

```bash
# Executar em modo simulação
php82 scripts/producao_correcao_veiculos.php --dry-run

# Verificar o log
tail -100 storage/logs/producao_correcao_*.log
```

**Esperado:**
- ✓ Conexão com banco OK
- ✓ 17 veículos a manter em LOCAL 3
- ✓ 2.891 não-veículos a mover para LOCAL 70
- ✓ DRY-RUN concluído

### ETAPA 3: Fazer Backup Manual (SEGURANÇA)

```bash
# Fazer dump do banco antes (ALTAMENTE RECOMENDADO)
mysqldump -u [USER] -p [DB_NAME] patr > ~/backup_patr_$(date +%Y%m%d_%H%M%S).sql
# Será pedida a senha

# Ou fazer backup via Laravel
php82 artisan backup:run --only-db
```

### ETAPA 4: EXECUTAR DE VERDADE (ALTERA O BANCO)

```bash
# ⚠️ ATENÇÃO: Este comando ALTERA o banco de dados!
php82 scripts/producao_correcao_veiculos.php

# Aguarde a conclusão...
```

### ETAPA 5: Verificar Resultado

```bash
# Ver o log completo da execução
tail -150 storage/logs/producao_correcao_*.log

# Verificar contagem no banco
php82 artisan tinker
# Dentro do tinker:
# >>> DB::table('patr')->where('CDLOCAL', 3)->count()    # Deve ser 17
# >>> DB::table('patr')->where('CDLOCAL', 70)->count()   # Deve ser 2.891+
# >>> exit

# Verificar alguns veículos específicos
php82 artisan tinker
# >>> DB::table('patr')->where('CDLOCAL', 3)->pluck('NUPATRIMONIO')->toArray()
# Deve listar: [22414, 22422, 17780, 17782, ...]
# >>> exit
```

## ✅ Checklist

- [ ] Fez PULL do repositório (git pull origin main)
- [ ] Executou em DRY-RUN e validou
- [ ] Fez backup do banco (mysqldump)
- [ ] Executou o script (SEM --dry-run)
- [ ] Verificou o log
- [ ] Confirmou contagem em LOCAL 3 = 17
- [ ] Confirmou contagem em LOCAL 70 >= 2.891
- [ ] Testou a aplicação (patrimonios aparecem certos)

## 🆘 Se algo der errado

### Opção 1: Reverter do Backup JSON
```bash
# Se o script foi interrompido, tem backup em:
ls -la storage/logs/producao_backup_*.json

# Restaurar é manual (precisa criar script de restore)
```

### Opção 2: Reverter do SQL (se fez mysqldump)
```bash
# Restaurar o dump
mysql -u [USER] -p [DB_NAME] < ~/backup_patr_YYYYMMDD_HHMMSS.sql
```

### Opção 3: Contatar suporte
- Informar data/hora da execução
- Disponibilizar o arquivo `storage/logs/producao_correcao_*.log`
- Ter o backup SQL em mãos

## 📝 Logs Importantes
- **Execução**: `storage/logs/producao_correcao_YYYY-MM-DD_HHMMSS.log`
- **Backup JSON**: `storage/logs/producao_backup_YYYY-MM-DD_HHMMSS.json`

## 🔄 Rollback Manual (se necessário)

Se precisar reverter, tem dois backups:
1. **JSON**: arquivo `producao_backup_*.json` com todos os dados originais
2. **SQL**: se fez mysqldump antes

---

**Script criado em**: 2025-12-09
**Status**: PRONTO PARA PRODUÇÃO
**Testes**: ✅ Validado localmente em dry-run
**Veículos corrigidos**: 17 mantidos em LOCAL 3
**Não-veículos corrigidos**: 2.891 movidos para LOCAL 70
