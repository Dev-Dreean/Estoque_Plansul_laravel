-- Buscar local SALA BOSCO no projeto SEDE (8)
SELECT id, cdlocal, delocal, tabfant_id FROM locais_projeto WHERE tabfant_id = 8 AND delocal LIKE 'SALA BOSCO';

-- Atualizar nome do local
UPDATE locais_projeto SET delocal = 'Sala I.A.' WHERE tabfant_id = 8 AND delocal LIKE 'SALA BOSCO';

-- Verificar alteração
SELECT id, cdlocal, delocal, tabfant_id FROM locais_projeto WHERE tabfant_id = 8 AND delocal LIKE 'Sala I.A.';
