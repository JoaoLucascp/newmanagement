<?php

/**
 * Newmanagement - Plugin GLPI
 * Controller: Servidor IPBX
 */

use GlpiPlugin\Newmanagement\Ipbx;

include('../../../inc/includes.php');

Session::checkLoginUser();

if (!Ipbx::canView()) {
    Html::displayRightError();
}

$ipbx = new Ipbx();

if (isset($_POST['add'])) {
    $ipbx->check(-1, CREATE);
    if ($newID = $ipbx->add($_POST)) {
        Html::redirect(Ipbx::getFormURLWithID($newID));
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    $ipbx->check($_POST['id'], UPDATE);
    $ipbx->update($_POST);
    Html::back();
} elseif (isset($_POST['delete'])) {
    $ipbx->check($_POST['id'], DELETE);
    $ipbx->delete($_POST);
    Html::redirect(Ipbx::getSearchURL());
} elseif (isset($_POST['restore'])) {
    $ipbx->check($_POST['id'], DELETE);
    $ipbx->restore($_POST);
    Html::back();
} elseif (isset($_POST['purge'])) {
    $ipbx->check($_POST['id'], PURGE);
    $ipbx->delete($_POST, true);
    Html::redirect(Ipbx::getSearchURL());
} else {
    $id = (int)($_GET['id'] ?? -1);
    Html::header(
        Ipbx::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'plugins',
        'newmanagement',
        'ipbx'
    );
    $ipbx->display(['id' => $id]);
    Html::footer();
}
