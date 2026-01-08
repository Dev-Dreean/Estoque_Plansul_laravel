#!/bin/bash
# one-off: Restaurar telas no KingHost acessotela table

echo "🔄 Restaurando telas no KingHost..."

ssh plansul@ftp.plansul.info << 'EOF'
cd ~/www/estoque-laravel

# Conectar ao MySQL KingHost e restaurar telas
php82 -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\Illuminate\Support\Facades\Facade::setFacadeApplication(\$app);

\$pdo = new PDO(
    'mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4',
    'plansul004_add2',
    'A33673170a',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

\$inserts = [
    [1000, 'Controle de Patrimônio', 'S', 'Sistema Principal', 'TODOS'],
    [1001, 'Dashboard - Gráficos', 'S', 'Sistema Principal', 'TODOS'],
    [1002, 'Cadastro de Locais', 'S', 'Sistema Principal', 'TODOS'],
    [1003, 'Cadastro de Usuários', 'S', 'Sistema Principal', 'TODOS'],
    [1004, 'Cadastro de Telas', 'S', 'Sistema Principal', 'TODOS'],
    [1005, 'Gerenciar Acessos', 'S', 'Sistema Principal', 'TODOS'],
    [1006, 'Relatórios', 'S', 'Sistema Principal', 'TODOS'],
    [1007, 'Histórico de Movimentações', 'S', 'Sistema Principal', 'TODOS'],
    [1008, 'Configurações de Tema', 'S', 'Sistema Principal', 'TODOS'],
    [1009, 'Removidos', 'S', 'Sistema Principal', 'TODOS'],
];

try {
    \$stmt = \$pdo->prepare('INSERT INTO acessotela (NUSEQTELA, DETELA, FLACESSO, NMSISTEMA, NIVEL_VISIBILIDADE) VALUES (?, ?, ?, ?, ?)');
    
    foreach (\$inserts as \$row) {
        \$stmt->execute(\$row);
    }
    
    echo \"✅ Telas restauradas com sucesso!\n\";
    echo \"Total inserido: \" . count(\$inserts) . \" telas\n\";
    
    \$count = \$pdo->query('SELECT COUNT(*) FROM acessotela')->fetchColumn();
    echo \"Total agora na tabela: \$count telas\n\";
} catch (Exception \$e) {
    echo \"❌ Erro ao restaurar: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"

EOF

echo "✅ Restauração concluída!"
