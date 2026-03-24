<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiResponse;
use App\Models\PermissionsModel;
use App\Models\RolesModel;
use App\Models\UsuarioModel;

class Main extends BaseController
{

    protected $api_response;

    public function __construct()
    {
        $this->api_response =  new ApiResponse();
    }


    public function status()
    {
        $this->api_response->validade_request('GET');
        return $this->api_response->set_success([], 'API is running...', 200);
    }

}
