<?php

namespace App\Libraries;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class ApiResponse
{
    use ResponseTrait;

    /**
     * @var string Versão da API
     */
    protected $version;

    /**
     * @var string Nome da API
     */
    protected $name;

    /**
     * @var string Documentação da API
     */
    protected $documentation;

    /**
     * @var string Contato da API
     */
    protected $contact;

    /**
     * @var string Licença da API
     */
    protected $license;

    /**
     * @var string Autor da API
     */
    protected $author;

    /**
     * @var project id
     */
    protected $project_id;

    /**
     * @var RequestInterface Request da API
     */
    protected RequestInterface $request;

    /**
     *  @var ResponseInterface response da API
     */
    protected ResponseInterface $response;



    public function __construct(?RequestInterface $request = null, ?ResponseInterface $response = null)
    {

        $this->request  = $request  ?? Services::request();
        $this->response = $response ?? Services::response();
        $this->project_id = current_project_id();

        // Verificar se a API está ativa
        if (!defined('API_ACTIVE') || !API_ACTIVE) {
            return $this->_api_not_active();
        }
        // Inicializar propriedades da API
        $this->initializeApiInfo();
    }

    /**
     * Inicializa informações da API
     */
    private function initializeApiInfo(): void
    {
        $this->version = defined('API_VERSION') ? API_VERSION : '1.0.0';
        $this->name = defined('API_NAME') ? API_NAME : 'API';
        $this->documentation = defined('API_DOCUMENTATION') ? API_DOCUMENTATION : '';
        $this->contact = defined('API_CONTACT') ? API_CONTACT : '';
        $this->license = defined('API_LICENSE') ? API_LICENSE : '';
        $this->author = defined('API_AUTHOR') ? API_AUTHOR : '';
    }


    /**
     * Valida método HTTP da requisição
     */
    public function validade_request(string $method)
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            return $this->set_response_error(405, 'Method not allowed');
            die(1);
        }
    }

    // success request
    public function set_success($data = [], $message = 'success', $code = 200)
    {
        return $this->set_response($code, true, $message, $data);
    }

    // error request
    public function set_error($message = 'error', $code = 400)
    {
        return $this->set_response_error($code, $message);
    }

    // validation
    public function set_validation_errors($errors = [])
    {
        return $this->set_response_error(422, 'Validation error', $errors);
    }

    /**
     * Resposta de sucesso padrão
     */
    public function set_response($code = 200, $status = true, $message = 'success', $data = [])
    {
        response()->setContentType('application/json');
        $response_data = $this->set_response_array($code, $status, $message, $data);
        return $this->respond($response_data, $code);
    }

    /**
     * Resposta de erro padrão
     */

    public function set_response_error($code = 400, $message = 'error', $errors = [])
    {
        response()->setContentType('application/json');
        $response_data = $this->set_response_array($code, false, $message, []);

        if (!empty($errors)) {
            $response_data['meta']['errors'] = $errors;
        }
        return $this->respond($response_data, $code);
    }


    /**************************************************************************
     *********************************PRIVATE FUNCTIONS************************
     **************************************************************************/


    /**
     * Constrói array padrão da resposta da API
     */
    private function set_response_array($code, $status, $message, $data)
    {
        return [
            'code' => $code,
            'status' => $status ? 'success' : 'error',
            'message' => $message,
            'info' => [
                'version' => $this->version,
                'name' => $this->name,
                'documentation' => $this->documentation,
                'contact' => $this->contact,
                'license' => $this->license,
                'author' => $this->author,
                'datetime' => date('Y-m-d H:i:s'),
                'timestamp' => time(),
                'project_id' => $this->project_id,
            ],
            'data' => $data,
            'meta' => []
        ];
    }

    /**
     * Determina código HTTP baseado no status
     */
    private function get_response_code($status, $message)
    {
        if ($status) {
            return 200;
        }

        return match (true) {
            str_contains($message, 'not found') => 404,
            str_contains($message, 'not allowed') => 405,
            str_contains($message, 'unauthorized') => 401,
            str_contains($message, 'forbidden') => 403,
            str_contains($message, 'bad request') => 400,
            default => 500
        };
    }

    /**
     * API não está ativa
     */
    private function _api_not_active()
    {
        response()->setContentType('application/json');
        $response_data = [
            'code'      => 503,
            'status'    => 'error',
            'message'   => 'API is not active...',
            'info'      => [
                'version'       => API_VERSION,
                'name'          => API_NAME,
                'documentation' => API_DOCUMENTATION,
                'contact'       => API_CONTACT,
                'license'       => API_LICENSE,
                'author'        => API_AUTHOR,
                'datetime'      => date('Y-m-d H:i:s'),
                'timestamp'     => time(),
                'project_id'    => null,
            ],
            'data'              => [],
            'meta'              => []
        ];

        return $this->respond($response_data, 503);
    }
}
