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
    public static $itemtype  = Company::class;
    public static $items_id  = 'companies_id';

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
        return ($item instanceof Company) ? self::getTypeName(1) : '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Company) {
            (new self())->showTabForCompany((int) $item->getID());
        }
        return true;
    }

    // ======================================================================
    // Tab principal
    // ======================================================================
    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        $rows   = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0], 'LIMIT' => 1]);
        $ipbx_id = 0;
        $fields  = ['id' => 0, 'companies_id' => $companies_id, 'model' => '', 'server_version' => '', 'ip_local' => '', 'ip_external' => '', 'web_port' => '', 'web_password' => '', 'ssh_port' => '', 'ssh_password' => '', 'comment' => ''];
        foreach ($rows as $row) {
            $fields = $row;
            $ipbx_id = (int)$row['id'];
            // Descriptografar senhas com fallback para valores legados (registros antigos sem criptografia)
            try {
                if (!empty($fields['web_password'])) {
                    $fields['web_password'] = \Toolbox::sodiumDecrypt($fields['web_password']);
                }
            } catch (\Throwable $e) {
                // Fallback: valor já é plain text (registro anterior à criptografia)
            }
            try {
                if (!empty($fields['ssh_password'])) {
                    $fields['ssh_password'] = \Toolbox::sodiumDecrypt($fields['ssh_password']);
                }
            } catch (\Throwable $e) {
                // Fallback: valor já é plain text (registro anterior à criptografia)
            }
        }

        $csrf   = \Session::getNewCSRFToken();
        $action = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';

        // helpers
        $h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        echo '<div class="nm-ipbx-tab" data-action-url="' . $h($action) . '" data-companies-id="' . $companies_id . '">';

        // ---- Formulário do servidor IPBX ----
        // FIX: sem method/action — submit é 100% via fetch() no JS.
        // Isso evita que o Chrome trate este form como form de login/senha
        // e dispare o aviso "Multiple forms should be contained in their own
        // form elements" junto com o erro de extensões de gerenciador de senhas.
        // A URL de destino fica armazenada em data-action-url no container pai.
        echo '<form id="nm-ipbx-form" autocomplete="off" onsubmit="return false;">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="' . ($ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx') . '">';
        echo '<input type="hidden" name="id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<table class="tab_cadre_fixe">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo', 'newmanagement') . '</td><td><input type="text" name="model" autocomplete="off" value="' . $h($fields['model']) . '" class="form-control"></td>';
        echo '<td>' . __('Versão', 'newmanagement') . '</td><td><input type="text" name="server_version" autocomplete="off" value="' . $h($fields['server_version']) . '" class="form-control"></td>';
        echo '</tr><tr class="tab_bg_1">';
        echo '<td>' . __('IP Local', 'newmanagement') . '</td><td><input type="text" name="ip_local" autocomplete="off" value="' . $h($fields['ip_local']) . '" class="form-control" placeholder="192.168.1.1"></td>';
        echo '<td>' . __('IP Externo', 'newmanagement') . '</td><td><input type="text" name="ip_external" autocomplete="off" value="' . $h($fields['ip_external']) . '" class="form-control" placeholder="201.x.x.x"></td>';
        echo '</tr><tr class="tab_bg_1">';
        echo '<td>' . __('Porta Web', 'newmanagement') . '</td><td><input type="text" name="web_port" autocomplete="off" value="' . $h($fields['web_port']) . '" class="form-control" placeholder="80"></td>';
        echo '<td>' . __('Senha Web', 'newmanagement') . '</td><td><div class="input-group"><input type="password" id="web_password" name="web_password" value="' . $h($fields['web_password']) . '" class="form-control" autocomplete="new-password"><button type="button" class="btn btn-outline-secondary nm-btn-eye" data-target="web_password"><i class="ti ti-eye"></i></button></div></td>';
        echo '</tr><tr class="tab_bg_1">';
        echo '<td>' . __('Porta SSH', 'newmanagement') . '</td><td><input type="text" name="ssh_port" autocomplete="off" value="' . $h($fields['ssh_port']) . '" class="form-control" placeholder="22"></td>';
        echo '<td>' . __('Senha SSH', 'newmanagement') . '</td><td><div class="input-group"><input type="password" id="ssh_password" name="ssh_password" value="' . $h($fields['ssh_password']) . '" class="form-control" autocomplete="new-password"><button type="button" class="btn btn-outline-secondary nm-btn-eye" data-target="ssh_password"><i class="ti ti-eye"></i></button></div></td>';
        echo '</tr><tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td><td colspan="3"><textarea name="comment" class="form-control" rows="2">' . $h($fields['comment']) . '</textarea></td>';
        echo '</tr></table>';
        echo '</form>';

        // ---- Ramais ----
        echo '<div class="nm-subsection mt-3">';
        echo '<h5><i class="ti ti-phone-call"></i> ' . __('Ramais', 'newmanagement') . '</h5>';
        $this->renderExtensions($ipbx_id, $companies_id, $csrf, $action);
        echo '</div>';

        // ---- Dispositivos ----
        echo '<div class="nm-subsection mt-3">';
        echo '<h5><i class="ti ti-device-desktop"></i> ' . __('Dispositivos', 'newmanagement') . '</h5>';
        $this->renderDevices($ipbx_id, $companies_id, $csrf, $action);
        echo '</div>';

        // ---- Rede ----
        echo '<div class="nm-subsection mt-3">';
        echo '<h5><i class="ti ti-network"></i> ' . __('Rede da Empresa', 'newmanagement') . '</h5>';
        $this->renderNetwork($ipbx_id, $companies_id, $csrf, $action);
        echo '</div>';

        // ---- Botão Salvar (IPBX) — fora dos forms ----
        echo '<div class="text-end mt-3 mb-3">';
        echo '<button type="button" id="nm-save-all" class="btn btn-primary"'
            . ' data-action-url="' . $h($action) . '">'
            . '<i class="ti ti-device-floppy"></i> ' . __('Salvar', 'newmanagement') . '</button>';
        echo '</div>';

        echo '</div>'; // .nm-ipbx-tab
    }

    // ======================================================================
    // Ramais
    // ======================================================================
    private function renderExtensions(int $ipbx_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_extensions', 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'number ASC'])
            : [];

        echo '<table class="tab_cadre_fixehov" id="nm-ext-table">';
        echo '<thead><tr class="noHover">';
        foreach ([__('Número','newmanagement'), __('Senha','newmanagement'), __('IP Aparelho','newmanagement'), __('Usuário','newmanagement'), __('Grava?','newmanagement'), __('Departamento','newmanagement'), __('Ação','newmanagement')] as $th) {
            echo '<th>' . $th . '</th>';
        }
        echo '</tr></thead><tbody id="nm-ext-tbody">';
        foreach ($rows as $row) {
            echo self::renderExtensionRow((int)$row['id'], $row, $companies_id, $csrf, $action);
        }
        echo '</tbody></table>';

        // FIX: form role="presentation" para conter o campo password sem
        // acionar rastreamento de login pelo Chrome/extensões de senha.
        echo '<form role="presentation" autocomplete="off" onsubmit="return false;" style="margin:0">';
        echo '<div class="nm-add-row d-flex flex-wrap gap-2 align-items-center mt-2" id="nm-ext-add">';
        echo '<input type="text" id="nm-ext-number" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Número','newmanagement') . '" style="width:110px">';
        echo '<input type="password" id="nm-ext-password" autocomplete="new-password" class="form-control form-control-sm" placeholder="' . __('Senha','newmanagement') . '" style="width:110px">';
        echo '<input type="text" id="nm-ext-device_ip" autocomplete="off" class="form-control form-control-sm" placeholder="IP" style="width:120px">';
        echo '<input type="text" id="nm-ext-user_name" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Usuário','newmanagement') . '" style="width:120px">';
        echo '<select id="nm-ext-records_calls" class="form-select form-select-sm" style="width:90px"><option value="0">' . __('Não','newmanagement') . '</option><option value="1">' . __('Sim','newmanagement') . '</option></select>';
        echo '<input type="text" id="nm-ext-department" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Departamento','newmanagement') . '" style="width:140px">';
        echo '<button type="button" class="btn btn-sm btn-success" id="nm-ext-add-btn"'
            . ' data-action="add_extension"'
            . ' data-ipbx-id="' . $ipbx_id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar Ramal','newmanagement') . '</button>';
        echo '</div>';
        echo '</form>';
    }

    public static function renderExtensionRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
    {
        $h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        return '<tr class="tab_bg_1" id="nm-ext-row-' . $id . '">'
            . '<td>' . $h($row['number']) . '</td>'
            . '<td>••••••</td>'
            . '<td>' . $h($row['device_ip']) . '</td>'
            . '<td>' . $h($row['user_name']) . '</td>'
            . '<td>' . ($row['records_calls'] ? __('Sim','newmanagement') : __('Não','newmanagement')) . '</td>'
            . '<td>' . $h($row['department']) . '</td>'
            . '<td><button type="button" class="btn btn-sm btn-danger nm-del-btn"'
            . ' data-action="delete_extension" data-id="' . $id . '"'
            . ' data-row="nm-ext-row-' . $id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
            . ' data-confirm="' . __('Remover ramal?','newmanagement') . '">'
            . '<i class="ti ti-trash"></i></button></td></tr>';
    }

    // ======================================================================
    // Dispositivos
    // ======================================================================
    private function renderDevices(int $ipbx_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_devices', 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'device_type ASC'])
            : [];

        echo '<table class="tab_cadre_fixehov" id="nm-dev-table">';
        echo '<thead><tr class="noHover">';
        foreach ([__('Tipo','newmanagement'), __('IP','newmanagement'), __('Senha','newmanagement'), __('Ação','newmanagement')] as $th) {
            echo '<th>' . $th . '</th>';
        }
        echo '</tr></thead><tbody id="nm-dev-tbody">';
        foreach ($rows as $row) {
            echo self::renderDeviceRow((int)$row['id'], $row, $companies_id, $csrf, $action);
        }
        echo '</tbody></table>';

        // FIX: form role="presentation" para conter o campo password
        echo '<form role="presentation" autocomplete="off" onsubmit="return false;" style="margin:0">';
        echo '<div class="nm-add-row d-flex flex-wrap gap-2 align-items-center mt-2" id="nm-dev-add">';
        echo '<input type="text" id="nm-dev-device_type" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Tipo','newmanagement') . '" style="width:160px">';
        echo '<input type="text" id="nm-dev-ip_address" autocomplete="off" class="form-control form-control-sm" placeholder="IP" style="width:160px">';
        echo '<input type="password" id="nm-dev-password" autocomplete="new-password" class="form-control form-control-sm" placeholder="' . __('Senha','newmanagement') . '" style="width:140px">';
        echo '<button type="button" class="btn btn-sm btn-success" id="nm-dev-add-btn"'
            . ' data-action="add_device"'
            . ' data-ipbx-id="' . $ipbx_id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar Dispositivo','newmanagement') . '</button>';
        echo '</div>';
        echo '</form>';
    }

    public static function renderDeviceRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
    {
        $h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        return '<tr class="tab_bg_1" id="nm-dev-row-' . $id . '">'
            . '<td>' . $h($row['device_type']) . '</td>'
            . '<td>' . $h($row['ip_address']) . '</td>'
            . '<td>••••••</td>'
            . '<td><button type="button" class="btn btn-sm btn-danger nm-del-btn"'
            . ' data-action="delete_device" data-id="' . $id . '"'
            . ' data-row="nm-dev-row-' . $id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
            . ' data-confirm="' . __('Remover dispositivo?','newmanagement') . '">'
            . '<i class="ti ti-trash"></i></button></td></tr>';
    }

    // ======================================================================
    // Rede da Empresa
    // ======================================================================
    private function renderNetwork(int $ipbx_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_network', 'WHERE' => ['ipbx_id' => $ipbx_id]])
            : [];

        echo '<table class="tab_cadre_fixehov" id="nm-net-table">';
        echo '<thead><tr class="noHover">';
        foreach ([__('IP Rede','newmanagement'), __('Máscara','newmanagement'), __('Gateway','newmanagement'), __('DNS Primário','newmanagement'), __('DNS Secundário','newmanagement'), __('Ação','newmanagement')] as $th) {
            echo '<th>' . $th . '</th>';
        }
        echo '</tr></thead><tbody id="nm-net-tbody">';
        foreach ($rows as $row) {
            echo self::renderNetworkRow((int)$row['id'], $row, $companies_id, $csrf, $action);
        }
        echo '</tbody></table>';

        echo '<div class="nm-add-row d-flex flex-wrap gap-2 align-items-center mt-2" id="nm-net-add">';
        echo '<input type="text" id="nm-net-ip_network" autocomplete="off" class="form-control form-control-sm" placeholder="192.168.1.0" style="width:140px">';
        echo '<input type="text" id="nm-net-netmask" autocomplete="off" class="form-control form-control-sm" placeholder="255.255.255.0" style="width:140px">';
        echo '<input type="text" id="nm-net-gateway" autocomplete="off" class="form-control form-control-sm" placeholder="192.168.1.1" style="width:130px">';
        echo '<input type="text" id="nm-net-dns_primary" autocomplete="off" class="form-control form-control-sm" placeholder="8.8.8.8" style="width:120px">';
        echo '<input type="text" id="nm-net-dns_secondary" autocomplete="off" class="form-control form-control-sm" placeholder="8.8.4.4" style="width:120px">';
        echo '<button type="button" class="btn btn-sm btn-success" id="nm-net-add-btn"'
            . ' data-action="add_network"'
            . ' data-ipbx-id="' . $ipbx_id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar Rede','newmanagement') . '</button>';
        echo '</div>';
    }

    public static function renderNetworkRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
    {
        $h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        return '<tr class="tab_bg_1" id="nm-net-row-' . $id . '">'
            . '<td>' . $h($row['ip_network']) . '</td>'
            . '<td>' . $h($row['netmask']) . '</td>'
            . '<td>' . $h($row['gateway']) . '</td>'
            . '<td>' . $h($row['dns_primary']) . '</td>'
            . '<td>' . $h($row['dns_secondary']) . '</td>'
            . '<td><button type="button" class="btn btn-sm btn-danger nm-del-btn"'
            . ' data-action="delete_network" data-id="' . $id . '"'
            . ' data-row="nm-net-row-' . $id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
            . ' data-confirm="' . __('Remover rede?','newmanagement') . '">'
            . '<i class="ti ti-trash"></i></button></td></tr>';
    }


}
