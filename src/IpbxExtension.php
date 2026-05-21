<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxExtension (Ramais do IPBX)
 * Exibida como aba dentro da ficha do IPBX On-Premise.
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxExtension extends \CommonDBChild
{
    public static $rightname      = 'plugin_newmanagement_ipbx';
    public static $itemtype       = Ipbx::class;
    public static $items_id       = 'ipbx_id';
    public static $mustBeAttached = false;

    public static function getTypeName($nb = 0): string
    {
        return _n('Ramal', 'Ramais', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_extensions';
    }

    // ------------------------------------------------------------------
    // Aba dentro do IPBX
    // ------------------------------------------------------------------
    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string|array
    {
        if ($item instanceof Ipbx) {
            $count = countElementsInTable(
                self::getTable(),
                ['ipbx_id' => $item->getID(), 'is_deleted' => 0]
            );
            return self::createTabEntry(self::getTypeName(2), $count);
        }
        return '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Ipbx) {
            self::showForIpbx($item);
        }
        return true;
    }

    // ------------------------------------------------------------------
    // Renderiza a lista de ramais + formulário de adição
    // ------------------------------------------------------------------
    public static function showForIpbx(Ipbx $ipbx): void
    {
        global $DB;

        $ipbx_id      = $ipbx->getID();
        $companies_id = (int) ($ipbx->fields['companies_id'] ?? 0);
        $can_write    = \Session::haveRight(self::$rightname, CREATE);
        $can_delete   = \Session::haveRight(self::$rightname, DELETE);
        $csrf         = \Session::getNewCSRFToken();
        $action_url   = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';

        $rows = iterator_to_array($DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['ipbx_id' => $ipbx_id, 'is_deleted' => 0],
            'ORDER' => 'number ASC',
        ]));

        $twig = plugin_newmanagement_getTwig();
        echo $twig->render('ipbx/tab_extensions.html.twig', [
            'rows'         => $rows,
            'ipbx_id'      => $ipbx_id,
            'companies_id' => $companies_id,
            'can_write'    => $can_write,
            'can_delete'   => $can_delete,
            'csrf'         => $csrf,
            'action_url'   => $action_url,
        ]);
    }
}
