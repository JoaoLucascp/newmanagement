<?php
/**
 * Newmanagement — Endpoint de exportação de Ramais IPBX
 *
 * GET  ?format=pdf&ipbx_id=X&companies_id=Y   → PDF via TCPDF do GLPI
 * GET  ?format=xlsx&ipbx_id=X&companies_id=Y  → CSV (fallback sem PhpSpreadsheet)
 *
 * Segurança:
 *  - Requer sessão GLPI ativa (Session::checkLoginUser)
 *  - Valida CSRF via Session::checkToken
 *  - Valida permissão READ no rightname do plugin
 */

define('GLPI_ROOT', dirname(dirname(dirname(dirname(__DIR__)))));
require_once GLPI_ROOT . '/inc/includes.php';

use GlpiPlugin\Newmanagement\IpbxExtension;

Session::checkLoginUser();
Session::checkToken();

if (!Session::haveRight('plugin_newmanagement_ipbx', READ)) {
    http_response_code(403);
    exit('Acesso negado');
}

$format      = strtolower($_GET['format']      ?? 'pdf');
$ipbx_id     = (int) ($_GET['ipbx_id']         ?? 0);
$companies_id = (int) ($_GET['companies_id']   ?? 0);

if (!$ipbx_id) {
    http_response_code(400);
    exit('ipbx_id obrigatório');
}

/* ── Busca dados ──────────────────────────────────────────────── */
global $DB;
$rows = [];
$iter = $DB->request([
    'SELECT'  => ['number','password','user_name','device_ip','department',
                  'records_calls','lof','loc','ddf','ddc','ddi','srv'],
    'FROM'    => 'glpi_plugin_newmanagement_ipbx_extensions',
    'WHERE'   => ['ipbx_id' => $ipbx_id, 'is_deleted' => 0],
    'ORDER'   => 'number ASC',
]);
foreach ($iter as $row) {
    $rows[] = $row;
}

$headers = ['Ramal','Senha','Usuário','IP Dispositivo','Departamento',
            'Grava','LOF','LOC','DDF','DDC','DDI','SRV'];

$yn = fn($v) => $v ? 'Sim' : 'Não';

/* ── PDF via TCPDF ──────────────────────────────────────────── */
if ($format === 'pdf') {
    // Localiza TCPDF dentro do GLPI
    $tcpdf_paths = [
        GLPI_ROOT . '/vendor/tecnickcom/tcpdf/tcpdf.php',
        GLPI_ROOT . '/lib/tcpdf/tcpdf.php',
    ];
    $tcpdf_found = false;
    foreach ($tcpdf_paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            $tcpdf_found = true;
            break;
        }
    }

    if (!$tcpdf_found) {
        http_response_code(500);
        exit('TCPDF não encontrado no GLPI. Use exportação Excel.');
    }

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Newmanagement Plugin');
    $pdf->SetAuthor('GLPI');
    $pdf->SetTitle('Ramais IPBX #' . $ipbx_id);
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->setHeaderFont(['helvetica', 'B', 11]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetDefaultMonospacedFont('courier');
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 8, 'Ramais IPBX #' . $ipbx_id, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 5, 'Gerado em ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(3);

    // Cabeçalho da tabela
    $widths = [18, 22, 35, 32, 32, 12, 10, 10, 10, 10, 10, 10];
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(220, 230, 241);
    foreach ($headers as $i => $h) {
        $pdf->Cell($widths[$i], 6, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->SetFont('helvetica', '', 7);
    $fill = false;
    $pdf->SetFillColor(240, 245, 250);
    foreach ($rows as $r) {
        $cells = [
            $r['number'],
            $r['password'],
            $r['user_name'],
            $r['device_ip'],
            $r['department'],
            $yn($r['records_calls']),
            $yn($r['lof']),
            $yn($r['loc']),
            $yn($r['ddf']),
            $yn($r['ddc']),
            $yn($r['ddi']),
            $yn($r['srv']),
        ];
        foreach ($cells as $i => $c) {
            $pdf->Cell($widths[$i], 5, $c, 1, 0, 'C', $fill);
        }
        $pdf->Ln();
        $fill = !$fill;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="ramais_ipbx_' . $ipbx_id . '.pdf"');
    echo $pdf->Output('', 'S');
    exit;
}

/* ── CSV/Excel fallback ──────────────────────────────────────── */
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="ramais_ipbx_' . $ipbx_id . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
fputcsv($out, $headers, ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['number'],
        $r['password'],
        $r['user_name'],
        $r['device_ip'],
        $r['department'],
        $yn($r['records_calls']),
        $yn($r['lof']),
        $yn($r['loc']),
        $yn($r['ddf']),
        $yn($r['ddc']),
        $yn($r['ddi']),
        $yn($r['srv']),
    ], ';');
}
fclose($out);
exit;
