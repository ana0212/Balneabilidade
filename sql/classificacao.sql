CREATE VIEW classificacao_trechos AS

WITH analises_ordenadas AS (
    SELECT
        a.analise_id,
        a.trecho_id,
        a.analise_data,
        a.quantitativo,

        ROW_NUMBER() OVER (
            PARTITION BY a.trecho_id
            ORDER BY a.analise_data DESC, a.analise_id DESC
        ) AS ordem_recente

    FROM analises a
),

ultimas_5 AS (
    SELECT *
    FROM analises_ordenadas
    WHERE ordem_recente <= 5
),

indicadores AS (
    SELECT
        trecho_id,

        COUNT(*) AS qtd_analises,

        MAX(
            CASE
                WHEN ordem_recente = 1
                THEN quantitativo
            END
        ) AS quantitativo_mais_recente,

        SUM(
            CASE
                WHEN quantitativo > 1000 THEN 1
                ELSE 0
            END
        ) AS qtd_acima_1000,

        SUM(
            CASE
                WHEN quantitativo <= 250 THEN 1
                ELSE 0
            END
        ) AS qtd_ate_250,

        SUM(
            CASE
                WHEN quantitativo <= 500 THEN 1
                ELSE 0
            END
        ) AS qtd_ate_500,

        SUM(
            CASE
                WHEN quantitativo <= 1000 THEN 1
                ELSE 0
            END
        ) AS qtd_ate_1000

    FROM ultimas_5
    GROUP BY trecho_id
)

SELECT
    t.trecho_id,
    t.trecho_nome,
    m.municipio_id,
    m.municipio_nome,

    COALESCE(i.qtd_analises, 0) AS qtd_analises,

    i.quantitativo_mais_recente,

    COALESCE(i.qtd_acima_1000, 0) AS qtd_acima_1000,

    COALESCE(i.qtd_ate_250, 0) AS qtd_ate_250,

    COALESCE(i.qtd_ate_500, 0) AS qtd_ate_500,

    COALESCE(i.qtd_ate_1000, 0) AS qtd_ate_1000,

    CASE

        WHEN COALESCE(i.qtd_analises, 0) < 5
            THEN 'Sem classificação'

        WHEN i.quantitativo_mais_recente > 2500
            THEN 'Imprópria'

        WHEN i.qtd_acima_1000 >= 2
            THEN 'Imprópria'

        WHEN i.qtd_ate_250 >= 4
            THEN 'Excelente'

        WHEN i.qtd_ate_500 >= 4
            THEN 'Muito Boa'

        WHEN i.qtd_ate_1000 >= 4
            THEN 'Satisfatória'

        ELSE 'Sem classificação'

    END AS classificacao

FROM trechos t

JOIN municipios m
    ON m.municipio_id = t.municipio_id

LEFT JOIN indicadores i
    ON i.trecho_id = t.trecho_id

WHERE t.excluido = 0;