-- ============================================================================
-- IMPORTAÇÃO COMPLEMENTAR PARA KINGHOST
-- Apenas INSERTS (não substitui dados existentes)
-- Data: 26 de novembro de 2025
-- ============================================================================

-- ============================================================================
-- 1. TABELA: tabfant (PROJETOS)
-- Inserção de projetos complementares
-- ============================================================================
-- Desabilitar verificação de chave estrangeira temporariamente
SET FOREIGN_KEY_CHECKS=0;

-- Inserir apenas se não existir (usando INSERT IGNORE)
INSERT IGNORE INTO tabfant (CDPROJETO, NOMEPROJETO, CDFILIAL, UF)
SELECT DISTINCT 
    CDFANTASIA AS CDPROJETO,
    DEFANTASIA AS NOMEPROJETO,
    CDFILIAL,
    UFPROJ AS UF
FROM (
    SELECT '281' AS CDFANTASIA, 'BRADESCO-RS' AS DEFANTASIA, 1 AS CDFILIAL, 'RS' AS UFPROJ
    UNION ALL
    SELECT '2', 'CASAN', 1, 'SC'
    UNION ALL
    SELECT '47', 'CASAN/CAD', 1, 'SC'
    -- ... adicionar demais projetos conforme necessário
) AS temp_tabfant;

-- ============================================================================
-- 2. TABELA: locais_projeto (LOCAIS DO PROJETO)
-- Inserção de locais complementares
-- ============================================================================

INSERT IGNORE INTO locais_projeto (cdlocal, delocal, tabfant_id, flativo)
SELECT DISTINCT 
    CAST(CDFANTASIA AS UNSIGNED) AS cdlocal,
    DEFANTASIA AS delocal,
    (SELECT id FROM tabfant WHERE CDPROJETO = CAST(CDFANTASIA AS UNSIGNED) LIMIT 1) AS tabfant_id,
    'S' AS flativo
FROM (
    SELECT '281' AS CDFANTASIA, 'BRADESCO-RS' AS DEFANTASIA
    UNION ALL
    SELECT '2', 'CASAN'
    UNION ALL
    SELECT '47', 'CASAN/CAD'
    -- ... adicionar demais locais conforme necessário
) AS temp_locais;

-- ============================================================================
-- 3. TABELA: patr (PATRIMÔNIOS)
-- Inserção de patrimônios complementares
-- ============================================================================

INSERT INTO patr (
    NUPATRIMONIO,
    SITUACAO,
    MARCA,
    CDLOCAL,
    MODELO,
    COR,
    DTAQUISICAO,
    DEHISTORICO,
    CDMATRFUNCIONARIO,
    CDPROJETO,
    NUDOCFISCAL,
    USUARIO,
    DTOPERACAO,
    NUMOF,
    CODOBJETO,
    FLCONFERIDO
) 
SELECT DISTINCT
    CAST(NUPATRIMONIO AS UNSIGNED),
    NULLIF(SITUACAO, ''),
    MARK,
    (SELECT id FROM locais_projeto WHERE cdlocal = CAST(CDLOCAL AS UNSIGNED) LIMIT 1),
    MODELO,
    NULLIF(COR, ''),
    STR_TO_DATE(DTAQUISICAO, '%d/%m/%Y'),
    NULLIF(DEHISTORICO, ''),
    CAST(CDMATRFUNCIONARIO AS UNSIGNED),
    CAST(CDPROJETO AS UNSIGNED),
    NULLIF(NUDOCFISCAL, ''),
    NULLIF(USUARIO, ''),
    STR_TO_DATE(DTOPERACAO, '%d/%m/%Y'),
    NULLIF(NUMOF, ''),
    NULLIF(CODOBJETO, ''),
    'N'
FROM (
    -- Dados do arquivo PATRIMONIO.TXT
    -- Esta é uma estrutura de exemplo - você precisa popular com os dados reais
    SELECT 
        '5640' AS NUPATRIMONIO,
        '<null>' AS SITUACAO,
        'LAVOR' AS MARK,
        '1' AS CDLOCAL,
        'MAXX 1600' AS MODELO,
        '<null>' AS COR,
        '18/07/2014' AS DTAQUISICAO,
        'Monte Alegre de Minas' AS DEHISTORICO,
        '80441' AS CDMATRFUNCIONARIO,
        '522' AS CDPROJETO,
        '22269' AS NUDOCFISCAL,
        '<null>' AS USUARIO,
        '<null>' AS DTOPERACAO,
        '<null>' AS NUMOF,
        '<null>' AS CODOBJETO
) AS temp_patr
WHERE NOT EXISTS (
    SELECT 1 FROM patr WHERE NUPATRIMONIO = CAST(NUPATRIMONIO AS UNSIGNED)
);

-- ============================================================================
-- 4. TABELA: historico_movimentacao (HISTÓRICO DE MOVIMENTAÇÃO)
-- Inserção de histórico complementar
-- ============================================================================

INSERT IGNORE INTO historico_movimentacao (
    patrimonio_id,
    tipo_movimentacao,
    local_origem_id,
    local_destino_id,
    responsavel_id,
    data_movimentacao,
    observacoes
)
SELECT DISTINCT
    (SELECT NUSEQPATR FROM patr WHERE NUPATRIMONIO = CAST(NUPATR AS UNSIGNED) LIMIT 1),
    'COMPLEMENTO_DADOS',
    NULL,
    NULL,
    NULL,
    NOW(),
    DEHISTORICO
FROM (
    -- Dados do arquivo MOVPATRHISTORICO.TXT
    -- Esta é uma estrutura de exemplo
    SELECT 
        '5640' AS NUPATR,
        'Monte Alegre de Minas' AS DEHISTORICO
) AS temp_mov
WHERE NOT EXISTS (
    SELECT 1 FROM historico_movimentacao 
    WHERE patrimonio_id = (SELECT NUSEQPATR FROM patr WHERE NUPATRIMONIO = CAST(NUPATR AS UNSIGNED) LIMIT 1)
    AND tipo_movimentacao = 'COMPLEMENTO_DADOS'
);

-- ============================================================================
-- Reabilitar verificação de chave estrangeira
SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- Resultado
-- ============================================================================
-- Este script é um TEMPLATE - você precisa:
-- 1. Substituir os dados de exemplo pelos dados reais dos arquivos TXT
-- 2. Adaptar as estruturas conforme necessário
-- 3. Testar em ambiente de desenvolvimento antes de executar em produção
-- ============================================================================
