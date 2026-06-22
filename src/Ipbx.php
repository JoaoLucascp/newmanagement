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

    const PAGE_SIZE = 20;

    const TABLE_EXTENSIONS = 'glpi_plugin_newmanagement_ipbx_extensions';
    const TABLE_DEVICES    = 'glpi_plugin_newmanagement_ipbx_devices';
    const TABLE_NETWORK    = 'glpi_plugin_newmanagement_ipbx_network';
    const PASSWORD_MASK    = '******';

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
        if (!\Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        if ($item instanceof Company) {
            (new self())->showTabForCompany((int) $item->getID());
        }
        return true;
    }

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

    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        if (!\Session::haveRight(self::$rightname, READ)) {
            echo '<div class="alert alert-warning">' . __('Acesso negado.', 'newmanagement') . '</div>';
            return;
        }

        $can_write         = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete        = \Session::haveRight(self::$rightname, DELETE);
        $can_view_password = $can_write;

        // Busca registro IPBX existente para a empresa
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
        $has_web_password = false;
        $has_ssh_password = false;

        // OPÇÃO A: se não existir IPBX, cria um registro pai vazio automaticamente
        if ($rows->count() === 0 && $can_write) {
            $ipbx = new self();
            $ipbx_id = (int) $ipbx->add([
                'companies_id' => $companies_id,
                'model'        => '',
                'server_version' => '',
                'ip_local'     => '',
                'ip_external'  => '',
                'web_port'     => '',
                'web_password' => '',
                'ssh_port'     => '',
                'ssh_password' => '',
                'comment'      => '',
            ]);

            if ($ipbx_id > 0) {
                $rows = $DB->request([
                    'FROM'  => self::getTable(),
                    'WHERE' => ['id' => $ipbx_id],
                    'LIMIT' => 1,
                ]);
            }
        }

        foreach ($rows as $row) {
            $fields           = $row;
            $ipbx_id          = (int) $row['id'];
            $has_web_password = !empty($fields['web_password']);
            $has_ssh_password = !empty($fields['ssh_password']);
            $fields['web_password'] = '';
            $fields['ssh_password'] = '';
        }

        $ext_page = max(1, (int) ($_GET['ext_page'] ?? 1));
        $dev_page = max(1, (int) ($_GET['dev_page'] ?? 1));
        $net_page = max(1, (int) ($_GET['net_page'] ?? 1));

        [$extensions, $ext_total] = self::fetchPage(
            self::TABLE_EXTENSIONS,
            ['ipbx_id' => $ipbx_id],
            'number ASC',
            $ext_page
        );
        foreach ($extensions as &$extension) {
            $extension['password_display'] = self::formatCredentialForDisplay(
                $extension['password'] ?? '',
                $can_view_password
            );
        }
        unset($extension);

        [$devices, $dev_total] = self::fetchPage(
            self::TABLE_DEVICES,
            ['ipbx_id' => $ipbx_id],
            'device_type ASC',
            $dev_page
        );
        [$network, $net_total] = self::fetchPage(
            self::TABLE_NETWORK,
            ['ipbx_id' => $ipbx_id],
            'ip_network ASC',
            $net_page
        );

        \Glpi\Application\View\TemplateRenderer::getInstance()->display(
            '@newmanagement/ipbx/tab.html.twig',
            [
                'action_url'       => \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php',
                'paginate_url'     => \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_paginate.php',
                'companies_id'     => $companies_id,
                'ipbx_id'          => $ipbx_id,
                'fields'           => $fields,
                'ipbx_action'      => $ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx',
                'csrf'             => \Session::getNewCSRFToken(),
                'can_write'        => $can_write,
                'can_delete'       => $can_delete,
                'can_view_extension_passwords' => $can_view_password,
                'web_placeholder'  => $has_web_password
                    ? __('(senha salva — deixe em branco para manter)', 'newmanagement')
                    : __('Senha Web', 'newmanagement'),
                'ssh_placeholder'  => $has_ssh_password
                    ? __('(senha salva — deixe em branco para manter)', 'newmanagement')
                    : __('Senha SSH', 'newmanagement'),
                'extensions'       => $extensions,
                'ext_page'         => $ext_page,
                'ext_total'        => $ext_total,
                'ext_page_size'    => self::PAGE_SIZE,
                'devices'          => $devices,
                'dev_page'         => $dev_page,
                'dev_total'        => $dev_total,
                'dev_page_size'    => self::PAGE_SIZE,
                'network'          => $network,
                'net_page'         => $net_page,
                'net_total'        => $net_total,
                'net_page_size'    => self::PAGE_SIZE,
            ]
        );
    }

    public static function fetchPage(string $table, array $where, string $order, int $page): array
    {
        global $DB;

        if (empty($where['ipbx_id']) || (int) $where['ipbx_id'] <= 0) {
            return [[], 0];
        }

        $total  = countElementsInTable($table, $where);
        $offset = ($page - 1) * self::PAGE_SIZE;

        $rows = iterator_to_array($DB->request([
            'FROM'  => $table,
            'WHERE' => $where,
            'ORDER' => $order,
            'LIMIT' => self::PAGE_SIZE,
            'START' => $offset,
        ]));

        return [$rows, (int) $total];
    }

    public static function ipbxBelongsToCompany(int $ipbx_id, int $companies_id): bool
    {
        global $DB;

        if ($ipbx_id <= 0 || $companies_id <= 0) {
            return false;
        }

        return $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'id'           => $ipbx_id,
                'companies_id' => $companies_id,
                'is_deleted'   => 0,
            ],
            'LIMIT' => 1,
        ])->count() > 0;
    }

    public static function decryptCredentialForDisplay(?string $value): string
    {
        $value = (string) ($value ?? '');
        if ($value === '') {
            return '';
        }

        if (class_exists('GLPIKey')) {
            try {
                $decrypted = (new \GLPIKey())->decrypt($value);
                if (is_string($decrypted) && $decrypted !== '') {
                    return $decrypted;
                }
            } catch (\Throwable $e) {
                // Fallback below supports legacy sodium/plaintext values.
            }
        }

        try {
            $decrypted = \Toolbox::sodiumDecrypt($value);
            if (
                is_string($decrypted)
                && $decrypted !== ''
                && self::isDisplayableCredential($decrypted)
            ) {
                return $decrypted;
            }
        } catch (\Throwable $e) {
            // Legacy records may still contain plaintext.
        }

        return $value;
    }

    private static function isDisplayableCredential(string $value): bool
    {
        return preg_match('/^[\P{C}\t\r\n]*$/u', $value) === 1;
    }

    public static function formatCredentialForDisplay(?string $value, bool $can_view_password): string
    {
        $value = (string) ($value ?? '');
        if ($value === '') {
            return '';
        }

        return $can_view_password
            ? self::decryptCredentialForDisplay($value)
            : self::PASSWORD_MASK;
    }

    /**
     * Renderiza uma linha <tr> de ramal para a lista de ramais.
     *
     * Colunas (12 + ação):
     *   Ramal | Senha (plain text) | Usuário | IP Dispositivo | Departamento
     *   | Grava | LOF | LOC | DDF | DDC | DDI | SRV | [Excluir]
     */
    public static function renderExtensionRow(
        int $id,
        array $row,
        int $companies_id,
        string $csrf,
        string $action,
        bool $can_delete = true,
        bool $can_view_password = false
    ): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

        $password_cell = '<code>' . $h(self::formatCredentialForDisplay(
            $row['password'] ?? '',
            $can_view_password
        )) . '</code>';

        // Badge para o campo "Grava"
        $grava = (int) ($row['records_calls'] ?? 0);
        $grava_badge = '<span class="badge ' . ($grava ? 'bg-success' : 'bg-secondary') . '">
            ' . ($grava ? __('Sim', 'newmanagement') : __('Não', 'newmanagement')) . '
        </span>';

        // Campos booleanos compactos: toggle switch read-only (AJAX on change via JS)
        $bool_cols = ['lof', 'loc', 'ddf', 'ddc', 'ddi', 'srv'];
        $bool_tds  = '';
        foreach ($bool_cols as $field) {
            $checked = !empty($row[$field]) ? ' checked' : '';
            $bool_tds .= '<td class="text-center">'
                . '<div class="form-check form-switch d-flex justify-content-center m-0 p-0">'
                . '<input class="form-check-input nm-toggle-bool" type="checkbox" role="switch"'
                . ' data-row-id="' . $id . '"'
                . ' data-field="' . $field . '"'
                . $checked
                . ' style="cursor:pointer">'
                . '</div>'
                . '</td>';
        }

        // Botão excluir
        $delete_btn = '';
        if ($can_delete) {
            $delete_btn = '<button type="button" class="btn btn-sm btn-icon nm-del-btn"'
                . ' data-action="delete_extension"'
                . ' data-id="' . $id . '"'
                . ' data-row="nm-ext-row-' . $id . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-url="' . $h($action) . '"'
                . ' data-confirm="' . __('Remover ramal?', 'newmanagement') . '"'
                . ' title="' . __('Excluir ramal', 'newmanagement') . '">'
                . '<i class="ti ti-trash text-danger"></i></button>';
        }

        return '<tr class="tab_bg_1 nm-ext-saved-row" id="nm-ext-row-' . $id . '">'
            . '<td>' . $h($row['number'])     . '</td>'
            . '<td>' . $password_cell         . '</td>'
            . '<td>' . $h($row['user_name'])  . '</td>'
            . '<td>' . $h($row['device_ip'])  . '</td>'
            . '<td>' . $h($row['department']) . '</td>'
            . '<td class="text-center">' . $grava_badge . '</td>'
            . $bool_tds
            . '<td class="text-end">' . $delete_btn . '</td>'
            . '</tr>';
    }

    public static function renderDeviceRow(int $id, array $row, int $companies_id, string $csrf, string $action, bool $can_delete = true): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);
        $delete_btn = '';
        if ($can_delete) {
            $delete_btn = '<button type="button" class="btn btn-sm btn-icon nm-del-btn"'
                . ' data-action="delete_device" data-id="' . $id . '"'
                . ' data-row="nm-dev-row-' . $id . '" data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . $h($csrf) . '" data-url="' . $h($action) . '"'
                . ' data-confirm="' . __('Remover dispositivo?', 'newmanagement') . '"'
                . ' title="' . __('Remover', 'newmanagement') . '">'
                . '<i class="ti ti-trash text-danger"></i></button>';
        }
        return '<tr class="tab_bg_1 nm-dev-saved-row" id="nm-dev-row-' . $id . '">'
            . '<td>' . $h($row['device_type']) . '</td>'
            . '<td>' . $h($row['ip_address']) . '</td>'
            . '<td>' . $h($row['login'] ?? '') . '</td>'
            . '<td>******</td>'
            . '<td class="text-end">' . $delete_btn . '</td></tr>';
    }

    public static function renderNetworkRow(int $id, array $row, int $companies_id, string $csrf, string $action, bool $can_delete = true): string
    {
        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);
        $delete_btn = '';
        if ($can_delete) {
            $delete_btn = '<button type="button" class="btn btn-sm btn-icon nm-del-btn"'
                . ' data-action="delete_network" data-id="' . $id . '"'
                . ' data-row="nm-net-row-' . $id . '" data-companies-id="' . $companies_id . '"'
                . ' data-csrf="' . $h($csrf) . '" data-url="' . $h($action) . '"'
                . ' data-confirm="' . __('Remover rede?', 'newmanagement') . '"'
                . ' title="' . __('Remover', 'newmanagement') . '">'
                . '<i class="ti ti-trash text-danger"></i></button>';
        }
        return '<tr class="tab_bg_1 nm-net-saved-row" id="nm-net-row-' . $id . '">'
            . '<td>' . $h($row['ip_network']) . '</td>'
            . '<td>' . $h($row['netmask']) . '</td>'
            . '<td>' . $h($row['gateway']) . '</td>'
            . '<td>' . $h($row['dns_primary']) . '</td>'
            . '<td>' . $h($row['dns_secondary']) . '</td>'
            . '<td>' . $h($row['supplier'] ?? '') . '</td>'
            . '<td class="text-end">' . $delete_btn . '</td></tr>';
    }
}
