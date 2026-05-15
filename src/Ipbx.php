<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Ipbx — aba Servidor IPBX dentro da ficha de Empresa
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Ipbx extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_ipbx';

    public static $itemtype = Company::class;
    public static $items_id = 'companies_id';

    public static function getTypeName($nb = 0): string
    {
        return _n('Servidor IPBX', 'Servidores IPBX', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx';
    }

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Company) {
            return self::getTypeName(1);
        }
        return '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Company) {
            $ipbx = new self();
            $ipbx->showTabForCompany((int) $item->getID());
        }
        return true;
    }

    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        $rows = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0],
            'LIMIT' => 1,
        ]);

        $ipbx_id = 0;
        $fields  = [
            'id' => 0, 'companies_id' => $companies_id,
            'model' => '', 'server_version' => '',
            'ip_local' => '', 'ip_external' => '',
            'web_port' => '', 'web_password' => '',
            'ssh_port' => '', 'ssh_password' => '',
            'comment' => '',
        ];

        foreach ($rows as $row) {
            $fields  = $row;
            $ipbx_id = (int) $row['id'];
        }

        $csrf     = \Session::getNewCSRFToken();
        $action   = (defined('GLPI_ROOT') ? \Plugin::getWebDir('newmanagement') : '') . '/ajax/ipbx_sub.php';
        $redirect = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES);

        echo '<div class="nm-ipbx-tab">';

        // ---- Form Servidor IPBX ----
        // autocomplete="off" no form para evitar aviso do Chrome sobre multiplos forms com senha
        echo '<form method="post" action="' . $action . '" id="nm-ipbx-form" autocomplete="off">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="' . ($ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx') . '">';
        echo '<input type="hidden" name="id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';

        echo '<table class="tab_cadre_fixe nm-table">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="model" autocomplete="off" value="' . htmlspecialchars($fields['model'] ?? '', ENT_QUOTES) . '" class="form-control"></td>';
        echo '<td>' . __('Versão', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="server_version" autocomplete="off" value="' . htmlspecialchars($fields['server_version'] ?? '', ENT_QUOTES) . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('IP Local', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="ip_local" autocomplete="off" value="' . htmlspecialchars($fields['ip_local'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="192.168.1.1"></td>';
        echo '<td>' . __('IP Externo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="ip_external" autocomplete="off" value="' . htmlspecialchars($fields['ip_external'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="201.x.x.x"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Porta Web', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="web_port" autocomplete="off" value="' . htmlspecialchars($fields['web_port'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="80"></td>';
        echo '<td>' . __('Senha Web', 'newmanagement') . '</td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="password" id="web_password" name="web_password" value="' . htmlspecialchars($fields['web_password'] ?? '', ENT_QUOTES) . '" class="form-control" autocomplete="new-password">';
        echo '    <button type="button" class="nm-btn-eye" data-target="web_password" title="Mostrar/Ocultar"><i class="ti ti-eye"></i></button>';
        echo '  </div>';
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Porta SSH', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="ssh_port" autocomplete="off" value="' . htmlspecialchars($fields['ssh_port'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="22"></td>';
        echo '<td>' . __('Senha SSH', 'newmanagement') . '</td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="password" id="ssh_password" name="ssh_password" value="' . htmlspecialchars($fields['ssh_password'] ?? '', ENT_QUOTES) . '" class="form-control" autocomplete="new-password">';
        echo '    <button type="button" class="nm-btn-eye" data-target="ssh_password" title="Mostrar/Ocultar"><i class="ti ti-eye"></i></button>';
        echo '  </div>';
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="2">' . htmlspecialchars($fields['comment'] ?? '', ENT_QUOTES) . '</textarea></td>';
        echo '</tr>';

        echo '</table>';
        echo '</form>';
        // ---- Fim Form IPBX ----

        // ---- Ramais ----
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-phone-call"></i> ' . __('Ramais', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderExtensionsTable($ipbx_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        // ---- Dispositivos ----
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-device-desktop"></i> ' . __('Dispositivos', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderDevicesTable($ipbx_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        // ---- Rede da Empresa ----
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-network"></i> ' . __('Rede da Empresa', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderNetworkTable($ipbx_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        // ---- Linha Fixa ----
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-phone"></i> ' . __('Linha Fixa', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderLinesForm($ipbx_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        echo '</div>'; // .nm-ipbx-tab
    }

    // ------------------------------------------------------------------
    // Ramais
    // ------------------------------------------------------------------
    private function renderExtensionsTable(int $ipbx_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_extensions', 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'number ASC'])
            : [];

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<thead><tr class="noHover">';
        echo '<th>' . __('Número', 'newmanagement') . '</th>';
        echo '<th>' . __('Senha', 'newmanagement') . '</th>';
        echo '<th>' . __('IP Aparelho', 'newmanagement') . '</th>';
        echo '<th>' . __('Usuário', 'newmanagement') . '</th>';
        echo '<th>' . __('Grava?', 'newmanagement') . '</th>';
        echo '<th>' . __('Departamento', 'newmanagement') . '</th>';
        echo '<th>' . __('Ação', 'newmanagement') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['number'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>••••••</td>';
            echo '<td>' . htmlspecialchars($row['device_ip'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['user_name'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . ($row['records_calls'] ? __('Sim', 'newmanagement') : __('Não', 'newmanagement')) . '</td>';
            echo '<td>' . htmlspecialchars($row['department'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>';
            echo '<button type="button" class="btn btn-sm btn-danger nm-delete-btn"'
                . ' data-action="delete_extension"'
                . ' data-id="' . $rid . '"'
                . ' data-companies_id="' . $companies_id . '"'
                . ' data-csrf="' . $csrf . '"'
                . ' data-action-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
                . ' data-redirect="' . $redirect . '"'
                . ' data-confirm="' . __('Remover ramal?', 'newmanagement') . '">'
                . '<i class="ti ti-trash"></i></button>';
            echo '</td></tr>';
        }

        echo '</tbody></table>';

        // Form de adição independente — autocomplete="off" no form inteiro
        echo '<form method="post" action="' . $action . '" class="nm-add-form nm-add-ext-form" autocomplete="off" style="margin-top:8px">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_extension">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<div class="nm-add-row d-flex flex-wrap gap-2 align-items-center">';
        echo '<input type="text" name="number" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Número', 'newmanagement') . '" style="width:110px">';
        echo '<input type="password" name="password" autocomplete="new-password" class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" style="width:110px">';
        echo '<input type="text" name="device_ip" autocomplete="off" class="form-control form-control-sm" placeholder="IP" style="width:120px">';
        echo '<input type="text" name="user_name" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Usuário', 'newmanagement') . '" style="width:120px">';
        echo '<select name="records_calls" class="form-select form-select-sm" style="width:90px"><option value="0">' . __('Não', 'newmanagement') . '</option><option value="1">' . __('Sim', 'newmanagement') . '</option></select>';
        echo '<input type="text" name="department" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Departamento', 'newmanagement') . '" style="width:140px">';
        echo '<button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar Ramal', 'newmanagement') . '</button>';
        echo '</div>';
        echo '</form>';
    }

    // ------------------------------------------------------------------
    // Dispositivos
    // ------------------------------------------------------------------
    private function renderDevicesTable(int $ipbx_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_devices', 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'device_type ASC'])
            : [];

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<thead><tr class="noHover">';
        echo '<th>' . __('Tipo', 'newmanagement') . '</th>';
        echo '<th>' . __('IP', 'newmanagement') . '</th>';
        echo '<th>' . __('Senha', 'newmanagement') . '</th>';
        echo '<th>' . __('Ação', 'newmanagement') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['device_type'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['ip_address'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>••••••</td>';
            echo '<td>';
            echo '<button type="button" class="btn btn-sm btn-danger nm-delete-btn"'
                . ' data-action="delete_device"'
                . ' data-id="' . $rid . '"'
                . ' data-companies_id="' . $companies_id . '"'
                . ' data-csrf="' . $csrf . '"'
                . ' data-action-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
                . ' data-redirect="' . $redirect . '"'
                . ' data-confirm="' . __('Remover dispositivo?', 'newmanagement') . '">'
                . '<i class="ti ti-trash"></i></button>';
            echo '</td></tr>';
        }

        echo '</tbody></table>';

        echo '<form method="post" action="' . $action . '" class="nm-add-form" autocomplete="off" style="margin-top:8px">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_device">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<div class="nm-add-row d-flex flex-wrap gap-2 align-items-center">';
        echo '<input type="text" name="device_type" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Tipo', 'newmanagement') . '" style="width:160px">';
        echo '<input type="text" name="ip_address" autocomplete="off" class="form-control form-control-sm" placeholder="IP" style="width:160px">';
        echo '<input type="password" name="password" autocomplete="new-password" class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" style="width:140px">';
        echo '<button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar Dispositivo', 'newmanagement') . '</button>';
        echo '</div>';
        echo '</form>';
    }

    // ------------------------------------------------------------------
    // Rede da Empresa
    // ------------------------------------------------------------------
    private function renderNetworkTable(int $ipbx_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_network', 'WHERE' => ['ipbx_id' => $ipbx_id]])
            : [];

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<thead><tr class="noHover">';
        echo '<th>' . __('IP Rede', 'newmanagement') . '</th>';
        echo '<th>' . __('Máscara', 'newmanagement') . '</th>';
        echo '<th>' . __('Gateway', 'newmanagement') . '</th>';
        echo '<th>' . __('DNS Primário', 'newmanagement') . '</th>';
        echo '<th>' . __('DNS Secundário', 'newmanagement') . '</th>';
        echo '<th>' . __('Ação', 'newmanagement') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['ip_network'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['netmask'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['gateway'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['dns_primary'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['dns_secondary'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>';
            echo '<button type="button" class="btn btn-sm btn-danger nm-delete-btn"'
                . ' data-action="delete_network"'
                . ' data-id="' . $rid . '"'
                . ' data-companies_id="' . $companies_id . '"'
                . ' data-csrf="' . $csrf . '"'
                . ' data-action-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
                . ' data-redirect="' . $redirect . '"'
                . ' data-confirm="' . __('Remover rede?', 'newmanagement') . '">'
                . '<i class="ti ti-trash"></i></button>';
            echo '</td></tr>';
        }

        echo '</tbody></table>';

        echo '<form method="post" action="' . $action . '" class="nm-add-form" autocomplete="off" style="margin-top:8px">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_network">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<div class="nm-add-row d-flex flex-wrap gap-2 align-items-center">';
        echo '<input type="text" name="ip_network" autocomplete="off" class="form-control form-control-sm" placeholder="192.168.1.0" style="width:140px">';
        echo '<input type="text" name="netmask" autocomplete="off" class="form-control form-control-sm" placeholder="255.255.255.0" style="width:140px">';
        echo '<input type="text" name="gateway" autocomplete="off" class="form-control form-control-sm" placeholder="192.168.1.1" style="width:130px">';
        echo '<input type="text" name="dns_primary" autocomplete="off" class="form-control form-control-sm" placeholder="8.8.8.8" style="width:120px">';
        echo '<input type="text" name="dns_secondary" autocomplete="off" class="form-control form-control-sm" placeholder="8.8.4.4" style="width:120px">';
        echo '<button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar Rede', 'newmanagement') . '</button>';
        echo '</div>';
        echo '</form>';
    }

    // ------------------------------------------------------------------
    // Linha Fixa
    // ------------------------------------------------------------------
    private function renderLinesForm(int $ipbx_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;

        $line_id = 0;
        $f = [
            'pilot_number'      => '',
            'ddr_count'         => '',
            'status'            => 1,
            'operator'          => '',
            'channels'          => '',
            'proxy_ip'          => '',
            'line_type'         => '',
            'audio_ip'          => '',
            'proxy_port'        => '',
            'portability_date'  => '',
            'previous_operator' => '',
            'activation_date'   => '',
            'expiration_date'   => '',
            'comment'           => '',
        ];

        if ($ipbx_id > 0) {
            $rows = $DB->request([
                'FROM'  => 'glpi_plugin_newmanagement_ipbx_lines',
                'WHERE' => ['ipbx_id' => $ipbx_id],
                'LIMIT' => 1,
            ]);
            foreach ($rows as $row) {
                $line_id = (int)$row['id'];
                foreach (array_keys($f) as $key) {
                    if (isset($row[$key])) {
                        $f[$key] = $row[$key];
                    }
                }
            }
        }

        $form_action = $line_id > 0 ? 'update_line' : 'add_line';
        $status_opts = [1 => __('Ativo', 'newmanagement'), 2 => __('Cancelado', 'newmanagement')];

        $v = function(string $key) use ($f): string {
            return htmlspecialchars((string)($f[$key] ?? ''), ENT_QUOTES);
        };

        echo '<form method="post" action="' . $action . '" id="nm-lines-form" autocomplete="off">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="' . $form_action . '">';
        echo '<input type="hidden" name="id" value="' . $line_id . '">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';

        echo '<table class="tab_cadre_fixe nm-table">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Número Piloto', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="pilot_number" autocomplete="off" value="' . $v('pilot_number') . '" class="form-control" placeholder="Ex: 1131000000"></td>';
        echo '<td>' . __('Quantidade de DDR', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="ddr_count" autocomplete="off" value="' . $v('ddr_count') . '" class="form-control" min="0" placeholder="0"></td>';
        echo '<td>' . __('Status', 'newmanagement') . '</td>';
        echo '<td><select name="status" class="form-select">';
        foreach ($status_opts as $val => $lbl) {
            $sel = ((int)$f['status'] === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $lbl . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Operadora', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="operator" autocomplete="off" value="' . $v('operator') . '" class="form-control" placeholder="Ex: Vivo"></td>';
        echo '<td>' . __('Quantidade de Canais', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="channels" autocomplete="off" value="' . $v('channels') . '" class="form-control" min="0" placeholder="0"></td>';
        echo '<td>' . __('IP Proxy', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="proxy_ip" autocomplete="off" value="' . $v('proxy_ip') . '" class="form-control" placeholder="Ex: 200.x.x.x"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Tipo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="line_type" autocomplete="off" value="' . $v('line_type') . '" class="form-control" placeholder="Ex: SIP, E1"></td>';
        echo '<td>' . __('IP Tráfego Áudio', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="audio_ip" autocomplete="off" value="' . $v('audio_ip') . '" class="form-control" placeholder="Ex: 200.x.x.x"></td>';
        echo '<td>' . __('Porta Proxy', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="proxy_port" autocomplete="off" value="' . $v('proxy_port') . '" class="form-control" placeholder="Ex: 5060"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Data Portabilidade', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="portability_date" autocomplete="off" value="' . $v('portability_date') . '" class="form-control"></td>';
        echo '<td>' . __('Operadora Anterior', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="previous_operator" autocomplete="off" value="' . $v('previous_operator') . '" class="form-control" placeholder="Ex: Claro"></td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Data de Ativação', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="activation_date" autocomplete="off" value="' . $v('activation_date') . '" class="form-control"></td>';
        echo '<td>' . __('Data de Vencimento', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="expiration_date" autocomplete="off" value="' . $v('expiration_date') . '" class="form-control"></td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="5"><textarea name="comment" class="form-control" rows="2">' . $v('comment') . '</textarea></td>';
        echo '</tr>';

        echo '</table>';
        echo '</form>';

        // ---- Botão Salvar ----
        echo '<div style="text-align:right;padding:8px 0">';
        echo '<button type="button" id="nm-save-all" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar', 'newmanagement') . '</button>';
        echo '</div>';

        // ---- JS: Salvar IPBX + Linha Fixa; Deletar via fetch ----
        echo '<script>';
        echo '(function(){';
        echo 'document.addEventListener("DOMContentLoaded", function(){';
        echo '  var btnSave = document.getElementById("nm-save-all");';
        echo '  if(!btnSave) return;';
        echo '  btnSave.addEventListener("click", function(){';
        echo '    var ipbxForm = document.getElementById("nm-ipbx-form");';
        echo '    var linesForm = document.getElementById("nm-lines-form");';
        echo '    if(!ipbxForm || !linesForm){ return; }';
        echo '    var fd = new FormData(ipbxForm);';
        echo '    fetch(ipbxForm.action, {method:"POST", body:fd})';
        echo '      .then(function(){ linesForm.submit(); })';
        echo '      .catch(function(){ linesForm.submit(); });';
        echo '  });';
        echo '  document.querySelectorAll(".nm-delete-btn").forEach(function(btn){';
        echo '    btn.addEventListener("click", function(){';
        echo '      var msg = btn.dataset.confirm || "Confirmar?";';
        echo '      if(!confirm(msg)) return;';
        echo '      var fd = new FormData();';
        echo '      fd.append("_glpi_csrf_token", btn.dataset.csrf);';
        echo '      fd.append("action", btn.dataset.action);';
        echo '      fd.append("id", btn.dataset.id);';
        echo '      fd.append("companies_id", btn.dataset.companies_id);';
        echo '      fd.append("redirect", btn.dataset.redirect);';
        echo '      fetch(btn.dataset.actionUrl, {method:"POST", body:fd})';
        echo '        .then(function(){ window.location.reload(); });';
        echo '    });';
        echo '  });';
        echo '});';
        echo '})();';
        echo '</script>';
    }
}
