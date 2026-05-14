<?php

/**
 * Newmanagement - Controller: Company
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Company;

Session::checkLoginUser();
Session::checkRight(Company::$rightname, READ);

$company = new Company();

// --- AÇÕES POST ---
if (isset($_POST['update'])) {
    Session::checkCsrfToken();
    Session::checkRight(Company::$rightname, UPDATE);
    $company->update($_POST);
    // Feedback de sucesso após atualização
    Session::addMessageAfterRedirect(
        __('Empresa atualizada com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(Company::$rightname, CREATE);
    $newid = $company->add($_POST);
    if ($newid) {
        // Feedback de sucesso após criação
        Session::addMessageAfterRedirect(
            __('Empresa criada com sucesso.', 'newmanagement'),
            true,
            INFO
        );
        Html::redirect(Company::getFormURL() . '?id=' . $newid);
    } else {
        Session::addMessageAfterRedirect(
            __('Erro ao criar empresa. Verifique os dados e tente novamente.', 'newmanagement'),
            true,
            ERROR
        );
        Html::back();
    }
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(Company::$rightname, DELETE);
    $company->delete($_POST);
    Session::addMessageAfterRedirect(
        __('Empresa removida com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::redirect(Company::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    // Formulário de edição
    $id = (int) $_GET['id'];
    if (!$company->getFromDB($id)) {
        Html::displayNotFoundError();
    }
    Html::header(
        Company::getTypeName(1),
        '',
        'plugins',
        'GlpiPlugin\\Newmanagement\\Company',
        'company'
    );
    $company->display(['id' => $id]);
    Html::footer();
} elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    // Formulário de criação
    Html::header(
        Company::getTypeName(1),
        '',
        'plugins',
        'GlpiPlugin\\Newmanagement\\Company',
        'company'
    );
    $company->showForm(-1);
    Html::footer();
} else {
    // Lista
    Html::header(
        Company::getTypeName(0),
        '',
        'plugins',
        'GlpiPlugin\\Newmanagement\\Company',
        'company'
    );
    Search::show(Company::class);
    Html::footer();
}
