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

        echo '<form method="post" action="' . $action . '" id="nm-ipbx-form">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="' . ($ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx') . '">';
        echo '<input type="hidden" name="id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';

        echo '<table class="tab_cadre_fixe nm-table">';
        echo '<tr><th colspan="4">' . __('Servidor IPBX', 'newmanagement') . '</th></tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="model" value="' . htmlspecialchars($fields['model'] ?? '', ENT_QUOTES) . '" class="form-control"></td>';
        echo '<td>' . __('Versão', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="server_version" value="' . htmlspecialchars($fields['server_version'] ?? '', ENT_QUOTES) . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('IP Local', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="ip_local" value="' . htmlspecialchars($fields['ip_local'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="192.168.1.1"></td>';
        echo '<td>' . __('IP Externo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="ip_external" value="' . htmlspecialchars($fields['ip_external'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="201.x.x.x"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Porta Web', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="web_port" value="' . htmlspecialchars($fields['web_port'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="80"></td>';
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
        echo '<td><input type="text" name="ssh_port" value="' . htmlspecialchars($fields['ssh_port'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="22"></td>';
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

        echo '<tr><td colspan="4" style="text-align:right;padding:8px">';
        echo '<button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar', 'newmanagement') . '</button>';
        echo '</td></tr>';
        echo '</table>';
        echo '</form>';

        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-phone-call"></i> ' . __('Ramais', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderExtensionsTable($ipbx_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-device-desktop"></i> ' . __('Dispositivos', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderDevicesTable($ipbx_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-network"></i> ' . __('Rede da Empresa', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderNetworkTable($ipbx_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-phone"></i> ' . __('Linha Fixa', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderLinesTable($ipbx_id, $companies_id, $csrf, $action, $redirect);
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
        echo '<tr class="noHover"><th>' . __('Número', 'newmanagement') . '</th><th>' . __('Senha', 'newmanagement') . '</th><th>' . __('IP Aparelho', 'newmanagement') . '</th><th>' . __('Usuário', 'newmanagement') . '</th><th>' . __('Grava?', 'newmanagement') . '</th><th>' . __('Departamento', 'newmanagement') . '</th><th>' . __('Ação', 'newmanagement') . '</th></tr>';

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
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_extension">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover ramal?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_extension">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<td><input type="text" name="number" class="form-control form-control-sm" placeholder="' . __('Número', 'newmanagement') . '"></td>';
        echo '<td><input type="password" name="password" class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" autocomplete="new-password"></td>';
        echo '<td><input type="text" name="device_ip" class="form-control form-control-sm" placeholder="IP"></td>';
        echo '<td><input type="text" name="user_name" class="form-control form-control-sm" placeholder="' . __('Usuário', 'newmanagement') . '"></td>';
        echo '<td><select name="records_calls" class="form-select form-select-sm"><option value="0">' . __('Não', 'newmanagement') . '</option><option value="1">' . __('Sim', 'newmanagement') . '</option></select></td>';
        echo '<td><input type="text" name="department" class="form-control form-control-sm" placeholder="' . __('Departamento', 'newmanagement') . '"></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar Ramal', 'newmanagement') . '</button></td>';
        echo '</form></tr>';
        echo '</table>';
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
        echo '<tr class="noHover"><th>' . __('Tipo', 'newmanagement') . '</th><th>' . __('IP', 'newmanagement') . '</th><th>' . __('Senha', 'newmanagement') . '</th><th>' . __('Ação', 'newmanagement') . '</th></tr>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['device_type'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['ip_address'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>••••••</td>';
            echo '<td>';
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_device">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover dispositivo?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_device">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<td><input type="text" name="device_type" class="form-control form-control-sm" placeholder="' . __('Tipo', 'newmanagement') . '"></td>';
        echo '<td><input type="text" name="ip_address" class="form-control form-control-sm" placeholder="IP"></td>';
        echo '<td><input type="password" name="password" class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" autocomplete="new-password"></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar Dispositivo', 'newmanagement') . '</button></td>';
        echo '</form></tr>';
        echo '</table>';
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
        echo '<tr class="noHover"><th>' . __('IP Rede', 'newmanagement') . '</th><th>' . __('Máscara', 'newmanagement') . '</th><th>' . __('Gateway', 'newmanagement') . '</th><th>' . __('DNS Primário', 'newmanagement') . '</th><th>' . __('DNS Secundário', 'newmanagement') . '</th><th>' . __('Ação', 'newmanagement') . '</th></tr>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['ip_network'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['netmask'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['gateway'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['dns_primary'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['dns_secondary'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_network">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover rede?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_network">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<td><input type="text" name="ip_network" class="form-control form-control-sm" placeholder="192.168.1.0"></td>';
        echo '<td><input type="text" name="netmask" class="form-control form-control-sm" placeholder="255.255.255.0"></td>';
        echo '<td><input type="text" name="gateway" class="form-control form-control-sm" placeholder="192.168.1.1"></td>';
        echo '<td><input type="text" name="dns_primary" class="form-control form-control-sm" placeholder="8.8.8.8"></td>';
        echo '<td><input type="text" name="dns_secondary" class="form-control form-control-sm" placeholder="8.8.4.4"></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar Rede', 'newmanagement') . '</button></td>';
        echo '</form></tr>';
        echo '</table>';
    }

    // ------------------------------------------------------------------
    // Linha Fixa — layout compacto
    // Colunas visíveis: Piloto | Tipo | Operadora | Canais | Status | Ação
    // Demais campos ficam numa linha de detalhes expansível (toggle)
    // ------------------------------------------------------------------
    private function renderLinesTable(int $ipbx_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_lines', 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'pilot_number ASC'])
            : [];

        $status_labels = [1 => __('Ativo', 'newmanagement'), 2 => __('Cancelado', 'newmanagement')];

        echo '<table class="tab_cadre_fixehov nm-table" id="nm-lines-table">';

        // Cabeçalho compacto
        echo '<tr class="noHover">';
        echo '<th style="width:20px"></th>'; // seta de expand
        echo '<th>' . __('Piloto', 'newmanagement') . '</th>';
        echo '<th>' . __('Tipo', 'newmanagement') . '</th>';
        echo '<th>' . __('Operadora', 'newmanagement') . '</th>';
        echo '<th>' . __('Canais', 'newmanagement') . '</th>';
        echo '<th>' . __('Status', 'newmanagement') . '</th>';
        echo '<th>' . __('Ação', 'newmanagement') . '</th>';
        echo '</tr>';

        foreach ($rows as $row) {
            $rid    = (int)$row['id'];
            $status = (int)($row['status'] ?? 1);
            $badge  = $status === 1 ? 'badge bg-success' : 'badge bg-danger';
            $uid    = 'nm-line-detail-' . $rid;

            // Linha principal
            echo '<tr class="tab_bg_1">';
            echo '<td style="text-align:center;cursor:pointer" onclick="nmToggleLine(\'' . $uid . '\', this)" title="' . __('Ver detalhes', 'newmanagement') . '">';
            echo '<i class="ti ti-chevron-right nm-chevron"></i>';
            echo '</td>';
            echo '<td><strong>' . htmlspecialchars($row['pilot_number'] ?? '', ENT_QUOTES) . '</strong></td>';
            echo '<td>' . htmlspecialchars($row['line_type'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['operator'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . (int)($row['channels'] ?? 0) . '</td>';
            echo '<td><span class="' . $badge . '">' . ($status_labels[$status] ?? '') . '</span></td>';
            echo '<td>';
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_line">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover linha?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';

            // Linha de detalhes (oculta por padrão)
            echo '<tr id="' . $uid . '" class="nm-line-detail" style="display:none">';
            echo '<td></td>';
            echo '<td colspan="6">';
            echo '<div class="nm-line-detail-grid">';

            $detail_fields = [
                __('DDR', 'newmanagement')              => (int)($row['ddr_count'] ?? 0),
                __('IP Proxy', 'newmanagement')         => htmlspecialchars($row['proxy_ip'] ?? '', ENT_QUOTES),
                __('Porta Proxy', 'newmanagement')      => htmlspecialchars($row['proxy_port'] ?? '', ENT_QUOTES),
                __('IP Áudio', 'newmanagement')         => htmlspecialchars($row['audio_ip'] ?? '', ENT_QUOTES),
                __('Portabilidade', 'newmanagement')    => htmlspecialchars($row['portability_date'] ?? '', ENT_QUOTES),
                __('Op. Anterior', 'newmanagement')     => htmlspecialchars($row['previous_operator'] ?? '', ENT_QUOTES),
                __('Ativação', 'newmanagement')       => htmlspecialchars($row['activation_date'] ?? '', ENT_QUOTES),
                __('Vencimento', 'newmanagement')       => htmlspecialchars($row['expiration_date'] ?? '', ENT_QUOTES),
            ];

            foreach ($detail_fields as $label => $value) {
                echo '<div class="nm-detail-item"><span class="nm-detail-label">' . $label . '</span><span class="nm-detail-value">' . ($value !== '' && $value !== 0 ? $value : '&mdash;') . '</span></div>';
            }

            echo '</div>';
            echo '</td></tr>';
        }

        // Linha de adição compacta
        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_line">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';

        // Campos ocultos que ainda precisam ser enviados
        echo '<input type="hidden" name="ddr_count" value="0">';
        echo '<input type="hidden" name="proxy_ip" value="">';
        echo '<input type="hidden" name="proxy_port" value="">';
        echo '<input type="hidden" name="audio_ip" value="">';
        echo '<input type="hidden" name="portability_date" value="">';
        echo '<input type="hidden" name="previous_operator" value="">';
        echo '<input type="hidden" name="activation_date" value="">';
        echo '<input type="hidden" name="expiration_date" value="">';

        echo '<td></td>'; // coluna da seta
        echo '<td><input type="text" name="pilot_number" class="form-control form-control-sm" placeholder="' . __('Piloto', 'newmanagement') . '"></td>';
        echo '<td><input type="text" name="line_type" class="form-control form-control-sm" placeholder="' . __('Tipo', 'newmanagement') . '"></td>';
        echo '<td><input type="text" name="operator" class="form-control form-control-sm" placeholder="' . __('Operadora', 'newmanagement') . '"></td>';
        echo '<td><input type="number" name="channels" class="form-control form-control-sm" placeholder="0" min="0"></td>';
        echo '<td><select name="status" class="form-select form-select-sm"><option value="1">' . __('Ativo', 'newmanagement') . '</option><option value="2">' . __('Cancelado', 'newmanagement') . '</option></select></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar', 'newmanagement') . '</button></td>';
        echo '</form></tr>';

        echo '</table>';

        // CSS e JS inline para o toggle
        echo '
<style>
.nm-chevron { transition: transform .2s ease; font-size: 14px; color: #6c757d; }
.nm-chevron.open { transform: rotate(90deg); }
.nm-line-detail td { background: var(--tblr-bg-surface, #f8f9fa) !important; padding: 10px 8px !important; }
.nm-line-detail-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 20px;
    padding: 4px 0;
}
.nm-detail-item {
    display: flex;
    flex-direction: column;
    min-width: 120px;
}
.nm-detail-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: #6c757d;
    letter-spacing: .03em;
}
.nm-detail-value {
    font-size: 13px;
    color: #212529;
}
</style>
<script>
function nmToggleLine(uid, cell) {
    var row = document.getElementById(uid);
    var icon = cell.querySelector(".nm-chevron");
    if (!row) return;
    if (row.style.display === "none") {
        row.style.display = "";
        if (icon) icon.classList.add("open");
    } else {
        row.style.display = "none";
        if (icon) icon.classList.remove("open");
    }
}
</script>';
    }
}
