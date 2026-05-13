<?php

/**
 * Newmanagement - Controller: FixedLine
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\FixedLine;

Session::checkLoginUser();
Session::checkRight(FixedLine::$rightname, READ);

$fixedline = new FixedLine();

// --- AÇÕES POST ---
if (isset($_POST['update'])) {
    Session::checkCsrfToken();
    Session::checkRight(FixedLine::$rightname, UPDATE);
    $fixedline->update($_POST);
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(FixedLine::$rightname, CREATE);
    $newid = $fixedline->add($_POST);
    Html::redirect(FixedLine::getFormURL() . '?id=' . $newid);
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(FixedLine::$rightname, DELETE);
    $fixedline->delete($_POST);
    Html::redirect(FixedLine::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $fixedline->getFromDB((int) $_GET['id']);
    Html::header(
        FixedLine::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\FixedLine'
    );
    $fixedline->showForm((int) $_GET['id']);
    Html::footer();
} elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    Html::header(
        FixedLine::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\FixedLine'
    );
    $fixedline->showForm(-1);
    Html::footer();
} else {
    Html::header(
        FixedLine::getTypeName(0),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\FixedLine'
    );
    Search::show(FixedLine::class);
    Html::footer();
}
