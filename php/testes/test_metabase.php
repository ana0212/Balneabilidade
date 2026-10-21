<?php

require_once 'metabase.php';

try {
    $token = gerarTokenMetabase();

    echo '<h1>Integração com Metabase</h1>';
    echo '<p>JWT gerado com sucesso.</p>';
    echo '<p>Quantidade de caracteres: ' . strlen($token) . '</p>';

} catch (Throwable $e) {
    echo '<h1>Erro</h1>';
    echo '<pre>';
    echo htmlspecialchars($e->getMessage());
    echo '</pre>';
}