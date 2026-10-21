<?php

// Localiza arquivo da base de dados
$dbPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'balneabilidade.db';

// Tenta carregar a base sqlite em um pdo, com tratamento de erros com exceções e associações com os nomes das colunas
try {
    $pdo = new PDO('sqlite:' . $dbPath);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Falha ao conectar ao banco SQLite: ' . $e->getMessage());

    http_response_code(500);
    exit('Não foi possível acessar o banco de dados.');
}