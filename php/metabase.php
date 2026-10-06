<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

function gerarTokenMetabase(): string
{
    $secret = $_ENV['METABASE_SECRET_KEY'] ?? null;

    if (!$secret) {
        throw new RuntimeException(
            'METABASE_SECRET_KEY não encontrada.'
        );
    }

    $payload = [
        'resource' => [
            'dashboard' => 3
        ],

        'params' => (object) [],

        '_embedding_params' => [
            'município' => 'enabled',
            'trecho' => 'enabled',
            'classificação' => 'enabled',
            'data' => 'enabled'
        ],

        'exp' => time() + (60 * 10)
    ];

    return JWT::encode(
        $payload,
        $secret,
        'HS256'
    );
}