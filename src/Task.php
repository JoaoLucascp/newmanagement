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

    /**
     * Retorna os labels de status disponíveis.
     */
    public static function getStatusLabels(): array
    {
        return [
            0 => __('Aberta', 'newmanagement'),
            1 => __('Em andamento', 'newmanagement'),
            2 => __('Concluída', 'newmanagement'),
        ];
    }

    /**
     * Renderiza o formulário via Twig (padrão do plugin).
     */
    public function showForm($ID, array $options = []): bool
    {
        global $DB, $CFG_GLPI;

        $this->initForm($ID, $options);

        $can_create = Session::haveRight(self::$rightname, CREATE);
        $can_update = Session::haveRight(self::$rightname, UPDATE);
        $can_delete = Session::haveRight(self::$rightname, DELETE);

        // Empresas para o select
        $companies = [];
        $result = $DB->request(['SELECT' => ['id', 'name'], 'FROM' => 'glpi_plugin_newmanagement_companies', 'WHERE' => ['is_deleted' => 0], 'ORDER' => 'name ASC']);
        foreach ($result as $row) {
            $companies[] = $row;
        }

        // Usuários para o select de responsável
        $users = [];
        $result = $DB->request(['SELECT' => ['id', 'name'], 'FROM' => 'glpi_users', 'WHERE' => ['is_deleted' => 0, 'is_active' => 1], 'ORDER' => 'name ASC']);
        foreach ($result as $row) {
            $users[] = $row;
        }

        $twig = plugin_newmanagement_getTwig();
        echo $twig->render('task/form.html.twig', [
            'item'        => $this->fields + ['id' => $this->fields['id'] ?? 0],
            'companies'   => $companies,
            'users'       => $users,
            'statuses'    => self::getStatusLabels(),
            'can_create'  => $can_create,
            'can_update'  => $can_update,
            'can_delete'  => $can_delete,
            'csrf_token'  => Session::getNewCSRFToken(),
            'form_url'    => self::getFormURL(),
            'search_url'  => self::getSearchURL(),
        ]);

        return true;
    }
}
