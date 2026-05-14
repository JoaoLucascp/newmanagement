<?php
/**
 * Proxy server-side para busca de e-mail na ReceitaWS.
 * Resolve o bloqueio de CORS quando o GLPI roda em localhost ou HTTP.
 *
 * Chamada: GET /ajax/cnpj_email.php?cnpj=11507196000121
 * Retorna: JSON { "email": "financeiro@empresa.com.br" } ou { "email": null }
 */

define('GLPI_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
require GLPI_ROOT . '/inc/includes.php';

// Apenas usuarios autenticados podem usar este endpoint
Session::checkLoginUser();

// Apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

$cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');

// Valida formato basico (14 digitos)
if (strlen($cnpj) !== 14) {
    header('Content-Type: application/json');
    echo json_encode(['email' => null, 'error' => 'CNPJ invalido']);
    exit;
}

// Faz a requisicao server-side (sem restricao de CORS)
$url  = 'https://receitaws.com.br/v1/cnpj/' . $cnpj;
$opts = [
    'http' => [
        'method'  => 'GET',
        'timeout' => 8,
        'header'  => "Accept: application/json\r\nUser-Agent: GLPI-Newmanagement-Plugin/1.0\r\n",
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
];

$context  = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

header('Content-Type: application/json');

if ($response === false) {
    echo json_encode(['email' => null, 'error' => 'Falha ao conectar na ReceitaWS']);
    exit;
}

$data  = json_decode($response, true);
$email = null;

if (isset($data['email']) && trim($data['email']) !== '') {
    // Sanitiza o e-mail antes de retornar
    $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
    if ($email === false) {
        $email = null;
    }
}

echo json_encode(['email' => $email]);
