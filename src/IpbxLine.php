<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: IpbxLine — Linha Fixa vinculada ao Servidor IPBX
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class IpbxLine extends \CommonDBChild
{
    public static $itemtype  = Ipbx::class;
    public static $items_id  = 'ipbx_id';
    public static $rightname = 'plugin_newmanagement_ipbxline';

    public static function getTypeName($nb = 0): string
    {
        return _n('Linha Fixa', 'Linhas Fixas', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_lines';
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
            echo '<h4 class="mb-3"><i class="ti ti-phone-call"></i> ' . __('Adicionar / Editar Linha Fixa', 'newmanagement') . '</h4>';
            echo '<form method="POST" action="' . \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_line.php">';
            echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
            echo '<input type="hidden" name="line_id" id="line_id" value="0">';
            echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);

            echo '<div class="row g-2">';

            // Número Piloto
            echo '<div class="col-md-3"><label>' . __('Número Piloto', 'newmanagement') . '</label>';
            echo '<input type="text" name="pilot_number" id="line_pilot" class="form-control" placeholder="(11) 3000-0000"></div>';

            // Tipo
            echo '<div class="col-md-2"><label>' . __('Tipo da linha', 'newmanagement') . '</label>';
            echo '<select name="line_type" id="line_type" class="form-select">';
            foreach (['Analógica', 'Digital E1/R2', 'SIP Trunk', 'ISDN BRI', 'VoIP'] as $t) {
                echo '<option value="' . $h($t) . '">' . $h($t) . '</option>';
            }
            echo '</select></div>';

            // Operadora
            echo '<div class="col-md-2"><label>' . __('Operadora', 'newmanagement') . '</label>';
            echo '<input type="text" name="operator" id="line_operator" class="form-control" placeholder="Vivo, Claro..."></div>';

            // Canais
            echo '<div class="col-md-1"><label>' . __('Canais', 'newmanagement') . '</label>';
            echo '<input type="number" name="channels_count" id="line_channels" class="form-control" min="0" value="0"></div>';

            // DDR
            echo '<div class="col-md-1"><label>' . __('DDR', 'newmanagement') . '</label>';
            echo '<input type="number" name="ddr_count" id="line_ddr" class="form-control" min="0" value="0"></div>';

            // Status
            echo '<div class="col-md-1"><label>' . __('Status', 'newmanagement') . '</label>';
            echo '<select name="line_status" id="line_status" class="form-select">';
            echo '<option value="0">' . __('Ativo', 'newmanagement') . '</option>';
            echo '<option value="1">' . __('Cancelado', 'newmanagement') . '</option>';
            echo '</select></div>';

            echo '</div>'; // row 1

            echo '<div class="row g-2 mt-1">';

            // IP Proxy
            echo '<div class="col-md-3"><label>' . __('IP Proxy Operadora', 'newmanagement') . '</label>';
            echo '<input type="text" name="proxy_ip" id="line_proxy_ip" class="form-control" placeholder="200.x.x.x"></div>';

            // Porta Proxy
            echo '<div class="col-md-1"><label>' . __('Porta Proxy', 'newmanagement') . '</label>';
            echo '<input type="text" name="proxy_port" id="line_proxy_port" class="form-control" placeholder="5060"></div>';

            // IP Áudio
            echo '<div class="col-md-3"><label>' . __('IP Tráfego de Áudio', 'newmanagement') . '</label>';
            echo '<input type="text" name="audio_ip" id="line_audio_ip" class="form-control" placeholder="200.x.x.x"></div>';

            // Portabilidade
            echo '<div class="col-md-2"><label>' . __('Data Portabilidade', 'newmanagement') . '</label>';
            echo '<input type="date" name="portability_date" id="line_port_date" class="form-control"></div>';

            // Operadora anterior
            echo '<div class="col-md-3"><label>' . __('Operadora Anterior', 'newmanagement') . '</label>';
            echo '<input type="text" name="previous_operator" id="line_prev_op" class="form-control"></div>';

            echo '</div>'; // row 2

            echo '<div class="row g-2 mt-1">';

            // Data Ativação
            echo '<div class="col-md-3"><label>' . __('Data de Ativação', 'newmanagement') . '</label>';
            echo '<input type="date" name="activation_date" id="line_act_date" class="form-control"></div>';

            // Data Vencimento
            echo '<div class="col-md-3"><label>' . __('Data de Vencimento', 'newmanagement') . '</label>';
            echo '<input type="date" name="expiration_date" id="line_exp_date" class="form-control"></div>';

            // Comentário
            echo '<div class="col-md-6"><label>' . __('Comentário', 'newmanagement') . '</label>';
            echo '<input type="text" name="comment" id="line_comment" class="form-control"></div>';

            echo '</div>'; // row 3

            echo '<div class="mt-2">';
            echo '<button type="submit" name="action" value="save_line" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar Linha', 'newmanagement') . '</button>';
            echo ' <button type="button" id="btn-clear-line" class="btn btn-outline-secondary"><i class="ti ti-x"></i> ' . __('Limpar', 'newmanagement') . '</button>';
            echo '</div></form></div>';
        }

        // Grade de linhas
        $rows = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['ipbx_id' => $ipbx_id], 'ORDER' => 'pilot_number ASC']);

        echo '<table class="tab_cadre_fixehov"><thead><tr>';
        foreach ([
            __('Piloto', 'newmanagement'),
            __('Tipo', 'newmanagement'),
            __('Operadora', 'newmanagement'),
            __('Canais', 'newmanagement'),
            __('DDR', 'newmanagement'),
            __('Status', 'newmanagement'),
            __('Ativação', 'newmanagement'),
            __('Vencimento', 'newmanagement'),
        ] as $th) {
            echo '<th>' . $th . '</th>';
        }
        if ($canEdit) echo '<th>' . __('Ações', 'newmanagement') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $statusClass  = $row['line_status'] ? 'bg-danger' : 'bg-success';
            $statusLabel  = $row['line_status'] ? __('Cancelado', 'newmanagement') : __('Ativo', 'newmanagement');
            echo '<tr class="tab_bg_1">';
            echo '<td>' . $h($row['pilot_number']) . '</td>';
            echo '<td>' . $h($row['line_type']) . '</td>';
            echo '<td>' . $h($row['operator']) . '</td>';
            echo '<td>' . (int)$row['channels_count'] . '</td>';
            echo '<td>' . (int)$row['ddr_count'] . '</td>';
            echo '<td><span class="badge ' . $statusClass . '">' . $statusLabel . '</span></td>';
            echo '<td>' . $h($row['activation_date']) . '</td>';
            echo '<td>' . $h($row['expiration_date']) . '</td>';
            if ($canEdit) {
                echo '<td class="nowrap">';
                // Botão editar com todos os dados via data-attributes
                echo '<button type="button" class="btn btn-sm btn-outline-primary nm-edit-line" ';
                echo 'data-id="' . $row['id'] . '" ';
                echo 'data-pilot="' . $h($row['pilot_number']) . '" ';
                echo 'data-type="' . $h($row['line_type']) . '" ';
                echo 'data-op="' . $h($row['operator']) . '" ';
                echo 'data-ch="' . (int)$row['channels_count'] . '" ';
                echo 'data-ddr="' . (int)$row['ddr_count'] . '" ';
                echo 'data-status="' . $row['line_status'] . '" ';
                echo 'data-proxy-ip="' . $h($row['proxy_ip']) . '" ';
                echo 'data-proxy-port="' . $h($row['proxy_port']) . '" ';
                echo 'data-audio-ip="' . $h($row['audio_ip']) . '" ';
                echo 'data-port-date="' . $h($row['portability_date']) . '" ';
                echo 'data-prev-op="' . $h($row['previous_operator']) . '" ';
                echo 'data-act="' . $h($row['activation_date']) . '" ';
                echo 'data-exp="' . $h($row['expiration_date']) . '" ';
                echo 'data-comment="' . $h($row['comment']) . '">';
                echo '<i class="ti ti-pencil"></i></button> ';

                echo '<form method="POST" action="' . \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_line.php" style="display:inline">';
                echo '<input type="hidden" name="ipbx_id" value="' . $ipbx_id . '">';
                echo '<input type="hidden" name="line_id" value="' . $row['id'] . '">';
                echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);
                echo '<button type="submit" name="action" value="delete_line" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Excluir esta linha?\')">';
                echo '<i class="ti ti-trash"></i></button></form></td>';
            }
            echo '</tr>';
        }

        if (iterator_count($rows) === 0) {
            echo '<tr><td colspan="' . ($canEdit ? 9 : 8) . '" class="center">' . __('Nenhuma linha fixa cadastrada.', 'newmanagement') . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<script>
        document.querySelectorAll(".nm-edit-line").forEach(function(btn) {
            btn.addEventListener("click", function() {
                var d = this.dataset;
                var m = {"line_id":d.id,"line_pilot":d.pilot,"line_operator":d.op,"line_channels":d.ch,"line_ddr":d.ddr,"line_status":d.status,"line_proxy_ip":d.proxyIp,"line_proxy_port":d.proxyPort,"line_audio_ip":d.audioIp,"line_port_date":d.portDate,"line_prev_op":d.prevOp,"line_act_date":d.act,"line_exp_date":d.exp,"line_comment":d.comment};
                Object.keys(m).forEach(function(id){var el=document.getElementById(id);if(el)el.value=m[id];});
                var ts = document.getElementById("line_type"); if(ts){for(var i=0;i<ts.options.length;i++){if(ts.options[i].value===d.type){ts.selectedIndex=i;break;}}}
            });
        });
        document.getElementById("btn-clear-line") && document.getElementById("btn-clear-line").addEventListener("click", function() {
            ["line_id","line_pilot","line_operator","line_channels","line_ddr","line_proxy_ip","line_proxy_port","line_audio_ip","line_port_date","line_prev_op","line_act_date","line_exp_date","line_comment"].forEach(function(id){
                var el=document.getElementById(id);if(el)el.value=id==="line_id"?"0":el.type==="number"?"0":"";
            });
            var sel=document.getElementById("line_status");if(sel)sel.value="0";
        });
        </script>';
    }
}
