<?php

require_once __DIR__ . '/relatorio_queries.php';

$semana = 'Semana 06/07 - 12/07';

try {
    $dados = getDadosEvolucaoQuantitativo(
        $pdo,
        $semana
    );

    echo '<pre>';
    print_r($dados);
    echo '</pre>';

    echo 'Total de registros: ' . count($dados);

} catch (Throwable $e) {
    echo 'Erro: ' . htmlspecialchars($e->getMessage());
}