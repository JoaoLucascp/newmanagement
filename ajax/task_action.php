<?php

/**
 * Newmanagement - AJAX: Ações de Tarefa
 * Ações: add_task | update_task | delete_task
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Task;

header('Content-Type: application/json; charset=utf-8');

Session::checkLoginUser();

// fix(A4/SE-02): verificação READ global — todos os endpoints IPBX/Chatbot fazem o mesmo
Session::checkRight(Task::$rightname, READ);

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

function nmTaskBelongsToCompany(int $task_id, int $companies_id): bool
{
    global $DB;

    if ($task_id <= 0 || $companies_id <= 0) {
        return false;
    }

    return $DB->request([
        'FROM'  => Task::getTable(),
        'WHERE' => [
            'id'           => $task_id,
            'companies_id' => $companies_id,
            'is_deleted'   => 0,
        ],
        'LIMIT' => 1,
    ])->count() > 0;
}

try {
    switch ($action) {

        // ── ADD ────────────────────────────────────────────────
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

            if (empty($data['companies_id'])) {
                echo json_encode(['success' => false, 'message' => __('Empresa obrigatoria.', 'newmanagement')]);
                exit;
            }

            $newid = $task->add($data);
            if ($newid) {
                echo json_encode([
                    'success' => true,
                    'id'      => $newid,
                    // fix(JS-01): campo 'csrf' alinhado com nmRefreshCsrfToken no JS
                    'csrf'    => Session::getNewCSRFToken(),
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => __('Erro ao criar tarefa.', 'newmanagement')]);
            }
            break;

        // ── UPDATE ───────────────────────────────────────────────
        case 'update_task':
            Session::checkRight(Task::$rightname, UPDATE);

            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => __('ID inválido.', 'newmanagement')]);
                exit;
            }

            $companies_id = (int) ($input['companies_id'] ?? 0);
            if (!nmTaskBelongsToCompany($id, $companies_id)) {
                echo json_encode(['success' => false, 'message' => __('Tarefa nao encontrada para esta empresa.', 'newmanagement')]);
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
                'success' => true,
                // fix(JS-01): campo 'csrf' alinhado com nmRefreshCsrfToken no JS
                'csrf'    => Session::getNewCSRFToken(),
            ]);
            break;

        // ── DELETE ───────────────────────────────────────────────
        case 'delete_task':
            Session::checkRight(Task::$rightname, DELETE);

            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => __('ID inválido.', 'newmanagement')]);
                exit;
            }

            $companies_id = (int) ($input['companies_id'] ?? 0);
            if (!nmTaskBelongsToCompany($id, $companies_id)) {
                echo json_encode(['success' => false, 'message' => __('Tarefa nao encontrada para esta empresa.', 'newmanagement')]);
                exit;
            }

            $task->delete(['id' => $id]);
            // fix(SE-02): retorna novo token CSRF após delete para manter sessão válida
            echo json_encode([
                'success' => true,
                'csrf'    => Session::getNewCSRFToken(),
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida: ' . htmlspecialchars($action, ENT_QUOTES)]);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('Erro interno do servidor.', 'newmanagement')]);
}
