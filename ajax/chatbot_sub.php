<?php

/**
 * Newmanagement - Plugin GLPI
 * Handler AJAX: operações CRUD da aba Chatbot
 * Responde SEMPRE com JSON: { success: bool, error?: string, id?: int }
 */

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('plugin_newmanagement_chatbot', READ);

// Garante que qualquer saída seja JSON
header('Content-Type: application/json; charset=utf-8');

// Helper: encerra com JSON
function nmJson(bool $ok, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

/**
 * Criptografa uma senha usando a API oficial do GLPI (Toolbox::encrypt).
 * Compatível com GLPI 10 e 11.
 * Toolbox::sodiumEncrypt() nunca existiu como método público no GLPI.
 */
function nmEncrypt(string $value): string {
    if ($value === '') {
        return '';
    }
    return \Toolbox::encrypt($value);
}

$action       = $_POST['action']       ?? '';
$companies_id = (int)($_POST['companies_id'] ?? 0);
$id           = (int)($_POST['id']     ?? 0);

if ($companies_id <= 0) {
    nmJson(false, ['error' => 'companies_id inválido']);
}

global $DB;
$now = date('Y-m-d H:i:s');

// ── Sanitizadores (sem htmlspecialchars — DB já faz escape) ─────────────────────────────
$s = fn(string $key, string $default = '') => trim($_POST[$key] ?? $default);
$i = fn(string $key, int $default = 0)    => (int)($_POST[$key] ?? $default);
$d = function(string $key): ?string {
    $val = trim($_POST[$key] ?? '');
    return ($val !== '' && $val !== '0000-00-00') ? $val : null;
};

try {
    switch ($action) {

        // ── Chatbot principal ────────────────────────────────────────────────────
        case 'add_chatbot':
            Session::checkRight('plugin_newmanagement_chatbot', CREATE);
            $data = [
                'companies_id'            => $companies_id,
                'model'                   => $s('model'),
                'chatbot_registration_id' => $s('chatbot_registration_id'),
                'activation_date'         => $d('activation_date'),
                'whatsapp_number'         => $s('whatsapp_number'),
                'access_link'             => $s('access_link'),
                'plan'                    => $s('plan'),
                'users_count'             => $i('users_count'),
                'supervisors_count'       => $i('supervisors_count'),
                'admins_count'            => $i('admins_count'),
                'admin_login'             => $s('admin_login'),
                'admin_password'          => nmEncrypt($s('admin_password')),
                'superadmin_login'        => $s('superadmin_login'),
                'superadmin_password'     => nmEncrypt($s('superadmin_password')),
                'manager_name'            => $s('manager_name'),
                'manager_contact'         => $s('manager_contact'),
                'manager_email'           => $s('manager_email'),
                'social_networks'         => $s('social_networks'),
                'comment'                 => $s('comment'),
                'date_creation'           => $now,
                'date_mod'                => $now,
                'is_deleted'              => 0,
            ];
            $DB->insert('glpi_plugin_newmanagement_chatbots', $data);
            $newId = $DB->insertId();
            if (!$newId) nmJson(false, ['error' => 'Falha ao inserir chatbot no banco']);
            nmJson(true, ['id' => $newId]);

        case 'update_chatbot':
            Session::checkRight('plugin_newmanagement_chatbot', UPDATE);
            $data = [
                'companies_id'            => $companies_id,
                'model'                   => $s('model'),
                'chatbot_registration_id' => $s('chatbot_registration_id'),
                'activation_date'         => $d('activation_date'),
                'whatsapp_number'         => $s('whatsapp_number'),
                'access_link'             => $s('access_link'),
                'plan'                    => $s('plan'),
                'users_count'             => $i('users_count'),
                'supervisors_count'       => $i('supervisors_count'),
                'admins_count'            => $i('admins_count'),
                'admin_login'             => $s('admin_login'),
                'admin_password'          => nmEncrypt($s('admin_password')),
                'superadmin_login'        => $s('superadmin_login'),
                'superadmin_password'     => nmEncrypt($s('superadmin_password')),
                'manager_name'            => $s('manager_name'),
                'manager_contact'         => $s('manager_contact'),
                'manager_email'           => $s('manager_email'),
                'social_networks'         => $s('social_networks'),
                'comment'                 => $s('comment'),
                'date_mod'                => $now,
            ];
            if ($id > 0) {
                $DB->update('glpi_plugin_newmanagement_chatbots', $data, ['id' => $id]);
                nmJson(true);
            }
            nmJson(false, ['error' => 'ID inválido']);
            break;

        // ── Comunicação em Massa ────────────────────────────────────────────────────
        case 'add_mass_comm':
            Session::checkRight('plugin_newmanagement_chatbot', CREATE);
            $chatbot_id = $i('chatbot_id');
            if ($chatbot_id <= 0) nmJson(false, ['error' => 'chatbot_id inválido']);
            $DB->insert('glpi_plugin_newmanagement_chatbot_mass_comm', [
                'chatbot_id'           => $chatbot_id,
                'companies_id'         => $companies_id,
                'system_name'          => $s('system_name'),
                'activation_date'      => $d('activation_date'),
                'authenticated_number' => $s('authenticated_number'),
                'homologation_type'    => $s('homologation_type'),
                'access_link'          => $s('access_link'),
                'login'                => $s('login'),
                'password'             => nmEncrypt($s('password')),
                'manager'              => $s('manager'),
                'date_creation'        => $now,
                'date_mod'             => $now,
            ]);
            $newId = $DB->insertId();
            if (!$newId) nmJson(false, ['error' => 'Falha ao inserir comunicação em massa']);
            nmJson(true, ['id' => $newId]);

        case 'delete_mass_comm':
            Session::checkRight('plugin_newmanagement_chatbot', DELETE);
            if ($id <= 0) nmJson(false, ['error' => 'ID inválido']);
            $DB->delete('glpi_plugin_newmanagement_chatbot_mass_comm', ['id' => $id]);
            nmJson(true);
            break;

        // ── Números Restritos ──────────────────────────────────────────────────────────
        case 'add_wa_restriction':
            Session::checkRight('plugin_newmanagement_chatbot', CREATE);
            $chatbot_id = $i('chatbot_id');
            if ($chatbot_id <= 0) nmJson(false, ['error' => 'chatbot_id inválido']);
            $DB->insert('glpi_plugin_newmanagement_chatbot_wa_restrictions', [
                'chatbot_id'       => $chatbot_id,
                'companies_id'     => $companies_id,
                'whatsapp_number'  => $s('whatsapp_number'),
                'restriction_date' => $d('restriction_date'),
                'restriction_time' => $s('restriction_time'),
                'date_creation'    => $now,
                'date_mod'         => $now,
            ]);
            $newId = $DB->insertId();
            if (!$newId) nmJson(false, ['error' => 'Falha ao inserir restrição WA']);
            nmJson(true, ['id' => $newId]);

        case 'delete_wa_restriction':
            Session::checkRight('plugin_newmanagement_chatbot', DELETE);
            if ($id <= 0) nmJson(false, ['error' => 'ID inválido']);
            $DB->delete('glpi_plugin_newmanagement_chatbot_wa_restrictions', ['id' => $id]);
            nmJson(true);
            break;

        // ── Usuários do Chatbot ──────────────────────────────────────────────────────────
        case 'add_chatbot_user':
            Session::checkRight('plugin_newmanagement_chatbot', CREATE);
            $chatbot_id = $i('chatbot_id');
            if ($chatbot_id <= 0) nmJson(false, ['error' => 'chatbot_id inválido']);
            $DB->insert('glpi_plugin_newmanagement_chatbot_users', [
                'chatbot_id'    => $chatbot_id,
                'companies_id'  => $companies_id,
                'user_name'     => $s('user_name'),
                'login'         => $s('login'),
                'password'      => nmEncrypt($s('password')),
                'email'         => $s('email'),
                'user_type'     => $s('user_type'),
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
            $newId = $DB->insertId();
            if (!$newId) nmJson(false, ['error' => 'Falha ao inserir usuário do chatbot']);
            nmJson(true, ['id' => $newId]);

        case 'delete_chatbot_user':
            Session::checkRight('plugin_newmanagement_chatbot', DELETE);
            if ($id <= 0) nmJson(false, ['error' => 'ID inválido']);
            $DB->delete('glpi_plugin_newmanagement_chatbot_users', ['id' => $id]);
            nmJson(true);
            break;

        default:
            nmJson(false, ['error' => 'Ação desconhecida: ' . $action]);
    }
} catch (\Throwable $e) {
    nmJson(false, ['error' => $e->getMessage()]);
}
