<?php

/**
 * Newmanagement - Plugin GLPI
 * Front: Task — página de formulário/edição de Tarefa
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Task;

\Session::checkLoginUser();
\Session::checkRight(Task::$rightname, READ);

$task = new Task();
$id   = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $task->getFromDB($id);
}

\Html::header(
    Task::getTypeName(1),
    '',
    'plugins',
    'newmanagement',
    'task'
);

$task->display(['id' => $id]);

\Html::footer();
