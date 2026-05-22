<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxDevice (sub-tabela de dispositivos do IPBX)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxDevice extends \CommonDBTM
{
    public static string $rightname = 'plugin_newmanagement_ipbx';
    public static string $itemtype  = 'GlpiPlugin\\Newmanagement\\Ipbx';
    public static string $items_id  = 'ipbx_id';

    public static function getTypeName($nb = 0): string
    {
        return _n('Dispositivo IPBX', 'Dispositivos IPBX', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return Ipbx::TABLE_DEVICES;
    }
}
