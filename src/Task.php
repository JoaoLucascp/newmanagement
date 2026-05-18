<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Task (Tarefas com Geolocalização)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Task extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_task';

    public static function getTypeName($nb = 0): string
    {
        return _n('Tarefa', 'Tarefas', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_tasks';
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

        // Sanitização de todos os campos — previne XSS e erros com valores null
        $name          = htmlspecialchars($this->fields['name']          ?? '', ENT_QUOTES);
        $date_due      = htmlspecialchars($this->fields['date_due']      ?? '', ENT_QUOTES);
        $km_calculated = htmlspecialchars($this->fields['km_calculated'] ?? '', ENT_QUOTES);
        $latitude      = htmlspecialchars($this->fields['latitude']      ?? '', ENT_QUOTES);
        $longitude     = htmlspecialchars($this->fields['longitude']     ?? '', ENT_QUOTES);
        $comment       = htmlspecialchars($this->fields['comment']       ?? '', ENT_QUOTES);
        $status        = (int) ($this->fields['status'] ?? 0);

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Título', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="name" value="' . $name . '" class="form-control" required></td>';
        echo '<td>' . __('Status', 'newmanagement') . '</td>';
        echo '<td>';
        $statuses = [0 => __('Aberta'), 1 => __('Em andamento'), 2 => __('Concluída')];
        echo '<select name="status" class="form-select">';
        foreach ($statuses as $val => $label) {
            $selected = ($status === $val) ? 'selected' : '';
            echo '<option value="' . $val . '" ' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES) . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Data Prevista', 'newmanagement') . '</td>';
        echo '<td><input type="datetime-local" name="date_due" value="' . $date_due . '" class="form-control"></td>';
        echo '<td>' . __('KM Calculado', 'newmanagement') . '</td>';
        echo '<td><input type="number" step="0.01" name="km_calculated" value="' . $km_calculated . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Latitude', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="latitude" value="' . $latitude . '" class="form-control"></td>';
        echo '<td>' . __('Longitude', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="longitude" value="' . $longitude . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="3">' . $comment . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }
}
