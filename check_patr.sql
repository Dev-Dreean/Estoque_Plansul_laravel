-- Verificar quantidade de patrimônios
SELECT COUNT(*) as 'Total na tabela' FROM patr;
SELECT COUNT(*) as 'Com NMPLANTA NULL' FROM patr WHERE NMPLANTA IS NULL;
SELECT COUNT(*) as 'Com NMPLANTA NOT NULL' FROM patr WHERE NMPLANTA IS NOT NULL;

-- Mostrar alguns exemplos de patrimônios disponíveis
SELECT NUPATRIMONIO, DEPATRIMONIO, MODELO, NMPLANTA FROM patr WHERE NMPLANTA IS NULL LIMIT 5;
