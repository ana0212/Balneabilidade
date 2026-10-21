<?php

require_once 'queries.php';

$historico = getHistorico($pdo, 1);

foreach ($historico as $analise) {
    echo $analise['analise_data'] . ' - ';
    echo $analise['quantitativo'] . ' - ';
    echo ($analise['classificacao'] ?? 'Sem classificação');
    echo '<br>';
}