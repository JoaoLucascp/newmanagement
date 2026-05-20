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
    // Tab principal — [FIX A1] Usa TemplateRenderer em vez de echo HTML
    // ======================================================================

    /**
     * Renderiza a aba IPBX completa via Twig.
     *
     * Responsabilidades deste método:
     *   - Consultar o banco (campos IPBX + sub-tabelas)
     *   - Verificar permissões
     *   - Preparar arrays de dados limpos (senhas nunca expostas)
     *   - Delegar TODA a apresentação ao template Twig
     */
    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        if (!\Session::haveRight(self::$rightname, READ)) {
            echo '<div class="alert alert-warning">' . __('Acesso negado.', 'newmanagement') . '</div>';
            return;
        }

        $can_write  = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete = \Session::haveRight(self::$rightname, DELETE);

        // --- Dados do IPBX principal ---
        $rows    = $DB->request([
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

        foreach ($rows as $row) {
            $fields           = $row;
            $ipbx_id          = (int) $row['id'];
            $has_web_password = !empty($fields['web_password']);
            $has_ssh_password = !empty($fields['ssh_password']);
            // Nunca expor senhas no HTML
            $fields['web_password'] = '';
            $fields['ssh_password'] = '';
        }

        // --- Sub-tabelas ---
        $extensions = ($ipbx_id > 0)
            ? iterator_to_array($DB->request(['FROM' => self::TABLE_EXTENSIONS, 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'number ASC']))
            : [];

        $devices = ($ipbx_id > 0)
            ? iterator_to_array($DB->request(['FROM' => self::TABLE_DEVICES, 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'device_type ASC']))
            : [];

        $network = ($ipbx_id > 0)
            ? iterator_to_array($DB->request(['FROM' => self::TABLE_NETWORK, 'WHERE' => ['ipbx_id' => $ipbx_id]]))
            : [];

        // --- Renderiza via Twig ---
        \Glpi\Application\View\TemplateRenderer::getInstance()->display(
            '@GlpiPlugin/Newmanagement/ipbx/tab.html.twig',
            [
                'action_url'     => \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php',
                'companies_id'   => $companies_id,
                'ipbx_id'        => $ipbx_id,
                'fields'         => $fields,
                'ipbx_action'    => $ipbx_id > 0 ? 'update_ipbx' : 'add_ipbx',
                'csrf'           => \Session::getNewCSRFToken(),
                'can_write'      => $can_write,
                'can_delete'     => $can_delete,
                'web_placeholder' => $has_web_password
                    ? __('(senha salva — deixe em branco para manter)', 'newmanagement')
                    : __('Senha Web', 'newmanagement'),
                'ssh_placeholder' => $has_ssh_password
                    ? __('(senha salva — deixe em branco para manter)', 'newmanagement')
                    : __('Senha SSH', 'newmanagement'),
                'extensions'     => $extensions,
                'devices'        => $devices,
                'network'        => $network,
            ]
        );
    }

    // ======================================================================
    // Métodos estáticos auxiliares — usados pelo AJAX para renderizar
    // linhas individuais ao adicionar um item sem recarregar a página.
    // Permanecem em PHP puro pois retornam string para o JSON do AJAX.
    // ======================================================================

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
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-url="' . $h($action) . '"'
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
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-url="' . $h($action) . '"'
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
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-url="' . $h($action) . '"'
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
