<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Chatbot (Chatbot Omnichannel)
 */

namespace GlpiPlugin\Newmanagement;

use CommonDBTM;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Chatbot extends CommonDBTM
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
        // TODO: renderizar template Twig
        $this->showFormButtons($options);
        return true;
    }
}
