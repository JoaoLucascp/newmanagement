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

        $rows    = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0], 'LIMIT' => 1]);
        $ipbx_id = 0;
        $fields  = ['id' => 0, 'companies_id' => $companies_id, 'model' => '', 'server_version' => '', 'ip_local' => '', 'ip_external' => '', 'web_port' => '', 'web_password' => '', 'ssh_port' => '', 'ssh_password' => '', 'comment' => ''];

        foreach ($rows as $row) {
            $fields  = $row;
            $ipbx_id = (int) $row['id'];
            try {
                if (!empty($fields['web_password'])) {
                    $fields['web_password'] = \Toolbox::sodiumDecrypt($fields['web_password']);
                }
            } catch (\Throwable $e) {}
            try {
                if (!empty($fields['ssh_password'])) {
                    $fields['ssh_password'] = \Toolbox::sodiumDecrypt($fields['ssh_password']);
                }
            } catch (\Throwable $e) {}
        }

        $csrf   = \Session::getNewCSRFToken();
        $action = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
        $h      = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        // Senhas são passadas como data-* para o JS injetar dinamicamente.
        // Isso evita type="password" no HTML estático, eliminando o aviso:
        // [DOM] Password field is not contained in a form
        echo '<div class="nm-ipbx-tab" data-action-url="' . $h($action) . '" data-companies-id="' . $companies_id . '">';

        echo '<div id="nm-ipbx-form">';
        echo '<input type="hidden" id="nm-ipbx-csrf"         value="' . $csrf . '">';
        echo '<input type="hidden" id="nm-ipbx-action"       value="' . ($ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx') . '">';
        echo '<input type="hidden" id="nm-ipbx-id"           value="' . $ipbx_id . '">';
        echo '<input type="hidden" id="nm-ipbx-companies-id" value="' . $companies_id . '">';

        echo '<table class="tab_cadre_fixe">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-model" autocomplete="off" value="' . $h($fields['model']) . '" class="form-control"></td>';
        echo '<td>' . __('Versão', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-server_version" autocomplete="off" value="' . $h($fields['server_version']) . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('IP Local', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-ip_local" autocomplete="off" value="' . $h($fields['ip_local']) . '" class="form-control" placeholder="192.168.1.1"></td>';
        echo '<td>' . __('IP Externo', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-ip_external" autocomplete="off" value="' . $h($fields['ip_external']) . '" class="form-control" placeholder="201.x.x.x"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Porta Web', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-web_port" autocomplete="off" value="' . $h($fields['web_port']) . '" class="form-control" placeholder="80"></td>';
        echo '<td>' . __('Senha Web', 'newmanagement') . '</td>';
        echo '<td><div class="input-group">';
        echo '<div id="nm-ipbx-web_password-slot" data-value="' . $h($fields['web_password']) . '" data-target-id="nm-ipbx-web_password"></div>';
        echo '<button type="button" class="btn btn-outline-secondary nm-btn-eye" data-target="nm-ipbx-web_password"><i class="ti ti-eye"></i></button>';
        echo '</div></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Porta SSH', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-ipbx-ssh_port" autocomplete="off" value="' . $h($fields['ssh_port']) . '" class="form-control" placeholder="22"></td>';
        echo '<td>' . __('Senha SSH', 'newmanagement') . '</td>';
        echo '<td><div class="input-group">';
        echo '<div id="nm-ipbx-ssh_password-slot" data-value="' . $h($fields['ssh_password']) . '" data-target-id="nm-ipbx-ssh_password"></div>';
        echo '<button type="button" class="btn btn-outline-secondary nm-btn-eye" data-target="nm-ipbx-ssh_password"><i class="ti ti-eye"></i></button>';
        echo '</div></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea id="nm-ipbx-comment" class="form-control" rows="2">' . $h($fields['comment']) . '</textarea></td>';
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
        echo '</div>';
        $this->renderExtensions($ipbx_id, $companies_id, $csrf, $action);
        echo '</div>';

        // ---- Dispositivos ----
        echo '<div class="nm-subsection mt-3">';
        echo '<div class="d-flex align-items-center mb-2">';
        echo '<i class="ti ti-device-desktop me-2 fs-5 text-muted"></i>';
        echo '<span class="fw-bold text-muted text-uppercase" style="font-size:0.75rem;letter-spacing:.05em">';
        echo __('Dispositivos', 'newmanagement');
        echo '</span>';
        echo '</div>';
        $this->renderDevices($ipbx_id, $companies_id, $csrf, $action);
        echo '</div>';

        // ---- Rede ----
        echo '<div class="nm-subsection mt-3">';
        echo '<div class="d-flex align-items-center mb-2">';
        echo '<i class="ti ti-network me-2 fs-5 text-muted"></i>';
        echo '<span class="fw-bold text-muted text-uppercase" style="font-size:0.75rem;letter-spacing:.05em">';
        echo __('Rede da Empresa', 'newmanagement');
        echo '</span>';
        echo '</div>';
        $this->renderNetwork($ipbx_id, $companies_id, $csrf, $action);
        echo '</div>';

        // ---- Botão Salvar ----
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

        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        echo '<table class="tab_cadre_fixehov" id="nm-ext-table">';

        // thead com headerRow noHover — padrão GLPI 10/11
        // Ordem: Número | Senha | IP Aparelho | Usuário | Grava? | Departamento | Ação
        echo '<thead>';
        echo '<tr class="headerRow noHover">';
        foreach ([
            __('Número', 'newmanagement'),
            __('Senha', 'newmanagement'),
            __('IP Aparelho', 'newmanagement'),
            __('Usuário', 'newmanagement'),
            __('Grava?', 'newmanagement'),
            __('Departamento', 'newmanagement'),
            __('Ação', 'newmanagement'),
        ] as $th) {
            echo '<th>' . $th . '</th>';
        }
        echo '</tr>';
        echo '</thead>';

        echo '<tbody id="nm-ext-tbody">';

        // Linhas existentes — class="tab_bg_1"
        foreach ($rows as $row) {
            echo self::renderExtensionRow((int) $row['id'], $row, $companies_id, $csrf, $action);
        }

        // Linha de adição — class="tab_bg_2", sempre a última no tbody — padrão GLPI
        echo '<tr class="tab_bg_2" id="nm-ext-add-row">';

        // Número
        echo '<td>';
        echo '<input type="text" id="nm-ext-number" autocomplete="off"';
        echo ' class="form-control form-control-sm"';
        echo ' placeholder="' . __('Número', 'newmanagement') . '">';
        echo '</td>';

        // Senha — slot: JS injeta <input type="password"> aqui via data-target-id
        echo '<td>';
        echo '<div id="nm-ext-password-slot"';
        echo ' data-target-id="nm-ext-password"';
        echo ' data-placeholder="' . __('Senha', 'newmanagement') . '">';
        echo '</div>';
        echo '</td>';

        // IP Aparelho
        echo '<td>';
        echo '<input type="text" id="nm-ext-device_ip" autocomplete="off"';
        echo ' class="form-control form-control-sm" placeholder="IP">';
        echo '</td>';

        // Usuário
        echo '<td>';
        echo '<input type="text" id="nm-ext-user_name" autocomplete="off"';
        echo ' class="form-control form-control-sm"';
        echo ' placeholder="' . __('Usuário', 'newmanagement') . '">';
        echo '</td>';

        // Grava?
        echo '<td>';
        echo '<select id="nm-ext-records_calls" class="form-select form-select-sm">';
        echo '<option value="0">' . __('Não', 'newmanagement') . '</option>';
        echo '<option value="1">' . __('Sim', 'newmanagement') . '</option>';
        echo '</select>';
        echo '</td>';

        // Departamento
        echo '<td>';
        echo '<input type="text" id="nm-ext-department" autocomplete="off"';
        echo ' class="form-control form-control-sm"';
        echo ' placeholder="' . __('Departamento', 'newmanagement') . '">';
        echo '</td>';

        // Botão adicionar — btn-outline-secondary + ti ti-plus — padrão GLPI
        echo '<td>';
        echo '<button type="button"';
        echo ' class="btn btn-sm btn-outline-secondary"';
        echo ' id="nm-ext-add-btn"';
        echo ' data-action="add_extension"';
        echo ' data-ipbx-id="' . $ipbx_id . '"';
        echo ' data-companies-id="' . $companies_id . '"';
        echo ' data-url="' . $h($action) . '">';
        echo '<i class="ti ti-plus"></i> ' . __('Adicionar Ramal', 'newmanagement');
        echo '</button>';
        echo '</td>';

        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
    }

    public static function renderExtensionRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        // Ordem: Número | Senha | IP Aparelho | Usuário | Grava? | Departamento | Ação
        // Botão excluir: btn-icon sem fundo colorido + ti ti-trash text-danger — padrão GLPI
        return '<tr class="tab_bg_1" id="nm-ext-row-' . $id . '">'
            . '<td>' . $h($row['number']) . '</td>'
            . '<td>••••••</td>'
            . '<td>' . $h($row['device_ip']) . '</td>'
            . '<td>' . $h($row['user_name']) . '</td>'
            . '<td>' . ($row['records_calls'] ? __('Sim', 'newmanagement') : __('Não', 'newmanagement')) . '</td>'
            . '<td>' . $h($row['department']) . '</td>'
            . '<td>'
            . '<button type="button"'
            . ' class="btn btn-sm btn-icon nm-del-btn"'
            . ' data-action="delete_extension"'
            . ' data-id="' . $id . '"'
            . ' data-row="nm-ext-row-' . $id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
            . ' data-confirm="' . __('Remover ramal?', 'newmanagement') . '"'
            . ' title="' . __('Remover', 'newmanagement') . '">'
            . '<i class="ti ti-trash text-danger"></i>'
            . '</button>'
            . '</td>'
            . '</tr>';
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

        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        echo '<table class="tab_cadre_fixehov" id="nm-dev-table">';

        // thead com headerRow noHover — padrão GLPI 10/11
        // Ordem: Tipo | IP | Senha | Ação
        echo '<thead>';
        echo '<tr class="headerRow noHover">';
        foreach ([
            __('Tipo', 'newmanagement'),
            __('IP', 'newmanagement'),
            __('Senha', 'newmanagement'),
            __('Ação', 'newmanagement'),
        ] as $th) {
            echo '<th>' . $th . '</th>';
        }
        echo '</tr>';
        echo '</thead>';

        echo '<tbody id="nm-dev-tbody">';

        // Linhas existentes — class="tab_bg_1"
        foreach ($rows as $row) {
            echo self::renderDeviceRow((int) $row['id'], $row, $companies_id, $csrf, $action);
        }

        // Linha de adição — class="tab_bg_2", sempre a última no tbody — padrão GLPI
        echo '<tr class="tab_bg_2" id="nm-dev-add-row">';

        // Tipo
        echo '<td>';
        echo '<input type="text" id="nm-dev-device_type" autocomplete="off"';
        echo ' class="form-control form-control-sm"';
        echo ' placeholder="' . __('Tipo', 'newmanagement') . '">';
        echo '</td>';

        // IP
        echo '<td>';
        echo '<input type="text" id="nm-dev-ip_address" autocomplete="off"';
        echo ' class="form-control form-control-sm" placeholder="IP">';
        echo '</td>';

        // Senha — slot: JS injeta <input type="password"> aqui via data-target-id
        // Ordem: Tipo | IP | Senha | Ação
        echo '<td>';
        echo '<div id="nm-dev-password-slot"';
        echo ' data-target-id="nm-dev-password"';
        echo ' data-placeholder="' . __('Senha', 'newmanagement') . '">';
        echo '</div>';
        echo '</td>';

        // Botão adicionar — btn-outline-secondary + ti ti-plus — padrão GLPI
        echo '<td>';
        echo '<button type="button"';
        echo ' class="btn btn-sm btn-outline-secondary"';
        echo ' id="nm-dev-add-btn"';
        echo ' data-action="add_device"';
        echo ' data-ipbx-id="' . $ipbx_id . '"';
        echo ' data-companies-id="' . $companies_id . '"';
        echo ' data-url="' . $h($action) . '">';
        echo '<i class="ti ti-plus"></i> ' . __('Adicionar Dispositivo', 'newmanagement');
        echo '</button>';
        echo '</td>';

        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
    }

    public static function renderDeviceRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        // Ordem: Tipo | IP | Senha | Ação
        // Botão excluir: btn-icon sem fundo colorido + ti ti-trash text-danger — padrão GLPI
        return '<tr class="tab_bg_1" id="nm-dev-row-' . $id . '">'
            . '<td>' . $h($row['device_type']) . '</td>'
            . '<td>' . $h($row['ip_address']) . '</td>'
            . '<td>••••••</td>'
            . '<td>'
            . '<button type="button"'
            . ' class="btn btn-sm btn-icon nm-del-btn"'
            . ' data-action="delete_device"'
            . ' data-id="' . $id . '"'
            . ' data-row="nm-dev-row-' . $id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
            . ' data-confirm="' . __('Remover dispositivo?', 'newmanagement') . '"'
            . ' title="' . __('Remover', 'newmanagement') . '">'
            . '<i class="ti ti-trash text-danger"></i>'
            . '</button>'
            . '</td>'
            . '</tr>';
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

        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        echo '<table class="tab_cadre_fixehov" id="nm-net-table">';

        // thead com headerRow noHover — padrão GLPI 10/11
        // Ordem: IP Rede | Máscara | Gateway | DNS Primário | DNS Secundário | Ação
        echo '<thead>';
        echo '<tr class="headerRow noHover">';
        foreach ([
            __('IP Rede', 'newmanagement'),
            __('Máscara', 'newmanagement'),
            __('Gateway', 'newmanagement'),
            __('DNS Primário', 'newmanagement'),
            __('DNS Secundário', 'newmanagement'),
            __('Ação', 'newmanagement'),
        ] as $th) {
            echo '<th>' . $th . '</th>';
        }
        echo '</tr>';
        echo '</thead>';

        echo '<tbody id="nm-net-tbody">';

        // Linhas existentes — class="tab_bg_1"
        foreach ($rows as $row) {
            echo self::renderNetworkRow((int) $row['id'], $row, $companies_id, $csrf, $action);
        }

        // Linha de adição — class="tab_bg_2", sempre a última no tbody — padrão GLPI
        // Ordem: ip_network | netmask | gateway | dns_primary | dns_secondary | Ação
        echo '<tr class="tab_bg_2" id="nm-net-add-row">';

        echo '<td>';
        echo '<input type="text" id="nm-net-ip_network" autocomplete="off"';
        echo ' class="form-control form-control-sm" placeholder="192.168.1.0">';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" id="nm-net-netmask" autocomplete="off"';
        echo ' class="form-control form-control-sm" placeholder="255.255.255.0">';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" id="nm-net-gateway" autocomplete="off"';
        echo ' class="form-control form-control-sm" placeholder="192.168.1.1">';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" id="nm-net-dns_primary" autocomplete="off"';
        echo ' class="form-control form-control-sm" placeholder="8.8.8.8">';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" id="nm-net-dns_secondary" autocomplete="off"';
        echo ' class="form-control form-control-sm" placeholder="8.8.4.4">';
        echo '</td>';

        // Botão adicionar — btn-outline-secondary + ti ti-plus — padrão GLPI
        echo '<td>';
        echo '<button type="button"';
        echo ' class="btn btn-sm btn-outline-secondary"';
        echo ' id="nm-net-add-btn"';
        echo ' data-action="add_network"';
        echo ' data-ipbx-id="' . $ipbx_id . '"';
        echo ' data-companies-id="' . $companies_id . '"';
        echo ' data-url="' . $h($action) . '">';
        echo '<i class="ti ti-plus"></i> ' . __('Adicionar Rede', 'newmanagement');
        echo '</button>';
        echo '</td>';

        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
    }

    public static function renderNetworkRow(int $id, array $row, int $companies_id, string $csrf, string $action): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        // Ordem: IP Rede | Máscara | Gateway | DNS Primário | DNS Secundário | Ação
        // Botão excluir: btn-icon sem fundo colorido + ti ti-trash text-danger — padrão GLPI
        return '<tr class="tab_bg_1" id="nm-net-row-' . $id . '">'
            . '<td>' . $h($row['ip_network']) . '</td>'
            . '<td>' . $h($row['netmask']) . '</td>'
            . '<td>' . $h($row['gateway']) . '</td>'
            . '<td>' . $h($row['dns_primary']) . '</td>'
            . '<td>' . $h($row['dns_secondary']) . '</td>'
            . '<td>'
            . '<button type="button"'
            . ' class="btn btn-sm btn-icon nm-del-btn"'
            . ' data-action="delete_network"'
            . ' data-id="' . $id . '"'
            . ' data-row="nm-net-row-' . $id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . htmlspecialchars($action, ENT_QUOTES) . '"'
            . ' data-confirm="' . __('Remover rede?', 'newmanagement') . '"'
            . ' title="' . __('Remover', 'newmanagement') . '">'
            . '<i class="ti ti-trash text-danger"></i>'
            . '</button>'
            . '</td>'
            . '</tr>';
    }
}
