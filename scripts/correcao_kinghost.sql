-- ════════════════════════════════════════════════════════════════════════════════
-- SCRIPT DE CORRECAO - CDLOCAL NO KINGHOST (SQL)
-- ════════════════════════════════════════════════════════════════════════════════

-- ETAPA 1: Backup
CREATE TABLE patr_backup_kinghost_20251204 LIKE patr;
INSERT INTO patr_backup_kinghost_20251204 SELECT * FROM patr;

-- ETAPA 2: Correção 
-- Usar a view temporária para mapear cada projeto ao seu local correto

-- Para cada projeto, buscar o local associado e atualizar os patrimônios

UPDATE patr p
SET p.CDLOCAL = (
    SELECT lp.id
    FROM locais_projeto lp
    INNER JOIN tabfant t ON lp.tabfant_id = t.id
    WHERE t.CDPROJETO = p.CDPROJETO
    LIMIT 1
)
WHERE p.CDPROJETO IS NOT NULL
AND p.CDLOCAL != (
    SELECT COALESCE((
        SELECT lp.id
        FROM locais_projeto lp
        INNER JOIN tabfant t ON lp.tabfant_id = t.id
        WHERE t.CDPROJETO = p.CDPROJETO
        LIMIT 1
    ), p.CDLOCAL)
);

-- ETAPA 3: Verificação
SELECT 
    'ESTATÍSTICAS PÓS-CORREÇÃO' as categoria,
    (SELECT COUNT(*) FROM patr) as total_patrimonios,
    (SELECT COUNT(*) FROM patr WHERE CDPROJETO IS NOT NULL) as com_projeto,
    (
        SELECT COUNT(DISTINCT p.NUPATRIMONIO)
        FROM patr p
        LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
        LEFT JOIN tabfant t ON lp.tabfant_id = t.id
        WHERE p.CDPROJETO IS NOT NULL
        AND lp.tabfant_id IS NOT NULL
        AND t.CDPROJETO != p.CDPROJETO
    ) as inconsistencias_restantes;

-- ETAPA 4: Verificar patrimônio 17546
SELECT 
    p.NUPATRIMONIO,
    p.CDLOCAL,
    p.CDPROJETO,
    lp.delocal as local_nome,
    lp.cdlocal as local_codigo,
    t.CDPROJETO as projeto_codigo,
    t.NOMEPROJETO as projeto_nome,
    IF(t.CDPROJETO = p.CDPROJETO, 'OK', 'ERRO') as status
FROM patr p
LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
LEFT JOIN tabfant t ON lp.tabfant_id = t.id
WHERE p.NUPATRIMONIO = 17546;
