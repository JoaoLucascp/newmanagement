<?php

/**
 * Newmanagement - Handler AJAX/POST para sub-dados do IPBX
 * Ações: add/delete de ramais, dispositivos, rede e linha fixa
 * + add/update do registro principal IPBX
 *
 * Responde SEMPRE com JSON { success: bool, error?: string, id?: int }
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Ipbx;

Session::checkLoginUser();
Session::checkRight(Ipbx::$rightname, READ);

// Garante que qualquer saída seja JSON
header('Content-Type: application/json; charset=utf-8');

// Helper: encerra com JSON
function nmJson(bool $ok, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

$action       = $_POST['action']       ?? '';
$companies_id = (int)($_POST['companies_id'] ?? 0);

if ($companies_id <= 0) {
    nmJson(false, ['error' => 'companies_id inválido']);
}

global $DB;
$now = date('Y-m-d H:i:s');

try {
    switch ($action) {

        // ------------------------------------------------------------------
        // IPBX principal
        // ------------------------------------------------------------------
        case 'add_ipbx':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx = new Ipbx();
            $newId = $ipbx->add([
                'companies_id'   => $companies_id,
                'model'          => $_POST['model']          ?? '',
                'server_version' => $_POST['server_version'] ?? '',
                'ip_local'       => $_POST['ip_local']       ?? '',
                'ip_external'    => $_POST['ip_external']    ?? '',
                'web_port'       => $_POST['web_port']       ?? '',
                'web_password'   => \Toolbox::sodiumEncrypt($_POST['web_password'] ?? ''),
                'ssh_port'       => $_POST['ssh_port']       ?? '',
                'ssh_password'   => \Toolbox::sodiumEncrypt($_POST['ssh_password'] ?? ''),
                'comment'        => $_POST['comment']        ?? '',
            ]);
            nmJson(true, ['id' => $newId]);

        case 'update_ipbx':
            Session::checkRight(Ipbx::$rightname, UPDATE);
            $ipbx = new Ipbx();
            $ipbx->update([
                'id'             => (int)($_POST['id'] ?? 0),
                'companies_id'   => $companies_id,
                'model'          => $_POST['model']          ?? '',
                'server_version' => $_POST['server_version'] ?? '',
                'ip_local'       => $_POST['ip_local']       ?? '',
                'ip_external'    => $_POST['ip_external']    ?? '',
                'web_port'       => $_POST['web_port']       ?? '',
                'web_password'   => \Toolbox::sodiumEncrypt($_POST['web_password'] ?? ''),
                'ssh_port'       => $_POST['ssh_port']       ?? '',
                'ssh_password'   => \Toolbox::sodiumEncrypt($_POST['ssh_password'] ?? ''),
                'comment'        => $_POST['comment']        ?? '',
            ]);
            nmJson(true);

        // ------------------------------------------------------------------
        // Ramais
        // ------------------------------------------------------------------
        case 'add_extension':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) nmJson(false, ['error' => 'ipbx_id inválido']);
            $newId = $DB->insert('glpi_plugin_newmanagement_ipbx_extensions', [
                'ipbx_id'       => $ipbx_id,
                'companies_id'  => $companies_id,
                'number'        => $_POST['number']       ?? '',
                'password'      => $_POST['password']     ?? '',
                'device_ip'     => $_POST['device_ip']    ?? '',
                'user_name'     => $_POST['user_name']    ?? '',
                'records_calls' => (int)($_POST['records_calls'] ?? 0),
                'department'    => $_POST['department']   ?? '',
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
            // Buscar a linha recém-inserida e gerar HTML
            $row = $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_extensions', 'WHERE' => ['id' => $newId]])->current();
            $csrf = \Session::getNewCSRFToken();
            $actionUrl = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
            $html = Ipbx::renderExtensionRow((int)$newId, $row, $companies_id, $csrf, $actionUrl);
            nmJson(true, ['id' => $newId, 'html' => $html]);

        case 'delete_extension':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete('glpi_plugin_newmanagement_ipbx_extensions', ['id' => (int)($_POST['id'] ?? 0)]);
            nmJson(true);

        // ------------------------------------------------------------------
        // Dispositivos
        // ------------------------------------------------------------------
        case 'add_device':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) nmJson(false, ['error' => 'ipbx_id inválido']);
            $newId = $DB->insert('glpi_plugin_newmanagement_ipbx_devices', [
                'ipbx_id'       => $ipbx_id,
                'companies_id'  => $companies_id,
                'device_type'   => $_POST['device_type'] ?? '',
                'ip_address'    => $_POST['ip_address']  ?? '',
                'password'      => $_POST['password']    ?? '',
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
            // Buscar a linha recém-inserida e gerar HTML
            $row = $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_devices', 'WHERE' => ['id' => $newId]])->current();
            $csrf = \Session::getNewCSRFToken();
            $actionUrl = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
            $html = Ipbx::renderDeviceRow((int)$newId, $row, $companies_id, $csrf, $actionUrl);
            nmJson(true, ['id' => $newId, 'html' => $html]);

        case 'delete_device':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete('glpi_plugin_newmanagement_ipbx_devices', ['id' => (int)($_POST['id'] ?? 0)]);
            nmJson(true);

        // ------------------------------------------------------------------
        // Rede
        // ------------------------------------------------------------------
        case 'add_network':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) nmJson(false, ['error' => 'ipbx_id inválido']);
            $newId = $DB->insert('glpi_plugin_newmanagement_ipbx_network', [
                'ipbx_id'       => $ipbx_id,
                'companies_id'  => $companies_id,
                'ip_network'    => $_POST['ip_network']    ?? '',
                'netmask'       => $_POST['netmask']       ?? '',
                'gateway'       => $_POST['gateway']       ?? '',
                'dns_primary'   => $_POST['dns_primary']   ?? '',
                'dns_secondary' => $_POST['dns_secondary'] ?? '',
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
            // Buscar a linha recém-inserida e gerar HTML
            $row = $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_network', 'WHERE' => ['id' => $newId]])->current();
            $csrf = \Session::getNewCSRFToken();
            $actionUrl = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
            $html = Ipbx::renderNetworkRow((int)$newId, $row, $companies_id, $csrf, $actionUrl);
            nmJson(true, ['id' => $newId, 'html' => $html]);

        case 'delete_network':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete('glpi_plugin_newmanagement_ipbx_network', ['id' => (int)($_POST['id'] ?? 0)]);
            nmJson(true);

        // ------------------------------------------------------------------
        // Linha Fixa
        // ------------------------------------------------------------------
        case 'add_line':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) nmJson(false, ['error' => 'ipbx_id inválido']);
            $toDate = static fn(string $v): ?string => $v !== '' ? $v : null;
            $DB->insert('glpi_plugin_newmanagement_ipbx_lines', [
                'ipbx_id'            => $ipbx_id,
                'companies_id'       => $companies_id,
                'pilot_number'       => $_POST['pilot_number']      ?? '',
                'line_type'          => $_POST['line_type']          ?? '',
                'operator'           => $_POST['operator']           ?? '',
                'channels'           => (int)($_POST['channels']     ?? 0),
                'ddr_count'          => (int)($_POST['ddr_count']    ?? 0),
                'proxy_ip'           => $_POST['proxy_ip']           ?? '',
                'proxy_port'         => $_POST['proxy_port']         ?? '',
                'audio_ip'           => $_POST['audio_ip']           ?? '',
                'portability_date'   => $toDate($_POST['portability_date']   ?? ''),
                'previous_operator'  => $_POST['previous_operator']  ?? '',
                'activation_date'    => $toDate($_POST['activation_date']    ?? ''),
                'expiration_date'    => $toDate($_POST['expiration_date']     ?? ''),
                'status'             => (int)($_POST['status']        ?? 1),
                'comment'            => $_POST['comment']             ?? '',
                'date_creation'      => $now,
                'date_mod'           => $now,
            ]);
            nmJson(true, ['id' => $DB->insertId()]);

        case 'update_line':
            Session::checkRight(Ipbx::$rightname, UPDATE);
            $toDate = static fn(string $v): ?string => $v !== '' ? $v : null;
            $DB->update(
                'glpi_plugin_newmanagement_ipbx_lines',
                [
                    'pilot_number'       => $_POST['pilot_number']      ?? '',
                    'line_type'          => $_POST['line_type']          ?? '',
                    'operator'           => $_POST['operator']           ?? '',
                    'channels'           => (int)($_POST['channels']     ?? 0),
                    'ddr_count'          => (int)($_POST['ddr_count']    ?? 0),
                    'proxy_ip'           => $_POST['proxy_ip']           ?? '',
                    'proxy_port'         => $_POST['proxy_port']         ?? '',
                    'audio_ip'           => $_POST['audio_ip']           ?? '',
                    'portability_date'   => $toDate($_POST['portability_date']   ?? ''),
                    'previous_operator'  => $_POST['previous_operator']  ?? '',
                    'activation_date'    => $toDate($_POST['activation_date']    ?? ''),
                    'expiration_date'    => $toDate($_POST['expiration_date']     ?? ''),
                    'status'             => (int)($_POST['status']        ?? 1),
                    'comment'            => $_POST['comment']             ?? '',
                    'date_mod'           => $now,
                ],
                ['id' => (int)($_POST['id'] ?? 0)]
            );
            nmJson(true);

        case 'delete_line':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete('glpi_plugin_newmanagement_ipbx_lines', ['id' => (int)($_POST['id'] ?? 0)]);
            nmJson(true);

        default:
            nmJson(false, ['error' => 'Ação desconhecida: ' . $action]);
    }
} catch (\Throwable $e) {
    nmJson(false, ['error' => $e->getMessage()]);
}
