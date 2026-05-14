<?php

/**
 * Newmanagement - Plugin GLPI
 * AJAX: Salvar / Excluir Linha Fixa do IPBX
 */

use GlpiPlugin\Newmanagement\IpbxLine;
use GlpiPlugin\Newmanagement\Ipbx;

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkCSRF($_POST);

$ipbx_id = (int)($_POST['ipbx_id'] ?? 0);
$line_id = (int)($_POST['line_id'] ?? 0);
$action  = $_POST['action'] ?? '';

$ipbx = new Ipbx();
if (!$ipbx->getFromDB($ipbx_id)) {
    Html::displayErrorAndDie('IPBX não encontrado.');
}

$line = new IpbxLine();

if ($action === 'save_line') {
    $ipbx->check($ipbx_id, UPDATE);

    $toDate = function ($val) {
        $v = trim($val ?? '');
        return $v !== '' ? $v : null;
    };

    $data = [
        'ipbx_id'           => $ipbx_id,
        'pilot_number'      => trim($_POST['pilot_number']      ?? ''),
        'line_type'         => trim($_POST['line_type']         ?? ''),
        'operator'          => trim($_POST['operator']          ?? ''),
        'channels_count'    => (int)($_POST['channels_count']   ?? 0),
        'ddr_count'         => (int)($_POST['ddr_count']        ?? 0),
        'proxy_ip'          => trim($_POST['proxy_ip']          ?? ''),
        'proxy_port'        => trim($_POST['proxy_port']        ?? ''),
        'audio_ip'          => trim($_POST['audio_ip']          ?? ''),
        'portability_date'  => $toDate($_POST['portability_date']  ?? ''),
        'previous_operator' => trim($_POST['previous_operator'] ?? ''),
        'activation_date'   => $toDate($_POST['activation_date']  ?? ''),
        'expiration_date'   => $toDate($_POST['expiration_date']  ?? ''),
        'line_status'       => (int)($_POST['line_status']      ?? 0),
        'comment'           => trim($_POST['comment']           ?? ''),
    ];

    if ($line_id > 0) {
        $data['id'] = $line_id;
        $line->update($data);
    } else {
        $line->add($data);
    }

} elseif ($action === 'delete_line' && $line_id > 0) {
    $ipbx->check($ipbx_id, UPDATE);
    $line->delete(['id' => $line_id], true);
}

Html::back();
