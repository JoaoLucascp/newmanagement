<?php

/**
 * Newmanagement - Controller: Ipbx
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Ipbx;

Session::checkLoginUser();
Session::checkRight(Ipbx::$rightname, READ);

$ipbx = new Ipbx();

// --- AÇÕES POST ---
if (isset($_POST['update'])) {
    Session::checkCsrfToken();
    Session::checkRight(Ipbx::$rightname, UPDATE);
    $ipbx->update($_POST);
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(Ipbx::$rightname, CREATE);
    $newid = $ipbx->add($_POST);
    Html::redirect(Ipbx::getFormURL() . '?id=' . $newid);
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(Ipbx::$rightname, DELETE);
    $ipbx->delete($_POST);
    Html::redirect(Ipbx::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $ipbx->getFromDB((int) $_GET['id']);
    Html::header(
        Ipbx::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Ipbx'
    );
    $ipbx->showForm((int) $_GET['id']);
    Html::footer();
} elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    Html::header(
        Ipbx::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Ipbx'
    );
    $ipbx->showForm(-1);
    Html::footer();
} else {
    Html::header(
        Ipbx::getTypeName(0),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Ipbx'
    );
    Search::show(Ipbx::class);
    Html::footer();
}
