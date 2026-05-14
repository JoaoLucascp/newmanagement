<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxCloud (Servidores Telefônicos Cloud)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxCloud extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_ipbxcloud';

    public static function getTypeName($nb = 0): string
    {
        return _n('IPBX Cloud', 'IPBXs Cloud', $nb, 'newmanagement');
    }

    /**
     * Nome da tabela alinhado com hook.php (glpi_plugin_newmanagement_ipbx_cloud)
     */
    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_cloud';
    }

    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];

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
            'field'    => 'provider',
            'name'     => __('Provedor', 'newmanagement'),
            'datatype' => 'string',
        ];
        $tab[] = [
            'id'       => 3,
            'table'    => self::getTable(),
            'field'    => 'cloud_region',
            'name'     => __('Região Cloud', 'newmanagement'),
            'datatype' => 'string',
        ];
        $tab[] = [
            'id'       => 4,
            'table'    => self::getTable(),
            'field'    => 'sip_trunk',
            'name'     => __('Tronco SIP', 'newmanagement'),
            'datatype' => 'string',
        ];
        $tab[] = [
            'id'       => 5,
            'table'    => self::getTable(),
            'field'    => 'extensions_count',
            'name'     => __('Qtd. Ramais', 'newmanagement'),
            'datatype' => 'integer',
        ];
        $tab[] = [
            'id'       => 6,
            'table'    => self::getTable(),
            'field'    => 'comment',
            'name'     => __('Comentário', 'newmanagement'),
            'datatype' => 'text',
        ];
        $tab[] = [
            'id'            => 19,
            'table'         => self::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id'            => 121,
            'table'         => self::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
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

        $name             = htmlspecialchars($this->fields['name']             ?? '', ENT_QUOTES);
        $provider         = htmlspecialchars($this->fields['provider']         ?? '', ENT_QUOTES);
        $cloud_region     = htmlspecialchars($this->fields['cloud_region']     ?? '', ENT_QUOTES);
        $sip_trunk        = htmlspecialchars($this->fields['sip_trunk']        ?? '', ENT_QUOTES);
        $extensions_count = (int) ($this->fields['extensions_count']           ?? 0);
        $comment          = htmlspecialchars($this->fields['comment']          ?? '', ENT_QUOTES);

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="name">' . __('Nome', 'newmanagement') . ' <span style="color:red">*</span></label></td>';
        echo '<td><input type="text" id="name" name="name" value="' . $name . '" class="form-control" required></td>';
        echo '<td><label for="provider">' . __('Provedor', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="provider" name="provider" value="' . $provider . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="cloud_region">' . __('Região Cloud', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="cloud_region" name="cloud_region" value="' . $cloud_region . '" class="form-control"></td>';
        echo '<td><label for="extensions_count">' . __('Qtd. Ramais', 'newmanagement') . '</label></td>';
        echo '<td><input type="number" id="extensions_count" name="extensions_count" value="' . $extensions_count . '" class="form-control" min="0"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="sip_trunk">' . __('Tronco SIP', 'newmanagement') . '</label></td>';
        echo '<td colspan="3"><input type="text" id="sip_trunk" name="sip_trunk" value="' . $sip_trunk . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="comment">' . __('Comentário', 'newmanagement') . '</label></td>';
        echo '<td colspan="3"><textarea id="comment" name="comment" class="form-control" rows="3">' . $comment . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }
}
