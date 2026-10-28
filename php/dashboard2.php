
<?php

require_once __DIR__ . '/helpers.php';

// Link do público do dash
$publicDashboardUrl = 'http://localhost:3000/public/dashboard/07a364e3-31d1-4a7b-80dc-455f1b5762b1';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Balneabilidade</title>
</head>

<body>
    <h1>Dashboard de Balneabilidade</h1>

    <iframe
        src="<?= escaparHtml($publicDashboardUrl) ?>#titled=true&bordered=false"
        width="100%"
        height="800"
        frameborder="0"
        allowfullscreen>
    </iframe>
</body>

</html>
