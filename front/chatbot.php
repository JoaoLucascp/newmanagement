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
    // [FIX] checkCSRF é o método correto no GLPI 11 para validar token POST
    Session::checkCSRF($_POST);
    Session::checkRight(Chatbot::$rightname, UPDATE);
    $chatbot->update($_POST);
    Session::addMessageAfterRedirect(
        __('Chatbot atualizado com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(Chatbot::$rightname, CREATE);
    $newid = $chatbot->add($_POST);
    if ($newid) {
        Session::addMessageAfterRedirect(
            __('Chatbot criado com sucesso.', 'newmanagement'),
            true,
            INFO
        );
        Html::redirect(Chatbot::getFormURL() . '?id=' . $newid);
    } else {
        Session::addMessageAfterRedirect(
            __('Erro ao criar Chatbot. Verifique os dados e tente novamente.', 'newmanagement'),
            true,
            ERROR
        );
        Html::back();
    }
}

if (isset($_POST['delete'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(Chatbot::$rightname, DELETE);
    $chatbot->delete($_POST);
    Session::addMessageAfterRedirect(
        __('Chatbot removido com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::redirect(Chatbot::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // [FIX] Exibe erro 404 nativo do GLPI se o registro não existir
    if (!$chatbot->getFromDB($id)) {
        Html::displayNotFoundError();
    }
    Html::header(
        Chatbot::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Chatbot'
    );
    $chatbot->showForm($id);
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
