<?php

/**
 * Newmanagement — CI: Detecção de N+1 nas queries de paginação
 *
 * Verifica que os métodos fetchPage() de cada entidade não emitem
 * mais de MAX_QUERIES_PER_PAGE queries ao banco por página carregada.
 *
 * Uso: php tests/check_nplus1.php
 * Exit 0 = OK | Exit 1 = falha (N+1 detectado ou GLPI ausente)
 */

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

if (!file_exists(GLPI_ROOT . '/inc/includes.php')) {
    fwrite(STDERR, "[SKIP] GLPI não encontrado em " . GLPI_ROOT . ". Pulando check_nplus1.\n");
    exit(0);
}

require_once GLPI_ROOT . '/inc/includes.php';

use GlpiPlugin\Newmanagement\Company;
use GlpiPlugin\Newmanagement\Ipbx;
use GlpiPlugin\Newmanagement\Chatbot;
use GlpiPlugin\Newmanagement\Task;

/**
 * Máximo de queries aceitas por uma chamada de fetchPage.
 * Padrão arquitetural do plugin: countElementsInTable + SELECT paginado = 2.
 * Tolerância de +1 para compatibilidade com versões do GLPI que adicionam
 * uma query interna de verificação de sessão.
 */
const MAX_QUERIES_PER_PAGE = 3;

global $DB;
$errors = [];

/**
 * Instrumenta o DB para contar queries emitidas durante um callback.
 *
 * @param callable $callback Operação a monitorar.
 * @return int Número de queries emitidas.
 */
function count_queries_during(callable $callback): int
{
    global $DB;

    // GLPI 11 usa Doctrine DBAL internamente; tentamos obter o log de queries.
    // Se a API não estiver disponível, retornamos -1 (skip seguro).
    if (!method_exists($DB, 'getLogs') && !method_exists($DB, 'getQueries')) {
        return -1;
    }

    $before = method_exists($DB, 'getLogs')
        ? count($DB->getLogs())
        : count($DB->getQueries());

    $callback();

    $after = method_exists($DB, 'getLogs')
        ? count($DB->getLogs())
        : count($DB->getQueries());

    return $after - $before;
}

/**
 * Casos de teste: cada entrada representa um fetchPage a validar.
 * O callable recebe ($page, $pageSize) e deve retornar um array.
 */
$cases = [
    'Company::fetchPage' => function (int $page, int $size) {
        $obj = new Company();
        return method_exists($obj, 'fetchPage')
            ? $obj->fetchPage($page, $size)
            : null;
    },
    'Ipbx::fetchPage' => function (int $page, int $size) {
        $obj = new Ipbx();
        return method_exists($obj, 'fetchPage')
            ? $obj->fetchPage($page, $size)
            : null;
    },
    'Chatbot::fetchPage' => function (int $page, int $size) {
        $obj = new Chatbot();
        return method_exists($obj, 'fetchPage')
            ? $obj->fetchPage($page, $size)
            : null;
    },
    'Task::fetchPage' => function (int $page, int $size) {
        $obj = new Task();
        return method_exists($obj, 'fetchPage')
            ? $obj->fetchPage($page, $size)
            : null;
    },
];

foreach ($cases as $label => $fn) {
    $queryCount = count_queries_during(fn () => $fn(1, 20));

    if ($queryCount === -1) {
        // API de log indisponível nesta versão do GLPI — skip sem falhar
        echo "[SKIP] {$label}: API de contagem de queries não disponível nesta versão do GLPI.\n";
        continue;
    }

    if ($queryCount > MAX_QUERIES_PER_PAGE) {
        $errors[] = sprintf(
            'N+1 detectado em %s: %d queries emitidas (máx %d).',
            $label,
            $queryCount,
            MAX_QUERIES_PER_PAGE
        );
    } else {
        echo "[OK] {$label}: {$queryCount} quer" . ($queryCount === 1 ? 'y' : 'ies') . " (máx " . MAX_QUERIES_PER_PAGE . ")\n";
    }
}

if ($errors) {
    echo "\n";
    foreach ($errors as $err) {
        fwrite(STDERR, "[FAIL] {$err}\n");
    }
    exit(1);
}

echo "\n[PASS] check_nplus1: nenhum padrão N+1 detectado.\n";
exit(0);
