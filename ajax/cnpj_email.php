<?php
/**
 * Proxy server-side para busca de e-mail na ReceitaWS.
 * Resolve o bloqueio de CORS quando o GLPI roda em localhost ou HTTP.
 *
 * Chamada: GET /ajax/cnpj_email.php?cnpj=11507196000121
 * Retorna: JSON { "email": "financeiro@empresa.com.br" } ou { "email": null }
 *
 * Segurança:
 *  - Session::checkLoginUser() — apenas usuários autenticados
 *  - Método exclusivamente GET
 *  - CNPJ validado (14 dígitos) antes de qualquer requisição externa
 *  - Erros de rede tratados sem supressor @ (logs preservados)
 */

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
    require GLPI_ROOT . '/inc/includes.php';
}

// Apenas usuários autenticados podem usar este endpoint
Session::checkLoginUser();

// Apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');

// Valida formato básico (14 dígitos)
if (strlen($cnpj) !== 14) {
    echo json_encode(['email' => null, 'error' => 'CNPJ inválido']);
    exit;
}

// Faz a requisição server-side (sem restrição de CORS)
// Remove o supressor @ para que erros de rede sejam registrados nos logs do GLPI
$url  = 'https://receitaws.com.br/v1/cnpj/' . $cnpj;
$opts = [
    'http' => [
        'method'  => 'GET',
        'timeout' => 8,
        'header'  => "Accept: application/json\r\nUser-Agent: GLPI-Newmanagement-Plugin/1.0\r\n",
        'ignore_errors' => true, // permite ler o body mesmo em respostas 4xx/5xx
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
];

$context  = stream_context_create($opts);
$response = file_get_contents($url, false, $context);

if ($response === false) {
    // Erro real de rede (timeout, DNS, etc.) — log preservado sem @
    \Toolbox::logDebug('cnpj_email.php: falha ao conectar na ReceitaWS para CNPJ ' . $cnpj);
    echo json_encode(['email' => null, 'error' => 'Falha ao conectar na ReceitaWS']);
    exit;
}

$data = json_decode($response, true);

// ReceitaWS retorna {"status":"ERROR",...} para CNPJs não encontrados ou rate-limit
if (!is_array($data) || ($data['status'] ?? '') === 'ERROR') {
    $msg = $data['message'] ?? 'CNPJ não encontrado ou limite de requisições atingido';
    \Toolbox::logDebug('cnpj_email.php: ReceitaWS retornou erro — ' . $msg);
    echo json_encode(['email' => null, 'error' => $msg]);
    exit;
}

$email = null;

if (isset($data['email']) && trim($data['email']) !== '') {
    // Sanitiza o e-mail antes de retornar
    $validated = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
    $email = $validated !== false ? $validated : null;
}

echo json_encode(['email' => $email]);
