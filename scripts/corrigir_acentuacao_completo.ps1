# Script para corrigir acentuação em todos os arquivos Blade
# Correção completa para português brasileiro (ABNT)

$rootPath = "c:\Users\marketing\Desktop\DEVELOPER - WORKS\MATRIZ - TRABALHOS\Projeto - Matriz\plansul\resources\views"

# Palavras a corrigir (apenas textos visíveis, não atributos HTML ou variáveis)
$replacements = @{
    # Em LABELS e TEXTOS VISÍVEIS
    '([>"\s])Numero([<"\s])' = '$1Número$2'
    '([>"\s])Situacao([<"\s])' = '$1Situação$2'
    '([>"\s])Descricao([<"\s])' = '$1Descrição$2'
    '([>"\s])Codigo([<"\s])' = '$1Código$2'
    '([>"\s])Observacao([<"\s])' = '$1Observação$2'
    '([>"\s])Operacao([<"\s])' = '$1Operação$2'
    '([>"\s])Historico([<"\s])' = '$1Histórico$2'
    '([>"\s])Relatorio([<"\s])' = '$1Relatório$2'
    '([>"\s])Acoes([<"\s])' = '$1Ações$2'
    
    # Frases específicas
    'Nova solicitacao' = 'Nova solicitação'
    'numero do patrimonio' = 'número do patrimônio'
    'numero de patrimonio' = 'número de patrimônio'
    'gerar relatorio' = 'gerar relatório'
    'Relatorio por' = 'Relatório por'
    'este patrimonio' = 'este patrimônio'
    'Escolha a situacao' = 'Escolha a situação'
    
    # Comentários HTML
    '<!-- Situacao -->' = '<!-- Situação -->'
    '<!-- Observacao -->' = '<!-- Observação -->'
    '<!-- SECAO' = '<!-- SEÇÃO'
    'Solicitacao -->' = 'Solicitação -->'
    
    # Mensagens e logs de erro
    'Falha ao gerar relatorio' = 'Falha ao gerar relatório'
    'Erro ao gerar relatorio' = 'Erro ao gerar relatório'
    
    # Labels em JavaScript (apenas strings visíveis)
    "'Relatorio por Numero de Patrimonio'" = "'Relatório por Número de Patrimônio'"
    "'Relatorio por Descricao'" = "'Relatório por Descrição'"
    "'Relatorio por Periodo de Aquisicao'" = "'Relatório por Período de Aquisição'"
    "'Relatorio por Periodo de Cadastro'" = "'Relatório por Período de Cadastro'"
    "'Relatorio por Projeto'" = "'Relatório por Projeto'"
    "'Relatorio por OC'" = "'Relatório por OC'"
    "'Relatorio por UF'" = "'Relatório por UF'"
    "'Relatorio por Situacao'" = "'Relatório por Situação'"
    "'Relatorio'" = "'Relatório'"
    
    # Labels JavaScript específicos
    "SITUACAO: 'Situação'" = "SITUACAO: 'Situação'"
    "DEHISTORICO: 'Histórico'" = "DEHISTORICO: 'Histórico'"
    "DTOPERACAO: 'Data operação'" = "DTOPERACAO: 'Data de operação'"
    
    # Labels inline
    '<span>numero do patrimonio</span>' = '<span>número do patrimônio</span>'
    '<span>numero de patrimonio</span>' = '<span>número de patrimônio</span>'
    'Projeto \(codigo\)' = 'Projeto (código)'
    'Local fisico \(codigo\)' = 'Local físico (código)'
}

$filesModified = 0
$totalReplacements = 0

Write-Host "Iniciando correcao de acentuacao em $rootPath..." -ForegroundColor Cyan
Write-Host ""

Get-ChildItem -Path $rootPath -Filter "*.blade.php" -Recurse | ForEach-Object {
    $file = $_
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    $fileReplacements = 0
    
    foreach ($pattern in $replacements.Keys) {
        $replacement = $replacements[$pattern]
        $before = $content
        $content = $content -replace $pattern, $replacement
        if ($content -ne $before) {
            $fileReplacements++
            $totalReplacements++
        }
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        $filesModified++
        $relativePath = $file.FullName.Replace($rootPath + "\", "")
        Write-Host "OK $relativePath" -ForegroundColor Green -NoNewline
        Write-Host " ($fileReplacements alteracoes)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Correcao concluida!" -ForegroundColor Green
Write-Host "Arquivos modificados: $filesModified" -ForegroundColor Yellow
Write-Host "Total de substituicoes: $totalReplacements" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
