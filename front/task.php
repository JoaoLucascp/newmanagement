<?php

/**
 * Newmanagement - Controller: Task
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Task;

Session::checkLoginUser();
Session::checkRight(Task::$rightname, READ);

$task = new Task();

// --- AÇÕES POST ---
if (isset($_POST['update'])) {
    Session::checkCsrfToken();
    Session::checkRight(Task::$rightname, UPDATE);
    $task->update($_POST);
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(Task::$rightname, CREATE);
    $newid = $task->add($_POST);
    Html::redirect(Task::getFormURL() . '?id=' . $newid);
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(Task::$rightname, DELETE);
    $task->delete($_POST);
    Html::redirect(Task::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $task->getFromDB((int) $_GET['id']);
    Html::header(
        Task::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Task'
    );
    $task->showForm((int) $_GET['id']);
    Html::footer();
} elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    Html::header(
        Task::getTypeName(1),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Task'
    );
    $task->showForm(-1);
    Html::footer();
} else {
    Html::header(
        Task::getTypeName(0),
        '',
        'tools',
        'GlpiPlugin\\Newmanagement\\Task'
    );
    Search::show(Task::class);
    Html::footer();
}
