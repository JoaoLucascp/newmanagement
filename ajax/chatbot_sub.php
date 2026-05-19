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
// O token chega em $_POST['_glpi_csrf_token'] (enviado pelo nmPost() no FormData).
Session::checkCSRF($_POST);
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
            // Se vierem usuários em modo bulk, inseri-los agora
            if (!empty($_POST['chatbot_users']) && is_array($_POST['chatbot_users'])) {
                $users = $_POST['chatbot_users'];
                $names  = $users['user_name'] ?? [];
                $logins = $users['login']     ?? [];
                $pwds   = $users['password']  ?? [];
                $emails = $users['email']     ?? [];
                $types  = $users['user_type'] ?? [];
                $count = max(0, count($names));
                for ($i = 0; $i < $count; $i++) {
                    $uname = trim($names[$i] ?? '');
                    $ulogin = trim($logins[$i] ?? '');
                    if ($uname === '' && $ulogin === '') continue;
                    $DB->insert('glpi_plugin_newmanagement_chatbot_users', [
                        'chatbot_id'    => $newId,
                        'companies_id'  => $companies_id,
                        'user_name'     => $uname,
                        'login'         => $ulogin,
                        'password'      => nmEncrypt(trim($pwds[$i] ?? '')),
                        'email'         => trim($emails[$i] ?? ''),
                        'user_type'     => trim($types[$i] ?? 'usuario'),
                        'date_creation' => $now,
                        'date_mod'      => $now,
                    ]);
                }
            }
            // Se vierem comunicações em massa em bulk, inseri-las
            if (!empty($_POST['chatbot_mass_comm']) && is_array($_POST['chatbot_mass_comm'])) {
                $mc = $_POST['chatbot_mass_comm'];
                $names = $mc['system_name'] ?? [];
                $acts  = $mc['activation_date'] ?? [];
                $auths = $mc['authenticated_number'] ?? [];
                $homs  = $mc['homologation_type'] ?? [];
                $links = $mc['access_link'] ?? [];
                $logins = $mc['login'] ?? [];
                $pwds  = $mc['password'] ?? [];
                $count = max(0, count($names));
                for ($i = 0; $i < $count; $i++) {
                    $n = trim($names[$i] ?? '');
                    if ($n === '') continue;
                    $DB->insert('glpi_plugin_newmanagement_chatbot_mass_comm', [
                        'chatbot_id' => $newId,
                        'companies_id' => $companies_id,
                        'system_name' => $n,
                        'activation_date' => trim($acts[$i] ?? ''),
                        'authenticated_number' => trim($auths[$i] ?? ''),
                        'homologation_type' => trim($homs[$i] ?? ''),
                        'access_link' => trim($links[$i] ?? ''),
                        'login' => trim($logins[$i] ?? ''),
                        'password' => nmEncrypt(trim($pwds[$i] ?? '')),
                        'date_creation' => $now,
                        'date_mod' => $now,
                    ]);
                }
            }

            // Se vierem restrições WA em bulk, inseri-las
            if (!empty($_POST['chatbot_wa_restrictions']) && is_array($_POST['chatbot_wa_restrictions'])) {
                $wa = $_POST['chatbot_wa_restrictions'];
                $nums = $wa['whatsapp_number'] ?? [];
                $rdates = $wa['restriction_date'] ?? [];
                $rtimes = $wa['restriction_time'] ?? [];
                $ends = $wa['end_date'] ?? [];
                $count = max(0, count($nums));
                for ($i = 0; $i < $count; $i++) {
                    $num = trim($nums[$i] ?? '');
                    if ($num === '') continue;
                    $DB->insert('glpi_plugin_newmanagement_chatbot_wa_restrictions', [
                        'chatbot_id' => $newId,
                        'companies_id' => $companies_id,
                        'whatsapp_number' => $num,
                        'restriction_date' => trim($rdates[$i] ?? ''),
                        'restriction_time' => trim($rtimes[$i] ?? ''),
                        'end_date' => trim($ends[$i] ?? ''),
                        'date_creation' => $now,
                        'date_mod' => $now,
                    ]);
                }
            }

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

                // Bulk replace users: se vierem arrays, substituir os existentes
                if (!empty($_POST['chatbot_users']) && is_array($_POST['chatbot_users'])) {
                    // Remover usuários atuais para este chatbot e re-inserir os enviados
                    $DB->delete('glpi_plugin_newmanagement_chatbot_users', ['chatbot_id' => $id]);
                    $users = $_POST['chatbot_users'];
                    $names  = $users['user_name'] ?? [];
                    $logins = $users['login']     ?? [];
                    $pwds   = $users['password']  ?? [];
                    $emails = $users['email']     ?? [];
                    $types  = $users['user_type'] ?? [];
                    $count = max(0, count($names));
                    for ($i = 0; $i < $count; $i++) {
                        $uname = trim($names[$i] ?? '');
                        $ulogin = trim($logins[$i] ?? '');
                        if ($uname === '' && $ulogin === '') continue;
                        $DB->insert('glpi_plugin_newmanagement_chatbot_users', [
                            'chatbot_id'    => $id,
                            'companies_id'  => $companies_id,
                            'user_name'     => $uname,
                            'login'         => $ulogin,
                            'password'      => nmEncrypt(trim($pwds[$i] ?? '')),
                            'email'         => trim($emails[$i] ?? ''),
                            'user_type'     => trim($types[$i] ?? 'usuario'),
                            'date_creation' => $now,
                            'date_mod'      => $now,
                        ]);
                    }
                }

                    // Bulk replace mass comm
                    if (!empty($_POST['chatbot_mass_comm']) && is_array($_POST['chatbot_mass_comm'])) {
                        $DB->delete('glpi_plugin_newmanagement_chatbot_mass_comm', ['chatbot_id' => $id]);
                        $mc = $_POST['chatbot_mass_comm'];
                        $names = $mc['system_name'] ?? [];
                        $acts  = $mc['activation_date'] ?? [];
                        $auths = $mc['authenticated_number'] ?? [];
                        $homs  = $mc['homologation_type'] ?? [];
                        $links = $mc['access_link'] ?? [];
                        $logins = $mc['login'] ?? [];
                        $pwds  = $mc['password'] ?? [];
                        $count = max(0, count($names));
                        for ($i = 0; $i < $count; $i++) {
                            $n = trim($names[$i] ?? '');
                            if ($n === '') continue;
                            $DB->insert('glpi_plugin_newmanagement_chatbot_mass_comm', [
                                'chatbot_id' => $id,
                                'companies_id' => $companies_id,
                                'system_name' => $n,
                                'activation_date' => trim($acts[$i] ?? ''),
                                'authenticated_number' => trim($auths[$i] ?? ''),
                                'homologation_type' => trim($homs[$i] ?? ''),
                                'access_link' => trim($links[$i] ?? ''),
                                'login' => trim($logins[$i] ?? ''),
                                'password' => nmEncrypt(trim($pwds[$i] ?? '')),
                                'date_creation' => $now,
                                'date_mod' => $now,
                            ]);
                        }
                    }

                    // Bulk replace WA restrictions
                    if (!empty($_POST['chatbot_wa_restrictions']) && is_array($_POST['chatbot_wa_restrictions'])) {
                        $DB->delete('glpi_plugin_newmanagement_chatbot_wa_restrictions', ['chatbot_id' => $id]);
                        $wa = $_POST['chatbot_wa_restrictions'];
                        $nums = $wa['whatsapp_number'] ?? [];
                        $rdates = $wa['restriction_date'] ?? [];
                        $rtimes = $wa['restriction_time'] ?? [];
                        $ends = $wa['end_date'] ?? [];
                        $count = max(0, count($nums));
                        for ($i = 0; $i < $count; $i++) {
                            $num = trim($nums[$i] ?? '');
                            if ($num === '') continue;
                            $DB->insert('glpi_plugin_newmanagement_chatbot_wa_restrictions', [
                                'chatbot_id' => $id,
                                'companies_id' => $companies_id,
                                'whatsapp_number' => $num,
                                'restriction_date' => trim($rdates[$i] ?? ''),
                                'restriction_time' => trim($rtimes[$i] ?? ''),
                                'end_date' => trim($ends[$i] ?? ''),
                                'date_creation' => $now,
                                'date_mod' => $now,
                            ]);
                        }
                    }

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
