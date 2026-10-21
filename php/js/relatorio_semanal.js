/**
 * Lê os dados JSON de um elemento HTML pelo seu ID.
 *
 * @param {string} id - ID do elemento que contém os dados.
 * @returns {Array} Dados convertidos de JSON para JavaScript.
 */
function lerDadosJson(id) {
    const elemento = document.getElementById(id);

    // Se o elemento não existir, retorna uma lista vazia.
    if (!elemento) {
        console.warn(`Elemento com ID "${id}" não encontrado.`);
        return [];
    }

    try {
        return JSON.parse(elemento.textContent);
    } catch (erro) {
        console.error(`Erro ao interpretar os dados "${id}":`, erro);
        return [];
    }
}

/**
 * Inicializa todos os gráficos do relatório.
 */
function inicializarGraficos() {
    // Confere se a biblioteca Chart.js foi carregada.
    if (typeof Chart === 'undefined') {
        console.error('A biblioteca Chart.js não foi carregada.');
        return;
    }

    // 1. GRÁFICO DE SITUAÇÃO
    const dadosSituacao = lerDadosJson('dados-situacao');
    const elementoSituacao = document.getElementById('graficoSituacao');

    if (elementoSituacao) {
        new Chart(elementoSituacao, {
            type: 'doughnut',

            data: {
                labels: dadosSituacao.map(item => item.Situacao),

                datasets: [{
                    data: dadosSituacao.map(item => Number(item.total)),
                    backgroundColor: [
                        '#2ecc71',
                        '#e74c3c',
                        '#f1c40f'
                    ]
                }]
            },

            options: {
                responsive: true,

                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const valores = context.dataset.data;
                                const total = valores.reduce(
                                    (soma, valor) => soma + valor,
                                    0
                                );

                                const valor = context.raw;
                                const percentual = total > 0
                                    ? (valor / total * 100).toFixed(1)
                                    : 0;

                                return `${context.label}: ${valor} (${percentual}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. GRÁFICO DE CLASSIFICAÇÃO
    const dadosClassificacao = lerDadosJson('dados-classificacao');
    const elementoClassificacao = document.getElementById('graficoClassificacao');

    if (elementoClassificacao) {
        new Chart(elementoClassificacao, {
            type: 'bar',

            data: {
                labels: dadosClassificacao.map(item => item.classificacao),

                datasets: [{
                    label: 'Quantidade',
                    data: dadosClassificacao.map(item => Number(item.total)),
                    backgroundColor: '#3498db'
                }]
            },

            options: {
                responsive: true,

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // 3. GRÁFICO DE PERIODICIDADE
    const dadosPeriodicidade = lerDadosJson('dados-periodicidade');
    const elementoPeriodicidade = document.getElementById('graficoPeriodicidade');

    if (elementoPeriodicidade) {
        new Chart(elementoPeriodicidade, {
            type: 'bar',

            data: {
                labels: dadosPeriodicidade.map(item => item.periodicidade_nome),

                datasets: [{
                    label: 'Quantidade',
                    data: dadosPeriodicidade.map(item => Number(item.total)),
                    backgroundColor: '#9b59b6'
                }]
            },

            options: {
                responsive: true,

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // 4. GRÁFICO DE CLASSIFICAÇÃO POR MUNICÍPIO
    const dadosClassificacaoMunicipio =
        lerDadosJson('dados-classificacao-municipio');

    const elementoClassificacaoMunicipio =
        document.getElementById('graficoClassificacaoMunicipio');

    if (elementoClassificacaoMunicipio) {
        // Obtém a lista de municípios sem repetir nomes.
        const municipios = [
            ...new Set(
                dadosClassificacaoMunicipio.map(item => item.municipio_nome)
            )
        ];

        // Obtém as classificações sem repetir valores.
        const classificacoes = [
            ...new Set(
                dadosClassificacaoMunicipio.map(item => item.classificacao)
            )
        ];

        // Define uma cor para cada classificação.
        const cores = [
            '#2ecc71',
            '#e74c3c',
            '#f1c40f',
            '#3498db',
            '#9b59b6'
        ];

        // Monta um conjunto de barras para cada classificação.
        const conjuntosDados = classificacoes.map((classificacao, indice) => {
            return {
                label: classificacao,

                data: municipios.map(municipio => {
                    const registro = dadosClassificacaoMunicipio.find(item =>
                        item.municipio_nome === municipio &&
                        item.classificacao === classificacao
                    );

                    return registro ? Number(registro.total) : 0;
                }),

                backgroundColor: cores[indice % cores.length]
            };
        });

        new Chart(elementoClassificacaoMunicipio, {
            type: 'bar',

            data: {
                labels: municipios,
                datasets: conjuntosDados
            },

            options: {
                responsive: true,

                scales: {
                    x: {
                        stacked: false
                    },

                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // 5. GRÁFICO DE MUDANÇAS DE CLASSIFICAÇÃO
    const dadosMudancas = lerDadosJson('dados-mudancas');
    const elementoMudancas = document.getElementById('graficoMudancas');

    if (elementoMudancas) {
        new Chart(elementoMudancas, {
            type: 'bar',

            data: {
                labels: dadosMudancas.map(item => item.mudanca_classificacao),

                datasets: [{
                    label: 'Quantidade',
                    data: dadosMudancas.map(item => Number(item.total)),
                    backgroundColor: '#e67e22'
                }]
            },

            options: {
                responsive: true,

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

    // 6. GRÁFICO DE EVOLUÇÃO QUANTITATIVA
    const dadosEvolucao = lerDadosJson('dados-evolucao');
    const elementoEvolucao = document.getElementById('graficoEvolucao');

    if (elementoEvolucao) {
        new Chart(elementoEvolucao, {
            type: 'line',

            data: {
                // As datas são usadas como rótulos no eixo horizontal.
                labels: dadosEvolucao.map(item => item.analise_data),

                datasets: [{
                    label: 'Média do quantitativo',

                    data: dadosEvolucao.map(item => ({
                        x: item.analise_data,
                        y: Number(item.media_quantitativo),
                        classificacao: item.classificacao_historica
                    })),

                    borderColor: '#2980b9',
                    backgroundColor: 'rgba(41, 128, 185, 0.2)',
                    tension: 0.2,
                    fill: false
                }]
            },

            options: {
                responsive: true,

                parsing: false,

                scales: {
                    x: {
                        type: 'category'
                    },

                    y: {
                        beginAtZero: true
                    }
                },

                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const ponto = context.raw;

                                return [
                                    `Média: ${ponto.y}`,
                                    `Classificação: ${ponto.classificacao ?? 'Não informada'}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }
}
}

// Executa a inicialização quando o HTML estiver pronto.
document.addEventListener('DOMContentLoaded', inicializarGraficos);
