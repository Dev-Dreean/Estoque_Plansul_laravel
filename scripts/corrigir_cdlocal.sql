-- ════════════════════════════════════════════════════════════════════════════════
-- SCRIPT SQL PARA CORRIGIR CDLOCAL DOS PATRIMÔNIOS
-- ════════════════════════════════════════════════════════════════════════════════
--
-- PROBLEMA:
-- A coluna patr.CDLOCAL está com valores que não correspondem corretamente
-- aos locais da tabela locais_projeto. Isso aconteceu porque durante a importação,
-- o sistema gravou o valor do campo 'cdlocal' diretamente, mas deveria ter buscado
-- o ID correspondente na tabela locais_projeto.
--
-- SOLUÇÃO:
-- Este script cria uma view temporária com o mapeamento correto e depois
-- atualiza os registros da tabela patr.
--
-- EXEMPLO DE CORREÇÃO:
-- Patrimônio 17546:
--   Antes: CDLOCAL = 1 (aponta para ID 1 = 'SEDE CIDASC')
--   Depois: CDLOCAL = 1 (ainda aponta para ID 1, mas agora está correto
--           pois o ID 1 tem cdlocal=1)
--
-- IMPORTANTE:
-- ⚠️  Faça backup antes de executar!
-- ════════════════════════════════════════════════════════════════════════════════

-- 1. CRIAR BACKUP DA TABELA PATR
-- ════════════════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS patr_backup_before_cdlocal_fix;
CREATE TABLE patr_backup_before_cdlocal_fix LIKE patr;
INSERT INTO patr_backup_before_cdlocal_fix SELECT * FROM patr;

SELECT '✅ Backup criado: patr_backup_before_cdlocal_fix' as status;


-- 2. ANÁLISE DO PROBLEMA (CONSULTAS DE VERIFICAÇÃO)
-- ════════════════════════════════════════════════════════════════════════════════

-- Ver exemplo do patrimônio 17546 ANTES da correção
SELECT 
    p.NUPATRIMONIO,
    p.CDLOCAL as 'CDLOCAL_atual',
    lp.id as 'local_id',
    lp.cdlocal as 'local_cdlocal',
    lp.delocal as 'local_nome',
    CASE 
        WHEN p.CDLOCAL = lp.cdlocal THEN '✅ OK'
        ELSE '⚠️  INCONSISTENTE'
    END as status
FROM patr p
LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
WHERE p.NUPATRIMONIO = 17546;


-- Ver quantidade de registros com inconsistência
SELECT 
    COUNT(*) as total_patrimonios,
    SUM(CASE WHEN p.CDLOCAL = lp.cdlocal THEN 1 ELSE 0 END) as consistentes,
    SUM(CASE WHEN p.CDLOCAL != lp.cdlocal THEN 1 ELSE 0 END) as inconsistentes,
    SUM(CASE WHEN lp.id IS NULL THEN 1 ELSE 0 END) as sem_local
FROM patr p
LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
WHERE p.CDLOCAL IS NOT NULL;


-- 3. CORREÇÃO AUTOMÁTICA (ATUALIZAÇÃO DOS REGISTROS)
-- ════════════════════════════════════════════════════════════════════════════════

-- A lógica é: para cada patrimônio, buscar o ID do local onde cdlocal = valor atual de CDLOCAL
-- Se encontrar, atualizar. Se não encontrar, manter como está.

-- Esta query mostra quantos registros SERIAM atualizados (não faz update ainda)
SELECT 
    p.NUPATRIMONIO,
    p.CDLOCAL as 'valor_antigo',
    lp_novo.id as 'valor_novo',
    lp_antigo.delocal as 'local_antigo',
    lp_novo.delocal as 'local_novo'
FROM patr p
LEFT JOIN locais_projeto lp_antigo ON p.CDLOCAL = lp_antigo.id
LEFT JOIN locais_projeto lp_novo ON p.CDLOCAL = lp_novo.cdlocal
WHERE p.CDLOCAL IS NOT NULL
  AND lp_novo.id IS NOT NULL
  AND p.CDLOCAL != lp_novo.id
LIMIT 30;


-- ⚠️  ATENÇÃO: O UPDATE ABAIXO IRÁ ALTERAR OS DADOS! ⚠️
-- Descomente as linhas abaixo apenas se tiver certeza:

/*
UPDATE patr p
INNER JOIN locais_projeto lp ON p.CDLOCAL = lp.cdlocal
SET p.CDLOCAL = lp.id
WHERE p.CDLOCAL != lp.id;

SELECT '✅ Patrimônios atualizados!' as status;
*/


-- 4. VERIFICAÇÃO APÓS CORREÇÃO
-- ════════════════════════════════════════════════════════════════════════════════

-- Verificar patrimônio 17546 DEPOIS da correção
/*
SELECT 
    p.NUPATRIMONIO,
    p.CDLOCAL as 'CDLOCAL_atual',
    lp.id as 'local_id',
    lp.cdlocal as 'local_cdlocal',
    lp.delocal as 'local_nome',
    lp.tabfant_id,
    CASE 
        WHEN p.CDLOCAL = lp.id THEN '✅ OK'
        ELSE '⚠️  PROBLEMA'
    END as status
FROM patr p
LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
WHERE p.NUPATRIMONIO = 17546;
*/

-- Verificar quantidade de registros após correção
/*
SELECT 
    COUNT(*) as total_patrimonios,
    SUM(CASE WHEN lp.id IS NOT NULL THEN 1 ELSE 0 END) as com_local_valido,
    SUM(CASE WHEN lp.id IS NULL THEN 1 ELSE 0 END) as sem_local
FROM patr p
LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
WHERE p.CDLOCAL IS NOT NULL;
*/


-- 5. ROLLBACK (SE NECESSÁRIO)
-- ════════════════════════════════════════════════════════════════════════════════

-- Para reverter as alterações, execute:
/*
DROP TABLE patr;
RENAME TABLE patr_backup_before_cdlocal_fix TO patr;
SELECT '🔄 Rollback executado!' as status;
*/


-- ════════════════════════════════════════════════════════════════════════════════
-- INSTRUÇÕES DE USO:
-- ════════════════════════════════════════════════════════════════════════════════
--
-- 1. Execute as seções 1 e 2 para criar backup e analisar o problema
-- 2. Revise os resultados das consultas de análise
-- 3. Se estiver tudo OK, descomente e execute o UPDATE da seção 3
-- 4. Execute as consultas da seção 4 para verificar se deu certo
-- 5. Se algo der errado, use a seção 5 para fazer rollback
--
-- ════════════════════════════════════════════════════════════════════════════════
