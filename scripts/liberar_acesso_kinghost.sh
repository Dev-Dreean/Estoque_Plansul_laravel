# one-off: Liberar acesso a THEO, TIAGOP, BEA.SC no KingHost
# Theo: Tela 1013 (criar solicitações)
# Tiagop + BEA.SC: Tela 1010, 1011 (confirmar, aprovar, listar)

echo "=== LIBERANDO ACESSO NO KINGHOST ==="

# 1. THEO - Tela 1013 (criar solicitações)
echo "Liberando THEO para criar solicitações..."
ssh plansul@ftp.plansul.info << 'EOFTHEO'
mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p'A33673170a' plansul04 << 'EOFDB'
-- Liberar Tela 1013 para THEO
INSERT INTO acesso_usuario (usuario_id, tela_id, criado_em, atualizado_em) 
VALUES (
    (SELECT id FROM usuario WHERE NMLOGIN = 'THEO'),
    1013,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE atualizado_em = NOW();

SELECT 'THEO com Tela 1013 (criar solicitações)' AS resultado;
EOFDB
EOFTHEO

# 2. TIAGOP - Telas 1010, 1011
echo "Liberando TIAGOP para confirmar e aprovar solicitações..."
ssh plansul@ftp.plansul.info << 'EOFTIAGOP'
mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p'A33673170a' plansul04 << 'EOFDB'
-- Liberar Telas 1010, 1011 para TIAGOP
INSERT INTO acesso_usuario (usuario_id, tela_id, criado_em, atualizado_em) 
VALUES 
    ((SELECT id FROM usuario WHERE NMLOGIN = 'TIAGOP'), 1010, NOW(), NOW()),
    ((SELECT id FROM usuario WHERE NMLOGIN = 'TIAGOP'), 1011, NOW(), NOW())
ON DUPLICATE KEY UPDATE atualizado_em = NOW();

SELECT 'TIAGOP com Telas 1010, 1011 (listar, confirmar, aprovar)' AS resultado;
EOFDB
EOFTIAGOP

# 3. BEA.SC - Telas 1010, 1011
echo "Liberando BEA.SC para confirmar e aprovar solicitações..."
ssh plansul@ftp.plansul.info << 'EOFBEA'
mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p'A33673170a' plansul04 << 'EOFDB'
-- Liberar Telas 1010, 1011 para BEA.SC
INSERT INTO acesso_usuario (usuario_id, tela_id, criado_em, atualizado_em) 
VALUES 
    ((SELECT id FROM usuario WHERE NMLOGIN = 'BEA.SC'), 1010, NOW(), NOW()),
    ((SELECT id FROM usuario WHERE NMLOGIN = 'BEA.SC'), 1011, NOW(), NOW())
ON DUPLICATE KEY UPDATE atualizado_em = NOW();

SELECT 'BEA.SC com Telas 1010, 1011 (listar, confirmar, aprovar)' AS resultado;
EOFDB
EOFBEA

echo "=== VERIFICANDO ACESSO LIBERADO ==="
ssh plansul@ftp.plansul.info "mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p'A33673170a' plansul04 -e \"SELECT u.NMLOGIN, u.NOMEUSER, GROUP_CONCAT(a.tela_id SEPARATOR ', ') as telas FROM usuario u LEFT JOIN acesso_usuario a ON u.id = a.usuario_id WHERE u.NMLOGIN IN ('THEO', 'TIAGOP', 'BEA.SC') GROUP BY u.id, u.NMLOGIN, u.NOMEUSER;\""

echo "=== CONCLUÍDO ==="
