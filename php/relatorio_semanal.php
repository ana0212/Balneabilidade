<?php

require_once __DIR__ . '/relatorio_queries.php';
require_once  __DIR__ . '/helpers.php';

try {
    // Busca as semanas disponíveis no modelo
    $semanas = getSemanasDisponiveis($pdo);

    // Identifica a semana selecionada
    $semanaSelecionada = $_GET['semana_referencia'] ?? '';

    if (
        $semanaSelecionada === '' ||
        !in_array($semanaSelecionada, $semanas, true)
    ) {
        $semanaSelecionada = $semanas[0] ?? '';
    }

    /**
     * Busca os indicadores do panorama semanal.
     * Esta consulta ainda usa tratamento direto; vamos decidir
     * depois se ela deve ser isolada ou tratada como essencial.
     */
    if ($semanaSelecionada !== '') {
        $indicadores = getIndicadoresPanorama(
            $pdo,
            $semanaSelecionada
        );
    } else {
        $indicadores = [
            'total_trechos' => 0,
            'total_municipios' => 0,
            'total_analises' => 0,
            'trechos_excelentes' => 0
        ];
    }

    // Busca dados para o gráfico de situação para banho
    $resultadoSituacao = executarConsultaComSeguranca(
        fn() => getDadosGraficoSituacao(
            $pdo,
            $semanaSelecionada
        ),
        'gráfico de situação'
    );

    $dadosSituacao = $resultadoSituacao['dados'];
    $erroDadosSituacao = !$resultadoSituacao['sucesso'];

    // Busca dados para o gráfico de classificação
    $resultadoClassificacao = executarConsultaComSeguranca(
        fn() => getDadosGraficoClassificacao(
            $pdo,
            $semanaSelecionada
        ),
        'gráfico de classificação'
    );

    $dadosClassificacao = $resultadoClassificacao['dados'];
    $erroDadosClassificacao = !$resultadoClassificacao['sucesso'];

    // Busca dados para o gráfico de periodicidade
    $resultadoPeriodicidade = executarConsultaComSeguranca(
        fn() => getDadosGraficoPeriodicidade(
            $pdo,
            $semanaSelecionada
        ),
        'gráfico de periodicidade'
    );

    $dadosPeriodicidade = $resultadoPeriodicidade['dados'];
    $erroDadosPeriodicidade = !$resultadoPeriodicidade['sucesso'];

    // Busca dados para o gráfico de classificação por município
    $resultadoClassificacaoMunicipio = executarConsultaComSeguranca(
        fn() => getDadosGraficoClassificacaoMunicipio(
            $pdo,
            $semanaSelecionada
        ),
        'gráfico de classificação por município'
    );

    $dadosClassificacaoMunicipio = $resultadoClassificacaoMunicipio['dados'];
    $erroDadosClassificacaoMunicipio =
        !$resultadoClassificacaoMunicipio['sucesso'];

    // Busca dados para o gráfico de mudanças na classificação
    $resultadoMudancas = executarConsultaComSeguranca(
        fn() => getDadosGraficoMudancas(
            $pdo,
            $semanaSelecionada
        ),
        'gráfico de mudanças na classificação'
    );

    $dadosMudancas = $resultadoMudancas['dados'];
    $erroDadosMudancas = !$resultadoMudancas['sucesso'];

    // Busca dados para a tabela de mudanças
    $resultadoTabelaMudancas = executarConsultaComSeguranca(
        fn() => getDadosTabelaMudancas(
            $pdo,
            $semanaSelecionada
        ),
        'tabela de mudanças'
    );

    $dadosTabelaMudancas = $resultadoTabelaMudancas['dados'];
    $erroTabelaMudancas = !$resultadoTabelaMudancas['sucesso'];

    // Busca os dados para a tabela de análises da semana
    $classificacaoTabela = $_GET['classificacao_tabela'] ?? '';
    $municipioTabela = $_GET['municipio_tabela'] ?? '';

    $classificacoesTabela = [];
    $municipiosTabela = [];
    $dadosTabelaTrechos = [];

    $erroClassificacoesTabela = false;
    $erroMunicipiosTabela = false;
    $erroTabelaTrechos = false;

    if ($semanaSelecionada !== '') {

        // Busca as opções de classificação para o filtro
        $resultadoClassificacoesTabela = executarConsultaComSeguranca(
            fn() => getClassificacoesFiltroTabela(
                $pdo,
                $semanaSelecionada
            ),
            'filtro de classificação da tabela'
        );

        $classificacoesTabela = $resultadoClassificacoesTabela['dados'];
        $erroClassificacoesTabela = !$resultadoClassificacoesTabela['sucesso'];

        // Busca as opções de município para o filtro
        $resultadoMunicipiosTabela = executarConsultaComSeguranca(
            fn() => getMunicipiosFiltroTabela(
                $pdo,
                $semanaSelecionada
            ),
            'filtro de município da tabela'
        );

        $municipiosTabela = $resultadoMunicipiosTabela['dados'];
        $erroMunicipiosTabela = !$resultadoMunicipiosTabela['sucesso'];

        // Valida os filtros recebidos pelo GET
        if (!in_array($classificacaoTabela, $classificacoesTabela, true)) {
            $classificacaoTabela = '';
        }

        if (!in_array($municipioTabela, $municipiosTabela, true)) {
            $municipioTabela = '';
        }

        // Busca os dados da tabela com os filtros validados
        $resultadoTabelaTrechos = executarConsultaComSeguranca(
            fn() => getDadosTabelaTrechosSemana(
                $pdo,
                $semanaSelecionada,
                $classificacaoTabela,
                $municipioTabela
            ),
            'tabela de análises da semana'
        );

        $dadosTabelaTrechos = $resultadoTabelaTrechos['dados'];
        $erroTabelaTrechos = !$resultadoTabelaTrechos['sucesso'];
    }


    // Busca os dados para o gráfico de evolução do quantitativo
    $trechoEvolucao = $_GET['trecho_evolucao'] ?? '';

    $dadosEvolucao = [];
    $trechosEvolucao = [];

    $erroTrechosEvolucao = false;
    $erroDadosEvolucao = false;

    if ($semanaSelecionada !== '') {

        // Busca os trechos disponíveis para o filtro
        $resultadoTrechosEvolucao = executarConsultaComSeguranca(
            fn() => getTrechosEvolucao($pdo),
            'opções de trechos do gráfico de evolução'
        );

        $trechosEvolucao = $resultadoTrechosEvolucao['dados'];
        $erroTrechosEvolucao = !$resultadoTrechosEvolucao['sucesso'];

        // Valida o trecho recebido pelo GET
        if (!in_array($trechoEvolucao, $trechosEvolucao, true)) {
            $trechoEvolucao = '';
        }

        // Busca os dados do gráfico com o filtro validado
        $resultadoDadosEvolucao = executarConsultaComSeguranca(
            fn() => getDadosEvolucaoQuantitativo(
                $pdo,
                $semanaSelecionada,
                $trechoEvolucao
            ),
            'gráfico de evolução do quantitativo'
        );

        $dadosEvolucao = $resultadoDadosEvolucao['dados'];
        $erroDadosEvolucao = !$resultadoDadosEvolucao['sucesso'];
    }
} catch (Throwable $e) {
    error_log(
        'Falha inesperada ao carregar relatório semanal: '
        . $e->getMessage()
    );

    http_response_code(500);

    exit(
        'Não foi possível carregar o relatório. '
        . 'Tente novamente mais tarde.'
    );
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Relatório Semanal de Balneabilidade</title>

    <link rel="stylesheet" href="css/relatorio_style.css">
</head>

<body>

<div class="container">

    <header class="cabecalho">
        <div class="relatorio-header">
            <div>
                <h1>Relatório Semanal de Balneabilidade</h1>
                <p>Indicadores e análises da qualidade da água.</p>
            </div>

            <a
                href="relatorio_pdf.php?semana=<?= urlencode($semanaSelecionada) ?>"
                class="dashboard-button"
                target="_blank"
            >
                Baixar PDF
            </a>
        </div>
    </header>

    <section class="filtros">
        <form method="GET" action="relatorio_semanal.php">

            <label for="semana_referencia">
                Semana de referência
            </label>

            <select
                name="semana_referencia"
                id="semana_referencia"
                onchange="this.form.submit()"
            >
                <?php if (empty($semanas)): ?>
                    <option value="">
                        Nenhuma semana disponível
                    </option>
                <?php else: ?>
                    <?php foreach ($semanas as $semana): ?>
                        <option
                            value="<?= escaparHtml($semana) ?>"
                            <?= $semana === $semanaSelecionada
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escaparHtml($semana) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <noscript>
                <button type="submit">Aplicar filtro</button>
            </noscript>

        </form>
    </section>

    <section class="panorama">
        <!-- Gráfico à esquerda -->
        <div class="grafico-container">
            <h2>Situação dos trechos</h2>

            <p class="grafico-descricao">
                Distribuição por situação de balneabilidade
            </p>

            <div class="grafico-rosca">
                <?php if ($erroDadosSituacao): ?>
                    <p>Não foi possível carregar este gráfico.</p>
                <?php endif; ?>
                <canvas id="graficoSituacao" <?= $erroDadosSituacao ? 'style="display: none;"' : '' ?>></canvas>
            </div>
        </div>

        <!-- Cards à direita -->
        <div class="panorama-cards">

            <article class="card">
                <span class="card-titulo">Trechos monitorados</span>
                <strong class="card-valor">
                    <?= number_format((int) $indicadores['total_trechos'], 0, ',', '.') ?>
                </strong>
            </article>

            <article class="card">
                <span class="card-titulo">Municípios</span>
                <strong class="card-valor">
                    <?= number_format((int) $indicadores['total_municipios'], 0, ',', '.') ?>
                </strong>
            </article>

            <article class="card">
                <span class="card-titulo">Análises realizadas</span>
                <strong class="card-valor">
                    <?= number_format((int) $indicadores['total_analises'], 0, ',', '.') ?>
                </strong>
            </article>

            <article class="card card-destaque">
                <span class="card-titulo">Trechos excelentes</span>
                <strong class="card-valor">
                    <?= number_format((int) $indicadores['trechos_excelentes'], 0, ',', '.') ?>
                </strong>
            </article>
        </div>
    </section>

    <!-- Gráfico de Barras - Classificação Histórica -->
    <section class="graficos-barras">
        <div class="grafico-container">
            <h2>Classificação histórica</h2>

            <p class="grafico-descricao">
                Trechos distintos por classificação na semana selecionada
            </p>

            <div class="grafico-barras-area">
                <?php if ($erroDadosClassificacao): ?>
                    <p>Não foi possível carregar este gráfico.</p>
                <?php endif; ?>
                <canvas id="graficoClassificacao" <?= $erroDadosClassificacao ? 'style="display: none;"' : '' ?>></canvas>
            </div>
        </div>
        
        <!-- Gráfico de barras - Periodicidade -->
        <div class="grafico-container">
            <h2>Periodicidade do monitoramento</h2>

            <p class="grafico-descricao">
                Trechos distintos por periodicidade
            </p>

            <div class="grafico-barras-area">
                <?php if ($erroDadosPeriodicidade): ?>
                    <p>Não foi possível carregar este gráfico.</p>
                <?php endif; ?>
                <canvas id="graficoPeriodicidade" <?= $erroDadosPeriodicidade ? 'style="display: none;"' : '' ?>></canvas>
            </div>
        </div>
    </section>

    <!-- Gráfico de barras - Classificação por município -->
    <section class="grafico-container grafico-municipios">
        <h2>Distribuição da classificação por município na semana selecionada</h2>

        <p class="grafico-descricao">
            Quantidade de trechos distintos por município e classificação histórica.
        </p>

        <div class="grafico-municipios-area">
            <?php if ($erroDadosClassificacaoMunicipio): ?>
                <p>Não foi possível carregar este gráfico.</p>
            <?php endif; ?>
            <canvas id="graficoClassificacaoMunicipio" <?= $erroDadosClassificacaoMunicipio ? 'style="display: none;"' : '' ?>></canvas>
        </div>
    </section>

    <!-- Gráfico de barras - Mudanças na Classificação -->
    <section class="grafico-container grafico-mudancas">
        <h2>Mudanças de classificação</h2>

        <p class="grafico-descricao">
            Quantidade de mudanças de classificação na semana selecionada.
        </p>

        <div class="grafico-mudancas-area">
            <?php if ($erroDadosMudancas): ?>
                <p>Não foi possível carregar este gráfico.</p>
            <?php endif; ?>
            <canvas id="graficoMudancas" <?= $erroDadosMudancas ? 'style="display: none;"' : '' ?>></canvas>
        </div>
    </section>
    
    <!-- Tabela - Mudanças na Classificação -->
    <section class="tabela-container">
        <h2>Detalhamento das mudanças de classificação</h2>

        <p class="grafico-descricao">
            Análises com mudança de classificação na semana selecionada.
        </p>

        <?php if ($erroTabelaMudancas): ?>
            <p>Não foi possível carregar a tabela de mudanças.</p>
        <?php else: ?>
        <div class="tabela-responsiva">
            <table class="tabela-mudancas">
                <thead>
                    <tr>
                        <th>Data da análise</th>
                        <th>Quantitativo</th>
                        <th>Trecho</th>
                        <th>Classificação na semana selecionada</th>
                        <th>Classificação anterior</th>
                        <th>Município</th>
                        <th>Evolução da classificação</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($dadosTabelaMudancas)): ?>
                        <tr>
                            <td colspan="7" class="tabela-vazia">
                                Nenhuma mudança encontrada nesta semana.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dadosTabelaMudancas as $linha): ?>

                            <?php
                            $evolucao = trim(
                                (string) ($linha['evolucao_classificacao'] ?? '')
                            );

                            $evolucaoNormalizada = mb_strtolower(
                                $evolucao,
                                'UTF-8'
                            );

                            $classeLinha = '';

                            if (
                                $evolucaoNormalizada === 'passou para imprópria'
                            ) {
                                $classeLinha = 'evolucao-impropria';
                            } elseif (
                                $evolucaoNormalizada === 'desceu de categoria'
                            ) {
                                $classeLinha = 'evolucao-desceu';
                            } elseif (
                                $evolucaoNormalizada === 'melhorou para excelente'
                            ) {
                                $classeLinha = 'evolucao-excelente';
                            } elseif (
                                $evolucaoNormalizada === 'melhorou de categoria'
                                || $evolucaoNormalizada === 'melhorou de categoria'
                            ) {
                                $classeLinha = 'evolucao-melhorou';
                            }

                            $quantitativo = $linha['quantitativo'] ?? null;

                            $quantitativoAlto = is_numeric($quantitativo)
                                && (float) $quantitativo > 1000;

                            // Prioridade: quantitativo alto deixa a linha rosa-claro.
                            if ($quantitativoAlto) {
                                $classeLinha = 'quantitativo-alto';
                            }

                            $dataFormatada = '';

                            if (!empty($linha['analise_data'])) {
                                $timestamp = strtotime($linha['analise_data']);

                                $dataFormatada = $timestamp !== false
                                    ? date('d/m/Y', $timestamp)
                                    : $linha['analise_data'];
                            }
                            ?>

                            <tr class="<?= escaparHtml($classeLinha) ?>">
                                <td><?= escaparHtml($dataFormatada) ?></td>

                                <td class="<?= $quantitativoAlto
                                    ? 'celula-quantitativo-alto'
                                    : '' ?>">
                                    <?= escaparHtml($quantitativo ?? '—') ?>
                                </td>

                                <td><?= escaparHtml($linha['trecho_nome'] ?? '—') ?></td>

                                <td>
                                    <?= escaparHtml(
                                        $linha['classificacao_historica'] ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= escaparHtml(
                                        $linha['classificacao_anterior'] ?? '—'
                                    ) ?>
                                </td>

                                <td><?= escaparHtml($linha['municipio_nome'] ?? '—') ?></td>

                                <td>
                                    <?= escaparHtml($evolucao ?: '—') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- Tabela - Trechos analisados na semana selecionada -->
    <section class="tabela-container">
        <h2>Trechos analisados na semana</h2>

        <p class="grafico-descricao">
            Consulte os resultados por classificação histórica e município.
        </p>

        <form method="GET" action="relatorio_semanal.php" class="filtros-tabela">
            <input
                type="hidden"
                name="semana_referencia"
                value="<?= escaparHtml($semanaSelecionada) ?>"
            >

            <div class="campo-filtro">
                <label for="classificacao_tabela">Classificação</label>

                <?php if ($erroClassificacoesTabela): ?>
                    <p>Não foi possível carregar as opções de classificação.</p>
                <?php endif; ?>
                <select name="classificacao_tabela" id="classificacao_tabela" <?= $erroClassificacoesTabela ? 'disabled' : '' ?>>
                    <option value="">Todas as classificações</option>

                    <?php foreach ($classificacoesTabela as $classificacao): ?>
                        <option
                            value="<?= escaparHtml($classificacao) ?>"
                            <?= $classificacao === $classificacaoTabela
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escaparHtml($classificacao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo-filtro">
                <label for="municipio_tabela">Município</label>

                <?php if ($erroMunicipiosTabela): ?>
                    <p>Não foi possível carregar as opções de município.</p>
                <?php endif; ?>
                <select name="municipio_tabela" id="municipio_tabela" <?= $erroMunicipiosTabela ? 'disabled' : '' ?>>
                    <option value="">Todos os municípios</option>

                    <?php foreach ($municipiosTabela as $municipio): ?>
                        <option
                            value="<?= escaparHtml($municipio) ?>"
                            <?= $municipio === $municipioTabela
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escaparHtml($municipio) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="acoes-filtro">
                <button type="submit">Filtrar tabela</button>

                <a href="relatorio_semanal.php?semana_referencia=<?= rawurlencode($semanaSelecionada) ?>">
                    Limpar filtros
                </a>
            </div>
        </form>

        <?php if ($erroTabelaTrechos): ?>
            <p>Não foi possível carregar a tabela de análises da semana.</p>
        <?php else: ?>
        <div class="tabela-responsiva">
            <table class="tabela-mudancas tabela-trechos">
                <thead>
                    <tr>
                        <th>Data da análise</th>
                        <th>Quantitativo</th>
                        <th>Trecho</th>
                        <th>Estação</th>
                        <th>Município</th>
                        <th>Classificação no período selecionado</th>
                        <th>Classificação anterior</th>
                        <th>Fonte</th>
                        <th>Periodicidade</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($dadosTabelaTrechos)): ?>
                        <tr>
                            <td colspan="9" class="tabela-vazia">
                                Nenhum trecho encontrado para os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dadosTabelaTrechos as $linha): ?>
                            <?php
                            $impropria = mb_strtolower(
                                trim((string) ($linha['classificacao_historica'] ?? '')),
                                'UTF-8'
                            ) === 'imprópria';

                            $quantitativo = $linha['quantitativo'] ?? null;

                            $quantitativoAlto = is_numeric($quantitativo)
                                && (float) $quantitativo > 1000;

                            $classeLinha = $impropria
                                ? 'linha-impropria'
                                : ($quantitativoAlto ? 'linha-quantitativo-alto' : '');

                            $data = $linha['analise_data'] ?? '';
                            $timestamp = $data !== '' ? strtotime($data) : false;
                            $dataFormatada = $timestamp !== false
                                ? date('d/m/Y', $timestamp)
                                : $data;
                            ?>

                            <tr class="<?= escaparHtml($classeLinha) ?>">
                                <td><?= escaparHtml($dataFormatada) ?></td>

                                <td class="<?= $quantitativoAlto
                                    ? 'celula-quantitativo-amarelo'
                                    : '' ?>">
                                    <?= escaparHtml($quantitativo ?? '—') ?>
                                </td>

                                <td><?= escaparHtml($linha['trecho_nome'] ?? '—') ?></td>
                                <td><?= escaparHtml($linha['estacao'] ?? '—') ?></td>
                                <td><?= escaparHtml($linha['municipio_nome'] ?? '—') ?></td>

                                <td class="<?= $impropria
                                    ? 'celula-classificacao-impropria'
                                    : '' ?>">
                                    <?= escaparHtml($linha['classificacao_historica'] ?? '—') ?>
                                </td>

                                <td>
                                    <?= escaparHtml($linha['classificacao_anterior'] ?? '—') ?>
                                </td>

                                <td><?= escaparHtml($linha['Fonte'] ?? '—') ?></td>
                                <td><?= escaparHtml($linha['Periodicidade'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
    
    <!-- Gráfico de Linha - Evolução do quantitativo -->
    <section class="grafico-container grafico-evolucao">
        <h2>Evolução do quantitativo por trecho</h2>

        <p class="grafico-descricao">
            Média do quantitativo ao longo do tempo até a semana selecionada.
            A parte destacada representa a semana de referência.
        </p>

        <form method="GET" action="relatorio_semanal.php"
            class="filtros-tabela">

            <input
                type="hidden"
                name="semana_referencia"
                value="<?= escaparHtml($semanaSelecionada) ?>"
            >

            <label for="trecho_evolucao">Trecho monitorado</label>

            <?php if ($erroTrechosEvolucao): ?>
                <p>Não foi possível carregar as opções de trechos.</p>
            <?php endif; ?>

            <select
                name="trecho_evolucao"
                id="trecho_evolucao"
                <?= $erroTrechosEvolucao ? 'disabled' : '' ?>
            >
                <option value="">Todos os trechos</option>

                <?php foreach ($trechosEvolucao as $trecho): ?>
                    <option
                        value="<?= escaparHtml($trecho) ?>"
                        <?= $trecho === $trechoEvolucao ? 'selected' : '' ?>
                    >
                        <?= escaparHtml($trecho) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Atualizar gráfico</button>
        </form>

        <div class="grafico-evolucao-area">
            <?php if ($erroDadosEvolucao): ?>
                <p>Não foi possível carregar este gráfico.</p>
            <?php endif; ?>
            <canvas id="graficoEvolucao" <?= $erroDadosEvolucao ? 'style="display: none;"' : '' ?>></canvas>
        </div>
    </section>
</div>

<!-- Biblioteca utilizada para desenhar os gráficos. -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Dados gerados pelo PHP e disponibilizados ao JavaScript. -->
<script type="application/json" id="dados-situacao">
<?= json_encode(
    $dadosSituacao,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
    JSON_HEX_QUOT | JSON_HEX_AMP
) ?>
</script>

<script type="application/json" id="dados-classificacao">
<?= json_encode(
    $dadosClassificacao,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
    JSON_HEX_QUOT | JSON_HEX_AMP
) ?>
</script>

<script type="application/json" id="dados-periodicidade">
<?= json_encode(
    $dadosPeriodicidade,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
    JSON_HEX_QUOT | JSON_HEX_AMP
) ?>
</script>

<script type="application/json" id="dados-classificacao-municipio">
<?= json_encode(
    $dadosClassificacaoMunicipio,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
    JSON_HEX_QUOT | JSON_HEX_AMP
) ?>
</script>

<script type="application/json" id="dados-mudancas">
<?= json_encode(
    $dadosMudancas,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
    JSON_HEX_QUOT | JSON_HEX_AMP
) ?>
</script>

<script type="application/json" id="dados-evolucao">
<?= json_encode(
    $dadosEvolucao,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
    JSON_HEX_QUOT | JSON_HEX_AMP
) ?>
</script>

<!-- Arquivo externo responsável por inicializar os gráficos. -->
<script src="js/relatorio_semanal.js" defer></script>
</body>
</html>
