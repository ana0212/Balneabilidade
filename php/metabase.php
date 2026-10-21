
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;

// Carrega as variáveis de ambiente do arquivo .env.
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();


/**
 * Gera um token JWT para incorporar um dashboard do Metabase.
 *
 * @param int $dashboardId ID do dashboard autorizado.
 * @return string Token JWT assinado com HS256.
 */
function gerarTokenMetabase(int $dashboardId): string
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
        'resource' => ['dashboard' => $dashboardId],
        'params' => (object) [],
        'exp' => time() + (60 * 10),
    ];

    return JWT::encode($payload, $secret, 'HS256');
}

