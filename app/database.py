import sqlite3
from pathlib import Path
import pandas as pd
import streamlit as st

# CONEXAO COM A BASE
BASE_DIR = Path(__file__).resolve().parent.parent
DB_PATH = BASE_DIR / "balneabilidade.db"


@st.cache_resource
def conectar_banco():
    return sqlite3.connect(
        DB_PATH,
        check_same_thread=False
    )

# FUNCAO PARA CARREGAR A CLASSIFICACAO
@st.cache_data
def carregar_classificacao():
    conn = conectar_banco()

    query = """
        SELECT *
        FROM classificacao_trechos
        ORDER BY trecho_id
    """

    return pd.read_sql_query(query, conn)

# FUNCAO PARA CARREGAR A TABELA CONJUNTA DAS ANÁLISES
@st.cache_data
def carregar_analises():
    conn = conectar_banco()

    query = """
        SELECT
            a.analise_id,
            a.trecho_id,
            a.analise_data,
            a.quantitativo,
            t.trecho_nome,
            t.latitude,
            t.longitude,
            m.municipio_id,
            m.municipio_nome
        FROM analises AS a
        INNER JOIN trechos AS t
            ON a.trecho_id = t.trecho_id
        INNER JOIN municipios AS m
            ON t.municipio_id = m.municipio_id
        WHERE t.excluido = 0
        ORDER BY a.analise_data
    """

    df = pd.read_sql_query(query, conn)
    df["analise_data"] = pd.to_datetime(df["analise_data"])

    return df

# FUNCAO PARA CARREGAR O MAPA
@st.cache_data
def carregar_mapa():
    conn = conectar_banco()

    query = """
        SELECT
            t.trecho_id,
            t.trecho_nome,
            t.latitude,
            t.longitude,
            m.municipio_nome
        FROM trechos AS t
        INNER JOIN municipios AS m
            ON t.municipio_id = m.municipio_id
        WHERE t.excluido = 0
    """

    return pd.read_sql_query(query, conn)

# FUNCAO PARA CARREGAR A CLASSIFICACAO HISTORICA
@st.cache_data
def carregar_classificacao_historica():
    conn = conectar_banco()

    query = """
        SELECT *
        FROM classificacao_historica
        ORDER BY trecho_id, data_referencia
    """

    return pd.read_sql_query(query, conn)