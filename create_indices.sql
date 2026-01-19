-- Criar índices para otimizar performance de busca
ALTER TABLE funcionarios ADD INDEX idx_nmfuncionario (NMFUNCIONARIO(100));
ALTER TABLE funcionarios ADD FULLTEXT INDEX ft_nmfuncionario (NMFUNCIONARIO);

-- Verificar indices criados
SHOW INDEXES FROM funcionarios;

-- Teste: confirmar cardinalidade  
SELECT COUNT(DISTINCT NMFUNCIONARIO) as nomes_unicos FROM funcionarios;
