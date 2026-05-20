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

    // Constantes para nomes de tabelas filhas
    const TABLE_EXTENSIONS = 'glpi_plugin_newmanagement_ipbx_extensions';
    const TABLE_DEVICES    = 'glpi_plugin_newmanagement_ipbx_devices';
    const TABLE_NETWORK    = 'glpi_plugin_newmanagement_ipbx_network';

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
        // [FIX] Verifica direito de leitura antes de renderizar a aba
        if (!\Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        if ($item instanceof Company) {
            (new self())->showTabForCompany((int) $item->getID());
        }
        return true;
    }

    // ======================================================================
    // Buscas nativas GLPI
    // ======================================================================
    public function rawSearchOptions(): array
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'         => 1,
            'table'      => self::getTable(),
            'field'      => 'model',
            'name'       => __('Modelo', 'newmanagement'),
            'searchtype' => 'contains',
            'datatype'   => 'string',
        ];

        $tab[] = [
            'id'         => 2,
            'table'      => self::getTable(),
            'field'      => 'ip_local',
            'name'       => __('IP Local', 'newmanagement'),
            'searchtype' => 'contains',
            'datatype'   => 'string',
        ];

        $tab[] = [
            'id'         => 3,
            'table'      => self::getTable(),
            'field'      => 'ip_external',
            'name'       => __('IP Externo', 'newmanagement'),
            'searchtype' => 'contains',
            'datatype'   => 'string',
        ];

        $tab[] = [
            'id'         => 4,
            'table'      => self::getTable(),
            'field'      => 'server_version',
            'name'       => __('Versão', 'newmanagement'),
            'searchtype' => 'contains',
            'datatype'   => 'string',
        ];

        return $tab;
    }

    // ======================================================================
    // Tab principal
    // ======================================================================
    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        // [FIX] Verificação de direito de leitura (segunda camada de segurança)
        if (!\Session::haveRight(self::$rightname, READ)) {
            echo '<div class="alert alert-warning">' . __('Acesso negado.', 'newmanagement') . '</div>';
            return;
        }

        // [FIX] Flag de permissão de escrita e deleção
        $can_write  = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete = \Session::haveRight(self::$rightname, DELETE);

        $rows    = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0], 'LIMIT' => 1]);
        $ipbx_id = 0;
        $fields  = ['id' => 0, 'companies_id' => $companies_id, 'model' => '', 'server_version' => '', 'ip_local' => '', 'ip_external' => '', 'web_port' => '', 'web_password' => '', 'ssh_port' => '', 'ssh_password' => '', 'comment' => ''];

        $has_web_password = false;
        $has_ssh_password = false;

        foreach ($rows as $row) {
            $fields           = $row;
            $ipbx_id          = (int) $row['id'];
            $has_web_password = !empty($fields['web_password']);
            $has_ssh_password = !empty($fields['ssh_password']);
            // [FIX] Nunca expor senha descriptografada no HTML
            $fields['web_password'] = '';
            $fields['ssh_password'] = '';
        }

        $csrf   = \Session::getNewCSRFToken();
        $action = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
        $h      = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        $web_placeholder = $has_web_password
            ? __('(senha salva — deixe em branco para manter)', 'newmanagement')
            : __('Senha Web', 'newmanagement');
        $ssh_placeholder = $has_ssh_password
            ? __('(senha salva — deixe em branco para manter)', 'newmanagement')
            : __('Senha SSH', 'newmanagement');

        echo '<div class="nm-ipbx-tab" data-action-url="' . $h($action) . '" data-companies-id="' . $companies_id . '">';

        echo '<div id="nm-ipbx-form">';
        echo '<input type="hidden" id="nm-ipbx-csrf"         value="' . $csrf . '">';
        echo '<input type="hidden" id="nm-ipbx-action"       value="' . ($ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx') . '">';
        echo '<input type="hidden" id="nm-ipbx-id"           value="' . $ipbx_id . '">';
        echo '<input type="hidden" id="nm-ipbx-companies-id" value="' . $companies_id . '">';

        echo '<table class="tab_cadre_fixe">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-model" autocomplete="off" value="' . $h($fields['model']) . '" class="form-control"' . ($can_write ? '' : ' readonly') . '></td>';
        echo '<td>' . __('Versão', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-server_version" autocomplete="off" value="' . $h($fields['server_version']) . '" class="form-control"' . ($can_write ? '' : ' readonly') . '></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('IP Local', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-ip_local" autocomplete="off" value="' . $h($fields['ip_local']) . '" class="form-control" placeholder="192.168.1.1"' . ($can_write ? '' : ' readonly') . '></td>';
        echo '<td>' . __('IP Externo', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-ip_external" autocomplete="off" value="' . $h($fields['ip_external']) . '" class="form-control" placeholder="201.x.x.x"' . ($can_write ? '' : ' readonly') . '></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Porta Web', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-web_port" autocomplete="off" value="' . $h($fields['web_port']) . '" class="form-control" placeholder="80"' . ($can_write ? '' : ' readonly') . '></td>';
        echo '<td>' . __('Senha Web', 'newmanagement') . '</td>';
        echo '<td><div class="input-group">';
        echo '<input type="password" id="nm-web-password" class="form-control" autocomplete="new-password" value="" placeholder="' . $h($web_placeholder) . '"' . ($can_write ? '' : ' readonly') . '>';
        echo '<button type="button" class="btn btn-sm btn-icon nm-btn-eye" data-target="nm-web-password"><i class="ti ti-eye"></i></button>';
        echo '</div></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Porta SSH', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-ssh_port" autocomplete="off" value="' . $h($fields['ssh_port']) . '" class="form-control" placeholder="22"' . ($can_write ? '' : ' readonly') . '></td>';
        echo '<td>' . __('Senha SSH', 'newmanagement') . '</td>';
        echo '<td><div class="input-group">';
        echo '<input type="password" id="nm-ssh-password" class="form-control" autocomplete="new-password" value="" placeholder="' . $h($ssh_placeholder) . '"' . ($can_write ? '' : ' readonly') . '>';
        echo '<button type="button" class="btn btn-sm btn-icon nm-btn-eye" data-target="nm-ssh-password"><i class="ti ti-eye"></i></button>';
        echo '</div></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea id="nm-ipbx-comment" class="form-control" rows="2"' . ($can_write ? '' : ' readonly') . '>' . $h($fields['comment']) . '</textarea></td>';
        echo '</tr>';

        echo '</table>';
        echo '</div>'; // #nm-ipbx-form

        // ---- Ramais ----
        echo '<div class="nm-subsection mt-3">';
        echo '<div class="d-flex align-items-center mb-2">';
        echo '<i class="ti ti-phone-call me-2 fs-5 text-muted"></i>';
        echo '<span class="fw-bold text-muted text-uppercase" style="font-size:0.75rem;letter-spacing:.05em">';
        echo __('Ramais', 'newmanagement');
        echo '</span>';
        echo '<button type="button" class="btn btn-sm btn-icon ms-auto nm-toggle-section" data-target="nm-ext-tbody" aria-expanded="true" title="Recolher/Expandir">';
        echo '<i class="ti ti-chevron-up"></i>';
        echo '</button>';
        echo '</div>';
        $this->renderExtensions($ipbx_id, $companies_id, $csrf, $action, $can_write, $can_delete);
        echo '</div>';

        // ---- Dispositivos ----
        echo '<div class="nm-subsection mt-3">';
        echo '<div class="d-flex align-items-center mb-2">';
        echo '<i class="ti ti-device-desktop me-2 fs-5 text-muted"></i>';
        echo '<span class="fw-bold text-muted text-uppercase" style="font-size:0.75rem;letter-spacing:.05em">';
        echo __('Dispositivos', 'newmanagement');
        echo '</span>';
        echo '<button type="button" class="btn btn-sm btn-icon ms-auto nm-toggle-section" data-target="nm-dev-tbody" aria-expanded="true" title="Recolher/Expandir">';
        echo '<i class="ti ti-chevron-up"></i>';
        echo '</button>';
        echo '</div>';
        $this->renderDevices($ipbx_id, $companies_id, $csrf, $action, $can_write, $can_delete);
        echo '</div>';

        // ---- Rede ----
        echo '<div class="nm-subsection mt-3">';
        echo '<div class="d-flex align-items-center mb-2">';
        echo '<i class="ti ti-network me-2 fs-5 text-muted"></i>';
        echo '<span class="fw-bold text-muted text-uppercase" style="font-size:0.75rem;letter-spacing:.05em">';
        echo __('Rede da Empresa', 'newmanagement');
        echo '</span>';
        echo '<button type="button" class="btn btn-sm btn-icon ms-auto nm-toggle-section" data-target="nm-net-tbody" aria-expanded="true" title="Recolher/Expandir">';
        echo '<i class="ti ti-chevron-up"></i>';
        echo '</button>';
        echo '</div>';
        $this->renderNetwork($ipbx_id, $companies_id, $csrf, $action, $can_write, $can_delete);
        echo '</div>';

        // ---- Botão Salvar (só para quem pode escrever) ----
        if ($can_write) {
            echo '<div class="text-end mt-3 mb-3">';
            echo '<button type="button" id="nm-save-all" class="btn btn-primary"'
                . ' data-action-url="' . $h($action) . '">'
                . '<i class="ti ti-device-floppy"></i> ' . __('Salvar', 'newmanagement') . '</button>';
            echo '</div>';
        }

        echo '</div>'; // .nm-ipbx-tab
    }

    // ======================================================================
    // Ramais
    // ======================================================================
    // [FIX] Assinatura recebe $can_write e $can_delete
    private function renderExtensions(int $ipbx_id, int $companies_id, string $csrf, string $action, bool $can_write = true, bool $can_delete = true): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => self::TABLE_EXTENSIONS, 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'number ASC'])
            : [];

        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        // [FIX] Botão Adicionar: visível só para quem pode escrever E só se IPBX já existe
        $add_disabled = '';
        if (!$can_write) {
            $add_disabled = ' disabled title="' . __('Sem permissão de escrita', 'newmanagement') . '"';
        } elseif ($ipbx_id === 0) {
            $add_disabled = ' disabled title="' . __('Salve o IPBX antes de adicionar ramais', 'newmanagement') . '"';
        }

        echo '<table class="tab_cadre_fixehov" id="nm-ext-table">';
        echo '<thead>';
        echo '<tr class="headerRow noHover">';
        echo '<th>' . __('Número', 'newmanagement') . '</th>';
        echo '<th>' . __('Senha', 'newmanagement') . '</th>';
        echo '<th>' . __('IP Aparelho', 'newmanagement') . '</th>';
        echo '<th>' . __('Usuário', 'newmanagement') . '</th>';
        echo '<th>' . __('Grava?', 'newmanagement') . '</th>';
        echo '<th>' . __('Departamento', 'newmanagement') . '</th>';
        echo '<th style="text-align:right">';
        if ($can_write) {
            echo '<button type="button"'
                . ' id="nm-ext-add-btn"'
                . ' class="btn btn-sm btn-outline-secondary"'
                . ' data-action="add_extension"'
                . ' data-ipbx-id="' . $ipbx_id . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-url="' . $h($action) . '"'
                . $add_disabled . '>'
                . '<i class="ti ti-plus"></i> ' . __('Adicionar Ramal', 'newmanagement')
                . '</button>';
        }
        echo '</th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody id="nm-ext-tbody">';

        foreach ($rows as $row) {
            echo self::renderExtensionRow((int) $row['id'], $row, $companies_id, $csrf, $action, $can_delete);
        }

        if ($can_write) {
            echo '<tr class="tab_bg_2" id="nm-ext-add-row">';
            echo '<td><input type="text" id="nm-ext-number" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Número', 'newmanagement') . '"></td>';
            echo '<td><input type="password" id="nm-ext-password" class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" autocomplete="new-password"></td>';
            echo '<td><input type="text" id="nm-ext-device_ip" autocomplete="off" class="form-control form-control-sm" placeholder="IP"></td>';
            echo '<td><input type="text" id="nm-ext-user_name" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Usuário', 'newmanagement') . '"></td>';
            echo '<td><select id="nm-ext-records_calls" class="form-select form-select-sm"><option value="0">' . __('Não', 'newmanagement') . '</option><option value="1">' . __('Sim', 'newmanagement') . '</option></select></td>';
            echo '<td><input type="text" id="nm-ext-department" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Departamento', 'newmanagement') . '"></td>';
            echo '<td></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    // [FIX] Assinatura recebe $can_delete
    public static function renderExtensionRow(int $id, array $row, int $companies_id, string $csrf, string $action, bool $can_delete = true): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        $delete_btn = '';
        if ($can_delete) {
            $delete_btn = '<button type="button"'
                . ' class="btn btn-sm btn-icon nm-del-btn"'
                . ' data-action="delete_extension"'
                . ' data-id="' . $id . '"'
                . ' data-row="nm-ext-row-' . $id . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . htmlspecialchars($csrf, ENT_QUOTES) . '"'
                . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
                . ' data-confirm="' . __('Remover ramal?', 'newmanagement') . '"'
                . ' title="' . __('Remover', 'newmanagement') . '">'
                . '<i class="ti ti-trash text-danger"></i>'
                . '</button>';
        }

        return '<tr class="tab_bg_1" id="nm-ext-row-' . $id . '">'
            . '<td>' . $h($row['number']) . '</td>'
            . '<td>••••••</td>'
            . '<td>' . $h($row['device_ip']) . '</td>'
            . '<td>' . $h($row['user_name']) . '</td>'
            . '<td>' . ($row['records_calls'] ? __('Sim', 'newmanagement') : __('Não', 'newmanagement')) . '</td>'
            . '<td>' . $h($row['department']) . '</td>'
            . '<td>' . $delete_btn . '</td>'
            . '</tr>';
    }

    // ======================================================================
    // Dispositivos
    // ======================================================================
    // [FIX] Assinatura recebe $can_write e $can_delete
    private function renderDevices(int $ipbx_id, int $companies_id, string $csrf, string $action, bool $can_write = true, bool $can_delete = true): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => self::TABLE_DEVICES, 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'device_type ASC'])
            : [];

        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        $add_disabled = '';
        if (!$can_write) {
            $add_disabled = ' disabled title="' . __('Sem permissão de escrita', 'newmanagement') . '"';
        } elseif ($ipbx_id === 0) {
            $add_disabled = ' disabled title="' . __('Salve o IPBX antes de adicionar dispositivos', 'newmanagement') . '"';
        }

        echo '<table class="tab_cadre_fixehov" id="nm-dev-table">';
        echo '<thead>';
        echo '<tr class="headerRow noHover">';
        echo '<th>' . __('Tipo', 'newmanagement') . '</th>';
        echo '<th>' . __('IP', 'newmanagement') . '</th>';
        echo '<th>' . __('Login', 'newmanagement') . '</th>';
        echo '<th>' . __('Senha', 'newmanagement') . '</th>';
        echo '<th style="text-align:right">';
        if ($can_write) {
            echo '<button type="button"'
                . ' id="nm-dev-add-btn"'
                . ' class="btn btn-sm btn-outline-secondary"'
                . ' data-action="add_device"'
                . ' data-ipbx-id="' . $ipbx_id . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-url="' . $h($action) . '"'
                . $add_disabled . '>'
                . '<i class="ti ti-plus"></i> ' . __('Adicionar Dispositivo', 'newmanagement')
                . '</button>';
        }
        echo '</th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody id="nm-dev-tbody">';

        foreach ($rows as $row) {
            echo self::renderDeviceRow((int) $row['id'], $row, $companies_id, $csrf, $action, $can_delete);
        }

        if ($can_write) {
            echo '<tr class="tab_bg_2" id="nm-dev-add-row">';
            echo '<td><input type="text" id="nm-dev-device_type" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Tipo', 'newmanagement') . '"></td>';
            echo '<td><input type="text" id="nm-dev-ip_address" autocomplete="off" class="form-control form-control-sm" placeholder="IP"></td>';
            echo '<td><input type="text" id="nm-dev-login" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Login', 'newmanagement') . '"></td>';
            echo '<td><input type="password" id="nm-dev-password" class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" autocomplete="new-password"></td>';
            echo '<td></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    // [FIX] Assinatura recebe $can_delete
    public static function renderDeviceRow(int $id, array $row, int $companies_id, string $csrf, string $action, bool $can_delete = true): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        $delete_btn = '';
        if ($can_delete) {
            $delete_btn = '<button type="button"'
                . ' class="btn btn-sm btn-icon nm-del-btn"'
                . ' data-action="delete_device"'
                . ' data-id="' . $id . '"'
                . ' data-row="nm-dev-row-' . $id . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . htmlspecialchars($csrf, ENT_QUOTES) . '"'
                . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
                . ' data-confirm="' . __('Remover dispositivo?', 'newmanagement') . '"'
                . ' title="' . __('Remover', 'newmanagement') . '">'
                . '<i class="ti ti-trash text-danger"></i>'
                . '</button>';
        }

        return '<tr class="tab_bg_1" id="nm-dev-row-' . $id . '">'
            . '<td>' . $h($row['device_type']) . '</td>'
            . '<td>' . $h($row['ip_address']) . '</td>'
            . '<td>' . $h($row['login'] ?? '') . '</td>'
            . '<td>••••••</td>'
            . '<td>' . $delete_btn . '</td>'
            . '</tr>';
    }

    // ======================================================================
    // Rede da Empresa
    // ======================================================================
    // [FIX] Assinatura recebe $can_write e $can_delete
    private function renderNetwork(int $ipbx_id, int $companies_id, string $csrf, string $action, bool $can_write = true, bool $can_delete = true): void
    {
        global $DB;
        $rows = ($ipbx_id > 0)
            ? $DB->request(['FROM' => self::TABLE_NETWORK, 'WHERE' => ['ipbx_id' => $ipbx_id]])
            : [];

        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        $add_disabled = '';
        if (!$can_write) {
            $add_disabled = ' disabled title="' . __('Sem permissão de escrita', 'newmanagement') . '"';
        } elseif ($ipbx_id === 0) {
            $add_disabled = ' disabled title="' . __('Salve o IPBX antes de adicionar redes', 'newmanagement') . '"';
        }

        echo '<table class="tab_cadre_fixehov" id="nm-net-table">';
        echo '<thead>';
        echo '<tr class="headerRow noHover">';
        echo '<th>' . __('IP Rede', 'newmanagement') . '</th>';
        echo '<th>' . __('Máscara', 'newmanagement') . '</th>';
        echo '<th>' . __('Gateway', 'newmanagement') . '</th>';
        echo '<th>' . __('DNS Primário', 'newmanagement') . '</th>';
        echo '<th>' . __('DNS Secundário', 'newmanagement') . '</th>';
        echo '<th>' . __('Fornecedor', 'newmanagement') . '</th>';
        echo '<th style="text-align:right">';
        if ($can_write) {
            echo '<button type="button"'
                . ' id="nm-net-add-btn"'
                . ' class="btn btn-sm btn-outline-secondary"'
                . ' data-action="add_network"'
                . ' data-ipbx-id="' . $ipbx_id . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-url="' . $h($action) . '"'
                . $add_disabled . '>'
                . '<i class="ti ti-plus"></i> ' . __('Adicionar Rede', 'newmanagement')
                . '</button>';
        }
        echo '</th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody id="nm-net-tbody">';

        foreach ($rows as $row) {
            echo self::renderNetworkRow((int) $row['id'], $row, $companies_id, $csrf, $action, $can_delete);
        }

        if ($can_write) {
            echo '<tr class="tab_bg_2" id="nm-net-add-row">';
            echo '<td><input type="text" id="nm-net-ip_network" autocomplete="off" class="form-control form-control-sm" placeholder="192.168.1.0"></td>';
            echo '<td><input type="text" id="nm-net-netmask" autocomplete="off" class="form-control form-control-sm" placeholder="255.255.255.0"></td>';
            echo '<td><input type="text" id="nm-net-gateway" autocomplete="off" class="form-control form-control-sm" placeholder="192.168.1.1"></td>';
            echo '<td><input type="text" id="nm-net-dns_primary" autocomplete="off" class="form-control form-control-sm" placeholder="8.8.8.8"></td>';
            echo '<td><input type="text" id="nm-net-dns_secondary" autocomplete="off" class="form-control form-control-sm" placeholder="8.8.4.4"></td>';
            echo '<td><input type="text" id="nm-net-supplier" autocomplete="off" class="form-control form-control-sm" placeholder="' . __('Fornecedor', 'newmanagement') . '"></td>';
            echo '<td></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    // [FIX] Assinatura recebe $can_delete
    public static function renderNetworkRow(int $id, array $row, int $companies_id, string $csrf, string $action, bool $can_delete = true): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        $delete_btn = '';
        if ($can_delete) {
            $delete_btn = '<button type="button"'
                . ' class="btn btn-sm btn-icon nm-del-btn"'
                . ' data-action="delete_network"'
                . ' data-id="' . $id . '"'
                . ' data-row="nm-net-row-' . $id . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . htmlspecialchars($csrf, ENT_QUOTES) . '"'
                . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
                . ' data-confirm="' . __('Remover rede?', 'newmanagement') . '"'
                . ' title="' . __('Remover', 'newmanagement') . '">'
                . '<i class="ti ti-trash text-danger"></i>'
                . '</button>';
        }

        return '<tr class="tab_bg_1" id="nm-net-row-' . $id . '">'
            . '<td>' . $h($row['ip_network']) . '</td>'
            . '<td>' . $h($row['netmask']) . '</td>'
            . '<td>' . $h($row['gateway']) . '</td>'
            . '<td>' . $h($row['dns_primary']) . '</td>'
            . '<td>' . $h($row['dns_secondary']) . '</td>'
            . '<td>' . $h($row['supplier'] ?? '') . '</td>'
            . '<td>' . $delete_btn . '</td>'
            . '</tr>';
    }
}
