from pathlib import Path

import geopandas as gpd


BASE_DIR = Path(__file__).resolve().parent.parent

SHAPEFILE = (
    BASE_DIR
    / "data"
    / "ibge"
    / "PB_Municipios_2025"
    / "PB_Municipios_2025.shp"
)

OUTPUT_DIR = BASE_DIR / "data" / "processed"

ARQUIVO_COMPLETO = OUTPUT_DIR / "municipios_pb.geojson"
ARQUIVO_METABASE = OUTPUT_DIR / "municipios_pb_metabase.geojson"

# Tolerância em graus.
TOLERANCE = 0.00003


def main():
    print("Lendo malha municipal do IBGE...")

    municipios = gpd.read_file(SHAPEFILE)

    print(f"Municípios encontrados: {len(municipios)}")

    # Mantém somente os campos necessários.
    municipios = municipios[
        [
            "CD_MUN",
            "NM_MUN",
            "SIGLA_UF",
            "geometry",
        ]
    ].copy()

    municipios = municipios.rename(
        columns={
            "CD_MUN": "codigo_ibge",
            "NM_MUN": "municipio_nome",
            "SIGLA_UF": "uf",
        }
    )

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    # 1. GeoJSON completo
    municipios.to_file(
        ARQUIVO_COMPLETO,
        driver="GeoJSON",
    )

    print(f"\nGeoJSON completo criado:")
    print(ARQUIVO_COMPLETO)

    # 2. GeoJSON simplificado para o Metabase
    municipios_metabase = municipios.copy()

    municipios_metabase["geometry"] = (
        municipios_metabase.geometry.simplify(
            tolerance=TOLERANCE,
            preserve_topology=True,
        )
    )

    municipios_metabase.to_file(
        ARQUIVO_METABASE,
        driver="GeoJSON",
    )

    print(f"\nGeoJSON simplificado criado:")
    print(ARQUIVO_METABASE)

    print(
        f"\nTolerância utilizada: {TOLERANCE}"
    )


if __name__ == "__main__":
    main()