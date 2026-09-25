DROP VIEW IF EXISTS classificacao_historica;

CREATE VIEW classificacao_historica AS

WITH analises_ordenadas AS (
    SELECT
        a.analise_id,
        a.trecho_id,
        a.analise_data,
        a.quantitativo,

        ROW_NUMBER() OVER (
            PARTITION BY a.trecho_id
            ORDER BY a.analise_data ASC, a.analise_id ASC
        ) AS ordem_historica

    FROM analises a
),

janelas AS (
    SELECT
        a.analise_id AS analise_referencia_id,
        a.trecho_id,
        a.analise_data AS data_referencia,

        b.analise_id,
        b.analise_data,
        b.quantitativo

    FROM analises_ordenadas a

    INNER JOIN analises_ordenadas b
        ON b.trecho_id = a.trecho_id
        AND b.ordem_historica BETWEEN
            a.ordem_historica - 4
            AND a.ordem_historica
),

indicadores AS (
    SELECT
        analise_referencia_id,
        trecho_id,
        data_referencia,

        COUNT(*) AS qtd_analises,

        MAX(
            CASE
                WHEN analise_id = analise_referencia_id
                THEN quantitativo
            END
        ) AS quantitativo_referencia,

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

    FROM janelas

    GROUP BY
        analise_referencia_id,
        trecho_id,
        data_referencia
)

SELECT
    i.analise_referencia_id,
    i.trecho_id,
    t.trecho_nome,
    m.municipio_id,
    m.municipio_nome,
    i.data_referencia,
    i.qtd_analises,
    i.quantitativo_referencia,

    CASE

        WHEN i.qtd_analises < 5
            THEN 'Sem classificação'

        WHEN i.quantitativo_referencia > 2500
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

FROM indicadores i

INNER JOIN trechos t
    ON t.trecho_id = i.trecho_id

INNER JOIN municipios m
    ON m.municipio_id = t.municipio_id

WHERE t.excluido = 0;