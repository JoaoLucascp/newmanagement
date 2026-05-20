<?php

/**
 * Newmanagement — Paginação AJAX das sub-tabelas de Ramais e Dispositivos.
 *
 * Recebe via GET:
 *   section      — 'extensions' | 'devices'
 *   ipbx_id      — int
 *   companies_id — int
 *   page         — int (base 1)
 *
 * Responde com JSON:
 *   { success: true, html: string, page: int, total: int, page_size: int }
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Ipbx;

// Segurança: sessão + direito READ (leitura não precisa de CSRF)
Session::checkLoginUser();
Session::checkRight(Ipbx::$rightname, READ);

header('Content-Type: application/json; charset=utf-8');

function pgJson(bool $ok, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

$section     = $_GET['section']      ?? '';
$ipbx_id     = (int) ($_GET['ipbx_id']     ?? 0);
$companies_id = (int) ($_GET['companies_id'] ?? 0);
$page        = max(1, (int) ($_GET['page']  ?? 1));

if ($ipbx_id <= 0 || $companies_id <= 0) {
    pgJson(false, ['error' => 'Parâmetros inválidos']);
}

$can_delete = Session::haveRight(Ipbx::$rightname, DELETE);
$csrf       = Session::getNewCSRFToken();
$action_url = Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';

try {
    switch ($section) {

        case 'extensions':
            [$rows, $total] = Ipbx::fetchPage(
                Ipbx::TABLE_EXTENSIONS,
                ['ipbx_id' => $ipbx_id],
                'number ASC',
                $page
            );
            $html = '';
            foreach ($rows as $row) {
                $html .= Ipbx::renderExtensionRow(
                    (int) $row['id'], $row, $companies_id, $csrf, $action_url, $can_delete
                );
            }
            if (empty($rows)) {
                $html = '<tr><td colspan="7" class="text-center text-muted py-3">'
                    . __('Nenhum ramal encontrado.', 'newmanagement')
                    . '</td></tr>';
            }
            pgJson(true, [
                'html'      => $html,
                'page'      => $page,
                'total'     => $total,
                'page_size' => Ipbx::PAGE_SIZE,
                'csrf'      => $csrf,
            ]);

        case 'devices':
            [$rows, $total] = Ipbx::fetchPage(
                Ipbx::TABLE_DEVICES,
                ['ipbx_id' => $ipbx_id],
                'device_type ASC',
                $page
            );
            $html = '';
            foreach ($rows as $row) {
                $html .= Ipbx::renderDeviceRow(
                    (int) $row['id'], $row, $companies_id, $csrf, $action_url, $can_delete
                );
            }
            if (empty($rows)) {
                $html = '<tr><td colspan="5" class="text-center text-muted py-3">'
                    . __('Nenhum dispositivo encontrado.', 'newmanagement')
                    . '</td></tr>';
            }
            pgJson(true, [
                'html'      => $html,
                'page'      => $page,
                'total'     => $total,
                'page_size' => Ipbx::PAGE_SIZE,
                'csrf'      => $csrf,
            ]);

        default:
            pgJson(false, ['error' => 'Seção inválida: ' . htmlspecialchars($section)]);
    }
} catch (\Throwable $e) {
    \Toolbox::logDebug('ipbx_paginate.php error: ' . $e->getMessage());
    pgJson(false, ['error' => 'Erro interno ao paginar']);
}
