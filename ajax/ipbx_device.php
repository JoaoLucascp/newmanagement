<?php

/**
 * Newmanagement - Plugin GLPI
 * AJAX: Salvar / Excluir Dispositivo do IPBX
 */

use GlpiPlugin\Newmanagement\IpbxDevice;
use GlpiPlugin\Newmanagement\Ipbx;

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkCSRF($_POST);

$ipbx_id   = (int)($_POST['ipbx_id']   ?? 0);
$device_id = (int)($_POST['device_id'] ?? 0);
$action    = $_POST['action'] ?? '';

$ipbx = new Ipbx();
if (!$ipbx->getFromDB($ipbx_id)) {
    Html::displayErrorAndDie('IPBX não encontrado.');
}

$dev = new IpbxDevice();

if ($action === 'save_device') {
    $ipbx->check($ipbx_id, UPDATE);

    $data = [
        'ipbx_id'         => $ipbx_id,
        'device_type'     => trim($_POST['device_type']     ?? ''),
        'device_ip'       => trim($_POST['device_ip']       ?? ''),
        'device_password' => trim($_POST['device_password'] ?? ''),
    ];

    if ($device_id > 0) {
        $data['id'] = $device_id;
        $dev->update($data);
    } else {
        $dev->add($data);
    }

} elseif ($action === 'delete_device' && $device_id > 0) {
    $ipbx->check($ipbx_id, UPDATE);
    $dev->delete(['id' => $device_id], true);
}

Html::back();
