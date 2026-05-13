<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Chatbot (Omnichannel)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Chatbot extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_chatbot';

    public static function getTypeName($nb = 0): string
    {
        return _n('Chatbot', 'Chatbots', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_chatbots';
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
        echo '<td>' . __('Plataforma', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="platform" value="' . $this->fields['platform'] . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Endpoint API', 'newmanagement') . '</td>';
        echo '<td colspan="3"><input type="text" name="api_endpoint" value="' . $this->fields['api_endpoint'] . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Canais', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="channels" class="form-control" rows="2">' . $this->fields['channels'] . '</textarea></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="3">' . $this->fields['comment'] . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }
}
