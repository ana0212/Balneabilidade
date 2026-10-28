<?php

require_once __DIR__ . '/queries.php';
require_once  __DIR__ . '/helpers.php';

// Validação dos filtros
try {
    $municipioId = validarIdOpcional($_GET, 'municipio');
    $trechoId = validarIdOpcional($_GET, 'trecho');
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    exit('Os filtros informados são inválidos.');
}


// Dados dos filtros
$municipios = getMunicipios($pdo);
$trechos = getTrechos($pdo, $municipioId);


// Situação atual
$situacoes = getSituacaoAtual($pdo, $trechoId);


// Histórico: somente quando um trecho foi selecionado
$historico = [];

if ($trechoId !== null) {
    $historico = getHistorico($pdo, $trechoId);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Balneabilidade da Paraíba</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<main class="container">

    <!-- Cabeçalho -->
    <header class="page-header">

        <h1>Balneabilidade da Paraíba</h1>

        <p>
            Consulta dos trechos monitorados e resultados das análises
            de qualidade da água.
        </p>

    </header>

    <!-- Acessos aos relatórios -->
    <section class="dashboard-card">
        <div class="dashboard-content">
            <div class="dashboard-text">
                <h2>Dashboard de Balneabilidade</h2>
                <p>
                    Explore indicadores, mapas, classificações e a evolução
                    histórica da qualidade da água nos trechos monitorados.
                </p>
            </div>

            <a href="dashboard2.php" class="dashboard-button">
                Acessar dashboard
            </a>
        </div>
    </section>

    <section class="dashboard-card">
        <div class="dashboard-content">
            <div class="dashboard-text">
                <h2>Relatório Semanal de Balneabilidade</h2>
                <p>
                    Consulte os indicadores semanais, as classificações dos
                    trechos, as mudanças identificadas e a evolução histórica.
                </p>
            </div>

            <a href="relatorio_semanal.php" class="dashboard-button">
                Acessar relatório semanal
            </a>
        </div>
    </section>

    <!-- Filtros -->
    <section class="card">
        <h2>Filtros</h2>
        <form method="GET" class="filters">
            <div class="field">
                <label for="municipio">
                    Município
                </label>

                <select
                    name="municipio"
                    id="municipio"
                    onchange="this.form.submit()"
                >

                    <option value="">
                        Todos os municípios
                    </option>

                    <?php foreach ($municipios as $municipio): ?>

                        <option
                            value="<?= $municipio['municipio_id'] ?>"
                            <?= $municipioId === (int) $municipio['municipio_id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escaparHtml($municipio['municipio_nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="trecho">
                    Trecho
                </label>

                <select
                    name="trecho"
                    id="trecho"
                >

                    <option value="">
                        Todos os trechos
                    </option>

                    <?php foreach ($trechos as $trecho): ?>

                        <option
                            value="<?= $trecho['trecho_id'] ?>"
                            <?= $trechoId === (int) $trecho['trecho_id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escaparHtml($trecho['trecho_nome']) ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field button-field">
                <button type="submit">
                    Consultar
                </button>
            </div>
        </form>
    </section>


    <!-- Situação atual -->
    <section class="card">
        <div class="section-header">
            <div>
                <h2>Situação atual</h2>
                <p>
                    Classificação baseada nas análises mais recentes
                    de cada trecho.
                </p>
            </div>
        </div>

        <?php if (count($situacoes) === 0): ?>
            <div class="empty-state">
                Nenhum resultado encontrado.
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Município</th>
                            <th>Trecho</th>
                            <th>Último resultado</th>
                            <th>Análises consideradas</th>
                            <th>Classificação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($situacoes as $situacao): ?>
                            <?php
                                $classificacao = $situacao['classificacao'];
                                $classe = strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $classificacao
                                    )
                                );
                            ?>
                            <tr>
                                <td>
                                    <?= escaparHtml(
                                        $situacao['municipio_nome']
                                    ) ?>
                                </td>
                                <td>
                                    <?= escaparHtml(
                                        $situacao['trecho_nome']
                                    ) ?>
                                </td>
                                <td>
                                    <?= escaparHtml(
                                        $situacao['quantitativo_mais_recente']
                                    ) ?>
                                </td>
                                <td>
                                    <?= escaparHtml(
                                        $situacao['qtd_analises']
                                    ) ?>
                                </td>
                                <td>

                                    <span class="badge <?= $classe ?>">
                                        <?= escaparHtml($classificacao) ?>
                                    </span>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>


    <!-- Histórico -->
    <?php if ($trechoId !== null): ?>
        <section class="card">
            <h2>Histórico de análises</h2>
            <?php if (count($historico) === 0): ?>

                <div class="empty-state">
                    Nenhuma análise encontrada para este trecho.
                </div>

            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Quantitativo</th>
                                <th>Classificação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $analise): ?>

                                <?php
                                    $classificacaoHistorica =
                                        $analise['classificacao']
                                        ?? 'Sem classificação';

                                    $classeHistorica = strtolower(
                                        str_replace(
                                            ' ',
                                            '-',
                                            $classificacaoHistorica
                                        )
                                    );
                                ?>
                                <tr>
                                    <td>
                                        <?= escaparHtml(
                                            $analise['analise_data']
                                        ) ?>
                                    </td>
                                    <td>
                                        <?= escaparHtml(
                                            $analise['quantitativo']
                                        ) ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?= $classeHistorica ?>"
                                        >
                                            <?= escaparHtml(
                                                $classificacaoHistorica
                                            ) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>