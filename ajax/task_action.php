<?php

/**
 * Newmanagement - AJAX: Ações de Tarefa
 * Ações: add_task | update_task | delete_task
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Task;

header('Content-Type: application/json; charset=utf-8');

Session::checkLoginUser();

// Lê payload JSON
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload inválido.']);
    exit;
}

// CSRF obrigatório
if (!Session::validateCSRF(['_glpi_csrf_token' => $input['_glpi_csrf_token'] ?? ''])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
    exit;
}

$action = $input['action'] ?? '';
$task   = new Task();

try {
    switch ($action) {

        // ── ADD ──────────────────────────────────────────
        case 'add_task':
            Session::checkRight(Task::$rightname, CREATE);

            $data = [
                'name'              => strip_tags($input['name'] ?? ''),
                'status'            => (int) ($input['status'] ?? 0),
                'companies_id'      => (int) ($input['companies_id'] ?? 0) ?: null,
                'assigned_user_id'  => (int) ($input['assigned_user_id'] ?? 0) ?: null,
                'date_due'          => $input['date_due'] ?? null,
                'km_calculated'     => is_numeric($input['km_calculated'] ?? '') ? (float) $input['km_calculated'] : null,
                'latitude'          => is_numeric($input['latitude'] ?? '')  ? (float) $input['latitude']  : null,
                'longitude'         => is_numeric($input['longitude'] ?? '') ? (float) $input['longitude'] : null,
                'digital_signature' => strip_tags($input['digital_signature'] ?? ''),
                'comment'           => strip_tags($input['comment'] ?? ''),
            ];

            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => __('O título é obrigatório.', 'newmanagement')]);
                exit;
            }

            $newid = $task->add($data);
            if ($newid) {
                echo json_encode([
                    'success'    => true,
                    'id'         => $newid,
                    'csrf_token' => Session::getNewCSRFToken(),
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => __('Erro ao criar tarefa.', 'newmanagement')]);
            }
            break;

        // ── UPDATE ───────────────────────────────────────
        case 'update_task':
            Session::checkRight(Task::$rightname, UPDATE);

            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => __('ID inválido.', 'newmanagement')]);
                exit;
            }

            $data = [
                'id'                => $id,
                'name'              => strip_tags($input['name'] ?? ''),
                'status'            => (int) ($input['status'] ?? 0),
                'companies_id'      => (int) ($input['companies_id'] ?? 0) ?: null,
                'assigned_user_id'  => (int) ($input['assigned_user_id'] ?? 0) ?: null,
                'date_due'          => $input['date_due'] ?? null,
                'km_calculated'     => is_numeric($input['km_calculated'] ?? '') ? (float) $input['km_calculated'] : null,
                'latitude'          => is_numeric($input['latitude'] ?? '')  ? (float) $input['latitude']  : null,
                'longitude'         => is_numeric($input['longitude'] ?? '') ? (float) $input['longitude'] : null,
                'digital_signature' => strip_tags($input['digital_signature'] ?? ''),
                'comment'           => strip_tags($input['comment'] ?? ''),
            ];

            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => __('O título é obrigatório.', 'newmanagement')]);
                exit;
            }

            $task->update($data);
            echo json_encode([
                'success'    => true,
                'csrf_token' => Session::getNewCSRFToken(),
            ]);
            break;

        // ── DELETE ───────────────────────────────────────
        case 'delete_task':
            Session::checkRight(Task::$rightname, DELETE);

            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => __('ID inválido.', 'newmanagement')]);
                exit;
            }

            $task->delete(['id' => $id], true); // true = purge (deleção permanente)
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida: ' . htmlspecialchars($action, ENT_QUOTES)]);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('Erro interno do servidor.', 'newmanagement')]);
}
