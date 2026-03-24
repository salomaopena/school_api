<?php

namespace App\Filters;

use App\Libraries\ApiResponse;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HttpBasicAuth implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verificar se a requisição contem as credenciais de autorização

        $api_response =  new ApiResponse();

        // verifica se tem autorização de aceder a rota
        if (!$request->hasHeader('Authorization')) {
            return $api_response->set_response_error(401,'Invalid authorization credencials');
        }

        // Buscar as credencias do request
        $username = $request->getServer('PHP_AUTH_USER');
        $password = $request->getServer('PHP_AUTH_PW');

        if ($username !== API_USERNAME || $password !== API_PASSWORD) {
            return $api_response->set_response_error(401,'Invalid authentication credencials');
        }
     
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
