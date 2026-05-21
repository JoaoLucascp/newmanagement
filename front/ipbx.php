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
    // [FIX] checkCSRF é o método correto no GLPI 11 para validar token POST
    Session::checkCSRF($_POST);
    Session::checkRight(Ipbx::$rightname, UPDATE);
    $ipbx->update($_POST);
    Session::addMessageAfterRedirect(
        __('IPBX atualizado com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(Ipbx::$rightname, CREATE);
    $newid = $ipbx->add($_POST);
    if ($newid) {
        Session::addMessageAfterRedirect(
            __('IPBX criado com sucesso.', 'newmanagement'),
            true,
            INFO
        );
        Html::redirect(Ipbx::getFormURL() . '?id=' . $newid);
    } else {
        Session::addMessageAfterRedirect(
            __('Erro ao criar IPBX. Verifique os dados e tente novamente.', 'newmanagement'),
            true,
            ERROR
        );
        Html::back();
    }
}

if (isset($_POST['delete'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(Ipbx::$rightname, DELETE);
    $ipbx->delete($_POST);
    Session::addMessageAfterRedirect(
        __('IPBX removido com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::redirect(Ipbx::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // [FIX] Exibe erro 404 nativo do GLPI se o registro não existir
    if (!$ipbx->getFromDB($id)) {
        Html::displayNotFoundError();
    }
    Html::header(
        Ipbx::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Ipbx'
    );
    $ipbx->showForm($id);
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
