-- fix_patr_usuario.sql
-- Objetivo: identificar, fazer backup e corrigir patr.USUARIO apontando para usuario.NMLOGIN
-- Local: banco `plansul04` (ajuste se necessário)

USE `plansul04`;

-- 1) Verificação rápida: contar problemáticos
SELECT COUNT(*) AS total_problematicos
FROM patr p
LEFT JOIN usuario u ON p.USUARIO = u.NMLOGIN
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR u.NUSEQUSUARIO IS NULL;

-- 2) Criar backup das linhas problemáticas (tabela local temporária)
DROP TABLE IF EXISTS patr_backup_problem;
CREATE TABLE patr_backup_problem AS
SELECT p.*
FROM patr p
LEFT JOIN usuario u ON p.USUARIO = u.NMLOGIN
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR u.NUSEQUSUARIO IS NULL;

-- (Opcional) Verifique o número de linhas no backup
SELECT COUNT(*) AS backup_rows FROM patr_backup_problem;

-- 3) Preview: quantos problemáticos têm correspondência por CDMATRFUNCIONARIO
SELECT COUNT(*) AS qtd_com_match_por_cdm,
       SUM(CASE WHEN TRIM(COALESCE(p.CDMATRFUNCIONARIO,'')) = '' THEN 1 ELSE 0 END) AS qtd_sem_cdm
FROM patr p
JOIN usuario u ON TRIM(u.CDMATRFUNCIONARIO) = TRIM(p.CDMATRFUNCIONARIO)
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR NOT EXISTS (SELECT 1 FROM usuario u2 WHERE u2.NMLOGIN = p.USUARIO);

-- 4) Amostra (preview) de mapeamento por CDMATRFUNCIONARIO
SELECT p.NUSEQPATR, p.NUPATRIMONIO, p.CDMATRFUNCIONARIO, p.USUARIO AS antigo_usuario,
       u.NMLOGIN AS novo_nmlogin, u.NOMEUSER
FROM patr p
LEFT JOIN usuario u ON TRIM(u.CDMATRFUNCIONARIO) = TRIM(p.CDMATRFUNCIONARIO)
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR NOT EXISTS (SELECT 1 FROM usuario u2 WHERE u2.NMLOGIN = p.USUARIO)
LIMIT 200;

-- 5) (SIMULAÇÃO) Quantas linhas seriam afetadas pelo UPDATE
SELECT COUNT(*) AS qtd_a_alterar
FROM patr p
JOIN usuario u ON TRIM(u.CDMATRFUNCIONARIO) = TRIM(p.CDMATRFUNCIONARIO)
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR NOT EXISTS (SELECT 1 FROM usuario u2 WHERE u2.NMLOGIN = p.USUARIO);

-- 6) UPDATE transacional (APLICAR quando revisar e estiver pronto)
-- Este UPDATE somente altera linhas problemáticas e somente quando há
-- correspondência entre patr.CDMATRFUNCIONARIO e usuario.CDMATRFUNCIONARIO
START TRANSACTION;

UPDATE patr p
JOIN usuario u ON TRIM(u.CDMATRFUNCIONARIO) = TRIM(p.CDMATRFUNCIONARIO)
SET p.USUARIO = u.NMLOGIN
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR NOT EXISTS (SELECT 1 FROM usuario u2 WHERE u2.NMLOGIN = p.USUARIO);

COMMIT;

-- 7) Conferir quantos ainda permanecem problemáticos
SELECT COUNT(*) AS restantes_sem_usuario
FROM patr p
LEFT JOIN usuario u ON p.USUARIO = u.NMLOGIN
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR u.NUSEQUSUARIO IS NULL;

-- 8) Exportar remanescentes para tabela temporária (recomendado quando OUTFILE é bloqueado)
-- Muitos hosts (ex.: KingHost) não permitem INTO OUTFILE para usuários remotos.
-- Criamos uma tabela `patr_remanescentes` que você poderá exportar via phpMyAdmin
-- ou baixar com uma consulta SELECT simples.
DROP TABLE IF EXISTS patr_remanescentes;
CREATE TABLE patr_remanescentes AS
SELECT p.NUSEQPATR, p.NUPATRIMONIO, p.CDMATRFUNCIONARIO, p.USUARIO
FROM patr p
LEFT JOIN usuario u ON p.USUARIO = u.NMLOGIN
WHERE p.USUARIO IS NULL
   OR TRIM(p.USUARIO) = ''
   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'
   OR u.NUSEQUSUARIO IS NULL;

-- Verifique quantas linhas ficaram na tabela de remanescentes
SELECT COUNT(*) AS patr_remanescentes_count FROM patr_remanescentes;

-- Depois, exporte `patr_remanescentes` via phpMyAdmin (Export -> CSV) ou
-- faça uma consulta e copie/cole os resultados conforme necessário.

-- 9) Dicas pós-UPDATE no Laravel
-- php artisan cache:clear
-- Se houver cache custom de nomes, reiniciar serviços conforme necessário.

-- FIM
