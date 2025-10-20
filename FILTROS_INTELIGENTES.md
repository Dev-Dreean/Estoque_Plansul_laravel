# Sistema de Filtros Inteligentes 🔍

## Visão Geral

O `FilterService` é um sistema centralizado de busca com ordenação automática que prioriza matches exatos e resultados relevantes. Funciona em toda a aplicação de forma consistente.

## Como Funciona

### Sistema de Scoring

Cada resultado recebe uma **pontuação** baseada no grau de relevância:

| Score | Tipo | Exemplo |
|-------|------|---------|
| **0** | Match Exato | Digita "8" → encontra "8" |
| **10-99** | Começa com termo | Digita "8" → encontra "80", "81", "87" |
| **50-199** | Contém termo | Digita "8" → encontra "18", "28", "180" |
| **100-299** | Nome começa/contém | Digita "sede" → encontra "SEDE-PR" |
| **500+** | Distância Levenshtein | Resultados similares (typos, etc) |

**Menor score = Mais relevante = Aparece primeiro ✅**

---

## Uso no Backend (PHP/Laravel)

### 1. Usar FilterService em um Endpoint

```php
use App\Services\FilterService;

class MeuController extends Controller
{
    public function buscar(Request $request)
    {
        $termo = trim((string) $request->input('q', ''));

        // 1. Buscar TODOS os registros (ou um subset se for muito grande)
        $items = MeuModel::select(['id', 'codigo', 'nome'])
            ->get()
            ->toArray();

        // 2. Aplicar filtro com FilterService
        $filtrados = FilterService::filtrar(
            $items,
            $termo,
            ['codigo', 'nome'],          // campos onde buscar
            ['codigo' => 'número', 'nome' => 'texto'],  // tipos
            50  // limite de resultados
        );

        return response()->json($filtrados);
    }
}
```

### 2. Opções de Configuração

```php
FilterService::filtrar(
    $items,              // Array de items
    $termo,              // String: termo de busca
    $searchFields,       // Array: campos onde buscar (ex: ['codigo', 'nome'])
    $fieldTypes,         // Array: tipo de cada campo (ex: ['codigo' => 'número'])
    $limit               // Integer: máx resultados (padrão: 100)
);
```

### 3. Tipos de Campo Suportados

- `'número'` - Campo numérico, penaliza menos quando começa com termo
- `'texto'` - Campo texto, scoring padrão

```php
$fieldTypes = [
    'CDPROJETO' => 'número',
    'NOMEPROJETO' => 'texto',
];
```

### 4. Exemplos Reais

#### Exemplo: Buscar Projetos
```php
public function pesquisarProjetos(Request $request): JsonResponse
{
    $termo = trim($request->input('q', ''));

    $projetos = Tabfant::select(['CDPROJETO', 'NOMEPROJETO'])
        ->get()
        ->toArray();

    $filtrados = FilterService::filtrar(
        $projetos,
        $termo,
        ['CDPROJETO', 'NOMEPROJETO'],
        ['CDPROJETO' => 'número', 'NOMEPROJETO' => 'texto'],
        100
    );

    return response()->json($filtrados);
}
```

#### Exemplo: Buscar Locais
```php
public function buscarLocais(Request $request): JsonResponse
{
    $termo = trim($request->input('termo', ''));

    $locais = LocalProjeto::select(['cdlocal', 'delocal'])
        ->get()
        ->map(fn($l) => [
            'cdlocal' => $l->cdlocal,
            'delocal' => $l->delocal,
        ])
        ->toArray();

    $filtrados = FilterService::filtrar(
        $locais,
        $termo,
        ['cdlocal', 'delocal'],
        ['cdlocal' => 'número', 'delocal' => 'texto'],
        100
    );

    return response()->json($filtrados);
}
```

---

## Uso no Frontend (Alpine.js)

### 1. Componente de Busca Genérico

A lógica de busca no frontend é simples - apenas chamar a API:

```html
<div x-data="{ 
    search: '', 
    results: [],
    loading: false 
}">
    <input 
        x-model="search" 
        @input.debounce.300ms="fetch(`/api/buscar?q=${search}`)"
                    .then(r => r.json())
                    .then(d => results = d)
                    .finally(() => loading = false)"
        placeholder="Buscar..." 
    />
    
    <div x-show="results.length > 0">
        <template x-for="item in results" :key="item.id">
            <div @click="selectItem(item)">
                <span x-text="item.codigo"></span> - 
                <span x-text="item.nome"></span>
            </div>
        </template>
    </div>
</div>
```

### 2. Não é Necessário Reordenar no Frontend

A ordenação já vem pronta do backend! Não precisa:
- ❌ Reordenar resultados
- ❌ Calcular distância de Levenshtein
- ❌ Comparar strings manualmente

---

## Implementar em Nova Tela

### Checklist:

1. **Criar/Atualizar Endpoint Backend**
```php
// routes/api.php ou web.php
Route::get('/api/meu-recurso/buscar', [MeuController::class, 'buscar']);
```

2. **Implementar Função em Controller**
```php
public function buscar(Request $request): JsonResponse
{
    $termo = trim($request->input('q', ''));
    
    $items = MeuModel::select(['id', 'codigo', 'nome'])
        ->get()
        ->toArray();
    
    $filtrados = FilterService::filtrar(
        $items,
        $termo,
        ['codigo', 'nome'],
        ['codigo' => 'número', 'nome' => 'texto'],
        100
    );
    
    return response()->json($filtrados);
}
```

3. **Usar no Frontend**
```html
<input 
    x-model="search"
    @input.debounce.300ms="buscar()"
/>

<script>
    // No Alpine component
    async buscar() {
        const r = await fetch(`/api/meu-recurso/buscar?q=${this.search}`);
        this.resultados = await r.json();
    }
</script>
```

---

## Performance

### Otimizações Implementadas

✅ **Scoring inteligente**: Pára na primeira ocorrência (match exato)
✅ **Limite configurável**: Apenas retorna N resultados necessários
✅ **Sem queries adicionais**: Filtra em memória PHP (não no DB)
✅ **Caching possível**: Resultados podem ser cacheados

### Quando Usar Filtros

| Cenário | Recomendação |
|---------|-------------|
| <10mil registros | ✅ Use FilterService (simples) |
| 10mil-100mil | ⚠️ Considere adicionar LIKE no DB primeiro |
| >100mil | 🔴 Filtro na aplicação pode ser lento, use fulltext search |

---

## Exemplos de Scoring

### Teste 1: Buscar Projeto "8"
```
Termo: "8"

Resultado da busca:
✅ 8 - SEDE                      (score 0 - EXATO)
   80 - BRADESCO-PR/2            (score 12 - começa com)
   81 - BRADESCO-RS              (score 12 - começa com)
   87 - BADESC                   (score 12 - começa com)
   810 - ANS-RJ-2                (score 13 - começa com)
   180 - AGU-CE                  (score 54 - contém)
```

### Teste 2: Buscar "ser" (usuário digitando parcial)
```
Termo: "ser"

Resultado da busca:
✅ SERVIDOR-1                    (score 10 - começa com)
   SERVIDOR-2                    (score 10 - começa com)
   GESTOR-ADMINISTRATIVO         (score 53 - contém)
   PERGUNTAS-FREQUENTES          (score 80 - similaridade)
```

---

## FAQ

**P: Por que o codigo "8" não aparece primeiro quando digito "8"?**
R: Verifique que o endpoint está usando FilterService. Se estiver usando queries diretas no DB com LIKE, adicione FilterService.

**P: Preciso cachecar os resultados?**
R: Geralmente não é necessário pois o FilterService é muito rápido. Mas você pode usar Cache::remember() se quiser.

**P: Como fazer busca full-text em textos muito grandes?**
R: Para datasets muito grandes (>100mil), considere usar MySQL Full-Text Search ou Elasticsearch.

**P: Posso usar FilterService com relacionamentos?**
R: Sim! Use notação com ponto: `'projeto.codigo'`, `'usuario.nome'`

---

## API Reference

### `FilterService::filtrar()`

```php
public static function filtrar(
    $items,              // Array de items para filtrar
    string $termo,       // Termo de busca
    array $searchFields, // Campos onde buscar: ['codigo', 'nome']
    array $fieldTypes,   // Tipos: ['codigo' => 'número', 'nome' => 'texto']
    int $limit = 100     // Máximo de resultados
): array                 // Array ordenado por relevância
```

### `FilterService::ordenar()`

```php
public static function ordenar(
    array $items,        // Items para ordenar
    array $campos        // Campos: ['codigo' => 'asc', 'nome' => 'desc']
): array                 // Array ordenado
```

---

## Histórico de Implementação

| Data | Implementação |
|------|---------------|
| 20/10/2025 | FilterService criado |
| 20/10/2025 | pesquisarProjetos refatorado |
| 20/10/2025 | buscarLocais refatorado |
| 20/10/2025 | pesquisar (Patrimônio) refatorado |
| 20/10/2025 | pesquisarCodigos refatorado |
| 20/10/2025 | FuncionarioController refatorado |

---

## Contribuindo

Para adicionar filtros em nova tela:
1. Copie o padrão da função `pesquisarProjetos()`
2. Adapte `searchFields` e `fieldTypes`
3. Teste com números e textos
4. Commit com mensagem clara

---

**Desenvolvido com ❤️ para melhorar a experiência de busca da aplicação**
