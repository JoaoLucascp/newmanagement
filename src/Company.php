<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Company (Empresas)
 */

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

namespace GlpiPlugin\Newmanagement;

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
