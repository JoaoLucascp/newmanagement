<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: FixedLine (Linhas Fixas)
 *
 * Nota: $rightname reutiliza 'plugin_newmanagement_ipbx' intencionalmente,
 * pois Linhas Fixas faz parte do módulo IPBX e compartilha suas permissões.
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class FixedLine extends \CommonDBTM
{
    /** Compartilha as permissões do módulo IPBX (intencional). */
    public static $rightname = 'plugin_newmanagement_ipbx';
    public static $itemtype  = Company::class;
    public static $items_id  = 'companies_id';

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
        return ($item instanceof Company) ? self::getTypeName(1) : '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Company) {
            (new self())->displayTabContentForCompany((int) $item->getID());
        }
        return true;
    }

    public function displayTabContentForCompany(int $companies_id): void
    {
        global $DB;

        // Verificação de direito de visualização
        if (!\Session::haveRight(self::$rightname, READ)) {
            echo __('Acesso negado.', 'newmanagement');
            return;
        }

        $can_write = \Session::haveRight(self::$rightname, UPDATE);
        $ro        = $can_write ? '' : ' readonly disabled';

        // Buscar o IPBX vinculado a esta empresa usando Ipbx::getTable()
        $ipbx_id  = 0;
        $ipbx_rows = $DB->request([
            'FROM'  => Ipbx::getTable(),
            'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0],
            'LIMIT' => 1,
        ]);
        foreach ($ipbx_rows as $ipbx_row) {
            $ipbx_id = (int) $ipbx_row['id'];
        }

        $line_id = 0;
        $f = [
            'pilot_number'      => '',
            'ddr_count'         => '',
            'status'            => 1,
            'operator'          => '',
            'channels'          => '',
            'proxy_ip'          => '',
            'line_type'         => '',
            'audio_ip'          => '',
            'proxy_port'        => '',
            'portability_date'  => '',
            'previous_operator' => '',
            'activation_date'   => '',
            'expiration_date'   => '',
            'comment'           => '',
        ];

        if ($ipbx_id > 0) {
            foreach ($DB->request(['FROM' => self::getTable(), 'WHERE' => ['ipbx_id' => $ipbx_id], 'LIMIT' => 1]) as $row) {
                $line_id = (int) $row['id'];
                foreach (array_keys($f) as $k) {
                    if (isset($row[$k])) $f[$k] = $row[$k];
                }
            }
        }

        $v           = fn($k) => htmlspecialchars((string) ($f[$k] ?? ''), ENT_QUOTES);
        $form_action = $line_id > 0 ? 'update_line' : 'add_line';
        $status_opts = [1 => __('Ativo', 'newmanagement'), 2 => __('Cancelado', 'newmanagement')];
        $csrf        = \Session::getNewCSRFToken();
        $action      = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';

        echo '<form id="nm-lines-form" autocomplete="off" onsubmit="return false;">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action"      value="' . $form_action . '">';
        echo '<input type="hidden" name="id"          value="' . $line_id . '">';
        echo '<input type="hidden" name="ipbx_id"     value="' . $ipbx_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';

        echo '<table class="tab_cadre_fixe">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Número Piloto', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="pilot_number" autocomplete="off" value="' . $v('pilot_number') . '" class="form-control" placeholder="Ex: 1131000000"' . $ro . '></td>';
        echo '<td>' . __('Quantidade de DDR', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="ddr_count" autocomplete="off" value="' . $v('ddr_count') . '" class="form-control" min="0" placeholder="0"' . $ro . '></td>';
        echo '<td>' . __('Status', 'newmanagement') . '</td>';
        echo '<td><select name="status" class="form-select"' . $ro . '>';
        foreach ($status_opts as $val => $lbl) {
            echo '<option value="' . $val . '"' . ((int) $f['status'] === $val ? ' selected' : '') . '>' . $lbl . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Operadora', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="operator" autocomplete="off" value="' . $v('operator') . '" class="form-control" placeholder="Ex: Vivo"' . $ro . '></td>';
        echo '<td>' . __('Quantidade de Canais', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="channels" autocomplete="off" value="' . $v('channels') . '" class="form-control" min="0" placeholder="0"' . $ro . '></td>';
        echo '<td>' . __('IP Proxy', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="proxy_ip" autocomplete="off" value="' . $v('proxy_ip') . '" class="form-control" placeholder="Ex: 200.x.x.x"' . $ro . '></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Tipo', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="line_type" autocomplete="off" value="' . $v('line_type') . '" class="form-control" placeholder="Ex: SIP, E1"' . $ro . '></td>';
        echo '<td>' . __('IP Tráfego Áudio', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="audio_ip" autocomplete="off" value="' . $v('audio_ip') . '" class="form-control" placeholder="Ex: 200.x.x.x"' . $ro . '></td>';
        echo '<td>' . __('Porta Proxy', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="proxy_port" autocomplete="off" value="' . $v('proxy_port') . '" class="form-control" placeholder="Ex: 5060"' . $ro . '></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Data Portabilidade', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="portability_date" autocomplete="off" value="' . $v('portability_date') . '" class="form-control"' . $ro . '></td>';
        echo '<td>' . __('Operadora Anterior', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="previous_operator" autocomplete="off" value="' . $v('previous_operator') . '" class="form-control" placeholder="Ex: Claro"' . $ro . '></td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Data de Ativação', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="activation_date" autocomplete="off" value="' . $v('activation_date') . '" class="form-control"' . $ro . '></td>';
        echo '<td>' . __('Data de Vencimento', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="expiration_date" autocomplete="off" value="' . $v('expiration_date') . '" class="form-control"' . $ro . '></td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="5"><textarea name="comment" class="form-control" rows="2"' . $ro . '>' . $v('comment') . '</textarea></td>';
        echo '</tr>';

        echo '</table>';

        if ($can_write) {
            echo '<div style="text-align:right;padding:var(--space-4,1rem) 0">';
            echo '<button type="submit" form="nm-lines-form" class="btn btn-primary">';
            echo '<i class="ti ti-device-floppy"></i> ' . __('Salvar Linha', 'newmanagement');
            echo '</button>';
            echo '</div>';
        }

        echo '</form>';
    }
}
