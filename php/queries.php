<?php

require_once __DIR__ . '/database.php';

/**
 * Retorna os municípios cadastrados, ordenados pelo nome.
 *
 * @param PDO $pdo Conexão ativa com o banco de dados.
 * @return array Lista de municípios com ID e nome.
 */
function getMunicipios(PDO $pdo): array
{
    $sql = "
        SELECT
            municipio_id,
            municipio_nome
        FROM municipios
        ORDER BY municipio_nome
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll();
}

/**
 * Retorna os trechos de balneabilidade, ordenados pelo nome,
 * opcionalmente filtrados por município.
 *
 * @param PDO $pdo Conexão ativa com o banco de dados.
 * @param int|null $municipioId ID do município ou null para não filtrar.
 * @return array Lista de trechos encontrados.
 */
function getTrechos(PDO $pdo, ?int $municipioId = null): array
{
    $sql = "
        SELECT
            trecho_id,
            trecho_nome,
            municipio_id
        FROM trechos
        WHERE excluido = 0
    ";

    $params = [];

    if ($municipioId !== null) {
        $sql .= " AND municipio_id = :municipio_id";
        $params[':municipio_id'] = $municipioId;
    }

    $sql .= " ORDER BY trecho_nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Retorna os trechos de balneabilidade, com a classificação mais recente
 * opcionalmente filtrados por trecho.
 *
 * @param PDO $pdo Conexão ativa com o banco de dados.
 * @param int|null $trechoId ID do trecho ou null para não filtrar.
 * @return array Lista de trechos encontrados.
 */
function getSituacaoAtual(PDO $pdo, ?int $trechoId = null): array
{
    $sql = "
        SELECT
            trecho_id,
            trecho_nome,
            municipio_nome,
            qtd_analises,
            quantitativo_mais_recente,
            classificacao
        FROM classificacao_trechos
        WHERE 1 = 1
    ";

    $params = [];

    if ($trechoId !== null) {
        $sql .= " AND trecho_id = :trecho_id";
        $params[':trecho_id'] = $trechoId;
    }

    $sql .= " ORDER BY municipio_nome, trecho_nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Retorna os trechos de balneabilidade, com a classificação histórica
 *
 * @param PDO $pdo Conexão ativa com o banco de dados.
 * @return array Lista de trechos encontrados.
 */
function getHistorico(PDO $pdo, int $trechoId): array
{
    $sql = "
        SELECT
            a.analise_id,
            a.analise_data,
            a.quantitativo,
            ch.classificacao
        FROM analises AS a
        LEFT JOIN classificacao_historica AS ch
            ON a.analise_id = ch.analise_referencia_id
        WHERE a.trecho_id = :trecho_id
        ORDER BY a.analise_data DESC, a.analise_id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':trecho_id' => $trechoId
    ]);

    return $stmt->fetchAll();
}