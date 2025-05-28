<?php

require_once 'SPCClient.php'; // Inclua o arquivo com a classe SPCClient

use ControleOnline\SPC\SPCClient;

// Configurações de credenciais (substitua pelos valores reais)
$operatorId = 'SEU_OPERATOR_ID';
$password = 'SUA_SENHA';

// Função para exibir erros de forma amigável
function handleSoapError(\SoapFault $e) {
    echo "Erro: {$e->getMessage()}\n";
    exit(1);
}

try {
    // Inicializa o cliente SPC
    $spcClient = new SPCClient($operatorId, $password);

    // Passo 1: Consultar um cliente (exemplo com CPF)
    echo "Consultando cliente...\n";
    $filtroConsulta = [
        'tipo-documento' => 'CPF',
        'numero-documento' => '12345678909', // Substitua pelo CPF desejado
        'codigo-produto' => 'SPC-MAX', // Exemplo de produto, ajuste conforme necessário
    ];

    $resultadoConsulta = $spcClient->consultar($filtroConsulta);
    if (!empty($resultadoConsulta)) {
        echo "Consulta realizada com sucesso:\n";
        print_r($resultadoConsulta);
    } else {
        echo "Nenhum resultado encontrado na consulta.\n";
    }

    // Passo 2: Negativar o cliente (inclusão no SPC)
    echo "\nNegativando cliente...\n";
    $parametrosInclusao = [
        'documento-devedor' => '12345678909', // CPF do devedor
        'tipo-devedor' => 'PF', // Pessoa Física
        'natureza-inclusao' => 'DIVIDA', // Tipo de inclusão
        'valor-debito' => 1500.00, // Valor do débito
        'data-vencimento' => date('Y-m-d'), // Data de vencimento
        'numero-contrato' => 'CONTRATO-123', // Identificador do contrato
        'nome-devedor' => 'Nome do Cliente', // Nome do devedor
        'endereco-devedor' => [
            'logradouro' => 'Rua Exemplo',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'cep' => '01001000',
        ],
    ];

    $resultadoInclusao = $spcClient->incluirSpc($parametrosInclusao);
    if (!empty($resultadoInclusao)) {
        echo "Negativação realizada com sucesso:\n";
        print_r($resultadoInclusao);
        $protocoloInclusao = $resultadoInclusao['protocolo'] ?? null; // Captura o protocolo
    } else {
        echo "Falha ao negativar o cliente.\n";
        exit(1);
    }

    // Passo 3: Remover a negativação (exclusão do SPC)
    echo "\nRemovendo negativação...\n";
    if ($protocoloInclusao) {
        $parametrosExclusao = [
            'protocolo' => $protocoloInclusao, // Protocolo retornado na inclusão
            'motivo-exclusao' => 'PAGAMENTO', // Motivo da exclusão
            'documento-devedor' => '12345678909', // CPF do devedor
        ];

        $resultadoExclusao = $spcClient->excluirSpc($parametrosExclusao);
        if (!empty($resultadoExclusao)) {
            echo "Negativação removida com sucesso:\n";
            print_r($resultadoExclusao);
        } else {
            echo "Falha ao remover a negativação.\n";
        }
    } else {
        echo "Protocolo de inclusão não encontrado. Não foi possível remover a negativação.\n";
    }

} catch (\SoapFault $e) {
    handleSoapError($e);
} catch (\Exception $e) {
    echo "Erro inesperado: {$e->getMessage()}\n";
    exit(1);
}

?>