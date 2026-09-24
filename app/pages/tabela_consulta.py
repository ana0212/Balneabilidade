import streamlit as st
import pandas as pd
from database import carregar_classificacao, carregar_analises


st.set_page_config(
    page_title="Consulta aos dados",
    layout="wide",
)


st.title("🔎 Consulta aos dados")

st.caption(
    "Consulte os trechos e análises de balneabilidade "
    "utilizando os filtros disponíveis."
)


# CARREGAMENTO
df_classificacao = carregar_classificacao()
df_analises = carregar_analises()


# FILTROS
st.subheader("Filtros")

col1, col2, col3 = st.columns(3)


with col1:

    municipios = ["Todos"] + sorted(
        df_classificacao["municipio_nome"]
        .dropna()
        .unique()
        .tolist()
    )

    municipio = st.selectbox(
        "Município",
        municipios
    )


with col2:

    if municipio == "Todos":

        df_trechos_opcoes = df_classificacao

    else:

        df_trechos_opcoes = df_classificacao[
            df_classificacao["municipio_nome"] == municipio
        ]

    trechos = ["Todos"] + sorted(
        df_trechos_opcoes["trecho_nome"]
        .dropna()
        .unique()
        .tolist()
    )

    trecho = st.selectbox(
        "Trecho",
        trechos
    )


with col3:

    classificacoes = ["Todos"] + sorted(
        df_classificacao["classificacao"]
        .dropna()
        .unique()
        .tolist()
    )

    classificacao = st.selectbox(
        "Classificação",
        classificacoes
    )


# PERÍODO
data_min = df_analises["analise_data"].min().date()
data_max = df_analises["analise_data"].max().date()

st.markdown("**Período das análises**")

periodo = st.slider(
    "Selecione o intervalo",
    min_value=data_min,
    max_value=data_max,
    value=(data_min, data_max),
    format="DD/MM/YYYY",
)

data_inicio, data_fim = periodo

st.caption(
    f"Período selecionado: "
    f"**{data_inicio.strftime('%d/%m/%Y')}** "
    f"até **{data_fim.strftime('%d/%m/%Y')}**"
)


# APLICAÇÃO DOS FILTROS
df_view = df_analises.copy()


if municipio != "Todos":

    df_view = df_view[
        df_view["municipio_nome"] == municipio
    ]


if trecho != "Todos":

    df_view = df_view[
        df_view["trecho_nome"] == trecho
    ]


if isinstance(periodo, tuple) and len(periodo) == 2:

    df_view = df_view[
        (df_view["analise_data"].dt.date >= data_inicio)
        & (df_view["analise_data"].dt.date <= data_fim)
    ]


# CLASSIFICAÇÃO ATUAL
df_view = df_view.merge(
    df_classificacao[
        [
            "trecho_id",
            "classificacao"
        ]
    ],
    on="trecho_id",
    how="left"
)


if classificacao != "Todos":

    df_view = df_view[
        df_view["classificacao"] == classificacao
    ]


# TABELA
st.subheader("Resultados")

if df_view.empty:

    st.info(
        "Nenhum registro encontrado para os filtros selecionados."
    )

else:

    tabela = df_view[
        [
            "municipio_nome",
            "trecho_nome",
            "classificacao",
            "analise_data",
            "quantitativo",
        ]
    ].copy()

    tabela = tabela.rename(
        columns={
            "municipio_nome": "Município",
            "trecho_nome": "Trecho",
            "classificacao": "Classificação Atual",
            "analise_data": "Data da análise",
            "quantitativo": "Quantitativo",
        }
    )

    tabela["Data da análise"] = (
        tabela["Data da análise"]
        .dt.strftime("%d/%m/%Y %H:%M")
    )

    st.dataframe(
        tabela,
        use_container_width=True,
        hide_index=True
    )