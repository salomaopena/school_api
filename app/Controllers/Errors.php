<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiResponse;

class Errors extends BaseController
{
    public function notFound()
    {
        $api_response = new ApiResponse();
        return $api_response->set_response_error(404,'Endpoint, does not exist!');
    }
}
