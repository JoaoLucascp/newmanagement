<?php

/**
 * Newmanagement - Handler AJAX/POST para sub-dados do IPBX
 * Ações: add/delete de ramais, dispositivos, rede e linha fixa
 * + add/update do registro principal IPBX
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Ipbx;
use GlpiPlugin\Newmanagement\Company;

Session::checkLoginUser();
// CSRF é validado automaticamente pelo middleware do GLPI 11 (Symfony)
// Session::checkCsrfToken() foi removido — não usar no GLPI 11
Session::checkRight(Ipbx::$rightname, READ);

$action       = $_POST['action']       ?? '';
$companies_id = (int)($_POST['companies_id'] ?? 0);
$redirect     = $_POST['redirect']     ?? '';

// Sanitiza redirect — aceita apenas URIs internas
if (!preg_match('#^/[^/]#', $redirect)) {
    $redirect = '/plugins/newmanagement/front/company.php';
}

if ($companies_id <= 0) {
    \Html::back();
    exit;
}

global $DB;
$now = date('Y-m-d H:i:s');

switch ($action) {

    // ------------------------------------------------------------------
    // IPBX principal
    // ------------------------------------------------------------------
    case 'add_ipbx':
        Session::checkRight(Ipbx::$rightname, CREATE);
        $ipbx = new Ipbx();
        $ipbx->add([
            'companies_id'   => $companies_id,
            'model'          => $_POST['model']          ?? '',
            'server_version' => $_POST['server_version'] ?? '',
            'ip_local'       => $_POST['ip_local']       ?? '',
            'ip_external'    => $_POST['ip_external']    ?? '',
            'web_port'       => $_POST['web_port']       ?? '',
            'web_password'   => $_POST['web_password']   ?? '',
            'ssh_port'       => $_POST['ssh_port']       ?? '',
            'ssh_password'   => $_POST['ssh_password']   ?? '',
            'comment'        => $_POST['comment']        ?? '',
        ]);
        break;

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
            'web_password'   => $_POST['web_password']   ?? '',
            'ssh_port'       => $_POST['ssh_port']       ?? '',
            'ssh_password'   => $_POST['ssh_password']   ?? '',
            'comment'        => $_POST['comment']        ?? '',
        ]);
        break;

    // ------------------------------------------------------------------
    // Ramais
    // ------------------------------------------------------------------
    case 'add_extension':
        Session::checkRight(Ipbx::$rightname, CREATE);
        $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
        if ($ipbx_id > 0) {
            $DB->insert('glpi_plugin_newmanagement_ipbx_extensions', [
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
        }
        break;

    case 'delete_extension':
        Session::checkRight(Ipbx::$rightname, DELETE);
        $DB->delete('glpi_plugin_newmanagement_ipbx_extensions', ['id' => (int)($_POST['id'] ?? 0)]);
        break;

    // ------------------------------------------------------------------
    // Dispositivos
    // ------------------------------------------------------------------
    case 'add_device':
        Session::checkRight(Ipbx::$rightname, CREATE);
        $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
        if ($ipbx_id > 0) {
            $DB->insert('glpi_plugin_newmanagement_ipbx_devices', [
                'ipbx_id'       => $ipbx_id,
                'companies_id'  => $companies_id,
                'device_type'   => $_POST['device_type'] ?? '',
                'ip_address'    => $_POST['ip_address']  ?? '',
                'password'      => $_POST['password']    ?? '',
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
        }
        break;

    case 'delete_device':
        Session::checkRight(Ipbx::$rightname, DELETE);
        $DB->delete('glpi_plugin_newmanagement_ipbx_devices', ['id' => (int)($_POST['id'] ?? 0)]);
        break;

    // ------------------------------------------------------------------
    // Rede
    // ------------------------------------------------------------------
    case 'add_network':
        Session::checkRight(Ipbx::$rightname, CREATE);
        $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
        if ($ipbx_id > 0) {
            $DB->insert('glpi_plugin_newmanagement_ipbx_network', [
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
        }
        break;

    case 'delete_network':
        Session::checkRight(Ipbx::$rightname, DELETE);
        $DB->delete('glpi_plugin_newmanagement_ipbx_network', ['id' => (int)($_POST['id'] ?? 0)]);
        break;

    // ------------------------------------------------------------------
    // Linha Fixa
    // ------------------------------------------------------------------
    case 'add_line':
        Session::checkRight(Ipbx::$rightname, CREATE);
        $ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
        if ($ipbx_id > 0) {
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
        }
        break;

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
        break;

    case 'delete_line':
        Session::checkRight(Ipbx::$rightname, DELETE);
        $DB->delete('glpi_plugin_newmanagement_ipbx_lines', ['id' => (int)($_POST['id'] ?? 0)]);
        break;
}

\Html::redirect($redirect);
