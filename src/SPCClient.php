<?php

namespace ControleOnline\SPC;

class SPCClient
{
    private $consultaClient; // SoapClient for consultaWebService
    private $insumoClient;  // SoapClient for SpcWebService
    private $operatorId;
    private $password;

    private const CONSULTA_WSDL = 'https://treinamento.spcbrasil.com.br/spc/remoting/ws/consulta/consultaWebService?wsdl';
    private const INSUMO_WSDL = 'https://treinamento.spcbrasil.com.br/spc/remoting/ws/insumo/spc/spcWebService?wsdl';

    /**
     * Constructor to initialize SOAP clients with credentials.
     *
     * @param string $operatorId Operator ID for authentication
     * @param string $password Password for authentication
     * @throws \SoapFault If SOAP client initialization fails
     */
    public function __construct(string $operatorId, string $password)
    {
        $this->operatorId = $operatorId;
        $this->password = $password;

        try {
            // Configurar contexto com autenticação básica
            $context = stream_context_create([
                'http' => [
                    'header' => sprintf('Authorization: Basic %s', base64_encode("$operatorId:$password")),
                ],
            ]);

            // Initialize consultaWebService client
            $this->consultaClient = new \SoapClient(self::CONSULTA_WSDL, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'soap_version' => SOAP_1_1,
                'stream_context' => $context,
            ]);
            $this->addWSSecurityHeaders($this->consultaClient);

            // Initialize SpcWebService client
            $this->insumoClient = new \SoapClient(self::INSUMO_WSDL, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'soap_version' => SOAP_1_1,
                'stream_context' => $context,
            ]);
            $this->addWSSecurityHeaders($this->insumoClient);
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to initialize SOAP client: " . $e->getMessage());
        }
    }

    /**
     * Adds WS-Security headers for authentication to a SoapClient.
     *
     * @param \SoapClient $client The SOAP client to add headers to
     */
    private function addWSSecurityHeaders(\SoapClient $client): void
    {
        $wsseNamespace = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        $security = new \SoapHeader(
            $wsseNamespace,
            'Security',
            new \SoapVar(
                new \SoapVar(
                    [
                        new \SoapVar(
                            [
                                new \SoapVar($this->operatorId, XSD_STRING, null, null, 'Username', $wsseNamespace),
                                new \SoapVar($this->password, XSD_STRING, null, null, 'Password', $wsseNamespace),
                            ],
                            SOAP_ENC_OBJECT,
                            null,
                            null,
                            'UsernameToken',
                            $wsseNamespace
                        ),
                    ],
                    SOAP_ENC_OBJECT,
                    null,
                    null,
                    null,
                    $wsseNamespace
                ),
                SOAP_ENC_OBJECT
            ),
            false
        );

        $client->__setSoapHeaders([$security]);
    }

    // === consultaWebService Methods ===

    /**
     * Lists available products.
     *
     * @return array List of products
     * @throws \SoapFault If the SOAP call fails
     */
    public function listarProdutos()
    {
        try {
            $response = $this->consultaClient->listarProdutos();
            return $response->produtos ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to list products: " . $e->getMessage());
        }
    }

    /**
     * Performs a query with the provided filter.
     *
     * @param array $filtro Filter parameters (structure depends on schema)
     * @return array Query result
     * @throws \SoapFault If the SOAP call fails
     */
    public function consultar(array $filtro)
    {
        try {
            $response = $this->consultaClient->consultar(['filtro' => $filtro]);
            return $response->resultado ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to perform query: " . $e->getMessage());
        }
    }

    /**
     * Retrieves details for a specific product.
     *
     * @param string $codigoProduto Product code
     * @return array Product details
     * @throws \SoapFault If the SOAP call fails
     */
    public function detalharProduto(string $codigoProduto)
    {
        try {
            $response = $this->consultaClient->detalharProduto(['codigo-produto' => $codigoProduto]);
            return $response->produto ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to get product details: " . $e->getMessage());
        }
    }

    /**
     * Performs a complementary query with the provided filter.
     *
     * @param array $filtroComplementar Complementary filter parameters
     * @return array Query result
     * @throws \SoapFault If the SOAP call fails
     */
    public function consultaComplementar(array $filtroComplementar)
    {
        try {
            $response = $this->consultaClient->consultaComplementar(['filtro-complementar' => $filtroComplementar]);
            return $response->resultado ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to perform complementary query: " . $e->getMessage());
        }
    }

    /**
     * Queries a score with the provided filter.
     *
     * @param array $filtroScore Score filter parameters
     * @return array Score query result
     * @throws \SoapFault If the SOAP call fails
     */
    public function consultaScore(array $filtroScore)
    {
        try {
            $response = $this->consultaClient->consultaScore(['filtro-score' => $filtroScore]);
            return $response->resultadoConsultaScore ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to query score: " . $e->getMessage());
        }
    }

    /**
     * Queries optional input data with the provided filter.
     *
     * @param array $filtroInsumoOpcional Optional input filter parameters
     * @return array Optional input query result
     * @throws \SoapFault If the SOAP call fails
     */
    public function consultaInsumoOpcional(array $filtroInsumoOpcional)
    {
        try {
            $response = $this->consultaClient->consultaInsumoOpcional(['filtro-insumo-opcional' => $filtroInsumoOpcional]);
            return $response->insumoOpcional ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to query optional input: " . $e->getMessage());
        }
    }

    // === SpcWebService Methods ===

    /**
     * Adds an SPC record.
     *
     * @param array $parameters Parameters for including an SPC record (structure depends on schema)
     * @return array Response data
     * @throws \SoapFault If the SOAP call fails
     */
    public function incluirSpc(array $parameters)
    {
        try {
            $response = $this->insumoClient->incluirSpc(['insumoSpc' => $parameters['insumoSpc']]);
            return $response->sucesso ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to include SPC record: " . $e->getMessage());
        } finally {
            //echo "\n\nÚltima requisição SOAP enviada (incluirSpc):\n";
            //echo $this->insumoClient->__getLastRequest();
            //echo "\n";
        }
    }

    /**
     * Removes an SPC record.
     *
     * @param array $parameters Parameters for excluding an SPC record (structure depends on schema)
     * @return array Response data
     * @throws \SoapFault If the SOAP call fails
     */
    public function excluirSpc(array $parameters)
    {
        try {
            $response = $this->insumoClient->excluirSpc(['parameters' => $parameters]);
            return $response->parameters ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to exclude SPC record: " . $e->getMessage());
        }
    }

    /**
     * Lists inclusion types.
     *
     * @param array $parameters Parameters for listing inclusion types (structure depends on schema)
     * @return array List of inclusion types
     * @throws \SoapFault If the SOAP call fails
     */
    public function listarNaturezaInclusao(array $parameters = [])
    {
        try {
            $response = $this->insumoClient->listarNaturezaInclusao(['parameters' => $parameters]);
            return $response->parameters ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to list inclusion types: " . $e->getMessage());
        }
    }

    /**
     * Lists exclusion reasons.
     *
     * @param array $parameters Parameters for listing exclusion reasons (structure depends on schema)
     * @return array List of exclusion reasons
     * @throws \SoapFault If the SOAP call fails
     */
    public function listarMotivoExclusao(array $parameters = [])
    {
        try {
            $response = $this->insumoClient->listarMotivoExclusao(['parameters' => $parameters]);
            return $response->parameters ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to list exclusion reasons: " . $e->getMessage());
        }
    }

    /**
     * Lists debtor types.
     *
     * @param array $parameters Parameters for listing debtor types (structure depends on schema)
     * @return array List of debtor types
     * @throws \SoapFault If the SOAP call fails
     */
    public function listarTipoDevedor(array $parameters = [])
    {
        try {
            $response = $this->insumoClient->listarTipoDevedor(['parameters' => $parameters]);
            return $response->parameters ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to list debtor types: " . $e->getMessage());
        }
    }

    /**
     * Adds an SPC record via mobile.
     *
     * @param array $parameters Parameters for including an SPC mobile record (structure depends on schema)
     * @return array Response data
     * @throws \SoapFault If the SOAP call fails
     */
    public function incluirSpcMobile(array $parameters)
    {
        try {
            $response = $this->insumoClient->incluirSpcMobile(['parameters' => $parameters]);
            return $response->parameters ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to include SPC mobile record: " . $e->getMessage());
        }
    }

    /**
     * Removes an SPC record via mobile.
     *
     * @param array $parameters Parameters for excluding an SPC mobile record (structure depends on schema)
     * @return array Response data
     * @throws \SoapFault If the SOAP call fails
     */
    public function excluirSpcMobile(array $parameters)
    {
        try {
            $response = $this->insumoClient->excluirSpcMobile(['parameters' => $parameters]);
            return $response->parameters ?? [];
        } catch (\SoapFault $e) {
            throw new \SoapFault($e->faultcode, "Failed to exclude SPC mobile record: " . $e->getMessage());
        }
    }
}
