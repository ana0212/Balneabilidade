# Testar acesso ao resultado de uma pergunta com parâmetro

from pathlib import Path
from dotenv import load_dotenv
import os
import requests

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

def consultar_pergunta(token, pergunta_id, regiao):
    """Executa uma pergunta salva com o parâmetro regiao."""
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
                    "id": "a5267c9b-757a-459b-8675-f5b58a129633",
                    "type": "string/=",
                    "value": [regiao],
                    "target": [
                        "variable",
                        ["template-tag", "regiao"],
                    ],
                }
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

        # ID da pergunta salva no Metabase
        pergunta_id = 296

        # Região que será enviada como parâmetro
        regiao = "João Pessoa"

        resultado = consultar_pergunta(
            token,
            pergunta_id,
            regiao,
        )

        colunas = resultado.get("data", {}).get("cols", [])
        linhas = resultado.get("data", {}).get("rows", [])

        print(f"Pergunta ID: {pergunta_id}")
        print(f"Região consultada: {regiao}")
        print(f"Quantidade de linhas retornadas: {len(linhas)}")
        print(
            "Colunas:",
            [
                coluna.get("display_name") or coluna.get("name")
                for coluna in colunas
            ],
        )

        if linhas:
            print("Primeira linha:", linhas[0])
        else:
            print("A consulta não retornou linhas.")

    except requests.HTTPError as erro:
        print("Erro HTTP:", erro.response.status_code)
        print("Resposta:", erro.response.text[:500])

    except requests.RequestException as erro:
        print("Erro de conexão:", erro)

    except ValueError as erro:
        print("Erro de configuração ou resposta:", erro)
