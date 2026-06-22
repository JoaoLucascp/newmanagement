<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxExtension (sub-tabela de ramais do IPBX)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxExtension extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_ipbx';
    public static string $itemtype  = 'GlpiPlugin\\Newmanagement\\Ipbx';
    public static string $items_id  = 'ipbx_id';

    public static function getTypeName($nb = 0): string
    {
        return _n('Ramal IPBX', 'Ramais IPBX', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return Ipbx::TABLE_EXTENSIONS;
    }
}
