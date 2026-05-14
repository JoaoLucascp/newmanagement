<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxExtension — Ramais do Servidor IPBX
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxExtension extends \CommonDBChild
{
    public static $itemtype  = Ipbx::class;
    public static $items_id  = 'ipbx_id';
    public static $rightname = 'plugin_newmanagement_ipbxextension';

    public static function getTypeName($nb = 0): string
    {
        return _n('Ramal', 'Ramais', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_extensions';
    }

    // ------------------------------------------------------------------
    // Exibe a aba com a grade de ramais
    // ------------------------------------------------------------------
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

    // ------------------------------------------------------------------
    // Grade de ramais + formulário inline
    // ------------------------------------------------------------------
    public static function showForIpbx(Ipbx $ipbx): void
    {
        global $DB;

        $ipbx_id = $ipbx->getID();
        $h       = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
        $canEdit = $ipbx->canUpdateItem();

        // --- Formulário de adição / edição
        if ($canEdit) {
            echo '<div class="nm-subform card mb-3 p-3">';
            echo '<h4 class="mb-3"><i class="ti ti-phone"></i> ' . __('Adicionar / Editar Ramal', 'newmanagement') . '</h4>';
            echo '<form method="POST" action="' . \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_extension.php">';
            echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
            echo '<input type="hidden" name="ext_id" id="ext_id" value="0">';
            echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);

            echo '<div class="row g-2">';
            // Número
            echo '<div class="col-md-2"><label>' . __('Número', 'newmanagement') . '</label>';
            echo '<input type="text" name="extension_number" id="ext_number" class="form-control" placeholder="1001"></div>';
            // Senha
            echo '<div class="col-md-2"><label>' . __('Senha', 'newmanagement') . '</label>';
            echo '<div class="nm-password-wrap"><input type="password" name="extension_pass" id="ext_pass" class="form-control" autocomplete="new-password">';
            echo '<button type="button" class="btn btn-sm btn-outline-secondary nm-toggle-pass" data-target="ext_pass"><i class="ti ti-eye"></i></button></div></div>';
            // IP do aparelho
            echo '<div class="col-md-2"><label>' . __('IP do aparelho', 'newmanagement') . '</label>';
            echo '<input type="text" name="device_ip" id="ext_device_ip" class="form-control" placeholder="192.168.1.x"></div>';
            // Nome do usuário
            echo '<div class="col-md-3"><label>' . __('Nome do usuário', 'newmanagement') . '</label>';
            echo '<input type="text" name="user_name" id="ext_user_name" class="form-control"></div>';
            // Grava ligações
            echo '<div class="col-md-1"><label>' . __('Grava', 'newmanagement') . '</label>';
            echo '<select name="records_calls" id="ext_records" class="form-select">';
            echo '<option value="0">' . __('Não', 'newmanagement') . '</option>';
            echo '<option value="1">' . __('Sim', 'newmanagement') . '</option>';
            echo '</select></div>';
            // Departamento
            echo '<div class="col-md-2"><label>' . __('Departamento', 'newmanagement') . '</label>';
            echo '<input type="text" name="department" id="ext_department" class="form-control"></div>';
            echo '</div>'; // row

            echo '<div class="mt-2">';
            echo '<button type="submit" name="action" value="save_extension" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar Ramal', 'newmanagement') . '</button>';
            echo ' <button type="button" id="btn-clear-ext" class="btn btn-outline-secondary"><i class="ti ti-x"></i> ' . __('Limpar', 'newmanagement') . '</button>';
            echo '</div>';
            echo '</form></div>';
        }

        // --- Grade de ramais existentes
        $rows = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'extension_number ASC']);

        echo '<table class="tab_cadre_fixehov">';
        echo '<thead><tr>';
        echo '<th>' . __('Número', 'newmanagement') . '</th>';
        echo '<th>' . __('Senha', 'newmanagement') . '</th>';
        echo '<th>' . __('IP do aparelho', 'newmanagement') . '</th>';
        echo '<th>' . __('Usuário', 'newmanagement') . '</th>';
        echo '<th>' . __('Grava ligações', 'newmanagement') . '</th>';
        echo '<th>' . __('Departamento', 'newmanagement') . '</th>';
        if ($canEdit) {
            echo '<th>' . __('Ações', 'newmanagement') . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr class="tab_bg_1">';
            echo '<td>' . $h($row['extension_number']) . '</td>';
            echo '<td><span class="nm-masked" data-value="' . $h($row['extension_pass']) . '">••••••</span>';
            echo ' <button type="button" class="btn btn-sm btn-link p-0 nm-reveal-pass"><i class="ti ti-eye"></i></button></td>';
            echo '<td>' . $h($row['device_ip']) . '</td>';
            echo '<td>' . $h($row['user_name']) . '</td>';
            echo '<td><span class="badge ' . ($row['records_calls'] ? 'bg-success' : 'bg-secondary') . '">';
            echo $row['records_calls'] ? __('Sim', 'newmanagement') : __('Não', 'newmanagement');
            echo '</span></td>';
            echo '<td>' . $h($row['department']) . '</td>';
            if ($canEdit) {
                echo '<td class="nowrap">';
                echo '<button type="button" class="btn btn-sm btn-outline-primary nm-edit-ext" ';
                echo 'data-id="' . $row['id'] . '" ';
                echo 'data-number="' . $h($row['extension_number']) . '" ';
                echo 'data-pass="' . $h($row['extension_pass']) . '" ';
                echo 'data-ip="' . $h($row['device_ip']) . '" ';
                echo 'data-user="' . $h($row['user_name']) . '" ';
                echo 'data-records="' . $row['records_calls'] . '" ';
                echo 'data-dept="' . $h($row['department']) . '">';
                echo '<i class="ti ti-pencil"></i></button> ';

                echo '<form method="POST" action="' . \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_extension.php" style="display:inline">';
                echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
                echo '<input type="hidden" name="ext_id" value="' . $row['id'] . '">';
                echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);
                echo '<button type="submit" name="action" value="delete_extension" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Excluir este ramal?\')">';
                echo '<i class="ti ti-trash"></i></button>';
                echo '</form>';
                echo '</td>';
            }
            echo '</tr>';
        }

        if (iterator_count($rows) === 0) {
            $cols = $canEdit ? 7 : 6;
            echo '<tr><td colspan="' . $cols . '" class="center">' . __('Nenhum ramal cadastrado.', 'newmanagement') . '</td></tr>';
        }

        echo '</tbody></table>';

        // Script para preencher o formulário ao editar
        echo '<script>
        document.querySelectorAll(".nm-edit-ext").forEach(function(btn) {
            btn.addEventListener("click", function() {
                document.getElementById("ext_id").value        = this.dataset.id;
                document.getElementById("ext_number").value    = this.dataset.number;
                document.getElementById("ext_pass").value      = this.dataset.pass;
                document.getElementById("ext_device_ip").value = this.dataset.ip;
                document.getElementById("ext_user_name").value = this.dataset.user;
                document.getElementById("ext_records").value   = this.dataset.records;
                document.getElementById("ext_department").value= this.dataset.dept;
                document.getElementById("ext_number").focus();
            });
        });
        document.getElementById("btn-clear-ext") && document.getElementById("btn-clear-ext").addEventListener("click", function() {
            ["ext_id","ext_number","ext_pass","ext_device_ip","ext_user_name","ext_department"].forEach(function(id){
                var el = document.getElementById(id); if(el) el.value = id === "ext_id" ? "0" : "";
            });
            var sel = document.getElementById("ext_records"); if(sel) sel.value = "0";
        });
        </script>';
    }
}
