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

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_cloud';
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
        echo '<td><input type="text" name="name" value="' . $this->fields['name'] . '" class="form-control" required></td>';
        echo '<td>' . __('Provedor', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="provider" value="' . $this->fields['provider'] . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Região Cloud', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="cloud_region" value="' . $this->fields['cloud_region'] . '" class="form-control"></td>';
        echo '<td>' . __('Ramal (qtd)', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="extensions_count" value="' . $this->fields['extensions_count'] . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Tronco SIP', 'newmanagement') . '</td>';
        echo '<td colspan="3"><input type="text" name="sip_trunk" value="' . $this->fields['sip_trunk'] . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="3">' . $this->fields['comment'] . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }
}
