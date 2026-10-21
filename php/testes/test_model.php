
<?php

require_once __DIR__ . '/relatorio_model.php';

try {
    $dados = getDadosRelatorio($pdo);

    echo '<h1>Teste do modelo de balneabilidade</h1>';

    echo '<p>Total de linhas retornadas: '
        . count($dados)
        . '</p>';

    if (count($dados) > 0) {
        echo '<h2>Primeiro registro</h2>';

        echo '<pre>';
        print_r($dados[0]);
        echo '</pre>';

        echo '<h2>Colunas retornadas</h2>';

        echo '<pre>';
        print_r(array_keys($dados[0]));
        echo '</pre>';
    } else {
        echo '<p>A consulta executou, mas não retornou registros.</p>';
    }

} catch (PDOException $e) {
    echo '<h2>Erro ao executar o modelo</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
