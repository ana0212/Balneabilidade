
<?php
// Teste controlado de falha na conexão SQLite.
$dbPath = dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'banco_teste_inexistente.db';

try {
    // O SQLite pode criar um arquivo inexistente.
    // Por isso, usamos um caminho de diretório inválido
    // para provocar uma falha de abertura.
    $dbPath = dirname(__DIR__)
        . DIRECTORY_SEPARATOR
        . 'diretorio_inexistente_teste'
        . DIRECTORY_SEPARATOR
        . 'balneabilidade.db';

    $pdo = new PDO('sqlite:' . $dbPath);

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    echo 'Conexão estabelecida com sucesso.';

} catch (PDOException $e) {
    error_log(
        'Teste de conexão SQLite: '
        . $e->getMessage()
    );

    http_response_code(500);

    exit('Não foi possível acessar o banco de dados.');
}
