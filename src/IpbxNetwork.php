<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxNetwork — Rede da empresa (1:1 com IPBX)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxNetwork extends \CommonDBChild
{
    public static $itemtype  = Ipbx::class;
    public static $items_id  = 'ipbx_id';
    public static $rightname = 'plugin_newmanagement_ipbxnetwork';

    public static function getTypeName($nb = 0): string
    {
        return __('Rede da Empresa', 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_network';
    }

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Ipbx) {
            return self::getTypeName();
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

    public static function showForIpbx(Ipbx $ipbx): void
    {
        global $DB;

        $ipbx_id = $ipbx->getID();
        $h       = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
        $canEdit = $ipbx->canUpdateItem();

        // Tenta carregar registro existente (1:1)
        $row = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['ipbx_id' => $ipbx_id]])->current();
        $r   = $row ?: [];
        $v   = fn($k) => $h($r[$k] ?? '');

        echo '<form method="POST" action="' . \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_network.php">';
        echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="net_id" value="' . ($r['id'] ?? 0) . '">';
        echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);

        echo '<table class="tab_cadre_fixe">';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="network_ip">' . __('IP de rede', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="network_ip" name="network_ip" value="' . $v('network_ip') . '" class="form-control" placeholder="192.168.1.0" ' . ($canEdit ? '' : 'readonly') . '></td>';
        echo '<td><label for="netmask">' . __('Máscara de rede', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="netmask" name="netmask" value="' . $v('netmask') . '" class="form-control" placeholder="255.255.255.0" ' . ($canEdit ? '' : 'readonly') . '></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="gateway">' . __('Gateway', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="gateway" name="gateway" value="' . $v('gateway') . '" class="form-control" placeholder="192.168.1.1" ' . ($canEdit ? '' : 'readonly') . '></td>';
        echo '<td><label for="dns_primary">' . __('DNS Primário', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="dns_primary" name="dns_primary" value="' . $v('dns_primary') . '" class="form-control" placeholder="8.8.8.8" ' . ($canEdit ? '' : 'readonly') . '></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="dns_secondary">' . __('DNS Secundário', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="dns_secondary" name="dns_secondary" value="' . $v('dns_secondary') . '" class="form-control" placeholder="8.8.4.4" ' . ($canEdit ? '' : 'readonly') . '></td>';
        echo '<td></td><td></td>';
        echo '</tr>';

        echo '</table>';

        if ($canEdit) {
            echo '<div class="mt-3">';
            echo '<button type="submit" name="action" value="save_network" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar Rede', 'newmanagement') . '</button>';
            echo '</div>';
        }

        echo '</form>';
    }
}
