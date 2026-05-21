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
    // [FIX] checkCSRF é o método correto no GLPI 11 para validar token POST
    Session::checkCSRF($_POST);
    Session::checkRight(IpbxCloud::$rightname, UPDATE);
    $ipbxcloud->update($_POST);
    Session::addMessageAfterRedirect(
        __('IPBX Cloud atualizado com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(IpbxCloud::$rightname, CREATE);
    $newid = $ipbxcloud->add($_POST);
    if ($newid) {
        Session::addMessageAfterRedirect(
            __('IPBX Cloud criado com sucesso.', 'newmanagement'),
            true,
            INFO
        );
        Html::redirect(IpbxCloud::getFormURL() . '?id=' . $newid);
    } else {
        Session::addMessageAfterRedirect(
            __('Erro ao criar IPBX Cloud. Verifique os dados e tente novamente.', 'newmanagement'),
            true,
            ERROR
        );
        Html::back();
    }
}

if (isset($_POST['delete'])) {
    Session::checkCSRF($_POST);
    Session::checkRight(IpbxCloud::$rightname, DELETE);
    $ipbxcloud->delete($_POST);
    Session::addMessageAfterRedirect(
        __('IPBX Cloud removido com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::redirect(IpbxCloud::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // [FIX] Exibe erro 404 nativo do GLPI se o registro não existir
    if (!$ipbxcloud->getFromDB($id)) {
        Html::displayNotFoundError();
    }
    Html::header(
        IpbxCloud::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\IpbxCloud'
    );
    $ipbxcloud->showForm($id);
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
