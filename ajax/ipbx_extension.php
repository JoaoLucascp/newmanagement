<?php

/**
 * Newmanagement - Plugin GLPI
 * AJAX: Salvar / Excluir Ramal do IPBX
 */

use GlpiPlugin\Newmanagement\IpbxExtension;
use GlpiPlugin\Newmanagement\Ipbx;

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkCSRF($_POST);

$ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
$ext_id  = (int)($_POST['ext_id']  ?? 0);
$action  = $_POST['action'] ?? '';

// Verifica permissão no IPBX pai
$ipbx = new Ipbx();
if (!$ipbx->getFromDB($ipbx_id)) {
    Html::displayErrorAndDie('IPBX não encontrado.');
}

$ext = new IpbxExtension();

if ($action === 'save_extension') {
    $ipbx->check($ipbx_id, UPDATE);

    $data = [
        'ipbx_id'          => $ipbx_id,
        'extension_number' => trim($_POST['extension_number'] ?? ''),
        'extension_pass'   => trim($_POST['extension_pass']   ?? ''),
        'device_ip'        => trim($_POST['device_ip']        ?? ''),
        'user_name'        => trim($_POST['user_name']        ?? ''),
        'records_calls'    => (int)($_POST['records_calls']   ?? 0),
        'department'       => trim($_POST['department']       ?? ''),
    ];

    if ($ext_id > 0) {
        $data['id'] = $ext_id;
        $ext->update($data);
    } else {
        $ext->add($data);
    }

} elseif ($action === 'delete_extension' && $ext_id > 0) {
    $ipbx->check($ipbx_id, UPDATE);
    $ext->delete(['id' => $ext_id], true);
}

Html::back();
