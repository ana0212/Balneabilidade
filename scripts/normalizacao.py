"""
normalizacao.py

Normalização da base tratada em três entidades:
- municipios
- trechos
- analises

Também realiza validações básicas de PK e FK e exporta
os três CSVs processados.
"""

from pathlib import Path
import pandas as pd


# Caminhos do projeto
BASE_DIR = Path(__file__).resolve().parents[1]
ARQUIVO_TRATADO = BASE_DIR / "data" / "processed" / "base_tratada.csv"
PASTA_SAIDA = BASE_DIR / "data" / "processed"


def validar_pk(df, coluna):
    duplicados = df[coluna].duplicated().sum()
    nulos = df[coluna].isna().sum()

    print(f"{coluna}:")
    print(f"  Nulos: {nulos}")
    print(f"  Duplicados: {duplicados}")

    return duplicados == 0 and nulos == 0


def main():
    PASTA_SAIDA.mkdir(parents=True, exist_ok=True)

    df = pd.read_csv(ARQUIVO_TRATADO)

# Garantindo que a coluna analise_data seja do tipo datetime
    df["analise_data"] = pd.to_datetime(
        df["analise_data"],
        errors="coerce"
    )

    # 1. Municípios
    municipios = (
        df[
            ["municipio_id", "municipio_nome"]
        ]
        .drop_duplicates()
        .sort_values("municipio_id")
        .reset_index(drop=True)
    )

    # 2. Trechos
    trechos = (
        df[
            [
                "trecho_id",
                "municipio_id",
                "trecho_nome",
                "trecho_descricao",
                "estacao",
                "latitude",
                "longitude",
                "periodicidade",
                "fonte",
                "excluido",
            ]
        ]
        .drop_duplicates()
        .sort_values("trecho_id")
        .reset_index(drop=True)
    )

    # 3. Análises
    analises = (
        df[
            [
                "analise_id",
                "trecho_id",
                "analise_data",
                "quantitativo",
            ]
        ]
        .drop_duplicates()
        .sort_values("analise_id")
        .reset_index(drop=True)
    )

    # Validação das PKs
    print("\nVALIDAÇÃO DAS PKs")
    validar_pk(municipios, "municipio_id")
    validar_pk(trechos, "trecho_id")
    validar_pk(analises, "analise_id")

    # Validação das FKs
    fk_municipio = ~trechos["municipio_id"].isin(
        municipios["municipio_id"]
    )

    fk_trecho = ~analises["trecho_id"].isin(
        trechos["trecho_id"]
    )

    print("\nVALIDAÇÃO DAS FKs")
    print("FK municipio inválidas:", fk_municipio.sum())
    print("FK trecho inválidas:", fk_trecho.sum())

    # Resumo
    print("\nDIMENSÕES FINAIS")
    print("Municípios:", len(municipios))
    print("Trechos:", len(trechos))
    print("Análises:", len(analises))

   
    # Exportação
    municipios.to_csv(
        PASTA_SAIDA / "municipios.csv",
        index=False,
        encoding="utf-8-sig",
    )

    trechos.to_csv(
        PASTA_SAIDA / "trechos.csv",
        index=False,
        encoding="utf-8-sig",
    )

    analises.to_csv(
        PASTA_SAIDA / "analises.csv",
        index=False,
        encoding="utf-8-sig",
    )


if __name__ == "__main__":
    main()
