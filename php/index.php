<?php

require_once 'queries.php';

$municipioId = isset($_GET['municipio']) && $_GET['municipio'] !== ''
    ? (int) $_GET['municipio']
    : null;

$trechoId = isset($_GET['trecho']) && $_GET['trecho'] !== ''
    ? (int) $_GET['trecho']
    : null;


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

    <link rel="stylesheet" href="style.css">
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
                            <?= htmlspecialchars($municipio['municipio_nome']) ?>
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
                            <?= htmlspecialchars($trecho['trecho_nome']) ?>
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
                                    <?= htmlspecialchars(
                                        $situacao['municipio_nome']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $situacao['trecho_nome']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $situacao['quantitativo_mais_recente']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $situacao['qtd_analises']
                                    ) ?>
                                </td>

                                <td>

                                    <span class="badge <?= $classe ?>">
                                        <?= htmlspecialchars($classificacao) ?>
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
                                        <?= htmlspecialchars(
                                            $analise['analise_data']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $analise['quantitativo']
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="badge <?= $classeHistorica ?>"
                                        >
                                            <?= htmlspecialchars(
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