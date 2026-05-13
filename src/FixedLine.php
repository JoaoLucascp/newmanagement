<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: FixedLine (Linhas Fixas)
 */

namespace GlpiPlugin\Newmanagement;

use CommonDBTM;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class FixedLine extends CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_fixedline';

    public static function getTypeName($nb = 0): string
    {
        return _n('Linha Fixa', 'Linhas Fixas', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_fixedlines';
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
