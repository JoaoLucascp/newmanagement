<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: FixedLine (Linhas Fixas)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class FixedLine extends \CommonDBTM
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

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Nome', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="name" value="' . $this->fields['name'] . '" class="form-control" required></td>';
        echo '<td>' . __('Número', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="number" value="' . $this->fields['number'] . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Operadora', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="operator" value="' . $this->fields['operator'] . '" class="form-control"></td>';
        echo '<td>' . __('Fim do contrato', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="contract_end" value="' . $this->fields['contract_end'] . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="3">' . $this->fields['comment'] . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }
}
