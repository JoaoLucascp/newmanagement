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
        foreach ($rows as $row) { $fields = $row; $ipbx_id = (int)$row['id']; }

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

        // ---- Linha Fixa ----
        echo '<div class="nm-subsection mt-3">';
        echo '<h5><i class="ti ti-phone"></i> ' . __('Linha Fixa', 'newmanagement') . '</h5>';
        $this->renderLines($ipbx_id, $companies_id, $csrf, $action);
        echo '</div>';

        // ---- Botão Salvar (IPBX + Linha Fixa) — fora dos forms ----
        echo '<div class="text-end mt-3 mb-3">';
        echo '<button type="button" id="nm-save-all" class="btn btn-primary"'
            . ' data-action-url="' . $h($action) . '">'
            . '<i class="ti ti-device-floppy"></i> ' . __('Salvar', 'newmanagement') . '</button>';
        echo '</div>';

        echo '</div>'; // .nm-ipbx-tab

        $this->renderJS($csrf, $action);
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
            echo $this->extRow((int)$row['id'], $row, $companies_id, $csrf, $action);
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
            . ' data-csrf="' . $csrf . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar Ramal','newmanagement') . '</button>';
        echo '</div>';
        echo '</form>';
    }

    private function extRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
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
            . ' data-csrf="' . $csrf . '"'
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
            echo $this->devRow((int)$row['id'], $row, $companies_id, $csrf, $action);
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
            . ' data-csrf="' . $csrf . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar Dispositivo','newmanagement') . '</button>';
        echo '</div>';
        echo '</form>';
    }

    private function devRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
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
            . ' data-csrf="' . $csrf . '"'
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
            echo $this->netRow((int)$row['id'], $row, $companies_id, $csrf, $action);
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
            . ' data-csrf="' . $csrf . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar Rede','newmanagement') . '</button>';
        echo '</div>';
    }

    private function netRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
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
            . ' data-csrf="' . $csrf . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
            . ' data-confirm="' . __('Remover rede?','newmanagement') . '">'
            . '<i class="ti ti-trash"></i></button></td></tr>';
    }

    // ======================================================================
    // Linha Fixa
    // ======================================================================
    private function renderLines(int $ipbx_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;
        $line_id = 0;
        $f = ['pilot_number' => '', 'ddr_count' => '', 'status' => 1, 'operator' => '', 'channels' => '', 'proxy_ip' => '', 'line_type' => '', 'audio_ip' => '', 'proxy_port' => '', 'portability_date' => '', 'previous_operator' => '', 'activation_date' => '', 'expiration_date' => '', 'comment' => ''];

        if ($ipbx_id > 0) {
            foreach ($DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_lines', 'WHERE' => ['ipbx_id' => $ipbx_id], 'LIMIT' => 1]) as $row) {
                $line_id = (int)$row['id'];
                foreach (array_keys($f) as $k) { if (isset($row[$k])) $f[$k] = $row[$k]; }
            }
        }

        $v = fn($k) => htmlspecialchars((string)($f[$k] ?? ''), ENT_QUOTES);
        $form_action = $line_id > 0 ? 'update_line' : 'add_line';
        $status_opts = [1 => __('Ativo','newmanagement'), 2 => __('Cancelado','newmanagement')];

        // FIX: sem method/action — submit é 100% via fetch() no JS.
        // Isso impede que o Chrome rastreie este form como candidato a
        // salvar senha e dispare o aviso sobre múltiplos forms.
        echo '<form id="nm-lines-form" autocomplete="off" onsubmit="return false;">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="' . $form_action . '">';
        echo '<input type="hidden" name="id" value="' . $line_id . '">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<table class="tab_cadre_fixe">';

        echo '<tr class="tab_bg_1"><td>' . __('Número Piloto','newmanagement') . '</td><td><input type="text" name="pilot_number" autocomplete="off" value="' . $v('pilot_number') . '" class="form-control" placeholder="Ex: 1131000000"></td>';
        echo '<td>' . __('Quantidade de DDR','newmanagement') . '</td><td><input type="number" name="ddr_count" autocomplete="off" value="' . $v('ddr_count') . '" class="form-control" min="0" placeholder="0"></td>';
        echo '<td>' . __('Status','newmanagement') . '</td><td><select name="status" class="form-select">';
        foreach ($status_opts as $val => $lbl) { echo '<option value="' . $val . '"' . ((int)$f['status'] === $val ? ' selected' : '') . '>' . $lbl . '</option>'; }
        echo '</select></td></tr>';

        echo '<tr class="tab_bg_1"><td>' . __('Operadora','newmanagement') . '</td><td><input type="text" name="operator" autocomplete="off" value="' . $v('operator') . '" class="form-control" placeholder="Ex: Vivo"></td>';
        echo '<td>' . __('Quantidade de Canais','newmanagement') . '</td><td><input type="number" name="channels" autocomplete="off" value="' . $v('channels') . '" class="form-control" min="0" placeholder="0"></td>';
        echo '<td>' . __('IP Proxy','newmanagement') . '</td><td><input type="text" name="proxy_ip" autocomplete="off" value="' . $v('proxy_ip') . '" class="form-control" placeholder="Ex: 200.x.x.x"></td></tr>';

        echo '<tr class="tab_bg_1"><td>' . __('Tipo','newmanagement') . '</td><td><input type="text" name="line_type" autocomplete="off" value="' . $v('line_type') . '" class="form-control" placeholder="Ex: SIP, E1"></td>';
        echo '<td>' . __('IP Tráfego Áudio','newmanagement') . '</td><td><input type="text" name="audio_ip" autocomplete="off" value="' . $v('audio_ip') . '" class="form-control" placeholder="Ex: 200.x.x.x"></td>';
        echo '<td>' . __('Porta Proxy','newmanagement') . '</td><td><input type="text" name="proxy_port" autocomplete="off" value="' . $v('proxy_port') . '" class="form-control" placeholder="Ex: 5060"></td></tr>';

        echo '<tr class="tab_bg_1"><td>' . __('Data Portabilidade','newmanagement') . '</td><td><input type="date" name="portability_date" autocomplete="off" value="' . $v('portability_date') . '" class="form-control"></td>';
        echo '<td>' . __('Operadora Anterior','newmanagement') . '</td><td><input type="text" name="previous_operator" autocomplete="off" value="' . $v('previous_operator') . '" class="form-control" placeholder="Ex: Claro"></td>';
        echo '<td colspan="2"></td></tr>';

        echo '<tr class="tab_bg_1"><td>' . __('Data de Ativação','newmanagement') . '</td><td><input type="date" name="activation_date" autocomplete="off" value="' . $v('activation_date') . '" class="form-control"></td>';
        echo '<td>' . __('Data de Vencimento','newmanagement') . '</td><td><input type="date" name="expiration_date" autocomplete="off" value="' . $v('expiration_date') . '" class="form-control"></td>';
        echo '<td colspan="2"></td></tr>';

        echo '<tr class="tab_bg_1"><td>' . __('Comentário','newmanagement') . '</td><td colspan="5"><textarea name="comment" class="form-control" rows="2">' . $v('comment') . '</textarea></td></tr>';
        echo '</table></form>';
    }

    // ======================================================================
    // JavaScript centralizado
    // ======================================================================
    private function renderJS(string $csrf, string $action): void
    {
        $actionEscaped = htmlspecialchars($action, ENT_QUOTES);
        echo <<<HTML
<script>
(function(){
  'use strict';

  // URL do endpoint — lida do data-action-url do botão salvar
  // para não depender de method/action no <form>.
  var NM_ACTION_URL = '{$actionEscaped}';

  // -----------------------------------------------------------------------
  // CSRF: envia o token no header X-Glpi-Csrf-Token (GLPI 11 / Symfony)
  // e também no body para retrocompatibilidade.
  // -----------------------------------------------------------------------
  function nmPost(url, data) {
    var fd = new FormData();
    Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });

    var token = data['_glpi_csrf_token'] || '';
    if (!token) {
      var meta = document.querySelector('meta[name="glpi-csrf-token"]');
      if (meta) token = meta.getAttribute('content');
    }

    return fetch(url, {
      method: 'POST',
      headers: { 'X-Glpi-Csrf-Token': token },
      body: fd
    });
  }

  function getCsrf(btn) { return btn.dataset.csrf || ''; }
  function getUrl(btn)  { return btn.dataset.url  || NM_ACTION_URL; }

  // -----------------------------------------------------------------------
  // Atualiza ipbx_id em todos os botões de sub-itens após gravar IPBX novo
  // -----------------------------------------------------------------------
  function nmUpdateIpbxId(newId) {
    ['#nm-ext-add-btn','#nm-dev-add-btn','#nm-net-add-btn'].forEach(function(sel){
      var el = document.querySelector(sel);
      if (el) el.dataset.ipbxId = newId;
    });
    var hiddenLines = document.querySelector('#nm-lines-form input[name="ipbx_id"]');
    if (hiddenLines) hiddenLines.value = newId;

    var hiddenAction = document.querySelector('#nm-ipbx-form input[name="action"]');
    if (hiddenAction) hiddenAction.value = 'update_ipbx';
    var hiddenId = document.querySelector('#nm-ipbx-form input[name="id"]');
    if (hiddenId) hiddenId.value = newId;
  }

  // -----------------------------------------------------------------------
  // Botão Salvar: grava IPBX via fetch → depois grava Linha Fixa via fetch
  // (os forms NÃO têm method/action — submit feito 100% aqui)
  // -----------------------------------------------------------------------
  document.addEventListener('click', function(e){
    if (!e.target.closest('#nm-save-all')) return;
    e.preventDefault();

    var ipbxForm  = document.getElementById('nm-ipbx-form');
    var linesForm = document.getElementById('nm-lines-form');
    if (!ipbxForm || !linesForm) return;

    var fd    = new FormData(ipbxForm);
    var token = fd.get('_glpi_csrf_token') || '';
    var meta  = document.querySelector('meta[name="glpi-csrf-token"]');
    if (!token && meta) token = meta.getAttribute('content');

    fetch(NM_ACTION_URL, {
      method: 'POST',
      headers: { 'X-Glpi-Csrf-Token': token },
      body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(json){
      if (json && json.id) {
        nmUpdateIpbxId(json.id);
        var hl = linesForm.querySelector('input[name="ipbx_id"]');
        if (hl) hl.value = json.id;
      }

      var lfd  = new FormData(linesForm);
      var ltok = lfd.get('_glpi_csrf_token') || token;
      fetch(NM_ACTION_URL, {
        method: 'POST',
        headers: { 'X-Glpi-Csrf-Token': ltok },
        body: lfd
      })
      .then(function(lr){ return lr.json(); })
      .then(function(lj){
        if (lj && lj.id) {
          var la = linesForm.querySelector('input[name="action"]');
          if (la) la.value = 'update_line';
          var li = linesForm.querySelector('input[name="id"]');
          if (li) li.value = lj.id;
        }
        var btn = document.getElementById('nm-save-all');
        if (btn) {
          btn.classList.add('btn-success');
          btn.classList.remove('btn-primary');
          setTimeout(function(){
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
          }, 2000);
        }
      })
      .catch(function(){ alert('Erro ao salvar Linha Fixa.'); });
    })
    .catch(function(){ alert('Erro ao salvar IPBX.'); });
  });

  // -----------------------------------------------------------------------
  // Deletar (qualquer tabela)
  // -----------------------------------------------------------------------
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.nm-del-btn');
    if (!btn) return;
    if (!confirm(btn.dataset.confirm || 'Confirmar?')) return;
    nmPost(getUrl(btn), {
      '_glpi_csrf_token': getCsrf(btn),
      action: btn.dataset.action,
      id: btn.dataset.id,
      companies_id: btn.dataset.companiesId
    }).then(function(r){ return r.json(); })
      .then(function(json){
        if (json && json.success) {
          var row = document.getElementById(btn.dataset.row);
          if (row) row.remove();
        } else {
          alert('Erro ao remover.');
        }
      }).catch(function(){ alert('Erro ao remover.'); });
  });

  // -----------------------------------------------------------------------
  // Valida ipbx_id antes de adicionar sub-item
  // -----------------------------------------------------------------------
  function checkIpbxSaved(btn) {
    var id = parseInt(btn.dataset.ipbxId || '0', 10);
    if (id <= 0) {
      alert('Salve o Servidor IPBX primeiro antes de adicionar sub-itens.');
      return false;
    }
    return true;
  }

  // -----------------------------------------------------------------------
  // Adicionar Ramal
  // -----------------------------------------------------------------------
  document.addEventListener('click', function(e){
    var btn = e.target.closest('#nm-ext-add-btn');
    if (!btn) return;
    if (!checkIpbxSaved(btn)) return;
    var data = {
      '_glpi_csrf_token': getCsrf(btn),
      action: btn.dataset.action,
      ipbx_id: btn.dataset.ipbxId,
      companies_id: btn.dataset.companiesId
    };
    ['number','password','device_ip','user_name','records_calls','department'].forEach(function(f){
      var el = document.getElementById('nm-ext-' + f);
      if (el) data[f] = el.value;
    });
    nmPost(getUrl(btn), data)
      .then(function(r){ return r.json(); })
      .then(function(json){
        if (json && json.success && json.html) {
          document.getElementById('nm-ext-tbody').insertAdjacentHTML('beforeend', json.html);
          ['number','password','device_ip','user_name','department'].forEach(function(f){
            var el = document.getElementById('nm-ext-' + f); if(el) el.value='';
          });
          var sel = document.getElementById('nm-ext-records_calls'); if(sel) sel.value='0';
        } else {
          alert(json && json.error ? json.error : 'Erro ao adicionar ramal.');
        }
      }).catch(function(){ alert('Erro ao adicionar ramal.'); });
  });

  // -----------------------------------------------------------------------
  // Adicionar Dispositivo
  // -----------------------------------------------------------------------
  document.addEventListener('click', function(e){
    var btn = e.target.closest('#nm-dev-add-btn');
    if (!btn) return;
    if (!checkIpbxSaved(btn)) return;
    var data = {
      '_glpi_csrf_token': getCsrf(btn),
      action: btn.dataset.action,
      ipbx_id: btn.dataset.ipbxId,
      companies_id: btn.dataset.companiesId
    };
    ['device_type','ip_address','password'].forEach(function(f){
      var el = document.getElementById('nm-dev-' + f); if(el) data[f] = el.value;
    });
    nmPost(getUrl(btn), data)
      .then(function(r){ return r.json(); })
      .then(function(json){
        if (json && json.success && json.html) {
          document.getElementById('nm-dev-tbody').insertAdjacentHTML('beforeend', json.html);
          ['device_type','ip_address','password'].forEach(function(f){
            var el = document.getElementById('nm-dev-' + f); if(el) el.value='';
          });
        } else {
          alert(json && json.error ? json.error : 'Erro ao adicionar dispositivo.');
        }
      }).catch(function(){ alert('Erro ao adicionar dispositivo.'); });
  });

  // -----------------------------------------------------------------------
  // Adicionar Rede
  // -----------------------------------------------------------------------
  document.addEventListener('click', function(e){
    var btn = e.target.closest('#nm-net-add-btn');
    if (!btn) return;
    if (!checkIpbxSaved(btn)) return;
    var data = {
      '_glpi_csrf_token': getCsrf(btn),
      action: btn.dataset.action,
      ipbx_id: btn.dataset.ipbxId,
      companies_id: btn.dataset.companiesId
    };
    ['ip_network','netmask','gateway','dns_primary','dns_secondary'].forEach(function(f){
      var el = document.getElementById('nm-net-' + f); if(el) data[f] = el.value;
    });
    nmPost(getUrl(btn), data)
      .then(function(r){ return r.json(); })
      .then(function(json){
        if (json && json.success && json.html) {
          document.getElementById('nm-net-tbody').insertAdjacentHTML('beforeend', json.html);
          ['ip_network','netmask','gateway','dns_primary','dns_secondary'].forEach(function(f){
            var el = document.getElementById('nm-net-' + f); if(el) el.value='';
          });
        } else {
          alert(json && json.error ? json.error : 'Erro ao adicionar rede.');
        }
      }).catch(function(){ alert('Erro ao adicionar rede.'); });
  });

  // -----------------------------------------------------------------------
  // Mostrar/Ocultar senha
  // -----------------------------------------------------------------------
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.nm-btn-eye');
    if (!btn) return;
    var inp = document.getElementById(btn.dataset.target);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    var icon = btn.querySelector('i');
    if (icon) { icon.className = inp.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off'; }
  });

})();
</script>
HTML;
    }
}
