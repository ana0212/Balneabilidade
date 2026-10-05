<?php

$dbPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'balneabilidade.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}