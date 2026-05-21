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
    // [FIX] checkCSRF é o método correto no GLPI 11 para validar token POST
    Session::checkCSRF($_POST);
    Session::checkRight(FixedLine::$rightname, UPDATE);
    $fixedline->update($_POST);
    Session::addMessageAfterRedirect(
        __('Linha Fixa atualizada com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(FixedLine::$rightname, CREATE);
    $newid = $fixedline->add($_POST);
    if ($newid) {
        Session::addMessageAfterRedirect(
            __('Linha Fixa criada com sucesso.', 'newmanagement'),
            true,
            INFO
        );
        Html::redirect(FixedLine::getFormURL() . '?id=' . $newid);
    } else {
        Session::addMessageAfterRedirect(
            __('Erro ao criar Linha Fixa. Verifique os dados e tente novamente.', 'newmanagement'),
            true,
            ERROR
        );
        Html::back();
    }
}

if (isset($_POST['delete'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(FixedLine::$rightname, DELETE);
    $fixedline->delete($_POST);
    Session::addMessageAfterRedirect(
        __('Linha Fixa removida com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::redirect(FixedLine::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // [FIX] Exibe erro 404 nativo do GLPI se o registro não existir
    if (!$fixedline->getFromDB($id)) {
        Html::displayNotFoundError();
    }
    Html::header(
        FixedLine::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\FixedLine'
    );
    $fixedline->showForm($id);
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
