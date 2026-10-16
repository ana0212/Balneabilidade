
from pathlib import Path
from dotenv import load_dotenv
import os
import requests

env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(env_path)

URL_BASE = os.getenv("METABASE_URL", "").rstrip("/")
USUARIO = os.getenv("METABASE_USERNAME")
SENHA = os.getenv("METABASE_PASSWORD")

# Autenticar e obter token
def autenticar_metabase():
    if not all([URL_BASE, USUARIO, SENHA]):
        raise ValueError(
            "Configure METABASE_URL, METABASE_USERNAME "
            "e METABASE_PASSWORD no arquivo .env"
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

    dados = resposta.json()
    token = dados.get("id")

    if not token:
        raise ValueError("A resposta não contém o ID da sessão.")

    return token

# Consultar dashboard
def consultar_dashboard(token, dashboard_id):
    resposta = requests.get(
        f"{URL_BASE}/api/dashboard/{dashboard_id}",
        headers={
            "X-Metabase-Session": token,
        },
        timeout=15,
    )

    resposta.raise_for_status()
    return resposta.json()


if __name__ == "__main__":
    try:
        token = autenticar_metabase()
        print("Autenticação realizada com sucesso!")

        dashboard_id = 3 
        dashboard = consultar_dashboard(token, dashboard_id)

        print("Consulta realizada com sucesso!")
        print("Nome do dashboard:", dashboard.get("name"))
        print("ID do dashboard:", dashboard.get("id"))

    except requests.RequestException as erro:
        print("Erro na comunicação com o Metabase:", erro)

    except ValueError as erro:
        print("Erro de configuração ou resposta:", erro)






