<?php

/**
 * Escapa valores para exibição em HTML.
 *
 * @param mixed $valor Valor a ser exibido.
 * @return string Valor escapado.
 */
function escaparHtml($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Valida um ID inteiro positivo recebido por parâmetro.
 *
 * Retorna null quando o parâmetro não foi informado.
 * Lança uma exceção quando o valor informado é inválido.
 */
function validarIdOpcional(array $parametros, string $nome): ?int
{
    if (!isset($parametros[$nome]) || $parametros[$nome] === '') {
        return null;
    }

    $valor = $parametros[$nome];

    if (!is_string($valor)) {
        throw new InvalidArgumentException(
            "Parâmetro inválido: {$nome}."
        );
    }

    $id = filter_var(
        $valor,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        throw new InvalidArgumentException(
            "Parâmetro inválido: {$nome}."
        );
    }

    return $id;
}


/**
 * Executa uma consulta e trata falhas específicas do PDO.
 *
 * Registra os detalhes técnicos da exceção no log e retorna
 * um resultado estruturado para que a página possa decidir
 * como apresentar a visualização ao usuário.
 *
 * @param callable $consulta Função que executa a consulta e retorna os dados.
 * @param string $contexto Identificação da visualização para o log.
 *
 * @return array{
 *     sucesso: bool,
 *     dados: mixed
 * } Indica se a consulta foi executada e contém os dados retornados.
 */
function executarConsultaComSeguranca(
    callable $consulta,
    string $contexto
): array {
    try {
        return [
            'sucesso' => true,
            'dados' => $consulta()
        ];
    } catch (PDOException $e) {
        error_log(
            "Falha ao carregar {$contexto}: "
            . $e->getMessage()
        );

        return [
            'sucesso' => false,
            'dados' => []
        ];
    }
}