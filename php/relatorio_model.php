<?php

require_once __DIR__ . '/database.php';

function getSqlModeloRelatorio(): string
{

    return <<<'SQL'
    WITH base AS (
        SELECT
                a.analise_id,
                a.analise_data,
                a.quantitativo,
                t.trecho_id,
                t.trecho_nome,
                t.trecho_descricao,
                t.estacao,
                t.latitude,
                t.longitude,
                t.periodicidade,
                t.fonte,
                t.excluido,
                m.municipio_id,
                m.municipio_nome,

                CASE
                    WHEN m.municipio_id = 1 THEN '2509305'
                    WHEN m.municipio_id = 2 THEN '2501401'
                    WHEN m.municipio_id = 3 THEN '2512903'
                    WHEN m.municipio_id = 4 THEN '2508604'
                    WHEN m.municipio_id = 5 THEN '2503209'
                    WHEN m.municipio_id = 6 THEN '2507507'
                    WHEN m.municipio_id = 7 THEN '2504603'
                    WHEN m.municipio_id = 8 THEN '2511905'
                END AS codigo_ibge,

                CASE
                    WHEN m.municipio_nome IN (
                        'Mataraca', 'Baia da Traição', 'Rio Tinto'
                    ) THEN 'Litoral Norte'
                    WHEN m.municipio_nome IN ('Lucena', 'Cabedelo')
                        THEN 'Norte Metropolitano'
                    WHEN m.municipio_nome IN ('Conde', 'Pitimbú')
                        THEN 'Litoral Sul'
                    WHEN m.municipio_nome = 'João Pessoa'
                        THEN 'João Pessoa'
                    ELSE 'Não definida'
                END AS regiao,

                CASE
                    WHEN date(a.analise_data)
                        BETWEEN '2026-05-14' AND '2026-05-17'
                    THEN 'Semana 14/05 - 17/05'
                    ELSE
                        'Semana ' ||
                        strftime(
                            '%d/%m',
                            date(
                                a.analise_data,
                                '-' ||
                                ((strftime('%w', a.analise_data) + 6) % 7) ||
                                ' days'
                            )
                        ) ||
                        ' - ' ||
                        strftime(
                            '%d/%m',
                            date(
                                a.analise_data,
                                '-' ||
                                ((strftime('%w', a.analise_data) + 6) % 7) ||
                                ' days',
                                '+6 days'
                            )
                        )
                END AS semana_referencia,

                ct.classificacao AS classificacao_atual,
                ch.classificacao AS classificacao_historica

            FROM analises a
            INNER JOIN trechos t
                ON a.trecho_id = t.trecho_id
            INNER JOIN municipios m
                ON t.municipio_id = m.municipio_id
            LEFT JOIN classificacao_trechos ct
                ON a.trecho_id = ct.trecho_id
            LEFT JOIN classificacao_historica ch
                ON a.analise_id = ch.analise_referencia_id
            WHERE t.excluido = 0
        ),
    historico AS (
        SELECT
        *,
        LAG(classificacao_historica) OVER (
            PARTITION BY trecho_id
            ORDER BY analise_data, analise_id
        ) AS classificacao_anterior
    FROM base
    ),
    mudancas AS (
        SELECT
            *,
            CASE
                WHEN classificacao_anterior IS NULL THEN NULL
                WHEN classificacao_anterior = classificacao_historica
                    THEN NULL
                ELSE
                    CAST(classificacao_anterior AS TEXT)
                    || ' → ' ||
                    CAST(classificacao_historica AS TEXT)
            END AS mudanca_classificacao
        FROM historico
    )
    SELECT
        *,
        CASE
            WHEN mudanca_classificacao IS NULL THEN NULL
            WHEN classificacao_historica = 'Imprópria'
                THEN 'Passou para Imprópria'
            WHEN classificacao_anterior = 'Excelente'
                THEN 'Desceu de categoria'
            WHEN classificacao_anterior = 'Muito Boa'
                 AND classificacao_historica IN ('Boa', 'Regular')
                THEN 'Desceu de categoria'
            WHEN classificacao_anterior = 'Boa'
                 AND classificacao_historica = 'Regular'
                THEN 'Desceu de categoria'
            WHEN classificacao_historica = 'Excelente'
                THEN 'Melhorou para Excelente'
            ELSE 'Melhorou de categoria'
        END AS evolucao_classificacao
    FROM mudancas
    SQL;
}