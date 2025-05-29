<?php

require_once 'SPCClient.php'; // Inclua o arquivo com a classe SPCClient

use ControleOnline\SPC\SPCClient;

// Configurações de credenciais (substitua pelos valores reais)
$operatorId = '128274690';
$password = 'Spc@2025!';

// Função para exibir erros de forma amigável
function handleSoapError(\SoapFault $e)
{
    echo "Erro: {$e->getMessage()}\n";
    exit(1);
}

try {
    $spcClient = new SPCClient($operatorId, $password);

    echo "\nTentando negativar cliente com a estrutura do NOVO WSDL...\n";
    $parametrosInclusao = [
        'insumoSpc' => [
            'tipo-pessoa' => 'F',
            'dados-pessoa-fisica' => [
                'cpf' => [
                    'numero' => '12345678909',
                    'regiao-origem' => 'SP',
                ],
                'nome' => 'Nome do Cliente',
                'data-nascimento' => date('c', strtotime('1975-12-23')),
            ],
            'data-vencimento' => date('c', strtotime('2025-03-29')),
            'data-compra' => date('c', strtotime('2025-01-28')),
            'numero-contrato' => 'CONTRATO-123',
            'valor-debito' => 1500.00,
            'natureza-inclusao' => [
                'id' => 104, // Ajustado para "ATRASO DE PAGAMENTO"
                'nome' => "ATRASO DE PAGAMENTO"
            ],
            'codigo-tipo-devedor' => 'C',
        ],
    ];

    $resultadoInclusao = $spcClient->incluirSpc($parametrosInclusao);

    if (!empty($resultadoInclusao)) {
        echo "Negativação realizada com sucesso:\n";
        print_r($resultadoInclusao);
        $protocoloInclusao = $resultadoInclusao['protocolo'] ?? null;
    } else {
        echo "Falha ao negativar o cliente.\n";
        exit(1);
    }
} catch (\SoapFault $e) {
    handleSoapError($e);
} catch (\Exception $e) {
    echo "Erro inesperado: {$e->getMessage()}\n";
    exit(1);
}
