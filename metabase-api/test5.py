# Teste de requisição e manipulação de dados

from pathlib import Path
from dotenv import load_dotenv
import os
import requests
import pandas as pd

# Carrega o .env na raiz do projeto
env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(env_path)

URL_BASE = os.getenv("METABASE_URL", "").rstrip("/")
USUARIO = os.getenv("METABASE_USERNAME")
SENHA = os.getenv("METABASE_PASSWORD")


def autenticar_metabase():
    """Cria uma sessão autenticada no Metabase."""
    if not all([URL_BASE, USUARIO, SENHA]):
        raise ValueError(
            "Configure METABASE_URL, METABASE_USERNAME "
            "e METABASE_PASSWORD no .env"
        )

    resposta = requests.post(
        f"{URL_BASE}/api/session",
        json={
            "username": USUARIO,
            "password": SENHA,
        },
        timeout=15,
    )

    resposta.raise_for_status()
    token = resposta.json().get("id")

    if not token:
        raise ValueError("A resposta não contém o ID da sessão.")

    return token


def consultar_pergunta(token, pergunta_id, data_inicio, data_fim):
    """Executa uma pergunta salva com parâmetros de data."""
    resposta = requests.post(
        f"{URL_BASE}/api/card/{pergunta_id}/query",
        headers={
            "X-Metabase-Session": token,
            "Content-Type": "application/json",
        },
        json={
            "ignore_cache": False,
            "collection_preview": False,
            "parameters": [
                {
                    "id": "574936b3-e795-46c2-8220-36cfb7547f5b",
                    "type": "date/single",
                    "value": data_inicio,
                    "target": [
                        "variable",
                        ["template-tag", "data_inicio"],
                    ],
                },
                {
                    "id": "67f23b6f-6e2b-4c28-8a51-dc298c562907",
                    "type": "date/single",
                    "value": data_fim,
                    "target": [
                        "variable",
                        ["template-tag", "data_fim"],
                    ],
                },
            ],
        },
        timeout=60,
    )

    resposta.raise_for_status()
    return resposta.json()


if __name__ == "__main__":
    try:
        token = autenticar_metabase()
        print("Autenticação realizada com sucesso!")

        pergunta_id = 297

        data_inicio = "2026-07-25"
        data_fim = "2026-08-01"  # Data final exclusiva

        resultado = consultar_pergunta(
            token,
            pergunta_id,
            data_inicio,
            data_fim,
        )

        # Extrai os dados retornados pelo Metabase
        dados = resultado.get("data", {})
        colunas = [
            coluna.get("display_name") or coluna.get("name")
            for coluna in dados.get("cols", [])
        ]
        linhas = dados.get("rows", [])

        # Transforma o resultado em DataFrame
        df = pd.DataFrame(linhas, columns=colunas)

        print(f"\nPeríodo: {data_inicio} a {data_fim}")
        print(f"Total de registros: {len(df)}")

        print("\nColunas disponíveis:")
        print(df.columns.tolist())

        print("\nPrimeiras linhas:")
        print(df.head())

        print("\nValores ausentes por coluna:")
        print(df.isna().sum())

        # Gera um CSV para validar os dados
        arquivo_saida = "resultado_balneabilidade.csv"
        df.to_csv(
            arquivo_saida,
            index=False,
            encoding="utf-8-sig",
        )

        print(f"\nCSV gerado: {arquivo_saida}")

    except requests.HTTPError as erro:
        print("Erro HTTP:", erro.response.status_code)
        print("Resposta:", erro.response.text[:1000])

    except requests.RequestException as erro:
        print("Erro de conexão:", erro)

    except (ValueError, KeyError) as erro:
        print("Erro ao processar os dados:", erro)
