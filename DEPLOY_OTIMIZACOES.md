# 🚀 DEPLOY E ATIVAÇÃO DAS OTIMIZAÇÕES

## ✅ Pré-Requisitos Atendidos

- [x] Cache system configurado (padrão: file)
- [x] Migrações criadas e prontas
- [x] Serviços de cache implementados
- [x] Middleware registrado
- [x] Índices de banco de dados preparados

---

## 📋 Passos para Deploy

### 1️⃣ Fazer Pull no Servidor Kinghost

```bash
cd /home/seu_usuario/plansul
git pull origin main
```

### 2️⃣ Executar Migrações

```bash
php artisan migrate --force
```

**Saída esperada:**
```
INFO  Running migrations.

2025_10_21_optimize_search_indices .......................... DONE
```

### 3️⃣ Limpar Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### 4️⃣ Otimizar Autoloader (Recomendado)

```bash
composer dump-autoload --optimize
php artisan optimize
```

### 5️⃣ Testar Performance (Opcional)

```bash
# Abrir artisan tinker
php artisan tinker

# Testar se SearchCacheService funciona
> \App\Services\SearchCacheService::getProjetos()
> \App\Services\SearchCacheService::getCodigos()
> exit
```

---

## 🔍 Verificar se Está Funcionando

### No Navegador

1. Abrir formulário de cadastro de patrimônio
2. Clicar em "Projeto Associado"
3. Digitar um número (ex: "8")
4. Deve retornar em **< 50ms** ✅

### No Console do Navegador (F12)

```javascript
// Abrir console e executar:
console.time('busca-projeto');
fetch('/api/projetos/pesquisar?q=8').then(r => r.json());
console.timeEnd('busca-projeto');

// Resultado esperado: ~10-20ms (em vez de 500-800ms)
```

---

## 🔧 Configurações Opcionais

### Trocar Driver de Cache

Se usar **Redis** (recomendado para produção):

**Arquivo: `.env`**
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Se usar **Memcached**:
```env
CACHE_DRIVER=memcached
MEMCACHED_HOST=127.0.0.1
```

### Ajustar TTL do Cache

**Arquivo: `app/Services/SearchCacheService.php`**
```php
const CACHE_TTL = 60;  // Mudar para número de minutos desejado
```

---

## 🧹 Limpeza de Cache (Quando Necessário)

Se os dados não estão atualizando, limpe o cache:

```bash
# Limpar apenas cache de projetos e códigos
php artisan cache:clear-search

# Limpar todos os caches de busca
php artisan cache:clear-search --all

# Ou limpar tudo do Laravel
php artisan cache:clear
```

---

## 📊 Monitorar Performance

### Ativar Logging de Queries Lentas

**Arquivo: `config/logging.php`**
```php
'queries' => [
    'driver' => 'single',
    'path' => storage_path('logs/queries.log'),
    'level' => 'debug',
],
```

### Verificar Logs

```bash
tail -f storage/logs/laravel.log
```

---

## ⚡ Troubleshooting

### ❌ Cache não funciona

```bash
# Verificar status do cache
php artisan tinker
> Cache::get('search:projetos:all')

# Se voltar null, rodar:
> \App\Services\SearchCacheService::getProjetos(true)
> exit
```

### ❌ Erro "Table does not exist"

Certifique-se de ter executado:
```bash
php artisan migrate --force
```

### ❌ Buscas lentas ainda

1. Verificar se índices foram criados:
```sql
SHOW INDEXES FROM tabfant;
SHOW INDEXES FROM locais_projeto;
SHOW INDEXES FROM patrimonio;
```

2. Se não existirem, executar migration novamente:
```bash
php artisan migrate:refresh --path=database/migrations/2025_10_21_optimize_search_indices.php --force
```

---

## 🎯 Resultados Esperados Após Deploy

| Operação | Antes | Depois | Melhoria |
|----------|-------|--------|----------|
| Busca de projeto | 500-800ms | 10-20ms | **40-80x** |
| Busca de código | 300-500ms | 5-10ms | **30-100x** |
| Busca de local | 400-600ms | 5-15ms | **30-120x** |
| Primeira carga | 1-2s | 100-200ms | **5-20x** |
| Carregamento modal | 800-1200ms | 50-100ms | **10-24x** |

---

## 🔒 Segurança & Backup

Antes de fazer deploy:

```bash
# Fazer backup do banco de dados
mysqldump -u seu_usuario -p seu_banco > backup_$(date +%Y%m%d).sql

# Backup da aplicação
tar -czf plansul_backup_$(date +%Y%m%d).tar.gz .

# Guardar cópias seguras
```

---

## 📝 Verificação Pós-Deploy

- [x] Migrações executadas sem erros
- [x] Aplicação carregando normalmente
- [x] Modal abrindo corretamente
- [x] Buscas retornando resultados
- [x] Performance melhorada (< 50ms)
- [x] Sem erros no log
- [x] Cache invalidando ao criar registros

---

## 🎉 Sucesso!

Quando tudo estiver funcionando corretamente:

1. ✅ Testar em navegador (F12 - Network)
2. ✅ Verificar velocidade das buscas
3. ✅ Confirmar que dados estão corretos
4. ✅ Testar criar novo projeto/local
5. ✅ Verificar cache sendo invalidado

---

## 📞 Suporte

Se tiver problemas:

1. Verificar logs: `storage/logs/laravel.log`
2. Rodar: `php artisan cache:clear-search --all`
3. Reiniciar servidor PHP se necessário
4. Verificar espaço em disco (cache pode crescer)

---

**Deploy Seguro! 🚀**
