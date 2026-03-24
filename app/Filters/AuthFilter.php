<?php

namespace App\Filters;

use App\Libraries\ApiResponse;
use App\Services\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
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

        $jwt = new JwtService();
        $api_response = new ApiResponse();

        $auth_header = $request->getHeaderLine('Authorization');

        if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
            return $api_response->set_error('Token não informado.', 401);;
        }

        $token   = substr($auth_header, 7);
        $payload = $jwt->decode_token($token);


        if (!$payload) {
            return $api_response->set_error('Token inválido ou expirado.', 401);
        }

        // Verifica permissão se passada como argumento na rota
        // ex: $routes->get('usuarios', ..., ['filter' => 'auth:usuarios.listar'])
        if (!empty($arguments)) {
            $permission_required = $arguments[0];
            if (!in_array($permission_required, $payload->permissions)) {
                return $api_response->set_error('Sem permissão para aceder a este recurso!', 403);
            }
        }

        // Injeta payload no request para uso nos controllers
        $request->jwt_payload = $payload;
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
