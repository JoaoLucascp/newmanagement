<?php

/**
 * Newmanagement - Plugin GLPI
 * Handler AJAX: operações CRUD da aba Chatbot
 */

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkCentralAccess();

if (!isset($_POST['_glpi_csrf_token'])) {
    die('CSRF token ausente');
}
if (!Session::validateCSRF($_POST)) {
    die('CSRF inválido');
}

$action       = $_POST['action']       ?? '';
$companies_id = (int)($_POST['companies_id'] ?? 0);
$redirect     = $_POST['redirect']     ?? '';
$id           = (int)($_POST['id']     ?? 0);

// ── Sanitizador simples ───────────────────────────────────────────────────────
$s = fn(string $key, string $default = '') => htmlspecialchars(trim($_POST[$key] ?? $default), ENT_QUOTES);
$i = fn(string $key, int $default = 0)    => (int)($_POST[$key] ?? $default);
$d = function(string $key): ?string {
    $val = trim($_POST[$key] ?? '');
    return ($val !== '' && $val !== '0000-00-00') ? $val : null;
};

switch ($action) {

    // ── Chatbot principal ─────────────────────────────────────────────────────
    case 'add_chatbot':
    case 'update_chatbot':
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
            'admin_password'          => $s('admin_password'),
            'superadmin_login'        => $s('superadmin_login'),
            'superadmin_password'     => $s('superadmin_password'),
            'manager_name'            => $s('manager_name'),
            'manager_contact'         => $s('manager_contact'),
            'manager_email'           => $s('manager_email'),
            'social_networks'         => $s('social_networks'),
            'comment'                 => $s('comment'),
            'date_mod'                => date('Y-m-d H:i:s'),
        ];
        if ($action === 'add_chatbot') {
            $data['date_creation'] = date('Y-m-d H:i:s');
            $data['is_deleted']    = 0;
            $DB->insert('glpi_plugin_newmanagement_chatbots', $data);
        } else {
            if ($id > 0) {
                $DB->update('glpi_plugin_newmanagement_chatbots', $data, ['id' => $id]);
            }
        }
        break;

    // ── Comunicação em Massa ──────────────────────────────────────────────────
    case 'add_mass_comm':
        $chatbot_id = $i('chatbot_id');
        if ($chatbot_id > 0) {
            $DB->insert('glpi_plugin_newmanagement_chatbot_mass_comm', [
                'chatbot_id'           => $chatbot_id,
                'companies_id'         => $companies_id,
                'system_name'          => $s('system_name'),
                'activation_date'      => $d('activation_date'),
                'authenticated_number' => $s('authenticated_number'),
                'homologation_type'    => $s('homologation_type'),
                'access_link'          => $s('access_link'),
                'login'                => $s('login'),
                'password'             => $s('password'),
                'manager'              => $s('manager'),
                'date_creation'        => date('Y-m-d H:i:s'),
                'date_mod'             => date('Y-m-d H:i:s'),
            ]);
        }
        break;

    case 'delete_mass_comm':
        if ($id > 0) {
            $DB->delete('glpi_plugin_newmanagement_chatbot_mass_comm', ['id' => $id]);
        }
        break;

    // ── Números Restritos ─────────────────────────────────────────────────────
    case 'add_wa_restriction':
        $chatbot_id = $i('chatbot_id');
        if ($chatbot_id > 0) {
            $DB->insert('glpi_plugin_newmanagement_chatbot_wa_restrictions', [
                'chatbot_id'       => $chatbot_id,
                'companies_id'     => $companies_id,
                'whatsapp_number'  => $s('whatsapp_number'),
                'restriction_date' => $d('restriction_date'),
                'restriction_time' => $s('restriction_time'),
                'date_creation'    => date('Y-m-d H:i:s'),
                'date_mod'         => date('Y-m-d H:i:s'),
            ]);
        }
        break;

    case 'delete_wa_restriction':
        if ($id > 0) {
            $DB->delete('glpi_plugin_newmanagement_chatbot_wa_restrictions', ['id' => $id]);
        }
        break;

    // ── Usuários do Chatbot ───────────────────────────────────────────────────
    case 'add_chatbot_user':
        $chatbot_id = $i('chatbot_id');
        if ($chatbot_id > 0) {
            $DB->insert('glpi_plugin_newmanagement_chatbot_users', [
                'chatbot_id'    => $chatbot_id,
                'companies_id'  => $companies_id,
                'user_name'     => $s('user_name'),
                'login'         => $s('login'),
                'password'      => $s('password'),
                'email'         => $s('email'),
                'user_type'     => $s('user_type'),
                'date_creation' => date('Y-m-d H:i:s'),
                'date_mod'      => date('Y-m-d H:i:s'),
            ]);
        }
        break;

    case 'delete_chatbot_user':
        if ($id > 0) {
            $DB->delete('glpi_plugin_newmanagement_chatbot_users', ['id' => $id]);
        }
        break;
}

if ($redirect) {
    Html::redirect($redirect);
} else {
    Html::back();
}
