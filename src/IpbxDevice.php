<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxDevice — Dispositivos do Servidor IPBX
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxDevice extends \CommonDBChild
{
    public static $itemtype  = Ipbx::class;
    public static $items_id  = 'ipbx_id';
    public static $rightname = 'plugin_newmanagement_ipbxdevice';

    public static function getTypeName($nb = 0): string
    {
        return _n('Dispositivo', 'Dispositivos', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_devices';
    }

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Ipbx) {
            $count = countElementsInTable(self::getTable(), ['ipbx_id' => $item->getID()]);
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

    public static function showForIpbx(Ipbx $ipbx): void
    {
        global $DB;

        $ipbx_id = $ipbx->getID();
        $h       = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
        $canEdit = $ipbx->canUpdateItem();

        if ($canEdit) {
            echo '<div class="nm-subform card mb-3 p-3">';
            echo '<h4 class="mb-3"><i class="ti ti-device-desktop"></i> ' . __('Adicionar / Editar Dispositivo', 'newmanagement') . '</h4>';
            echo '<form method="POST" action="' . \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_device.php">';
            echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
            echo '<input type="hidden" name="device_id" id="device_id" value="0">';
            echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);

            echo '<div class="row g-2">';
            echo '<div class="col-md-4"><label>' . __('Tipo de dispositivo', 'newmanagement') . '</label>';
            echo '<input type="text" name="device_type" id="dev_type" class="form-control" placeholder="Ex: IP Phone, ATA, Gateway"></div>';

            echo '<div class="col-md-4"><label>' . __('IP de acesso', 'newmanagement') . '</label>';
            echo '<input type="text" name="device_ip" id="dev_ip" class="form-control" placeholder="192.168.1.x"></div>';

            echo '<div class="col-md-4"><label>' . __('Senha de acesso', 'newmanagement') . '</label>';
            echo '<div class="nm-password-wrap"><input type="password" name="device_password" id="dev_pass" class="form-control" autocomplete="new-password">';
            echo '<button type="button" class="btn btn-sm btn-outline-secondary nm-toggle-pass" data-target="dev_pass"><i class="ti ti-eye"></i></button></div></div>';
            echo '</div>';

            echo '<div class="mt-2">';
            echo '<button type="submit" name="action" value="save_device" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar Dispositivo', 'newmanagement') . '</button>';
            echo ' <button type="button" id="btn-clear-dev" class="btn btn-outline-secondary"><i class="ti ti-x"></i> ' . __('Limpar', 'newmanagement') . '</button>';
            echo '</div></form></div>';
        }

        $rows = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'device_type ASC']);

        echo '<table class="tab_cadre_fixehov"><thead><tr>';
        echo '<th>' . __('Tipo', 'newmanagement') . '</th>';
        echo '<th>' . __('IP de acesso', 'newmanagement') . '</th>';
        echo '<th>' . __('Senha', 'newmanagement') . '</th>';
        if ($canEdit) echo '<th>' . __('Ações', 'newmanagement') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr class="tab_bg_1">';
            echo '<td>' . $h($row['device_type']) . '</td>';
            echo '<td>' . $h($row['device_ip']) . '</td>';
            echo '<td><span class="nm-masked" data-value="' . $h($row['device_password']) . '">••••••</span>';
            echo ' <button type="button" class="btn btn-sm btn-link p-0 nm-reveal-pass"><i class="ti ti-eye"></i></button></td>';
            if ($canEdit) {
                echo '<td class="nowrap">';
                echo '<button type="button" class="btn btn-sm btn-outline-primary nm-edit-dev" ';
                echo 'data-id="' . $row['id'] . '" data-type="' . $h($row['device_type']) . '" ';
                echo 'data-ip="' . $h($row['device_ip']) . '" data-pass="' . $h($row['device_password']) . '">';
                echo '<i class="ti ti-pencil"></i></button> ';

                echo '<form method="POST" action="' . \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_device.php" style="display:inline">';
                echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
                echo '<input type="hidden" name="device_id" value="' . $row['id'] . '">';
                echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);
                echo '<button type="submit" name="action" value="delete_device" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Excluir este dispositivo?\')">';
                echo '<i class="ti ti-trash"></i></button></form></td>';
            }
            echo '</tr>';
        }

        if (iterator_count($rows) === 0) {
            echo '<tr><td colspan="' . ($canEdit ? 4 : 3) . '" class="center">' . __('Nenhum dispositivo cadastrado.', 'newmanagement') . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<script>
        document.querySelectorAll(".nm-edit-dev").forEach(function(btn) {
            btn.addEventListener("click", function() {
                document.getElementById("device_id").value = this.dataset.id;
                document.getElementById("dev_type").value  = this.dataset.type;
                document.getElementById("dev_ip").value    = this.dataset.ip;
                document.getElementById("dev_pass").value  = this.dataset.pass;
            });
        });
        document.getElementById("btn-clear-dev") && document.getElementById("btn-clear-dev").addEventListener("click", function() {
            ["device_id","dev_type","dev_ip","dev_pass"].forEach(function(id){
                var el = document.getElementById(id); if(el) el.value = id === "device_id" ? "0" : "";
            });
        });
        </script>';
    }
}
