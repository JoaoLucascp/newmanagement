<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Ipbx — Servidor IPBX On-Premise
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Ipbx extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_ipbx';

    public static function getTypeName($nb = 0): string
    {
        return _n('Servidor IPBX', 'Servidores IPBX', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx';
    }

    // ------------------------------------------------------------------
    // Abas do formulário
    // ------------------------------------------------------------------
    public function defineTabs($options = []): array
    {
        $ong = [];
        $this->addDefaultFormTab($ong);                    // Aba 1 — Servidor
        $this->addStandardTab(IpbxExtension::class, $ong, $options); // Aba 2 — Ramais
        $this->addStandardTab(IpbxDevice::class,    $ong, $options); // Aba 3 — Dispositivos
        $this->addStandardTab(IpbxNetwork::class,   $ong, $options); // Aba 4 — Rede
        $this->addStandardTab(IpbxLine::class,      $ong, $options); // Aba 5 — Linha Fixa
        $this->addStandardTab('Log',                $ong, $options);
        return $ong;
    }

    // ------------------------------------------------------------------
    // Opções de busca
    // ------------------------------------------------------------------
    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];

        $tab[] = ['id' => 1,  'table' => self::getTable(), 'field' => 'name',           'name' => __('Nome', 'newmanagement'),              'datatype' => 'itemlink'];
        $tab[] = ['id' => 2,  'table' => self::getTable(), 'field' => 'server_model',   'name' => __('Modelo', 'newmanagement'),            'datatype' => 'string'];
        $tab[] = ['id' => 3,  'table' => self::getTable(), 'field' => 'server_version', 'name' => __('Versão', 'newmanagement'),            'datatype' => 'string'];
        $tab[] = ['id' => 4,  'table' => self::getTable(), 'field' => 'ip_local',       'name' => __('IP Local', 'newmanagement'),          'datatype' => 'string'];
        $tab[] = ['id' => 5,  'table' => self::getTable(), 'field' => 'ip_external',    'name' => __('IP Externo', 'newmanagement'),        'datatype' => 'string'];
        $tab[] = ['id' => 6,  'table' => self::getTable(), 'field' => 'web_port',       'name' => __('Porta Web', 'newmanagement'),         'datatype' => 'string'];
        $tab[] = ['id' => 7,  'table' => self::getTable(), 'field' => 'ssh_port',       'name' => __('Porta SSH', 'newmanagement'),         'datatype' => 'string'];
        $tab[] = ['id' => 8,  'table' => self::getTable(), 'field' => 'comment',        'name' => __('Comentário', 'newmanagement'),        'datatype' => 'text'];
        $tab[] = ['id' => 9,  'table' => self::getTable(), 'field' => 'date_creation',  'name' => __('Criado em', 'newmanagement'),         'datatype' => 'datetime', 'massiveaction' => false];
        $tab[] = ['id' => 10, 'table' => self::getTable(), 'field' => 'date_mod',       'name' => __('Modificado em', 'newmanagement'),     'datatype' => 'datetime', 'massiveaction' => false];

        return $tab;
    }

    // ------------------------------------------------------------------
    // Formulário principal (Aba 1 — Servidor)
    // ------------------------------------------------------------------
    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $v = $this->fields;
        $h = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');

        // --- Linha 1: Nome + Empresa
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="name">' . __('Nome do servidor', 'newmanagement') . ' <span style="color:red">*</span></label></td>';
        echo '<td><input type="text" id="name" name="name" value="' . $h($v['name']) . '" class="form-control" required></td>';
        echo '<td><label for="companies_id">' . __('Empresa vinculada', 'newmanagement') . '</label></td>';
        echo '<td>';
        // Dropdown de empresas
        $rand = mt_rand();
        \Dropdown::show(
            'GlpiPlugin\\Newmanagement\\Company',
            ['name' => 'companies_id', 'value' => $v['companies_id'] ?? 0, 'rand' => $rand]
        );
        echo '</td></tr>';

        // --- Linha 2: Modelo + Versão
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="server_model">' . __('Modelo do servidor', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="server_model" name="server_model" value="' . $h($v['server_model']) . '" class="form-control" placeholder="Ex: Asterisk, FreePBX, 3CX"></td>';
        echo '<td><label for="server_version">' . __('Versão do servidor', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="server_version" name="server_version" value="' . $h($v['server_version']) . '" class="form-control" placeholder="Ex: 20.5.0"></td>';
        echo '</tr>';

        // --- Linha 3: IP Local + IP Externo
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="ip_local">' . __('IP local do servidor', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="ip_local" name="ip_local" value="' . $h($v['ip_local']) . '" class="form-control" placeholder="192.168.1.100"></td>';
        echo '<td><label for="ip_external">' . __('IP externo do servidor', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="ip_external" name="ip_external" value="' . $h($v['ip_external']) . '" class="form-control" placeholder="200.150.100.50"></td>';
        echo '</tr>';

        // --- Linha 4: Porta Web + Senha Web
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="web_port">' . __('Porta de acesso Web', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="web_port" name="web_port" value="' . $h($v['web_port']) . '" class="form-control" placeholder="80 ou 443"></td>';
        echo '<td><label for="web_password">' . __('Senha de acesso Web', 'newmanagement') . '</label></td>';
        echo '<td>';
        echo '<div class="nm-password-wrap">';
        echo '<input type="password" id="web_password" name="web_password" value="' . $h($v['web_password']) . '" class="form-control" autocomplete="new-password">';
        echo '<button type="button" class="btn btn-sm btn-outline-secondary nm-toggle-pass" data-target="web_password" title="Mostrar/ocultar senha"><i class="ti ti-eye"></i></button>';
        echo '</div></td></tr>';

        // --- Linha 5: Porta SSH + Senha SSH
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="ssh_port">' . __('Porta de acesso SSH', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="ssh_port" name="ssh_port" value="' . $h($v['ssh_port']) . '" class="form-control" placeholder="22"></td>';
        echo '<td><label for="ssh_password">' . __('Senha de acesso SSH', 'newmanagement') . '</label></td>';
        echo '<td>';
        echo '<div class="nm-password-wrap">';
        echo '<input type="password" id="ssh_password" name="ssh_password" value="' . $h($v['ssh_password']) . '" class="form-control" autocomplete="new-password">';
        echo '<button type="button" class="btn btn-sm btn-outline-secondary nm-toggle-pass" data-target="ssh_password" title="Mostrar/ocultar senha"><i class="ti ti-eye"></i></button>';
        echo '</div></td></tr>';

        // --- Linha 6: Comentário
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="comment">' . __('Comentário', 'newmanagement') . '</label></td>';
        echo '<td colspan="3"><textarea id="comment" name="comment" class="form-control" rows="4">' . $h($v['comment']) . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }

    // ------------------------------------------------------------------
    // Menu no GLPI
    // ------------------------------------------------------------------
    public static function getMenuContent(): array
    {
        $menu = [];
        $menu['title'] = self::getTypeName(2);
        $menu['page']  = '/plugins/newmanagement/front/ipbx.php';
        $menu['icon']  = 'ti ti-server';
        return $menu;
    }
}
