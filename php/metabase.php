
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Dotenv\Dotenv;

// Carrega o .env localizado na raiz do projeto
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

function gerarTokenMetabase(): string
{
    $secret = $_ENV['METABASE_SECRET_KEY']
        ?? getenv('METABASE_SECRET_KEY')
        ?: null;

    if (!$secret) {
        throw new RuntimeException(
            'METABASE_SECRET_KEY não encontrada.'
        );
    }

    $payload = [
        'resource' => ['dashboard' => 3],
        'params' => (object) [],
        'exp' => time() + (60 * 10)
    ];

    return JWT::encode($payload, $secret, 'HS256');
}
