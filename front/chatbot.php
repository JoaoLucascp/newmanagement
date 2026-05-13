<?php

/**
 * Newmanagement - Controller: Chatbot
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Chatbot;

Session::checkLoginUser();
Session::checkRight(Chatbot::$rightname, READ);

$chatbot = new Chatbot();

// --- AÇÕES POST ---
if (isset($_POST['update'])) {
    Session::checkCsrfToken();
    Session::checkRight(Chatbot::$rightname, UPDATE);
    $chatbot->update($_POST);
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(Chatbot::$rightname, CREATE);
    $newid = $chatbot->add($_POST);
    Html::redirect(Chatbot::getFormURL() . '?id=' . $newid);
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(Chatbot::$rightname, DELETE);
    $chatbot->delete($_POST);
    Html::redirect(Chatbot::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $chatbot->getFromDB((int) $_GET['id']);
    Html::header(
        Chatbot::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Chatbot'
    );
    $chatbot->showForm((int) $_GET['id']);
    Html::footer();
} elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    Html::header(
        Chatbot::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Chatbot'
    );
    $chatbot->showForm(-1);
    Html::footer();
} else {
    Html::header(
        Chatbot::getTypeName(0),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Chatbot'
    );
    Search::show(Chatbot::class);
    Html::footer();
}
