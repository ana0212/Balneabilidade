
<?php

require_once __DIR__ . '/relatorio_queries.php';

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

// Busca os indicadores da semana selecionada
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

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Busca dados para gráfico de situação para banho
$dadosSituacao = [];

if ($semanaSelecionada !== '') {
    $dadosSituacao = getDadosGraficoSituacao(
        $pdo,
        $semanaSelecionada
    );
}

// Busca dados para gráficos de barra, classificação e periodicidade
$dadosClassificacao = [];
$dadosPeriodicidade = [];

if ($semanaSelecionada !== '') {
    $dadosClassificacao = getDadosGraficoClassificacao(
        $pdo,
        $semanaSelecionada
    );

    $dadosPeriodicidade = getDadosGraficoPeriodicidade(
        $pdo,
        $semanaSelecionada
    );
}

// Busca os dados para o gráfico de classificação por município
$dadosClassificacaoMunicipio = [];

if ($semanaSelecionada !== '') {
    $dadosClassificacaoMunicipio =
        getDadosGraficoClassificacaoMunicipio(
            $pdo,
            $semanaSelecionada
        );
}

// Busca os dados para o gráfico de mudanças na classificação
$dadosMudancas = [];

if ($semanaSelecionada !== '') {
    $dadosMudancas = getDadosGraficoMudancas(
        $pdo,
        $semanaSelecionada
    );
}

// Busca os dados para a tabela de mudanças
$dadosTabelaMudancas = [];

if ($semanaSelecionada !== '') {
    $dadosTabelaMudancas = getDadosTabelaMudancas(
        $pdo,
        $semanaSelecionada
    );
}

// Busca os dados para a tabela de análises da semana
$classificacaoTabela = $_GET['classificacao_tabela'] ?? '';
$municipioTabela = $_GET['municipio_tabela'] ?? '';

$classificacoesTabela = [];
$municipiosTabela = [];
$dadosTabelaTrechos = [];

if ($semanaSelecionada !== '') {
    $classificacoesTabela = getClassificacoesFiltroTabela(
        $pdo,
        $semanaSelecionada
    );

    $municipiosTabela = getMunicipiosFiltroTabela(
        $pdo,
        $semanaSelecionada
    );

    // Valida os filtros recebidos
    if (!in_array($classificacaoTabela, $classificacoesTabela, true)) {
        $classificacaoTabela = '';
    }

    if (!in_array($municipioTabela, $municipiosTabela, true)) {
        $municipioTabela = '';
    }

    $dadosTabelaTrechos = getDadosTabelaTrechosSemana(
        $pdo,
        $semanaSelecionada,
        $classificacaoTabela,
        $municipioTabela
    );
}

// Busca os dados para o gráfico de evolução do quantitativo
$trechoEvolucao = $_GET['trecho_evolucao'] ?? '';

$dadosEvolucao = [];
$trechosEvolucao = [];

if ($semanaSelecionada !== '') {
    $dadosEvolucao = getDadosEvolucaoQuantitativo(
        $pdo,
        $semanaSelecionada,
        $trechoEvolucao
    );

    $modelo = getSqlModeloRelatorio();

    $sqlTrechos = "
        WITH modelo AS (
            $modelo
        )
        SELECT DISTINCT trecho_nome
        FROM modelo
        WHERE trecho_nome IS NOT NULL
        ORDER BY trecho_nome ASC
    ";

    $trechosEvolucao = $pdo
        ->query($sqlTrechos)
        ->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array($trechoEvolucao, $trechosEvolucao, true)) {
        $trechoEvolucao = '';
        $dadosEvolucao = getDadosEvolucaoQuantitativo(
            $pdo,
            $semanaSelecionada
        );
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Relatório Semanal de Balneabilidade</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <header class="cabecalho">
        <div>
            <h1>Relatório Semanal de Balneabilidade</h1>
            <p>
                Panorama das análises de qualidade da água
                por semana de referência.
            </p>
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
                            value="<?= escapar($semana) ?>"
                            <?= $semana === $semanaSelecionada
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escapar($semana) ?>
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
            <canvas id="graficoSituacao"></canvas>
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
    <section class="graficos-barras">
        <div class="grafico-container">
            <h2>Classificação histórica</h2>

            <p class="grafico-descricao">
                Trechos distintos por classificação na semana selecionada
            </p>

            <div class="grafico-barras-area">
                <canvas id="graficoClassificacao"></canvas>
            </div>
        </div>

        <div class="grafico-container">
            <h2>Periodicidade do monitoramento</h2>

            <p class="grafico-descricao">
                Trechos distintos por periodicidade
            </p>

            <div class="grafico-barras-area">
                <canvas id="graficoPeriodicidade"></canvas>
            </div>
        </div>
    </section>

    <section class="grafico-container grafico-municipios">
        <h2>Distribuição da classificação por município na semana selecionada</h2>

        <p class="grafico-descricao">
            Quantidade de trechos distintos por município e classificação histórica.
        </p>

        <div class="grafico-municipios-area">
            <canvas id="graficoClassificacaoMunicipio"></canvas>
        </div>
    </section>

    <section class="grafico-container grafico-mudancas">
        <h2>Mudanças de classificação</h2>

        <p class="grafico-descricao">
            Quantidade de mudanças de classificação na semana selecionada.
        </p>

        <div class="grafico-mudancas-area">
            <canvas id="graficoMudancas"></canvas>
        </div>
    </section>
    
    <section class="tabela-container">
        <h2>Detalhamento das mudanças de classificação</h2>

        <p class="grafico-descricao">
            Análises com mudança de classificação na semana selecionada.
        </p>

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

                            <tr class="<?= escapar($classeLinha) ?>">
                                <td><?= escapar($dataFormatada) ?></td>

                                <td class="<?= $quantitativoAlto
                                    ? 'celula-quantitativo-alto'
                                    : '' ?>">
                                    <?= escapar($quantitativo ?? '—') ?>
                                </td>

                                <td><?= escapar($linha['trecho_nome'] ?? '—') ?></td>

                                <td>
                                    <?= escapar(
                                        $linha['classificacao_historica'] ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= escapar(
                                        $linha['classificacao_anterior'] ?? '—'
                                    ) ?>
                                </td>

                                <td><?= escapar($linha['municipio_nome'] ?? '—') ?></td>

                                <td>
                                    <?= escapar($evolucao ?: '—') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    
    <section class="tabela-container">
        <h2>Trechos analisados na semana</h2>

        <p class="grafico-descricao">
            Consulte os resultados por classificação histórica e município.
        </p>

        <form method="GET" action="relatorio_semanal.php" class="filtros-tabela">
            <input
                type="hidden"
                name="semana_referencia"
                value="<?= escapar($semanaSelecionada) ?>"
            >

            <div class="campo-filtro">
                <label for="classificacao_tabela">Classificação</label>

                <select name="classificacao_tabela" id="classificacao_tabela">
                    <option value="">Todas as classificações</option>

                    <?php foreach ($classificacoesTabela as $classificacao): ?>
                        <option
                            value="<?= escapar($classificacao) ?>"
                            <?= $classificacao === $classificacaoTabela
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escapar($classificacao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo-filtro">
                <label for="municipio_tabela">Município</label>

                <select name="municipio_tabela" id="municipio_tabela">
                    <option value="">Todos os municípios</option>

                    <?php foreach ($municipiosTabela as $municipio): ?>
                        <option
                            value="<?= escapar($municipio) ?>"
                            <?= $municipio === $municipioTabela
                                ? 'selected'
                                : '' ?>
                        >
                            <?= escapar($municipio) ?>
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

                            <tr class="<?= escapar($classeLinha) ?>">
                                <td><?= escapar($dataFormatada) ?></td>

                                <td class="<?= $quantitativoAlto
                                    ? 'celula-quantitativo-amarelo'
                                    : '' ?>">
                                    <?= escapar($quantitativo ?? '—') ?>
                                </td>

                                <td><?= escapar($linha['trecho_nome'] ?? '—') ?></td>
                                <td><?= escapar($linha['estacao'] ?? '—') ?></td>
                                <td><?= escapar($linha['municipio_nome'] ?? '—') ?></td>

                                <td class="<?= $impropria
                                    ? 'celula-classificacao-impropria'
                                    : '' ?>">
                                    <?= escapar($linha['classificacao_historica'] ?? '—') ?>
                                </td>

                                <td>
                                    <?= escapar($linha['classificacao_anterior'] ?? '—') ?>
                                </td>

                                <td><?= escapar($linha['Fonte'] ?? '—') ?></td>
                                <td><?= escapar($linha['Periodicidade'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    
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
                value="<?= escapar($semanaSelecionada) ?>"
            >

            <label for="trecho_evolucao">Trecho monitorado</label>

            <select
                name="trecho_evolucao"
                id="trecho_evolucao"
            >
                <option value="">Todos os trechos</option>

                <?php foreach ($trechosEvolucao as $trecho): ?>
                    <option
                        value="<?= escapar($trecho) ?>"
                        <?= $trecho === $trechoEvolucao ? 'selected' : '' ?>
                    >
                        <?= escapar($trecho) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Atualizar gráfico</button>
        </form>

        <div class="grafico-evolucao-area">
            <canvas id="graficoEvolucao"></canvas>
        </div>
    </section>


</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const labelsSituacao = <?= json_encode(
        array_column($dadosSituacao, 'Situacao'),
        JSON_UNESCAPED_UNICODE
    ) ?>;

    const valoresSituacao = <?= json_encode(
        array_map('intval', array_column($dadosSituacao, 'total'))
    ) ?>;

    const canvas = document.getElementById('graficoSituacao');

    new Chart(canvas, {
        type: 'doughnut',

        data: {
            labels: labelsSituacao,

            datasets: [{
                data: valoresSituacao,
                backgroundColor: [
                    '#dc3545',
                    '#198754'
                ],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',

            plugins: {
                legend: {
                    position: 'bottom'
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const valor = context.raw;

                            const total = context.dataset.data
                                .reduce((soma, item) => soma + item, 0);

                            const percentual = total > 0
                                ? (valor / total * 100).toFixed(1)
                                : '0.0';

                            return `${context.label}: ${valor} trechos (${percentual}%)`;
                        }
                    }
                }
            }
        }
    });
    // Dados do gráfico de classificação
    const dadosClassificacao = <?= json_encode(
        $dadosClassificacao,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
        JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    // Dados do gráfico de periodicidade
    const dadosPeriodicidade = <?= json_encode(
        $dadosPeriodicidade,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
        JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    // Gráfico 1: classificação histórica
    new Chart(document.getElementById('graficoClassificacao'), {
        type: 'bar',

        data: {
            labels: dadosClassificacao.map(
                item => item.classificacao ?? 'Sem classificação'
            ),

            datasets: [{
                label: 'Trechos distintos',
                data: dadosClassificacao.map(
                    item => Number(item.total)
                ),
                backgroundColor: '#1976a8',
                borderRadius: 5,
                maxBarThickness: 55
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Quantidade de trechos'
                    }
                },

                x: {
                    ticks: {
                        autoSkip: false
                    }
                }
            }
        }
    });

    // Gráfico 2: periodicidade
    new Chart(document.getElementById('graficoPeriodicidade'), {
        type: 'bar',

        data: {
            labels: dadosPeriodicidade.map(
                item => item.periodicidade_nome
            ),

            datasets: [{
                label: 'Trechos distintos',
                data: dadosPeriodicidade.map(
                    item => Number(item.total)
                ),
                backgroundColor: ['#21966b', '#e6a23c'],
                borderRadius: 5,
                maxBarThickness: 65
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Quantidade de trechos'
                    }
                }
            }
        }
    });

    // Gráfico de classificação por município
    const dadosMunicipios = <?= json_encode(
        $dadosClassificacaoMunicipio,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
        JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    const municipios = [
        ...new Set(dadosMunicipios.map(item => item.municipio_nome))
    ];

    const classificacoes = [
        ...new Set(dadosMunicipios.map(item => item.classificacao))
    ].filter(valor => valor !== null);

    const coresClassificacao = {
        'Excelente': '#198754',
        'Muito Boa': '#75b798',
        'Boa': '#1976a8',
        'Regular': '#e6a23c',
        'Imprópria': '#dc3545'
    };

    const datasetsMunicipios = classificacoes.map(classificacao => ({
        label: classificacao,

        data: municipios.map(municipio => {
            const registro = dadosMunicipios.find(item =>
                item.municipio_nome === municipio &&
                item.classificacao === classificacao
            );

            return registro ? Number(registro.total) : 0;
        }),

        backgroundColor:
            coresClassificacao[classificacao] ?? '#78909c',

        borderRadius: 3
    }));

    new Chart(
        document.getElementById('graficoClassificacaoMunicipio'),
        {
            type: 'bar',

            data: {
                labels: municipios,
                datasets: datasetsMunicipios
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    mode: 'index',
                    intersect: false
                },

                plugins: {
                    legend: {
                        position: 'top'
                    },

                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw} trechos`;
                            }
                        }
                    }
                },

                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Município'
                        }
                    },

                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Quantidade de trechos'
                        }
                    }
                }
            }
        }
    );
    // Gráfico de mudanças na classificação
    const dadosMudancas = <?= json_encode(
        $dadosMudancas,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS |
        JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    new Chart(document.getElementById('graficoMudancas'), {
        type: 'bar',

        data: {
            labels: dadosMudancas.map(
                item => item.mudanca_classificacao
            ),

            datasets: [{
                label: 'Quantidade de mudanças',
                data: dadosMudancas.map(
                    item => Number(item.total)
                ),
                backgroundColor: '#1976a8',
                borderRadius: 5,
                maxBarThickness: 65
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                x: {
                    ticks: {
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 0
                    }
                },

                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Quantidade de mudanças'
                    }
                }
            }
        }
    });

    // Gráfico de evolução do quantitativo    
document.addEventListener('DOMContentLoaded', function () {

    const dadosEvolucao = <?= json_encode(
        $dadosEvolucao,
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;

    const canvas = document.getElementById('graficoEvolucao');

    if (!canvas || typeof Chart === 'undefined' || !dadosEvolucao.length) {
        return;
    }

    const cores = {
        'Excelente': '#1976a8',
        'Muito Boa': '#198754',
        'Boa': '#75b798',
        'Satisfatória': '#efa536',
        'Regular': '#efa536',
        'Imprópria': '#198754',
        'Sem classificação': '#dc3545'
    };

    const pontos = dadosEvolucao
        .filter(item =>
            item.analise_data &&
            item.media_quantitativo !== null &&
            Number.isFinite(Number(item.media_quantitativo))
        )
        .map(item => ({
            x: item.analise_data,
            y: Number(item.media_quantitativo),
            classificacao: item.classificacao || 'Sem classificação'
        }));

    const datas = [...new Set(pontos.map(p => p.x))].sort();

    const valoresPorData = new Map();

    pontos.forEach(p => {
        valoresPorData.set(
            `${p.x}|${p.classificacao}`,
            p
        );
    });

    const classificacoes = [
        ...new Set(pontos.map(p => p.classificacao))
    ];

    const datasets = classificacoes.map(classificacao => {
        const cor = cores[classificacao] || '#64748b';

        return {
            label: classificacao,

            data: datas.map(data =>
                valoresPorData.get(`${data}|${classificacao}`) || {
                    x: data,
                    y: null,
                    classificacao: classificacao
                }
            ),

            borderColor: cor,
            backgroundColor: cor,
            pointBackgroundColor: cor,
            pointBorderColor: '#ffffff',
            pointRadius: 4,
            pointHoverRadius: 7,
            borderWidth: 2,
            tension: 0,
            spanGaps: false
        };
    });

    new Chart(canvas, {
        type: 'line',

        data: {
            labels: datas,
            datasets: datasets
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: {
                xAxisKey: 'x',
                yAxisKey: 'y'
            },

            interaction: {
                mode: 'nearest',
                intersect: true
            },

            plugins: {
                legend: {
                    position: 'bottom'
                },

                tooltip: {
                    callbacks: {
                        title: items => items.length ? items[0].raw.x : '',

                        label: context => {
                            const ponto = context.raw;
                            const valor = Number(ponto.y).toLocaleString(
                                'pt-BR',
                                { maximumFractionDigits: 2 }
                            );

                            return [
                                `Classificação: ${ponto.classificacao}`,
                                `Média do quantitativo: ${valor}`
                            ];
                        }
                    }
                }
            },

            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Data da análise'
                    }
                },

                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Média do quantitativo'
                    }
                }
            }
        }
    });
});
</script>


</body>
</html>
