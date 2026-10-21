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