import streamlit as st

pg = st.navigation(
    [
        st.Page(
            "pages/visao_geral.py",
            title="Visão geral",
        ),
        st.Page(
            "pages/tabela_consulta.py",
            title="Consulta aos dados",
        ),
        st.Page(
            "pages/analise_regional.py",
            title="Análise regional",
        ),
    ]
)

pg.run()