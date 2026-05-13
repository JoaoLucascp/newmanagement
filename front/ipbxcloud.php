<?php

/**
 * Newmanagement - Controller: IpbxCloud
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\IpbxCloud;

Session::checkLoginUser();
Session::checkRight(IpbxCloud::$rightname, READ);

$ipbxcloud = new IpbxCloud();

// --- AÇÕES POST ---
if (isset($_POST['update'])) {
    Session::checkCsrfToken();
    Session::checkRight(IpbxCloud::$rightname, UPDATE);
    $ipbxcloud->update($_POST);
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(IpbxCloud::$rightname, CREATE);
    $newid = $ipbxcloud->add($_POST);
    Html::redirect(IpbxCloud::getFormURL() . '?id=' . $newid);
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(IpbxCloud::$rightname, DELETE);
    $ipbxcloud->delete($_POST);
    Html::redirect(IpbxCloud::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $ipbxcloud->getFromDB((int) $_GET['id']);
    Html::header(
        IpbxCloud::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\IpbxCloud'
    );
    $ipbxcloud->showForm((int) $_GET['id']);
    Html::footer();
} elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    Html::header(
        IpbxCloud::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\IpbxCloud'
    );
    $ipbxcloud->showForm(-1);
    Html::footer();
} else {
    Html::header(
        IpbxCloud::getTypeName(0),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\IpbxCloud'
    );
    Search::show(IpbxCloud::class);
    Html::footer();
}
