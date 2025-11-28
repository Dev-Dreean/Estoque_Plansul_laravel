-- Script para remover (PRE) dos nomes de usuários
-- E consolidar usuários duplicados na tabela usuario
-- Gerado em: 2025-11-28 08:57:49

USE plansul04;

START TRANSACTION;

-- BACKUP
CREATE TABLE IF NOT EXISTS usuario_backup_pre_remove AS SELECT * FROM usuario;

-- OPERAÇÃO: Remover ' (PRE)' dos nomes de usuários
-- Isso vai unificar ABIGAIL (PRE) com ABIGAIL, etc.

-- Consolidando: 'ABIGAIL (PRE)' → 'ABIGAIL'
UPDATE usuario SET NMLOGIN = 'ABIGAIL' WHERE NMLOGIN = 'ABIGAIL (PRE)';
-- Consolidando: 'ANDRE (PRE)' → 'ANDRE'
UPDATE usuario SET NMLOGIN = 'ANDRE' WHERE NMLOGIN = 'ANDRE (PRE)';
-- Consolidando: 'BEA.SC (PRE)' → 'BEA.SC'
UPDATE usuario SET NMLOGIN = 'BEA.SC' WHERE NMLOGIN = 'BEA.SC (PRE)';
-- Consolidando: 'CURY.SC (PRE)' → 'CURY.SC'
UPDATE usuario SET NMLOGIN = 'CURY.SC' WHERE NMLOGIN = 'CURY.SC (PRE)';
-- Consolidando: 'IANDRAF.SC (PRE)' → 'IANDRAF.SC'
UPDATE usuario SET NMLOGIN = 'IANDRAF.SC' WHERE NMLOGIN = 'IANDRAF.SC (PRE)';
-- Consolidando: 'ISABEL.SC (PRE)' → 'ISABEL.SC'
UPDATE usuario SET NMLOGIN = 'ISABEL.SC' WHERE NMLOGIN = 'ISABEL.SC (PRE)';
-- Consolidando: 'LUIZ (PRE)' → 'LUIZ'
UPDATE usuario SET NMLOGIN = 'LUIZ' WHERE NMLOGIN = 'LUIZ (PRE)';
-- Consolidando: 'RYAN (PRE)' → 'RYAN'
UPDATE usuario SET NMLOGIN = 'RYAN' WHERE NMLOGIN = 'RYAN (PRE)';
-- Consolidando: 'TEIXEIRA (PRE)' → 'TEIXEIRA'
UPDATE usuario SET NMLOGIN = 'TEIXEIRA' WHERE NMLOGIN = 'TEIXEIRA (PRE)';
-- Consolidando: 'THEO (PRE)' → 'THEO'
UPDATE usuario SET NMLOGIN = 'THEO' WHERE NMLOGIN = 'THEO (PRE)';
-- Consolidando: 'TIAGOP (PRE)' → 'TIAGOP'
UPDATE usuario SET NMLOGIN = 'TIAGOP' WHERE NMLOGIN = 'TIAGOP (PRE)';

COMMIT;

-- VALIDAÇÃO
SELECT COUNT(*) AS usuarios_com_pre FROM usuario WHERE NMLOGIN LIKE '%PRE%';
SELECT NMLOGIN, NOMEUSER FROM usuario ORDER BY NMLOGIN;
