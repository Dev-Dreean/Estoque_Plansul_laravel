-- Script para SUBSTITUIR usuários (PRE) pelos usuários corretos em patr
-- Gerado em: 2025-11-28 08:49:54
-- Redireciona ALL os lançamentos das versões (PRE) para os usuários corretos
-- Depois remove as versões (PRE)

USE plansul04;

START TRANSACTION;

-- Backup antes de modificar
CREATE TABLE IF NOT EXISTS patr_backup_before_usuario_cleanup AS SELECT * FROM patr;

-- ETAPA 1: Atualizar patrimônios - substitui (PRE) pelo correto
UPDATE patr SET USUARIO = 'ABIGAIL' WHERE USUARIO = 'ABIGAIL (PRE)';
UPDATE patr SET USUARIO = 'ANDRE' WHERE USUARIO = 'ANDRE (PRE)';
UPDATE patr SET USUARIO = 'ANDRE LUIS PAIM FURTADO' WHERE USUARIO = 'ANDRE LUIS PAIM FURTADO (PRE)';
UPDATE patr SET USUARIO = 'BEA.SC' WHERE USUARIO = 'BEA.SC (PRE)';
UPDATE patr SET USUARIO = 'BEATRIZ PATRICIA V...' WHERE USUARIO = 'BEATRIZ PATRICIA V... (PRE)';
UPDATE patr SET USUARIO = 'BEATRIZ.SC' WHERE USUARIO = 'BEATRIZ.SC (PRE)';
UPDATE patr SET USUARIO = 'BRUNO' WHERE USUARIO = 'BRUNO (PRE)';
UPDATE patr SET USUARIO = 'CURY.SC' WHERE USUARIO = 'CURY.SC (PRE)';
UPDATE patr SET USUARIO = 'GISELE DE SOUZA PE...' WHERE USUARIO = 'GISELE DE SOUZA PE... (PRE)';
UPDATE patr SET USUARIO = 'IANDRAF.SC' WHERE USUARIO = 'IANDRAF.SC (PRE)';

COMMIT;

-- VALIDAÇÃO: Verificar se ainda existem (PRE)
SELECT COUNT(*) AS total_com_pre FROM patr WHERE USUARIO LIKE '%PRE%';

-- Distribuição final de usuários em patrimônios
SELECT USUARIO, COUNT(*) as total FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' GROUP BY USUARIO ORDER BY total DESC;
