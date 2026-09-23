"""
tratamento.py

Tratamento da base bruta de balneabilidade.
Etapas extraídas do notebook de exploração:
- leitura da base bruta;
- conversão de analise_data para datetime;
- remoção de registros integralmente duplicados,
  desconsiderando analise_id;
- validações básicas;
- exportação da base tratada.
"""

from pathlib import Path
import pandas as pd


# Caminhos do projeto
BASE_DIR = Path(__file__).resolve().parents[1]
ARQUIVO_BRUTO = BASE_DIR / "data" / "bruto" / "balneabilidade_bruto.csv"
ARQUIVO_TRATADO = BASE_DIR / "data" / "processed" / "base_tratada.csv"


def main():
    ARQUIVO_TRATADO.parent.mkdir(parents=True, exist_ok=True)

    df = pd.read_csv(ARQUIVO_BRUTO)

    print(f"Base original: {len(df)} linhas x {len(df.columns)} colunas")

    # Conversão do campo de data.
    df["analise_data"] = pd.to_datetime(
        df["analise_data"],
        errors="coerce"
    )

    datas_invalidas = df["analise_data"].isna().sum()
    print(f"Datas inválidas após conversão: {datas_invalidas}")

    # A duplicidade é avaliada em todas as colunas, exceto analise_id
    colunas_duplicidade = [
        c for c in df.columns
        if c != "analise_id"
    ]

    linhas_duplicadas = df.duplicated(
        subset=colunas_duplicidade,
        keep=False
    ).sum()

    linhas_removidas = df.duplicated(
        subset=colunas_duplicidade,
        keep="first"
    ).sum()

    df_clean = df.drop_duplicates(
        subset=colunas_duplicidade,
        keep="first"
    ).copy()

    print(f"Linhas envolvidas em grupos duplicados: {linhas_duplicadas}")
    print(f"Linhas removidas: {linhas_removidas}")
    print(f"Base tratada: {len(df_clean)} linhas")
    print(
        "analise_id duplicados após tratamento:",
        df_clean["analise_id"].duplicated().sum()
    )

    df_clean.to_csv(
        ARQUIVO_TRATADO,
        index=False,
        encoding="utf-8-sig"
    )

    print(f"Base tratada salva em: {ARQUIVO_TRATADO}")


if __name__ == "__main__":
    main()
