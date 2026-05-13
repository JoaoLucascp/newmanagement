<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Company (Empresas)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Company extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_company';

    public static function getTypeName($nb = 0): string
    {
        return _n('Empresa', 'Empresas', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_companies';
    }

    /**
     * Define as colunas exibidas na listagem (Search::show)
     * Sem isso, a tabela aparece vazia mesmo com dados
     */
    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = [
            'id'            => 'common',
            'name'          => self::getTypeName(1),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => self::getTable(),
            'field'         => 'name',
            'name'          => __('Nome', 'newmanagement'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => 2,
            'table'    => self::getTable(),
            'field'    => 'cnpj',
            'name'     => __('CNPJ', 'newmanagement'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => 3,
            'table'    => self::getTable(),
            'field'    => 'phone',
            'name'     => __('Telefone', 'newmanagement'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => 4,
            'table'    => self::getTable(),
            'field'    => 'email',
            'name'     => __('E-mail', 'newmanagement'),
            'datatype' => 'email',
        ];

        $tab[] = [
            'id'       => 5,
            'table'    => self::getTable(),
            'field'    => 'address',
            'name'     => __('Endereço', 'newmanagement'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id'       => 6,
            'table'    => self::getTable(),
            'field'    => 'comment',
            'name'     => __('Comentário', 'newmanagement'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id'       => 19,
            'table'    => self::getTable(),
            'field'    => 'date_mod',
            'name'     => __('Last update'),
            'datatype' => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => 121,
            'table'    => self::getTable(),
            'field'    => 'date_creation',
            'name'     => __('Creation date'),
            'datatype' => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    /**
     * Menu lateral do GLPI 11
     */
    public static function getMenuContent(): array
    {
        $menu = [];

        $menu['title'] = 'Newmanagement';
        $menu['page']  = '/plugins/newmanagement/front/company.php';
        $menu['icon']  = 'ti ti-building';

        $menu['options']['company']['title']           = __('Empresas', 'newmanagement');
        $menu['options']['company']['page']            = '/plugins/newmanagement/front/company.php';
        $menu['options']['company']['icon']            = 'ti ti-building';
        $menu['options']['company']['links']['search'] = '/plugins/newmanagement/front/company.php';
        $menu['options']['company']['links']['add']    = '/plugins/newmanagement/front/company.php?action=add';

        $menu['options']['ipbx']['title']           = __('IPBX', 'newmanagement');
        $menu['options']['ipbx']['page']            = '/plugins/newmanagement/front/ipbx.php';
        $menu['options']['ipbx']['icon']            = 'ti ti-server';
        $menu['options']['ipbx']['links']['search'] = '/plugins/newmanagement/front/ipbx.php';
        $menu['options']['ipbx']['links']['add']    = '/plugins/newmanagement/front/ipbx.php?action=add';

        $menu['options']['ipbxcloud']['title']           = __('IPBX Cloud', 'newmanagement');
        $menu['options']['ipbxcloud']['page']            = '/plugins/newmanagement/front/ipbxcloud.php';
        $menu['options']['ipbxcloud']['icon']            = 'ti ti-cloud';
        $menu['options']['ipbxcloud']['links']['search'] = '/plugins/newmanagement/front/ipbxcloud.php';
        $menu['options']['ipbxcloud']['links']['add']    = '/plugins/newmanagement/front/ipbxcloud.php?action=add';

        $menu['options']['chatbot']['title']           = __('Chatbots', 'newmanagement');
        $menu['options']['chatbot']['page']            = '/plugins/newmanagement/front/chatbot.php';
        $menu['options']['chatbot']['icon']            = 'ti ti-robot';
        $menu['options']['chatbot']['links']['search'] = '/plugins/newmanagement/front/chatbot.php';
        $menu['options']['chatbot']['links']['add']    = '/plugins/newmanagement/front/chatbot.php?action=add';

        $menu['options']['fixedline']['title']           = __('Linhas Fixas', 'newmanagement');
        $menu['options']['fixedline']['page']            = '/plugins/newmanagement/front/fixedline.php';
        $menu['options']['fixedline']['icon']            = 'ti ti-phone';
        $menu['options']['fixedline']['links']['search'] = '/plugins/newmanagement/front/fixedline.php';
        $menu['options']['fixedline']['links']['add']    = '/plugins/newmanagement/front/fixedline.php?action=add';

        $menu['options']['task']['title']           = __('Tarefas', 'newmanagement');
        $menu['options']['task']['page']            = '/plugins/newmanagement/front/task.php';
        $menu['options']['task']['icon']            = 'ti ti-checklist';
        $menu['options']['task']['links']['search'] = '/plugins/newmanagement/front/task.php';
        $menu['options']['task']['links']['add']    = '/plugins/newmanagement/front/task.php?action=add';

        return $menu;
    }

    public function defineTabs($options = []): array
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        return $ong;
    }

    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Nome', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="name" value="' . ($this->fields['name'] ?? '') . '" class="form-control" required></td>';
        echo '<td>' . __('CNPJ', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="cnpj" value="' . ($this->fields['cnpj'] ?? '') . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Telefone', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="phone" value="' . ($this->fields['phone'] ?? '') . '" class="form-control"></td>';
        echo '<td>' . __('E-mail', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="email" value="' . ($this->fields['email'] ?? '') . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Endereço', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="address" class="form-control" rows="2">' . ($this->fields['address'] ?? '') . '</textarea></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="3">' . ($this->fields['comment'] ?? '') . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }
}
