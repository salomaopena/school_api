<?php

namespace App\Routes;

use Config\Services;


$routes = Services::routes();

$routes->set404Override('\App\Controllers\Errors::notFound');


$routes->group('v1', function ($routes) {

    // Show api status
    $routes->get('status', 'Main::status');


    //Auth group routes
    $routes->group('auth', static function ($routes) {

        $routes->post('login', 'AuthController::login');
        $routes->post('logout', 'AuthController::logout', ['filter' => 'auth']);
        $routes->post('forgot-password', 'AuthController::forgot_password');
        $routes->post('reset-password', 'AuthController::reset_password');
        $routes->post('refresh-token', 'AuthController::refresh_token');
        $routes->post('change-password', 'AuthController::change_password', ['filter' => 'auth']);
    });

    // user management
    $routes->group('users', static function ($routes) {

        $routes->post('create', 'UserManagement::create', [
            'filter' => 'auth:usuarios.criar'
        ]);

        $routes->post('update', 'UserManagement::update', [
            'filter' => 'auth:usuarios.atualizar'
        ]);

        $routes->post('delete', 'UserManagement::delete', [
            'filter' => 'auth:usuarios.excluir'
        ]);

        $routes->post('list', 'UserManagement::list', [
            'filter' => 'auth:usuarios.ler'
        ]);

        $routes->post('deactivate', 'UserManagement::deactivate', [
            'filter' => 'auth:usuarios.desativar'
        ]);


        $routes->post('activate', 'UserManagement::activate', [
            'filter' => 'auth:usuarios.ativar'
        ]);


        $routes->post('show', 'UserManagement::show', [
            'filter' => 'auth:usuarios.ver'
        ]);
    });



    // candidaturas
    $routes->group('candidaturas', ['filter' => 'auth'], function ($routes) {
        $routes->post('list',              'CandidaturaController::list');
        $routes->post('show',            'CandidaturaController::show');
        $routes->post('create',         'CandidaturaController::create');
        $routes->post(
            'status',
            'CandidaturaController::update_status',
            ['filter' => 'auth:candidaturas.validar']
        );
        $routes->post(
            'delete',
            'CandidaturaController::delete',
            ['filter' => 'auth:candidaturas.excluir']
        );
        $routes->post('submit-proof', 'CandidaturaController::submit_proof');
        $routes->post('validate-payment', 'CandidaturaController::validate_payment');
    });


    // Exames de aptidão
    $routes->group('exames-aptidao', ['filter' => 'auth'], function ($routes) {
        $routes->post(
            'list',
            'ExameAptidaoController::listar',
            ['filter' => 'auth:exames.ler']
        );
        $routes->post(
            'show',
            'ExameAptidaoController::show',
            ['filter' => 'auth:exames.ver']
        );
        $routes->post(
            'create',
            'ExameAptidaoController::create',
            ['filter' => 'auth:exames.criar']
        );
        $routes->post(
            'define-date',
            'ExameAptidaoController::definir_data',
            ['filter' => 'auth:exames.data']
        );
        $routes->post(
            'register-score',
            'ExameAptidaoController::registar_notas',
            ['filter' => 'auth:exames.notas']
        );
    });


    // Alunos
    $routes->group('alunos', ['filter' => 'auth'], function ($routes) {
        $routes->post('list',           'AlunoController::list', ['filter' => 'auth:alunos.ler']);
        $routes->post('show',           'AlunoController::show', ['filter' => 'auth:alunos.ver']);
        $routes->post('seriar',         'AlunoController::seriar', ['filter' => 'auth:alunos.seriar']);
        $routes->post('seriar-massa',   'AlunoController::seriar_massa', ['filter' => 'auth:alunos.seriar']);
    });


    $routes->group('matricula', ['filter' => 'auth'], function ($routes) {
        $routes->post('list',           'MatriculaController::list', ['filter' => 'auth:matricula.ler']);
        $routes->post('show',            'MatriculaController::show', ['filter' => 'auth:matricula.ver']);
        $routes->post('create',         'MatriculaController::create', ['filter' => 'auth:matricula.criar']);
        $routes->post('update',         'MatriculaController::update', ['filter' => 'auth:matricula.atualizar']);
        $routes->post('validate',       'MatriculaController::validar', ['filter' => 'auth:matricula.validar']);
        $routes->post('cancel',         'MatriculaController::cancelar', ['filter' => 'auth:matricula.anular']);
        $routes->post('reconfirm',      'MatriculaController::reconfirmar', ['filter' => 'auth:matricula.criar']);
        $routes->post('proof',          'MatriculaController::submeter_comprovativo_matricula');
        $routes->post('validate-payment',     'MatriculaController::validar_pagamento_matricula', ['filter' => 'auth:matricula.validar']);
    });


    // Plano Curricular
    $routes->group('plano/curricular', ['filter' => 'auth'], function ($routes) {
        $routes->post('create',        'PlanoCurricularController::create');
        $routes->post('search',        'PlanoCurricularController::consultar');
        $routes->post('update',         'PlanoCurricularController::update');
        $routes->post('delete',      'PlanoCurricularController::delete');
    });


    // Docência
    $routes->group('docencia', ['filter' => 'auth'], function ($routes) {
        // Turma/Disciplina
        $routes->post('atribuir/disciplina/turma',      'DocenciaController::atribuir_disciplinas_turma');
        $routes->post('list/disciplina/turma',          'DocenciaController::disciplinas_por_turma');
        $routes->post('remover/disciplina/turma',       'DocenciaController::remover_disciplina_turma');

        // Docente/Disciplina
        $routes->post('atribuir/disciplina/docente',    'DocenciaController::atribuir_docente');
        $routes->post('list/disciplina/docente',        'DocenciaController::docentes_por_disciplina');
        $routes->post('remover/disciplina/docente',     'DocenciaController::remover_docente');
    });
});
