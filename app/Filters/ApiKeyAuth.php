<?php

namespace App\Filters;

use App\Libraries\ApiResponse;
use App\Models\ApiKeyModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiKeyAuth implements FilterInterface
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

        // check if request header contains encryptation
        $apiKey = $request->getHeaderLine('X-API-KEY');

        if (!$apiKey) {
            return (new ApiResponse())->set_response_error(401, 'Unauthorized request. API key missing...');
        }

        $model = model(ApiKeyModel::class);

        $hash = hash('sha256', $apiKey);

        $key = $model
            ->where('api_key_hash', $hash)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->first();

        if (!$key) {
            return (new ApiResponse())->set_response_error(401, 'Unauthorized request. Invalid API key...');
        }


        /**
         * Rate limit
         */

        $cache = \Config\Services::cache();

        $keyCache = 'rate_' . $key->api_id;

        $count = $cache->get($keyCache) ?? 0;

        if ($count >= 1000) {
            return (new ApiResponse())->set_response_error(429, 'Rate limit exceeded');
        }

        $cache->save($keyCache, $count + 1, 60);


        // adicionar dados no request
        $request->project_id = $key->project_id;
        $request->api_id = $key->api_id;

        return;
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
