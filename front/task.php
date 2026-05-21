<?php

/**
 * Newmanagement - Controller: Task
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Task;

Session::checkLoginUser();
Session::checkRight(Task::$rightname, READ);

$task = new Task();

// --- AÇÕES POST (fallback sem JS) ---
if (isset($_POST['update'])) {
    Session::checkCsrfToken();
    Session::checkRight(Task::$rightname, UPDATE);
    $task->update($_POST);
    Session::addMessageAfterRedirect(
        __('Tarefa atualizada com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::back();
}

if (isset($_POST['add'])) {
    Session::checkCsrfToken();
    Session::checkRight(Task::$rightname, CREATE);
    $newid = $task->add($_POST);
    if ($newid) {
        Session::addMessageAfterRedirect(
            __('Tarefa criada com sucesso.', 'newmanagement'),
            true,
            INFO
        );
        Html::redirect(Task::getFormURL() . '?id=' . $newid);
    } else {
        Session::addMessageAfterRedirect(
            __('Erro ao criar tarefa.', 'newmanagement'),
            true,
            ERROR
        );
        Html::back();
    }
}

if (isset($_POST['delete'])) {
    Session::checkCsrfToken();
    Session::checkRight(Task::$rightname, DELETE);
    $task->delete($_POST, true);
    Session::addMessageAfterRedirect(
        __('Tarefa excluída com sucesso.', 'newmanagement'),
        true,
        INFO
    );
    Html::redirect(Task::getSearchURL());
}

// --- EXIBIÇÃO ---
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if (!$task->getFromDB($id)) {
        Html::displayNotFoundError();
    }
    Html::header(
        Task::getTypeName(1),
        '',
        'plugins',
        'GlpiPlugin\\Newmanagement\\Task',
        'task'
    );
    $task->display(['id' => $id]);
    Html::footer();
} elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    Html::header(
        Task::getTypeName(1),
        '',
        'plugins',
        'GlpiPlugin\\Newmanagement\\Task',
        'task'
    );
    $task->showForm(-1);
    Html::footer();
} else {
    Html::header(
        Task::getTypeName(0),
        '',
        'plugins',
        'GlpiPlugin\\Newmanagement\\Task',
        'task'
    );
    Search::show(Task::class);
    Html::footer();
}
