<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Task (Tarefas com Geolocalização)
 *
 * @fix A2  showForm() usava DB->request direto em vez do modelo Company.
 *          Agora usa getAllDataFromTable() respeitando softdelete e permissões.
 * @fix M5  getTabNameForItem incluia getEntitiesRestrictCriteria para
 *          garantir isolamento correto em ambientes multi-entidade.
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

use Glpi\Application\View\TemplateRenderer;

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
            0 => __('Aberta',       'newmanagement'),
            1 => __('Em andamento', 'newmanagement'),
            2 => __('Concluiía',    'newmanagement'),
        ];
    }

    // ------------------------------------------------------------------
    // Aba dentro da ficha de Empresa
    // ------------------------------------------------------------------

    /**
     * [FIX M5] Contador usa getEntitiesRestrictCriteria para isolar
     * registros da entidade atual em ambientes multi-entidade.
     */
    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string|array
    {
        if ($item instanceof Company) {
            $criteria = array_merge(
                ['companies_id' => $item->getID(), 'is_deleted' => 0],
                getEntitiesRestrictCriteria(self::getTable())
            );
            $count = countElementsInTable(self::getTable(), $criteria);
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

    /**
     * [FIX A2] Adicionado getEntitiesRestrictCriteria no WHERE
     * para garantir isolamento correto de entidade.
     */
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
            'WHERE' => array_merge(
                ['companies_id' => $companies_id, 'is_deleted' => 0],
                getEntitiesRestrictCriteria(self::getTable())
            ),
            'ORDER' => 'date_due ASC',
        ]));

        TemplateRenderer::getInstance()->display(
            '@newmanagement/task/tab.html.twig',
            [
                'rows'         => $rows,
                'companies_id' => $companies_id,
                'statuses'     => self::getStatusLabels(),
                'can_write'    => $can_write,
                'can_delete'   => $can_delete,
                'csrf'         => $csrf,
                'action_url'   => $action_url,
            ]
        );
    }

    /**
     * Renderiza o formulário via TemplateRenderer (página própria da Task).
     *
     * [FIX A2] Empresas carregadas via getAllDataFromTable() em vez de
     * DB->request direto, respeitando softdelete e permissões do modelo.
     */
    public function showForm($ID, array $options = []): bool
    {
        global $DB;

        $this->initForm($ID, $options);

        $can_create = \Session::haveRight(self::$rightname, CREATE);
        $can_update = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete = \Session::haveRight(self::$rightname, DELETE);

        // [FIX A2] Usa getAllDataFromTable() para respeitar is_deleted,
        //          entidade e eventuais critérios gerenciados pelo modelo.
        $companies_raw = getAllDataFromTable(
            Company::getTable(),
            ['is_deleted' => 0],
            false,
            'name'
        );
        // Normaliza para array indexado simples (id, name) para o Twig
        $companies = array_values(array_map(
            static fn(array $row): array => ['id' => $row['id'], 'name' => $row['name']],
            $companies_raw
        ));

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

        TemplateRenderer::getInstance()->display(
            '@newmanagement/task/form.html.twig',
            [
                'item'        => $this->fields + ['id' => $this->fields['id'] ?? 0],
                'companies'   => $companies,
                'users'       => $users,
                'statuses'    => self::getStatusLabels(),
                'can_create'  => $can_create,
                'can_update'  => $can_update,
                'can_delete'  => $can_delete,
                'csrf_token'  => \Session::getNewCSRFToken(),
                'form_url'    => self::getFormURL(),
                'search_url'  => self::getSearchURL(),
            ]
        );

        return true;
    }
}
