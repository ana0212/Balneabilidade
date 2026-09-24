import folium
import pandas as pd
import streamlit as st
from streamlit_folium import st_folium
from database import (carregar_classificacao, carregar_analises, carregar_mapa)


# CONFIGURAÇÃO
st.set_page_config(
    page_title="Monitoramento da Balneabilidade da Paraíba",
    layout="wide",
)

st.title("Monitoramento da Balneabilidade da Paraíba")
st.caption(
    "Panorama histórico e situação dos trechos de balneabilidade, "
    "com análise geográfica, temporal e classificação."
)

# CONECTAR COM A BASE
df_classificacao = carregar_classificacao()
df_analises = carregar_analises()
df_mapa = carregar_mapa()

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
    "Selecione o intervalo de datas",
    min_value=data_min,
    max_value=data_max,
    value=(data_min, data_max),
    format="DD/MM/YYYY",
)

data_inicio, data_fim = periodo

st.caption(
    f"Período selecionado: "
    f"**{data_inicio.strftime('%d/%m/%Y')}** "
    f"até "
    f"**{data_fim.strftime('%d/%m/%Y')}**"
)

# APLICAÇÃO DOS FILTROS
df_view = df_classificacao.copy()
df_analises_view = df_analises.copy()


if municipio != "Todos":

    df_view = df_view[
        df_view["municipio_nome"] == municipio
    ]

    df_analises_view = df_analises_view[
        df_analises_view["municipio_nome"] == municipio
    ]


if trecho != "Todos":

    df_view = df_view[
        df_view["trecho_nome"] == trecho
    ]

    df_analises_view = df_analises_view[
        df_analises_view["trecho_nome"] == trecho
    ]


if classificacao != "Todos":

    df_view = df_view[
        df_view["classificacao"] == classificacao
    ]


if isinstance(periodo, tuple) and len(periodo) == 2:

    data_inicio, data_fim = periodo

    df_analises_view = df_analises_view[
        (df_analises_view["analise_data"].dt.date >= data_inicio)
        & (df_analises_view["analise_data"].dt.date <= data_fim)
    ]

# KPIs
st.subheader("Panorama")

kpi1, kpi2, kpi3, kpi4 = st.columns(4)

with kpi1:
    st.metric(
        "Trechos",
        df_view["trecho_id"].nunique()
    )

with kpi2:
    st.metric(
        "Municípios",
        df_view["municipio_id"].nunique()
    )

with kpi3:
    st.metric(
        "Análises",
        len(df_analises_view)
    )

with kpi4:

    total_trechos = len(df_view)

    if total_trechos > 0:

        improprios = (
            df_view["classificacao"] == "Imprópria"
        ).sum()

        percentual = improprios / total_trechos * 100

    else:

        percentual = 0

    st.metric(
        "Trechos atualmente impróprios",
        f"{percentual:.1f}%"
    )

# DISTRIBUIÇÃO DAS CLASSIFICAÇÕES
st.subheader("Distribuição dos trechos por classificação")

distribuicao = (
    df_view["classificacao"]
    .value_counts()
    .rename_axis("classificacao")
    .to_frame("quantidade")
)

if not distribuicao.empty:
    st.bar_chart(distribuicao)

# MAPA
st.subheader("Mapa dos trechos")

# Aplicar filtro de município
if municipio != "Todos":

    df_mapa = df_mapa[
        df_mapa["municipio_nome"] == municipio
    ]


# Aplicar filtro de trecho
if trecho != "Todos":

    df_mapa = df_mapa[
        df_mapa["trecho_nome"] == trecho
    ]


# Adicionar classificação
df_mapa = df_mapa.merge(
    df_classificacao[
        [
            "trecho_id",
            "classificacao"
        ]
    ],
    on="trecho_id",
    how="left"
)


# Remover coordenadas inválidas
df_mapa = df_mapa.dropna(
    subset=["latitude", "longitude"]
)


if not df_mapa.empty:

    # Centro do mapa
    centro_lat = df_mapa["latitude"].mean()
    centro_lon = df_mapa["longitude"].mean()

    mapa = folium.Map(
        location=[centro_lat, centro_lon],
        zoom_start=10,
        control_scale=True
    )


    # Cores por classificação
    cores_classificacao = {
        "Excelente": "green",
        "Muito Boa": "blue",
        "Satisfatória": "orange",
        "Imprópria": "red",
        "Sem classificação": "gray",
    }


    # Adicionar marcadores
    for _, row in df_mapa.iterrows():

        classificacao_mapa = row["classificacao"]

        cor = cores_classificacao.get(
            classificacao_mapa,
            "gray"
        )


        popup = folium.Popup(
            f"""
            <b>Trecho:</b> {row['trecho_nome']}<br>
            <b>Município:</b> {row['municipio_nome']}<br>
            <b>Classificação:</b> {classificacao_mapa}
            """,
            max_width=300
        )


        folium.CircleMarker(
            location=[
                row["latitude"],
                row["longitude"]
            ],
            radius=8,
            color=cor,
            fill=True,
            fill_color=cor,
            fill_opacity=0.8,
            popup=popup,
            tooltip=row["trecho_nome"],
        ).add_to(mapa)

        # Legenda do mapa
        legenda = """
        <div style="
            position: fixed;
            bottom: 30px;
            right: 20px;
            z-index: 9999;
            background-color: white;
            color: black;
            border: 2px solid #999;
            border-radius: 6px;
            padding: 10px;
            font-size: 13px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        ">
            <div style="color: black; font-weight: bold; margin-bottom: 5px;">
                Classificação
            </div>

            <div style="color: black;">
                <span style="color: green;">●</span> Excelente
            </div>

            <div style="color: black;">
                <span style="color: blue;">●</span> Muito Boa
            </div>

            <div style="color: black;">
                <span style="color: orange;">●</span> Satisfatória
            </div>

            <div style="color: black;">
                <span style="color: red;">●</span> Imprópria
            </div>

            <div style="color: black;">
                <span style="color: gray;">●</span> Sem classificação
            </div>
        </div>
        """

        mapa.get_root().html.add_child(
            folium.Element(legenda)
        )


    st_folium(
        mapa,
        use_container_width=True,
        height=500
    )

else:

    st.info(
        "Não há coordenadas disponíveis para os filtros selecionados."
    )

# EVOLUÇÃO TEMPORAL
st.subheader("Evolução do quantitativo")

if trecho == "Todos":

    st.info(
        "Selecione um trecho no filtro acima para visualizar "
        "a evolução das análises."
    )

else:

    serie = (
        df_analises_view
        .sort_values("analise_data")
        [["analise_data", "quantitativo"]]
        .set_index("analise_data")
    )

    if not serie.empty:

        st.line_chart(serie)

        st.caption(
            "A frequência das análises varia entre os trechos; "
            "a interpretação da série deve considerar a "
            "disponibilidade das observações."
        )

    else:

        st.info(
            "Não existem análises no período selecionado."
        )