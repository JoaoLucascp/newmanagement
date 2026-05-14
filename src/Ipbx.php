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

    // Chave estrangeira para Company
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

    // ------------------------------------------------------------------
    // Integração como aba dentro da ficha de Company
    // ------------------------------------------------------------------
    public static function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
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

    // ------------------------------------------------------------------
    // Renderiza toda a aba dentro de Company
    // ------------------------------------------------------------------
    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        // Busca ou cria registro IPBX para esta empresa
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

        $csrf   = \Session::getNewCSRFToken();
        $action = (defined('GLPI_ROOT') ? \Plugin::getWebDir('newmanagement') : '') . '/ajax/ipbx_sub.php';

        echo '<div class="nm-ipbx-tab">';

        // --------------------------------------------------
        // Formulário principal do servidor
        // --------------------------------------------------
        echo '<form method="post" action="' . $action . '" id="nm-ipbx-form">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="' . ($ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx') . '">';
        echo '<input type="hidden" name="id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';

        echo '<table class="tab_cadre_fixe nm-table">';
        echo '<tr><th colspan="4">' . __('Servidor IPBX', 'newmanagement') . '</th></tr>';

        // Linha 1: Modelo | Versão
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="model" value="' . htmlspecialchars($fields['model'] ?? '', ENT_QUOTES) . '" class="form-control"></td>';
        echo '<td>' . __('Versão', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="server_version" value="' . htmlspecialchars($fields['server_version'] ?? '', ENT_QUOTES) . '" class="form-control"></td>';
        echo '</tr>';

        // Linha 2: IP Local | IP Externo
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('IP Local', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="ip_local" value="' . htmlspecialchars($fields['ip_local'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="192.168.1.1"></td>';
        echo '<td>' . __('IP Externo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="ip_external" value="' . htmlspecialchars($fields['ip_external'] ?? '', ENT_QUOTES) . '" class="form-control" placeholder="201.x.x.x"></td>';
        echo '</tr>';

        // Linha 3: Porta Web | Senha Web
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

        // Linha 4: Porta SSH | Senha SSH
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

        // Linha 5: Comentário
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="2">' . htmlspecialchars($fields['comment'] ?? '', ENT_QUOTES) . '</textarea></td>';
        echo '</tr>';

        echo '<tr><td colspan="4" style="text-align:right;padding:8px">';
        echo '<button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar', 'newmanagement') . '</button>';
        echo '</td></tr>';
        echo '</table>';
        echo '</form>';

        // --------------------------------------------------
        // Sub-seção: Ramais
        // --------------------------------------------------
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header">';
        echo '<h4><i class="ti ti-phone-call"></i> ' . __('Ramais', 'newmanagement') . '</h4>';
        echo '</div>';
        echo '<div class="nm-subsection-body">';
        $this->renderExtensionsTable($ipbx_id, $companies_id, $csrf, $action);
        echo '</div></div>';

        // --------------------------------------------------
        // Sub-seção: Dispositivos
        // --------------------------------------------------
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header">';
        echo '<h4><i class="ti ti-device-desktop"></i> ' . __('Dispositivos', 'newmanagement') . '</h4>';
        echo '</div>';
        echo '<div class="nm-subsection-body">';
        $this->renderDevicesTable($ipbx_id, $companies_id, $csrf, $action);
        echo '</div></div>';

        // --------------------------------------------------
        // Sub-seção: Rede da Empresa
        // --------------------------------------------------
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header">';
        echo '<h4><i class="ti ti-network"></i> ' . __('Rede da Empresa', 'newmanagement') . '</h4>';
        echo '</div>';
        echo '<div class="nm-subsection-body">';
        $this->renderNetworkTable($ipbx_id, $companies_id, $csrf, $action);
        echo '</div></div>';

        // --------------------------------------------------
        // Sub-seção: Linha Fixa
        // --------------------------------------------------
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header">';
        echo '<h4><i class="ti ti-phone"></i> ' . __('Linha Fixa', 'newmanagement') . '</h4>';
        echo '</div>';
        echo '<div class="nm-subsection-body">';
        $this->renderLinesTable($ipbx_id, $companies_id, $csrf, $action);
        echo '</div></div>';

        echo '</div>'; // .nm-ipbx-tab
    }

    // ------------------------------------------------------------------
    // Ramais
    // ------------------------------------------------------------------
    private function renderExtensionsTable(int $ipbx_id, int $companies_id, string $csrf, string $action): void
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
            echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover ramal?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        // Formulário de adição
        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_extension">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
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
    private function renderDevicesTable(int $ipbx_id, int $companies_id, string $csrf, string $action): void
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
            echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
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
        echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
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
    private function renderNetworkTable(int $ipbx_id, int $companies_id, string $csrf, string $action): void
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
            echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
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
        echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
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
    // Linha Fixa
    // ------------------------------------------------------------------
    private function renderLinesTable(int $ipbx_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;

        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_ipbx_lines', 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'pilot_number ASC'])
            : [];

        $status_labels = [1 => __('Ativo', 'newmanagement'), 2 => __('Cancelado', 'newmanagement')];

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<tr class="noHover">';
        echo '<th>' . __('Piloto', 'newmanagement') . '</th>';
        echo '<th>' . __('Tipo', 'newmanagement') . '</th>';
        echo '<th>' . __('Operadora', 'newmanagement') . '</th>';
        echo '<th>' . __('Canais', 'newmanagement') . '</th>';
        echo '<th>' . __('DDR', 'newmanagement') . '</th>';
        echo '<th>' . __('IP Proxy', 'newmanagement') . '</th>';
        echo '<th>' . __('Porta Proxy', 'newmanagement') . '</th>';
        echo '<th>' . __('IP Áudio', 'newmanagement') . '</th>';
        echo '<th>' . __('Portabilidade', 'newmanagement') . '</th>';
        echo '<th>' . __('Op. Anterior', 'newmanagement') . '</th>';
        echo '<th>' . __('Ativação', 'newmanagement') . '</th>';
        echo '<th>' . __('Vencimento', 'newmanagement') . '</th>';
        echo '<th>' . __('Status', 'newmanagement') . '</th>';
        echo '<th>' . __('Ação', 'newmanagement') . '</th>';
        echo '</tr>';

        foreach ($rows as $row) {
            $rid    = (int)$row['id'];
            $status = (int)($row['status'] ?? 1);
            $badge  = $status === 1 ? 'badge bg-success' : 'badge bg-danger';
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['pilot_number'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['line_type'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['operator'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . (int)($row['channels'] ?? 0) . '</td>';
            echo '<td>' . (int)($row['ddr_count'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['proxy_ip'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['proxy_port'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['audio_ip'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['portability_date'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['previous_operator'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['activation_date'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['expiration_date'] ?? '', ENT_QUOTES) . '</td>';
            echo '<td><span class="' . $badge . '">' . ($status_labels[$status] ?? '') . '</span></td>';
            echo '<td>';
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_line">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover linha?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        // Formulário de adição — linha de inputs
        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_line">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) . '">';
        echo '<td><input type="text" name="pilot_number" class="form-control form-control-sm" placeholder="Piloto"></td>';
        echo '<td><input type="text" name="line_type" class="form-control form-control-sm" placeholder="Tipo"></td>';
        echo '<td><input type="text" name="operator" class="form-control form-control-sm" placeholder="Operadora"></td>';
        echo '<td><input type="number" name="channels" class="form-control form-control-sm" placeholder="0" min="0"></td>';
        echo '<td><input type="number" name="ddr_count" class="form-control form-control-sm" placeholder="0" min="0"></td>';
        echo '<td><input type="text" name="proxy_ip" class="form-control form-control-sm" placeholder="IP"></td>';
        echo '<td><input type="text" name="proxy_port" class="form-control form-control-sm" placeholder="Porta"></td>';
        echo '<td><input type="text" name="audio_ip" class="form-control form-control-sm" placeholder="IP"></td>';
        echo '<td><input type="date" name="portability_date" class="form-control form-control-sm"></td>';
        echo '<td><input type="text" name="previous_operator" class="form-control form-control-sm" placeholder="Op. Anterior"></td>';
        echo '<td><input type="date" name="activation_date" class="form-control form-control-sm"></td>';
        echo '<td><input type="date" name="expiration_date" class="form-control form-control-sm"></td>';
        echo '<td><select name="status" class="form-select form-select-sm"><option value="1">' . __('Ativo', 'newmanagement') . '</option><option value="2">' . __('Cancelado', 'newmanagement') . '</option></select></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar Linha Fixa', 'newmanagement') . '</button></td>';
        echo '</form></tr>';

        echo '</table>';
    }
}
