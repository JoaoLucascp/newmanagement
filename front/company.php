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
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(Company::$rightname, CREATE);
    $newid = $company->add($_POST);
    Html::redirect(Company::getFormURL() . '?id=' . $newid);
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(Company::$rightname, DELETE);
    $company->delete($_POST);
    Html::redirect(Company::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    // Formulário de edição
    $company->getFromDB((int) $_GET['id']);
    Html::header(
        Company::getTypeName(1),
        '',
        'plugins',
        'GlpiPlugin\\Newmanagement\\Company',
        'company'
    );
    $company->showForm((int) $_GET['id']);
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
