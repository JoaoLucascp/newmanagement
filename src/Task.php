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

    // ------------------------------------------------------------------
    // Aba dentro da ficha de Empresa
    // ------------------------------------------------------------------

    /**
     * Retorna o nome da aba com contador de tarefas.
     *
     * Fix [M5]: inclui getEntitiesRestrictCriteria() para que ambientes
     * multi-entidade não exibam contagem de tarefas de outras entidades.
     */
    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string|array
    {
        if ($item instanceof Company) {
            $count = countElementsInTable(
                self::getTable(),
                [
                    'companies_id' => $item->getID(),
                    'is_deleted'   => 0,
                ] + getEntitiesRestrictCriteria(self::getTable())
            );
            return self::createTabEntry(self::getTypeName(2), $count);
        }
        return '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Company) {
            self::showForCompany($item);
        }
        return true;
    }

    public static function showForCompany(Company $company): void
    {
        global $DB;

        $companies_id = $company->getID();
        $can_write    = \Session::haveRight(self::$rightname, CREATE);
        $can_delete   = \Session::haveRight(self::$rightname, DELETE);
        $csrf         = \Session::getNewCSRFToken();
        $action_url   = \Plugin::getWebDir('newmanagement') . '/ajax/task_action.php';

        $rows = iterator_to_array($DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0],
            'ORDER' => 'date_due ASC',
        ]));

        $twig = plugin_newmanagement_getTwig();
        echo $twig->render('task/tab.html.twig', [
            'rows'         => $rows,
            'companies_id' => $companies_id,
            'statuses'     => self::getStatusLabels(),
            'can_write'    => $can_write,
            'can_delete'   => $can_delete,
            'csrf'         => $csrf,
            'action_url'   => $action_url,
        ]);
    }

    /**
     * Renderiza o formulário via TemplateRenderer (página própria da Task).
     *
     * Fix [A2]: substitui query direta em glpi_plugin_newmanagement_companies
     * por getAllDataFromTable(), que respeita softdelete, entidade e helpers
     * nativos do GLPI — eliminando duplicação de lógica e risco de dados
     * de outras entidades vazarem no select.
     */
    public function showForm($ID, array $options = []): bool
    {
        global $DB;

        $this->initForm($ID, $options);

        $can_create = \Session::haveRight(self::$rightname, CREATE);
        $can_update = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete = \Session::haveRight(self::$rightname, DELETE);

        // Fix [A2]: getAllDataFromTable em vez de $DB->request direto.
        // Respeita is_deleted, order e todos os helpers do modelo Company.
        $companies = getAllDataFromTable(
            Company::getTable(),
            ['is_deleted' => 0],
            false,
            'name'
        );

        // Usuários ativos para o select de responsável
        $users = [];
        $result = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_users',
            'WHERE'  => ['is_deleted' => 0, 'is_active' => 1],
            'ORDER'  => 'name ASC',
        ]);
        foreach ($result as $row) {
            $users[] = $row;
        }

        $twig = plugin_newmanagement_getTwig();
        echo $twig->render('task/form.html.twig', [
            'item'        => $this->fields + ['id' => $this->fields['id'] ?? 0],
            'companies'   => array_values($companies),
            'users'       => $users,
            'statuses'    => self::getStatusLabels(),
            'can_create'  => $can_create,
            'can_update'  => $can_update,
            'can_delete'  => $can_delete,
            'csrf_token'  => \Session::getNewCSRFToken(),
            'form_url'    => self::getFormURL(),
            'search_url'  => self::getSearchURL(),
        ]);

        return true;
    }
}
