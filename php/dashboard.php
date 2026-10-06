<?php

require_once 'metabase.php';

$metabaseToken = gerarTokenMetabase();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard de Balneabilidade</title>

    <script
        defer
        src="http://localhost:3000/app/embed.js">
    </script>

    <script>
        window.metabaseConfig = {
            theme: {
                preset: "light"
            },
            isGuest: true,
            instanceUrl: "http://localhost:3000"
        };
    </script>
</head>

<body>

    <h1>Dashboard de Balneabilidade</h1>

    <metabase-dashboard
        token="<?= htmlspecialchars($metabaseToken) ?>"
        with-title="true"
        with-downloads="false">
    </metabase-dashboard>

</body>

</html>