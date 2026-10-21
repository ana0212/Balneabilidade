<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/relatorio_queries.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Escapa um valor para inserção segura no HTML do PDF.
 *
 * Converte null em string vazia e trata caracteres especiais
 * para que sejam interpretados como texto, e não como marcação HTML.
 *
 * @param mixed $valor Valor que será convertido em texto.
 * @return string Texto escapado para HTML.
 */
function escaparPdf($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Monta uma tabela HTML a partir de registros e colunas definidos.
 *
 * O array $colunas associa os nomes dos campos aos títulos
 * que serão exibidos no cabeçalho da tabela.
 *
 * @param array $dados Registros que preencherão a tabela.
 * @param array $colunas Campos e títulos das colunas.
 * @return string HTML da tabela ou mensagem para lista vazia.
 */
function formatarTabelaPdf(array $dados, array $colunas): string
{
    // Evita exibir uma tabela sem registros.
    if (empty($dados)) {
        return '<p class="vazio">Nenhum registro encontrado.</p>';
    }

    // Inicia a tabela e cria o cabeçalho.
    $html = '<table><thead><tr>';

    foreach ($colunas as $campo => $titulo) {
        // Escapa os títulos antes de inseri-los no HTML.
        $html .= '<th>' . escaparPdf($titulo) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    // Percorre os registros para preencher as linhas da tabela.
    foreach ($dados as $linha) {
        $html .= '<tr>';

        foreach ($colunas as $campo => $titulo) {
            // Se o campo não existir ou for null, utiliza string vazia.
            $valor = $linha[$campo] ?? '';

            // Escapa o valor antes de inseri-lo na célula HTML.
            $html .= '<td>' . escaparPdf($valor) . '</td>';
        }

        $html .= '</tr>';
    }

    // Finaliza a estrutura da tabela.
    $html .= '</tbody></table>';

    return $html;
}


try {
    // Obtém as semanas disponíveis para validar o parâmetro da URL.
    $semanas = getSemanasDisponiveis($pdo);

    // Lê e normaliza a semana recebida via GET.
    $semanaSelecionada = trim(
        (string) ($_GET['semana'] ?? '')
    );

    // Prepara uma lista simples de semanas válidas.
    // A função atual retorna valores escalares via FETCH_COLUMN,
    // mas este trecho também aceita registros associativos.
    $semanasValidas = [];

    foreach ($semanas as $item) {
        $semanasValidas[] = is_array($item)
            ? ($item['semana_referencia'] ?? '')
            : $item;
    }

    // Rejeita a solicitação se a semana estiver ausente
    // ou não corresponder a uma opção disponível.
    if (
        $semanaSelecionada === ''
        || !in_array($semanaSelecionada, $semanasValidas, true)
    ) {
        http_response_code(400);
        exit('Semana inválida ou não informada.');
    }

    // Consulta os indicadores e os registros necessários ao PDF.
    // As funções reutilizam a lógica SQL definida no relatório.
    $indicadores = getIndicadoresPanorama(
        $pdo,
        $semanaSelecionada
    );

    $dadosTrechos = getDadosTabelaTrechosSemana(
        $pdo,
        $semanaSelecionada
    );

    $dadosMudancas = getDadosTabelaMudancas(
        $pdo,
        $semanaSelecionada
    );


    // Cria um documento HTML independente da página web do relatório.
    // O Dompdf converterá este HTML em um arquivo PDF.
    $html = '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <style>
            /* Define as margens das páginas do PDF. */
            @page {
                margin: 28px;
            }

            /* Estilos gerais do documento. */
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 10px;
                color: #263238;
            }

            h1 {
                font-size: 20px;
                color: #164e63;
                margin-bottom: 5px;
            }

            h2 {
                font-size: 14px;
                color: #164e63;
                margin-top: 24px;
            }

            p {
                line-height: 1.5;
            }

            /* Cabeçalho do relatório. */
            .cabecalho {
                border-bottom: 2px solid #164e63;
                padding-bottom: 12px;
                margin-bottom: 20px;
            }

            /* Área que agrupa os indicadores. */
            .indicadores {
                margin: 15px 0;
                padding: 10px;
                background: #f1f5f9;
                border-radius: 4px;
            }

            .indicador {
                margin-bottom: 6px;
            }

            /* Formatação das tabelas. */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            th {
                background: #164e63;
                color: white;
                text-align: left;
            }

            th, td {
                border: 1px solid #d1d5db;
                padding: 6px;
                vertical-align: top;
            }

            /* Tenta evitar que uma linha seja dividida entre páginas. */
            tr {
                page-break-inside: avoid;
            }

            /* Estilo da mensagem exibida quando não há registros. */
            .vazio {
                color: #64748b;
                font-style: italic;
            }

            /* Rodapé do documento. */
            .rodape {
                margin-top: 20px;
                color: #64748b;
                font-size: 9px;
            }
        </style>
    </head>
    <body>';

    // Acrescenta o título e a semana selecionada ao cabeçalho.
    // O valor dinâmico é escapado antes de entrar no HTML.
    $html .= '
        <div class="cabecalho">
            <h1>Relatório Semanal de Balneabilidade</h1>
            <p>Semana de referência: '
                . escaparPdf($semanaSelecionada) .
            '</p>
        </div>';

    // Adiciona a seção dos indicadores.
    $html .= '<h2>Indicadores do panorama</h2>';
    $html .= '<div class="indicadores">';

    // Percorre os indicadores retornados pela consulta.
    foreach ($indicadores as $nome => $valor) {

        // Ignora valores que sejam arrays, pois esta seção
        // espera valores simples para apresentar como texto.
        if (!is_array($valor)) {

            // Transforma o nome do campo em um rótulo legível.
            // Ex.: total_trechos passa a ser "Total trechos".
            $rotulo = ucfirst(
                str_replace('_', ' ', (string) $nome)
            );

            // Escapa tanto o rótulo quanto o valor exibido.
            $html .= '<div class="indicador"><strong>'
                . escaparPdf($rotulo)
                . ':</strong> '
                . escaparPdf($valor)
                . '</div>';
        }
    }

    $html .= '</div>';

    // Acrescenta a tabela com as análises da semana.
    $html .= '<h2>Análises da semana</h2>';

    // Define quais campos serão exibidos e seus títulos.
    $html .= formatarTabelaPdf($dadosTrechos, [
        'analise_data' => 'Data',
        'trecho_nome' => 'Trecho',
        'municipio_nome' => 'Município',
        'quantitativo' => 'Quantitativo',
        'classificacao_historica' => 'Classificação histórica',
    ]);

    // Acrescenta a tabela de mudanças de classificação.
    $html .= '<h2>Mudanças de classificação</h2>';

    $html .= formatarTabelaPdf($dadosMudancas, [
        'analise_data' => 'Data',
        'trecho_nome' => 'Trecho',
        'municipio_nome' => 'Município',
        'classificacao_anterior' => 'Classificação anterior',
        'classificacao_historica' => 'Classificação atual',
        'mudanca_classificacao' => 'Mudança',
    ]);

    // Finaliza o documento HTML com o rodapé.
    $html .= '
        <div class="rodape">
            Relatório gerado pelo sistema de balneabilidade.
        </div>
    </body>
    </html>';


    // Configura o comportamento do Dompdf.
    $opcoes = new Options();

    // Impede o carregamento de recursos remotos.
    $opcoes->set('isRemoteEnabled', false);

    // Define a fonte padrão do documento.
    $opcoes->set('defaultFont', 'DejaVu Sans');

    // Cria o gerador de PDF com as opções definidas.
    $dompdf = new Dompdf($opcoes);

    // Entrega o HTML ao Dompdf e informa sua codificação.
    $dompdf->loadHtml($html, 'UTF-8');

    // Define o tamanho da página e a orientação paisagem.
    $dompdf->setPaper('A4', 'landscape');

    // Processa o HTML e gera o conteúdo PDF.
    $dompdf->render();

    // Define o nome do arquivo usando a data atual do servidor.
    $nomeArquivo = 'relatorio-balneabilidade-' .
        date('Y-m-d') . '.pdf';

    // Envia o PDF ao navegador como arquivo para download.
    $dompdf->stream($nomeArquivo, [
        'Attachment' => true,
    ]);

    // Encerra a execução após o envio do PDF.
    exit;

} catch (Throwable $e) {

    // Registra detalhes técnicos no log do servidor,
    // sem expor informações internas ao usuário.
    error_log(
        'Erro ao gerar relatório PDF: ' . $e->getMessage()
    );

    // Informa que ocorreu uma falha interna.
    http_response_code(500);
}