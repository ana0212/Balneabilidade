import folium
import pandas as pd
import streamlit as st
from streamlit_folium import st_folium
import plotly.express as px

from database import (
    carregar_classificacao,
    carregar_analises,
    carregar_mapa,
)


# CONFIGURAÇÃO
st.set_page_config(
    page_title="Análise regional",
    layout="wide",
)

st.title("Análise regional")

st.caption(
    "Comparação da situação da balneabilidade entre os grupos "
    "geográficos definidos para a análise."
)


# GRUPOS REGIONAIS
GRUPOS_REGIONAIS = {
    1: "Litoral Norte",          # Mataraca
    2: "Litoral Norte",          # Baía da Traição
    3: "Litoral Norte",          # Rio Tinto
    4: "Norte Metropolitano",    # Lucena
    5: "Norte Metropolitano",    # Cabedelo
    6: "João Pessoa",            # João Pessoa
    7: "Litoral Sul",             # Conde
    8: "Litoral Sul",             # Pitimbú
}


# CARREGAMENTO
df_classificacao = carregar_classificacao()
df_analises = carregar_analises()
df_mapa = carregar_mapa()


# CRIAR GRUPO REGIONAL
def identificar_grupo(municipio_id):
    return GRUPOS_REGIONAIS.get(municipio_id, "Não classificado")

df_classificacao["grupo_regional"] = (
    df_classificacao["municipio_id"]
    .map(GRUPOS_REGIONAIS)
    .fillna("Não classificado")
)

df_analises["grupo_regional"] = (
    df_analises["municipio_id"]
    .map(GRUPOS_REGIONAIS)
    .fillna("Não classificado")
)

GRUPOS_POR_MUNICIPIO = {
    "Mataraca": "Litoral Norte",
    "Baia da Traição": "Litoral Norte",
    "Baía da Traição": "Litoral Norte",
    "Rio Tinto": "Litoral Norte",
    "Lucena": "Norte Metropolitano",
    "Cabedelo": "Norte Metropolitano",
    "João Pessoa": "João Pessoa",
    "Conde": "Litoral Sul",
    "Pitimbú": "Litoral Sul",
    "Pitimbu": "Litoral Sul",
}

df_mapa["grupo_regional"] = (
    df_mapa["municipio_nome"]
    .map(GRUPOS_POR_MUNICIPIO)
    .fillna("Não classificado")
)

# 1. RESUMO DOS GRUPOS

resumo = (
    df_classificacao
    .groupby("grupo_regional")
    .agg(
        municipios=("municipio_nome", "nunique"),
        trechos=("trecho_id", "nunique"),
        trechos_improprios=(
            "classificacao",
            lambda x: (x == "Imprópria").sum()
        ),
    )
    .reset_index()
)

# Quantidade real de análises
qtd_analises = (
    df_analises
    .groupby("grupo_regional")
    .agg(
        analises=("analise_id", "nunique")
    )
    .reset_index()
)

# Juntar
resumo = resumo.merge(
    qtd_analises,
    on="grupo_regional",
    how="left",
)

# Percentual de trechos impróprios
resumo["percentual_improprios"] = (
    resumo["trechos_improprios"]
    / resumo["trechos"]
    * 100
)

# Nomes para exibição
resumo = resumo.rename(
    columns={
        "grupo_regional": "Grupo",
        "municipios": "Municípios",
        "trechos": "Trechos",
        "analises": "Análises",
        "trechos_improprios": "Trechos impróprios",
        "percentual_improprios": "% impróprios",
    }
)

st.dataframe(
    resumo.style.format(
        {
            "% impróprios": "{:.1f}%",
        }
    ),
    use_container_width=True,
    hide_index=True,
)

# 2. DISTRIBUIÇÃO DAS CLASSIFICAÇÕES
st.subheader("Distribuição das classificações por grupo")

distribuicao = (
    df_classificacao
    .groupby(
        [
            "grupo_regional",
            "classificacao",
        ]
    )
    .size()
    .reset_index(name="trechos")
)


ordem_classificacao = [
    "Excelente",
    "Muito Boa",
    "Satisfatória",
    "Imprópria",
    "Sem classificação",
]


distribuicao["classificacao"] = pd.Categorical(
    distribuicao["classificacao"],
    categories=ordem_classificacao,
    ordered=True,
)


fig_classificacao = px.bar(
    distribuicao,
    x="grupo_regional",
    y="trechos",
    color="classificacao",
    barmode="group",
    category_orders={
        "classificacao": ordem_classificacao
    },
    labels={
        "grupo_regional": "Grupo",
        "trechos": "Quantidade de trechos",
        "classificacao": "Classificação",
    },
)


st.plotly_chart(
    fig_classificacao,
    use_container_width=True,
)

# 3. PERCENTUAL DE TRECHOS IMPRÓPRIOS
st.subheader("Percentual de trechos atualmente impróprios")


improprios = (
    df_classificacao
    .groupby("grupo_regional")
    .agg(
        trechos=("trecho_id", "nunique"),
        improprios=(
            "classificacao",
            lambda x: (x == "Imprópria").sum()
        ),
    )
    .reset_index()
)


improprios["percentual"] = (
    improprios["improprios"]
    / improprios["trechos"]
    * 100
)


fig_improprios = px.bar(
    improprios,
    x="grupo_regional",
    y="percentual",
    labels={
        "grupo_regional": "Grupo",
        "percentual": "Trechos impróprios (%)",
    },
    text="percentual",
)


fig_improprios.update_traces(
    texttemplate="%{text:.1f}%",
    textposition="outside",
)


fig_improprios.update_yaxes(
    range=[
        0,
        max(improprios["percentual"].max() * 1.2, 10)
    ]
)


st.plotly_chart(
    fig_improprios,
    use_container_width=True,
)

# 4. MAPA POR GRUPO
st.subheader("Distribuição geográfica dos trechos")


df_mapa = df_mapa.dropna(
    subset=["latitude", "longitude"]
)


if not df_mapa.empty:

    centro_lat = df_mapa["latitude"].mean()
    centro_lon = df_mapa["longitude"].mean()

    mapa = folium.Map(
        location=[centro_lat, centro_lon],
        zoom_start=10,
        control_scale=True,
    )


    cores_grupos = {
        "Litoral Norte": "blue",
        "Norte Metropolitano": "purple",
        "Litoral Sul": "green",
        "João Pessoa": "red",
    }


    for _, row in df_mapa.iterrows():

        grupo = row["grupo_regional"]

        cor = cores_grupos.get(
            grupo,
            "gray"
        )


        popup = folium.Popup(
            f"""
            <b>Trecho:</b> {row['trecho_nome']}<br>
            <b>Município:</b> {row['municipio_nome']}<br>
            <b>Grupo:</b> {grupo}
            """,
            max_width=300,
        )


        folium.CircleMarker(
            location=[
                row["latitude"],
                row["longitude"],
            ],
            radius=8,
            color=cor,
            fill=True,
            fill_color=cor,
            fill_opacity=0.8,
            popup=popup,
            tooltip=row["trecho_nome"],
        ).add_to(mapa)


    st_folium(
        mapa,
        use_container_width=True,
        height=500,
    )

else:

    st.info(
        "Não há coordenadas disponíveis para os trechos."
    )