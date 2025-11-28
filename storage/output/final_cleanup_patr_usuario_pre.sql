-- ====================================================================
-- SCRIPT FINAL: Remove TODOS os usuários (PRE) do servidor KingHost
-- ====================================================================
-- Gerado em: 2025-11-28 08:52:53
-- Operações:
--   1. Substitui patrimônios (PRE) pelos usuários corretos
--   2. Remove usuários (PRE) da tabela usuario
-- ====================================================================

USE plansul04;

START TRANSACTION;

-- BACKUP
CREATE TABLE IF NOT EXISTS patr_backup_final_cleanup AS SELECT * FROM patr;
CREATE TABLE IF NOT EXISTS usuario_backup_final_cleanup AS SELECT * FROM usuario;

-- ETAPA 1: Substituir patrimônios com usuário (PRE) pelo usuário correto
UPDATE patr SET USUARIO = 'ABIGAIL' WHERE USUARIO = 'ABIGAIL (PRE)';
UPDATE patr SET USUARIO = 'ANDRE' WHERE USUARIO = 'ANDRE (PRE)';
UPDATE patr SET USUARIO = 'BEA.SC' WHERE USUARIO = 'BEA.SC (PRE)';
UPDATE patr SET USUARIO = 'CURY.SC' WHERE USUARIO = 'CURY.SC (PRE)';
UPDATE patr SET USUARIO = 'IANDRAF.SC' WHERE USUARIO = 'IANDRAF.SC (PRE)';
UPDATE patr SET USUARIO = 'ISABEL.SC' WHERE USUARIO = 'ISABEL.SC (PRE)';
UPDATE patr SET USUARIO = 'LUIZ' WHERE USUARIO = 'LUIZ (PRE)';
UPDATE patr SET USUARIO = 'RYAN' WHERE USUARIO = 'RYAN (PRE)';
UPDATE patr SET USUARIO = 'TEIXEIRA' WHERE USUARIO = 'TEIXEIRA (PRE)';
UPDATE patr SET USUARIO = 'THEO' WHERE USUARIO = 'THEO (PRE)';
UPDATE patr SET USUARIO = 'TIAGOP' WHERE USUARIO = 'TIAGOP (PRE)';

-- ETAPA 2: Remover usuários (PRE) da tabela usuario
DELETE FROM usuario WHERE NMLOGIN = 'ABIGAIL (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'ANDRE (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'BEA.SC (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'CURY.SC (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'IANDRAF.SC (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'ISABEL.SC (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'LUIZ (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'RYAN (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'TEIXEIRA (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'THEO (PRE)';
DELETE FROM usuario WHERE NMLOGIN = 'TIAGOP (PRE)';

COMMIT;

-- VALIDAÇÃO
-- 1. Verificar se ainda existem (PRE) em patrimônios
SELECT COUNT(*) AS total_patr_com_pre FROM patr WHERE USUARIO LIKE '%PRE%';

-- 2. Verificar se ainda existem (PRE) em usuários
SELECT COUNT(*) AS total_usuario_com_pre FROM usuario WHERE NMLOGIN LIKE '%PRE%';

-- 3. Distribuição final de patrimônios por usuário
SELECT USUARIO, COUNT(*) as total FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' GROUP BY USUARIO ORDER BY total DESC;

-- 4. Listar usuários (PRE) restantes (se houver)
SELECT NMLOGIN FROM usuario WHERE NMLOGIN LIKE '%PRE%' ORDER BY NMLOGIN;
