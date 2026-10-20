
<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/relatorio_model.php';

# Obter as semanas para os filtros
function getSemanasDisponiveis(PDO $pdo): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            semana_referencia
        FROM modelo
        GROUP BY semana_referencia
        ORDER BY MIN(date(analise_data)) DESC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

# Obter os dados dos indicadores dos cards com filtro de semana
function getIndicadoresPanorama(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            COUNT(DISTINCT trecho_id) AS total_trechos,
            COUNT(DISTINCT municipio_id) AS total_municipios,
            COUNT(DISTINCT analise_id) AS total_analises,
            COUNT(
                DISTINCT CASE
                    WHEN classificacao_historica = 'Excelente'
                    THEN trecho_id
                END
            ) AS trechos_excelentes
        FROM modelo
        WHERE semana_referencia = :semana
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':semana' => $semana
    ]);

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    return $resultado ?: [
        'total_trechos' => 0,
        'total_municipios' => 0,
        'total_analises' => 0,
        'trechos_excelentes' => 0
    ];
}

# Obter a contagem dos trechos próprios para banho para o gráfico de rosca
function getDadosGraficoSituacao(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        ),
        situacoes AS (
            SELECT
                trecho_id,
                CASE
                    WHEN classificacao_historica = 'Imprópria'
                        THEN 'Imprópria'
                    ELSE 'Própria'
                END AS Situacao
            FROM modelo
            WHERE fonte = 0
              AND semana_referencia = :semana
        )
        SELECT
            Situacao,
            COUNT(DISTINCT trecho_id) AS total
        FROM situacoes
        GROUP BY Situacao
        ORDER BY Situacao ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':semana' => $semana
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

# Obter dados para o gráfico de barras de classificação
function getDadosGraficoClassificacao(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            classificacao_historica AS classificacao,
            COUNT(DISTINCT trecho_id) AS total
        FROM modelo
        WHERE semana_referencia = :semana
        GROUP BY classificacao_historica
        ORDER BY classificacao_historica ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':semana' => $semana
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

# Obter dados para gráfico de barras de Periodicidade
function getDadosGraficoPeriodicidade(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            CASE
                WHEN periodicidade = 1 THEN 'Semanal'
                WHEN periodicidade = 2 THEN 'Mensal'
            END AS periodicidade_nome,
            COUNT(DISTINCT trecho_id) AS total
        FROM modelo
        WHERE semana_referencia = :semana
          AND periodicidade IN (1, 2)
        GROUP BY periodicidade
        ORDER BY periodicidade ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':semana' => $semana
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

# Obter dados para gráfico de classificação por município
function getDadosGraficoClassificacaoMunicipio(
    PDO $pdo,
    string $semana
): array {
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            municipio_nome,
            classificacao_historica AS classificacao,
            COUNT(DISTINCT trecho_id) AS total
        FROM modelo
        WHERE semana_referencia = :semana
        GROUP BY
            municipio_id,
            municipio_nome,
            classificacao_historica
        ORDER BY
            municipio_nome ASC,
            classificacao_historica ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':semana' => $semana
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

# Obter dados para gráfico de mudança de classificação
function getDadosGraficoMudancas(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            mudanca_classificacao,
            COUNT(*) AS total
        FROM modelo
        WHERE semana_referencia = :semana
          AND mudanca_classificacao IS NOT NULL
        GROUP BY mudanca_classificacao
        ORDER BY total DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':semana' => $semana
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

# Obter dados para tabela de mudanças
function getDadosTabelaMudancas(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            analise_data,
            quantitativo,
            trecho_nome,
            classificacao_historica,
            classificacao_anterior,
            municipio_nome,
            evolucao_classificacao
        FROM modelo
        WHERE mudanca_classificacao IS NOT NULL
          AND semana_referencia = :semana
        ORDER BY analise_data DESC, municipio_nome ASC, trecho_nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':semana' => $semana
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

# Obter os dados da tabela de análises da semana
function getDadosTabelaTrechosSemana(
    PDO $pdo,
    string $semana,
    string $classificacao = '',
    string $municipio = ''
): array {
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT
            analise_data,
            quantitativo,
            trecho_nome,
            estacao,
            municipio_nome,
            classificacao_historica,
            classificacao_anterior,
            CASE
                WHEN fonte = 0 THEN 'Trecho de Praia'
                WHEN fonte = 1 THEN 'Foz de Rio ou Riacho'
                ELSE 'Não informada'
            END AS Fonte,
            CASE
                WHEN periodicidade = 1 THEN 'Semanal'
                WHEN periodicidade = 2 THEN 'Mensal'
                ELSE 'Não informada'
            END AS Periodicidade
        FROM modelo
        WHERE semana_referencia = :semana
    ";

    $parametros = [
        ':semana' => $semana
    ];

    if ($classificacao !== '') {
        $sql .= "
            AND classificacao_historica = :classificacao
        ";

        $parametros[':classificacao'] = $classificacao;
    }

    if ($municipio !== '') {
        $sql .= "
            AND municipio_nome = :municipio
        ";

        $parametros[':municipio'] = $municipio;
    }

    $sql .= "
        ORDER BY analise_data DESC, municipio_nome ASC, trecho_nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClassificacoesFiltroTabela(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT DISTINCT classificacao_historica
        FROM modelo
        WHERE semana_referencia = :semana
          AND classificacao_historica IS NOT NULL
        ORDER BY classificacao_historica ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':semana' => $semana]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getMunicipiosFiltroTabela(PDO $pdo, string $semana): array
{
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        )
        SELECT DISTINCT municipio_nome
        FROM modelo
        WHERE semana_referencia = :semana
          AND municipio_nome IS NOT NULL
        ORDER BY municipio_nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':semana' => $semana]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

# Obter os dados para o gráfico de evolução do quantitativo
function getDadosEvolucaoQuantitativo(
    PDO $pdo,
    string $semana,
    string $trecho = ''
): array {
    $modelo = getSqlModeloRelatorio();

    $sql = "
        WITH modelo AS (
            $modelo
        ),
        limite AS (
            SELECT MAX(date(analise_data)) AS data_limite
            FROM modelo
            WHERE semana_referencia = :semana_limite
        )
        SELECT
            date(m.analise_data) AS analise_data,
            m.trecho_nome,
            m.classificacao_historica AS classificacao,
            AVG(m.quantitativo) AS media_quantitativo,
            m.semana_referencia
        FROM modelo m
        CROSS JOIN limite l
        WHERE l.data_limite IS NOT NULL
        AND date(m.analise_data) <= l.data_limite
            ";

    $parametros = [
        ':semana_limite' => $semana
    ];

    if ($trecho !== '') {
        $sql .= " AND m.trecho_nome = :trecho ";
        $parametros[':trecho'] = $trecho;
    }

    $sql .= "
        GROUP BY
            date(m.analise_data),
            m.trecho_nome,
            m.classificacao_historica,
            m.semana_referencia
        ORDER BY
            date(m.analise_data) ASC
            ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




