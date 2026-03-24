<?php

function print_info($data, $die = true)
{
    echo ('<pre>');
    echo (str_repeat('*', 50) . '<br>');
    echo (print_r($data, true));
    echo ('<br>');
    echo (str_repeat('*', 50) . '<br>');
    echo ('</pre>');

    if ($die) {
        die(1);
    }
}


function display_error($field, $errors)
{
    if (empty($errors)) {
        return;
    }

    if (array_key_exists($field, $errors)) {
        // Retorna o erro específico para o campo
        return isset($errors[$field]) ? '<div class="text-danger fw-semibold" role="alert"><small> <i class="fa-regular fa-circle-xmark me-1 mt-1"></i>' . $errors[$field] . '</small></div>' : '';
    }
}


if (!function_exists('mensagens_alertas')) {
    function mensagens_alertas()
    {
        $session = session();

        if ($session->getFlashdata('sucesso')) {
            '<div class="alert alert-success alert-dismissible fade show position-fixed"
                style="top: 20px; right: 20px; z-index: 9999; min-width: 350px;" role="alert">
                <i class="fas fa-check-circle me-2"></i>'
                . $session->getFlashdata('sucesso') .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }


        if ($session->getFlashdata('erro')) {
            return '<div class="alert alert-danger alert-dismissible fade show position-fixed"
                style="top: 20px; right: 20px; z-index: 9999; min-width: 350px;" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>'
                . esc($session->getFlashdata('erro')) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
    }
}



function normalize_date($date)
{
    return date('d/m/Y', strtotime($date));
}

function normalize_datetime($datetime)
{
    return date('d/m/Y H:i', strtotime($datetime));
}

function normalize_timstampe($datetime)
{
    return date('d/m/Y H:i:s', strtotime($datetime));
}

function normalize_time($time)
{
    return date('H:i:s', strtotime($time));
}


// função para gerar chaves da API
function generate_api_key()
{
    return bin2hex(random_bytes(32));
}