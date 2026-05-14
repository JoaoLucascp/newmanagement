<?php

/**
 * Newmanagement - Plugin GLPI
 * AJAX: Salvar Rede da Empresa (1:1 com IPBX)
 */

use GlpiPlugin\Newmanagement\IpbxNetwork;
use GlpiPlugin\Newmanagement\Ipbx;

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkCSRF($_POST);

$ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
$net_id  = (int)($_POST['net_id']  ?? 0);
$action  = $_POST['action'] ?? '';

$ipbx = new Ipbx();
if (!$ipbx->getFromDB($ipbx_id)) {
    Html::displayErrorAndDie('IPBX não encontrado.');
}

if ($action === 'save_network') {
    $ipbx->check($ipbx_id, UPDATE);

    $data = [
        'ipbx_id'       => $ipbx_id,
        'network_ip'    => trim($_POST['network_ip']    ?? ''),
        'netmask'       => trim($_POST['netmask']       ?? ''),
        'gateway'       => trim($_POST['gateway']       ?? ''),
        'dns_primary'   => trim($_POST['dns_primary']   ?? ''),
        'dns_secondary' => trim($_POST['dns_secondary'] ?? ''),
    ];

    $net = new IpbxNetwork();

    if ($net_id > 0) {
        $data['id'] = $net_id;
        $net->update($data);
    } else {
        $net->add($data);
    }
}

Html::back();
