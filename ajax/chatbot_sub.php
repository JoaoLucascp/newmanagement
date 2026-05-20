<?php

/**
 * Newmanagement - Plugin GLPI
 * Handler AJAX: operações CRUD da aba Chatbot
 * Responde SEMPRE com JSON: { success: bool, error?: string, id?: int }
 */

include('../../../inc/includes.php');

Session::checkLoginUser();
// GLPI 11 — CheckCsrfListener do Symfony rejeita o request com 403 se
// Session::checkCSRF() não for chamado explicitamente em endpoints ajax/*.php.
Session::checkCSRF($_POST);
Session::checkRight('plugin_newmanagement_chatbot', READ);

header('Content-Type: application/json; charset=utf-8');

function nmJson(bool $ok, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

function nmEncrypt(string $value): string {
    if ($value === '') return '';
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

$s = fn(string $key, string $default = '') => trim($_POST[$key] ?? $default);
$n = fn(string $key, int $default = 0)     => (int)($_POST[$key] ?? $default);
$d = function(string $key): ?string {
    $val = trim($_POST[$key] ?? '');
    return ($val !== '' && $val !== '0000-00-00') ? $val : null;
};

function nmBulkUsers(int $chatbotId, int $companiesId, array $users, string $now): void {
    global $DB;
    $names  = $users['user_name'] ?? [];
    $logins = $users['login']     ?? [];
    $pwds   = $users['password']  ?? [];
    $emails = $users['email']     ?? [];
    $types  = $users['user_type'] ?? [];
    $count  = count($names);
    for ($idx = 0; $idx < $count; $idx++) {
        $uname  = trim($names[$idx]  ?? '');
        $ulogin = trim($logins[$idx] ?? '');
        if ($uname === '' && $ulogin === '') continue;
        $DB->insert('glpi_plugin_newmanagement_chatbot_users', [
            'chatbot_id'    => $chatbotId,
            'companies_id'  => $companiesId,
            'user_name'     => $uname,
            'login'         => $ulogin,
            'password'      => nmEncrypt(trim($pwds[$idx]  ?? '')),
            'email'         => trim($emails[$idx] ?? ''),
            'user_type'     => trim($types[$idx]  ?? 'usuario'),
            'date_creation' => $now,
            'date_mod'      => $now,
        ]);
    }
}

function nmBulkMassComm(int $chatbotId, int $companiesId, array $mc, string $now): void {
    global $DB;
    $names  = $mc['system_name']          ?? [];
    $acts   = $mc['activation_date']      ?? [];
    $auths  = $mc['authenticated_number'] ?? [];
    $homs   = $mc['homologation_type']    ?? [];
    $links  = $mc['access_link']          ?? [];
    $logins = $mc['login']                ?? [];
    $pwds   = $mc['password']             ?? [];
    $mgrs   = $mc['manager']              ?? [];
    $count  = count($names);
    for ($idx = 0; $idx < $count; $idx++) {
        $n = trim($names[$idx] ?? '');
        if ($n === '') continue;
        $DB->insert('glpi_plugin_newmanagement_chatbot_mass_comm', [
            'chatbot_id'           => $chatbotId,
            'companies_id'         => $companiesId,
            'system_name'          => $n,
            'activation_date'      => trim($acts[$idx]  ?? '') ?: null,
            'authenticated_number' => trim($auths[$idx] ?? ''),
            'homologation_type'    => trim($homs[$idx]  ?? ''),
            'access_link'          => trim($links[$idx] ?? ''),
            'login'                => trim($logins[$idx] ?? ''),
            'password'             => nmEncrypt(trim($pwds[$idx] ?? '')),
            'manager'              => trim($mgrs[$idx]  ?? ''),
            'date_creation'        => $now,
            'date_mod'             => $now,
        ]);
    }
}

function nmBulkWaRestrictions(int $chatbotId, int $companiesId, array $wa, string $now): void {
    global $DB;
    $nums   = $wa['whatsapp_number']  ?? [];
    $rdates = $wa['restriction_date'] ?? [];
    $rtimes = $wa['restriction_time'] ?? [];
    $ends   = $wa['end_date']         ?? [];
    $count  = count($nums);
    for ($idx = 0; $idx < $count; $idx++) {
        $num = trim($nums[$idx] ?? '');
        if ($num === '') continue;
        $DB->insert('glpi_plugin_newmanagement_chatbot_wa_restrictions', [
            'chatbot_id'       => $chatbotId,
            'companies_id'     => $companiesId,
            'whatsapp_number'  => $num,
            'restriction_date' => trim($rdates[$idx] ?? '') ?: null,
            'restriction_time' => trim($rtimes[$idx] ?? ''),
            'end_date'         => trim($ends[$idx]   ?? '') ?: null,
            'date_creation'    => $now,
            'date_mod'         => $now,
        ]);
    }
}

try {
    switch ($action) {

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
                'users_count'             => $n('users_count'),
                'supervisors_count'       => $n('supervisors_count'),
                'admins_count'            => $n('admins_count'),
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
            if (!empty($_POST['chatbot_users']) && is_array($_POST['chatbot_users']))
                nmBulkUsers($newId, $companies_id, $_POST['chatbot_users'], $now);
            if (!empty($_POST['chatbot_mass_comm']) && is_array($_POST['chatbot_mass_comm']))
                nmBulkMassComm($newId, $companies_id, $_POST['chatbot_mass_comm'], $now);
            if (!empty($_POST['chatbot_wa_restrictions']) && is_array($_POST['chatbot_wa_restrictions']))
                nmBulkWaRestrictions($newId, $companies_id, $_POST['chatbot_wa_restrictions'], $now);
            nmJson(true, ['id' => $newId]);

        case 'update_chatbot':
            Session::checkRight('plugin_newmanagement_chatbot', UPDATE);
            if ($id <= 0) nmJson(false, ['error' => 'ID inválido']);
            $data = [
                'companies_id'            => $companies_id,
                'model'                   => $s('model'),
                'chatbot_registration_id' => $s('chatbot_registration_id'),
                'activation_date'         => $d('activation_date'),
                'whatsapp_number'         => $s('whatsapp_number'),
                'access_link'             => $s('access_link'),
                'plan'                    => $s('plan'),
                'users_count'             => $n('users_count'),
                'supervisors_count'       => $n('supervisors_count'),
                'admins_count'            => $n('admins_count'),
                'admin_login'             => $s('admin_login'),
                'superadmin_login'        => $s('superadmin_login'),
                'manager_name'            => $s('manager_name'),
                'manager_contact'         => $s('manager_contact'),
                'manager_email'           => $s('manager_email'),
                'social_networks'         => $s('social_networks'),
                'comment'                 => $s('comment'),
                'date_mod'                => $now,
            ];
            if ($s('admin_password') !== '')
                $data['admin_password']      = nmEncrypt($s('admin_password'));
            if ($s('superadmin_password') !== '')
                $data['superadmin_password'] = nmEncrypt($s('superadmin_password'));
            $DB->update('glpi_plugin_newmanagement_chatbots', $data, ['id' => $id]);
            if (!empty($_POST['chatbot_users']) && is_array($_POST['chatbot_users'])) {
                $DB->delete('glpi_plugin_newmanagement_chatbot_users', ['chatbot_id' => $id]);
                nmBulkUsers($id, $companies_id, $_POST['chatbot_users'], $now);
            }
            if (!empty($_POST['chatbot_mass_comm']) && is_array($_POST['chatbot_mass_comm'])) {
                $DB->delete('glpi_plugin_newmanagement_chatbot_mass_comm', ['chatbot_id' => $id]);
                nmBulkMassComm($id, $companies_id, $_POST['chatbot_mass_comm'], $now);
            }
            if (!empty($_POST['chatbot_wa_restrictions']) && is_array($_POST['chatbot_wa_restrictions'])) {
                $DB->delete('glpi_plugin_newmanagement_chatbot_wa_restrictions', ['chatbot_id' => $id]);
                nmBulkWaRestrictions($id, $companies_id, $_POST['chatbot_wa_restrictions'], $now);
            }
            nmJson(true);

        case 'add_mass_comm':
            Session::checkRight('plugin_newmanagement_chatbot', CREATE);
            $chatbot_id = $n('chatbot_id');
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

        case 'add_wa_restriction':
            Session::checkRight('plugin_newmanagement_chatbot', CREATE);
            $chatbot_id = $n('chatbot_id');
            if ($chatbot_id <= 0) nmJson(false, ['error' => 'chatbot_id inválido']);
            $DB->insert('glpi_plugin_newmanagement_chatbot_wa_restrictions', [
                'chatbot_id'       => $chatbot_id,
                'companies_id'     => $companies_id,
                'whatsapp_number'  => $s('whatsapp_number'),
                'restriction_date' => $d('restriction_date'),
                'restriction_time' => $s('restriction_time'),
                'end_date'         => $d('end_date'),
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

        case 'add_chatbot_user':
            Session::checkRight('plugin_newmanagement_chatbot', CREATE);
            $chatbot_id = $n('chatbot_id');
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

        default:
            nmJson(false, ['error' => 'Ação desconhecida: ' . $action]);
    }
} catch (\Throwable $e) {
    nmJson(false, ['error' => $e->getMessage()]);
}
