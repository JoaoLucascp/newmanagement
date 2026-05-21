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
    // [FIX] Exibe erro 404 nativo do GLPI se o registro não existir
    if (!$task->getFromDB($id)) {
        \Html::displayNotFoundError();
    }
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
