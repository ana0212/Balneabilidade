import pandas as pd
import streamlit as st
import plotly.express as px
import plotly.graph_objects as go

from database import (
    carregar_classificacao,
    carregar_classificacao_historica,
    carregar_analises,
    carregar_trechos,
)

# CONFIGURAÇÃO
st.set_page_config(
    page_title="Histórico",
    layout="wide",
)

st.title("Histórico")

st.caption(
    "Evolução temporal da balneabilidade, mudanças de classificação "
    "e características do monitoramento dos trechos."
)

# CARREGAMENTO
df_classificacao = carregar_classificacao()
df_historica = carregar_classificacao_historica()
df_analises = carregar_analises()
df_trechos = carregar_trechos()


# Garantir datas
df_historica["data_referencia"] = pd.to_datetime(
    df_historica["data_referencia"]
)

df_analises["analise_data"] = pd.to_datetime(
    df_analises["analise_data"]
)


# ============================================================
# FILTROS
# ============================================================

st.subheader("Filtros")

col1, col2, col3 = st.columns(3)

with col1:

    municipios = sorted(
        df_historica["municipio_nome"]
        .dropna()
        .unique()
    )

    municipio_selecionado = st.selectbox(
        "Município",
        ["Todos"] + municipios,
    )


if municipio_selecionado != "Todos":

    df_filtro = df_historica[
        df_historica["municipio_nome"]
        == municipio_selecionado
    ].copy()

else:

    df_filtro = df_historica.copy()


with col2:

    trechos = sorted(
        df_filtro["trecho_nome"]
        .dropna()
        .unique()
    )

    trecho_selecionado = st.selectbox(
        "Trecho",
        ["Todos"] + trechos,
    )


if trecho_selecionado != "Todos":

    df_filtro = df_filtro[
        df_filtro["trecho_nome"]
        == trecho_selecionado
    ].copy()


with col3:

    classificacoes = [
        "Excelente",
        "Muito Boa",
        "Satisfatória",
        "Imprópria",
    ]

    classificacao_selecionada = st.selectbox(
        "Classificação",
        ["Todas"] + classificacoes,
    )


if classificacao_selecionada != "Todas":

    df_filtro = df_filtro[
        df_filtro["classificacao"]
        == classificacao_selecionada
    ].copy()

# 1. INDICADORES GERAIS
st.subheader("Indicadores históricos")

col1, col2, col3, col4 = st.columns(4)


qtd_trechos = df_filtro["trecho_id"].nunique()

qtd_analises = df_filtro[
    "analise_referencia_id"
].nunique()


qtd_improprias = (
    df_filtro["classificacao"]
    .eq("Imprópria")
    .sum()
)


qtd_trechos_improprios = (
    df_filtro.loc[
        df_filtro["classificacao"] == "Imprópria",
        "trecho_id",
    ]
    .nunique()
)


col1.metric(
    "Trechos analisados",
    qtd_trechos,
)

col2.metric(
    "Análises classificadas",
    qtd_analises,
)

col3.metric(
    "Ocorrências de Imprópria",
    qtd_improprias,
)

col4.metric(
    "Trechos com Imprópria",
    qtd_trechos_improprios,
)

# 2. EVOLUÇÃO DO TRECHO
if trecho_selecionado != "Todos":

    st.subheader(
        "Evolução do trecho selecionado"
    )

    serie = (
        df_analises[
            df_analises["trecho_nome"]
            == trecho_selecionado
        ]
        .sort_values("analise_data")
        .copy()
    )

    classificacao_trecho = (
        df_historica[
            df_historica["trecho_nome"]
            == trecho_selecionado
        ][
            [
                "analise_referencia_id",
                "classificacao",
            ]
        ]
    )

    serie = serie.merge(
        classificacao_trecho,
        left_on="analise_id",
        right_on="analise_referencia_id",
        how="left",
    )

    fig = go.Figure()

    # Linha principal
    fig.add_trace(
        go.Scatter(
            x=serie["analise_data"],
            y=serie["quantitativo"],
            mode="lines",
            name="Quantitativo",
        )
    )

    # Pontos por classificação
    cores = {
        "Excelente": "green",
        "Muito Boa": "blue",
        "Satisfatória": "orange",
        "Imprópria": "red",
        "Sem classificação": "gray",
    }

    for classificacao, cor in cores.items():

        dados = serie[
            serie["classificacao"]
            == classificacao
        ]

        if dados.empty:
            continue

        fig.add_trace(
            go.Scatter(
                x=dados["analise_data"],
                y=dados["quantitativo"],
                mode="markers",
                name=classificacao,
                marker=dict(
                    color=cor,
                    size=9,
                ),
            )
        )

    # Linhas de referência
    fig.add_hline(
        y=250,
        line_dash="dash",
        annotation_text="250",
    )

    fig.add_hline(
        y=500,
        line_dash="dash",
        annotation_text="500",
    )

    fig.add_hline(
        y=1000,
        line_dash="dash",
        annotation_text="1000",
    )

    fig.add_hline(
        y=2500,
        line_dash="dash",
        annotation_text="2500",
    )

    fig.update_layout(
        xaxis_title="Data da análise",
        yaxis_title="Quantitativo (NMP/100 mL)",
        hovermode="x unified",
        legend_title="Classificação",
    )

    st.plotly_chart(
        fig,
        use_container_width=True,
    )

else:

    st.info(
        "Selecione um trecho para visualizar sua evolução temporal."
    )

# 3. DISTRIBUIÇÃO HISTÓRICA DAS CLASSIFICAÇÕES
st.subheader(
    "Evolução das classificações ao longo do tempo"
)

evolucao = (
    df_filtro
    .groupby(
        [
            "data_referencia",
            "classificacao",
        ]
    )
    .size()
    .reset_index(name="trechos")
)


ordem = [
    "Excelente",
    "Muito Boa",
    "Satisfatória",
    "Imprópria",
]


evolucao = evolucao[
    evolucao["classificacao"].isin(ordem)
]


fig_evolucao = px.line(
    evolucao,
    x="data_referencia",
    y="trechos",
    color="classificacao",
    markers=True,
    category_orders={
        "classificacao": ordem
    },
    labels={
        "data_referencia": "Data",
        "trechos": "Quantidade de trechos",
        "classificacao": "Classificação",
    },
)

st.plotly_chart(
    fig_evolucao,
    use_container_width=True,
)

# 4. MUDANÇAS DE CLASSIFICAÇÃO
st.subheader(
    "Mudanças de classificação"
)


historico_mudancas = (
    df_historica
    .sort_values(
        [
            "trecho_id",
            "data_referencia",
        ]
    )
    .copy()
)


historico_mudancas["classificacao_anterior"] = (
    historico_mudancas
    .groupby("trecho_id")["classificacao"]
    .shift(1)
)


mudancas = historico_mudancas[
    (
        historico_mudancas["classificacao_anterior"]
        .notna()
    )
    &
    (
        historico_mudancas["classificacao"]
        != historico_mudancas["classificacao_anterior"]
    )
].copy()


col1, col2 = st.columns(2)

with col1:

    st.metric(
        "Total de mudanças",
        len(mudancas),
    )

with col2:

    trechos_com_mudanca = (
        mudancas["trecho_id"]
        .nunique()
    )

    st.metric(
        "Trechos com mudança",
        trechos_com_mudanca,
    )


if not mudancas.empty:

    mudancas["transicao"] = (
        mudancas["classificacao_anterior"]
        + " → "
        + mudancas["classificacao"]
    )

    transicoes = (
        mudancas
        .groupby("transicao")
        .size()
        .reset_index(name="quantidade")
        .sort_values(
            "quantidade",
            ascending=False,
        )
    )

    fig_transicoes = px.bar(
        transicoes,
        x="quantidade",
        y="transicao",
        orientation="h",
        labels={
            "quantidade": "Quantidade",
            "transicao": "Transição",
        },
    )

    st.plotly_chart(
        fig_transicoes,
        use_container_width=True,
    )

# 5. HISTÓRICO DE IMPRÓPRIA
st.subheader(
    "Histórico de ocorrência de classificação Imprópria"
)


impropria = (
    df_filtro[
        df_filtro["classificacao"]
        == "Imprópria"
    ]
    .groupby("municipio_nome")
    .agg(
        ocorrencias=("analise_referencia_id", "count"),
        trechos=("trecho_id", "nunique"),
    )
    .reset_index()
    .sort_values(
        "ocorrencias",
        ascending=False,
    )
)


if impropria.empty:

    st.info(
        "Não foram encontradas ocorrências de classificação Imprópria nos filtros selecionados."
    )

else:

    fig_impropria = px.bar(
        impropria,
        x="municipio_nome",
        y="ocorrencias",
        labels={
            "municipio_nome": "Município",
            "ocorrencias": "Ocorrências de Imprópria",
        },
    )

    st.plotly_chart(
        fig_impropria,
        use_container_width=True,
    )

# 6. PERIODICIDADE DO MONITORAMENTO
st.subheader(
    "Periodicidade do monitoramento"
)


df_monitoramento = df_trechos.copy()


if municipio_selecionado != "Todos":

    df_monitoramento = df_monitoramento[
        df_monitoramento["municipio_nome"]
        == municipio_selecionado
    ]


if trecho_selecionado != "Todos":

    df_monitoramento = df_monitoramento[
        df_monitoramento["trecho_nome"]
        == trecho_selecionado
    ]


df_monitoramento["periodicidade_nome"] = (
    df_monitoramento["periodicidade"]
    .map(
        {
            1: "Semanal",
            2: "Mensal",
        }
    )
)


col1, col2 = st.columns(2)


with col1:

    qtd_semanais = (
        df_monitoramento["periodicidade"]
        .eq(1)
        .sum()
    )

    qtd_mensais = (
        df_monitoramento["periodicidade"]
        .eq(2)
        .sum()
    )

    periodicidade = pd.DataFrame(
        {
            "Periodicidade": [
                "Semanal",
                "Mensal",
            ],
            "Trechos": [
                qtd_semanais,
                qtd_mensais,
            ],
        }
    )

    fig_periodicidade = px.bar(
        periodicidade,
        x="Periodicidade",
        y="Trechos",
        text="Trechos",
    )

    st.plotly_chart(
        fig_periodicidade,
        use_container_width=True,
    )


with col2:

    analises_periodicidade = (
        df_analises
        .merge(
            df_trechos[
                [
                    "trecho_id",
                    "periodicidade",
                ]
            ],
            on="trecho_id",
            how="left",
        )
    )

    if municipio_selecionado != "Todos":

        analises_periodicidade = (
            analises_periodicidade[
                analises_periodicidade[
                    "municipio_nome"
                ]
                == municipio_selecionado
            ]
        )


    if trecho_selecionado != "Todos":

        analises_periodicidade = (
            analises_periodicidade[
                analises_periodicidade[
                    "trecho_nome"
                ]
                == trecho_selecionado
            ]
        )


    analises_periodicidade[
        "periodicidade_nome"
    ] = (
        analises_periodicidade["periodicidade"]
        .map(
            {
                1: "Semanal",
                2: "Mensal",
            }
        )
    )


    qtd_analises_periodicidade = (
        analises_periodicidade
        .groupby("periodicidade_nome")
        .size()
        .reset_index(
            name="Análises"
        )
    )


    fig_analises_periodicidade = px.bar(
        qtd_analises_periodicidade,
        x="periodicidade_nome",
        y="Análises",
        text="Análises",
        labels={
            "periodicidade_nome": "Periodicidade",
        },
    )

    st.plotly_chart(
        fig_analises_periodicidade,
        use_container_width=True,
    )

# 7. PERIODICIDADE POR MUNICÍPIO
st.subheader(
    "Periodicidade por município"
)


periodicidade_municipio = (
    df_trechos
    .groupby(
        [
            "municipio_nome",
            "periodicidade",
        ]
    )
    .size()
    .reset_index(name="trechos")
)

periodicidade_municipio[
    "periodicidade"
] = (
    periodicidade_municipio["periodicidade"]
    .map(
        {
            1: "Semanal",
            2: "Mensal",
        }
    )
)

tabela_periodicidade = (
    periodicidade_municipio
    .pivot(
        index="municipio_nome",
        columns="periodicidade",
        values="trechos",
    )
    .fillna(0)
    .reset_index()
)

tabela_periodicidade = (
    tabela_periodicidade
    .rename(
        columns={
            "Semanal": "Trechos semanais",
            "Mensal": "Trechos mensais",
        }
    )
)

for coluna in [
    "Trechos semanais",
    "Trechos mensais",
]:

    if coluna not in tabela_periodicidade.columns:
        tabela_periodicidade[coluna] = 0


tabela_periodicidade["Total"] = (
    tabela_periodicidade["Trechos semanais"]
    + tabela_periodicidade["Trechos mensais"]
)


st.dataframe(
    tabela_periodicidade,
    use_container_width=True,
    hide_index=True,
)

# 8. CLASSIFICAÇÃO ATUAL × PERIODICIDADE
st.subheader(
    "Classificação atual por periodicidade"
)

st.caption(
    "Comparação descritiva entre a periodicidade de monitoramento "
    "e a classificação atual dos trechos."
)


# Junta periodicidade dos trechos com a classificação atual
classificacao_periodicidade = (
    df_classificacao[
        [
            "trecho_id",
            "classificacao",
        ]
    ]
    .drop_duplicates("trecho_id")
    .merge(
        df_trechos[
            [
                "trecho_id",
                "periodicidade",
            ]
        ],
        on="trecho_id",
        how="inner",
    )
)


# Traduz código da periodicidade
classificacao_periodicidade["periodicidade_nome"] = (
    classificacao_periodicidade["periodicidade"]
    .map(
        {
            1: "Semanal",
            2: "Mensal",
        }
    )
)


# Tabela de proporções
tabela_classificacao_periodicidade = (
    pd.crosstab(
        classificacao_periodicidade["periodicidade_nome"],
        classificacao_periodicidade["classificacao"],
        normalize="index",
    )
    * 100
)


# Garante que as quatro classificações apareçam
for classificacao in ordem:

    if classificacao not in tabela_classificacao_periodicidade.columns:
        tabela_classificacao_periodicidade[
            classificacao
        ] = 0.0


# Ordena as colunas
tabela_classificacao_periodicidade = (
    tabela_classificacao_periodicidade[
        ordem
    ]
    .reset_index()
)


# Exibição
st.dataframe(
    tabela_classificacao_periodicidade.style.format(
        {
            coluna: "{:.1f}%"
            for coluna in ordem
            if coluna in tabela_classificacao_periodicidade.columns
        }
    ),
    use_container_width=True,
    hide_index=True,
)