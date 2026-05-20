<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Chatbot — aba dentro da ficha de Empresa
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

use Glpi\Application\View\TemplateRenderer;

class Chatbot extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_chatbot';

    public static $itemtype = Company::class;
    public static $items_id = 'companies_id';

    public static function getTypeName($nb = 0): string
    {
        return _n('Chatbot', 'Chatbots', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_chatbots';
    }

    // Tabelas filhas como constantes para evitar strings hardcoded
    const TABLE_MASS_COMM       = 'glpi_plugin_newmanagement_chatbot_mass_comm';
    const TABLE_WA_RESTRICTIONS = 'glpi_plugin_newmanagement_chatbot_wa_restrictions';
    const TABLE_USERS           = 'glpi_plugin_newmanagement_chatbot_users';

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Company) {
            return self::getTypeName(1);
        }
        return '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Company) {
            $chatbot = new self();
            $chatbot->showTabForCompany((int) $item->getID());
        }
        return true;
    }

    public function defineTabs($options = []): array
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        return $ong;
    }

    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        if (!\Session::haveRight(self::$rightname, READ)) {
            echo __('Acesso negado.', 'newmanagement');
            return;
        }

        $can_write  = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete = \Session::haveRight(self::$rightname, DELETE);

        // Carrega dados do chatbot principal
        $rows = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0],
            'LIMIT' => 1,
        ]);

        $chatbot_id = 0;
        $f = [
            'id'                      => 0,
            'companies_id'            => $companies_id,
            'model'                   => '',
            'chatbot_registration_id' => '',
            'activation_date'         => '',
            'whatsapp_number'         => '',
            'access_link'             => '',
            'plan'                    => '',
            'users_count'             => '',
            'supervisors_count'       => '',
            'admins_count'            => '',
            'admin_login'             => '',
            'admin_password'          => '',
            'superadmin_login'        => '',
            'superadmin_password'     => '',
            'manager_name'            => '',
            'manager_contact'         => '',
            'manager_email'           => '',
            'social_networks'         => '',
            'comment'                 => '',
        ];

        foreach ($rows as $row) {
            foreach (array_keys($f) as $key) {
                if (isset($row[$key])) {
                    $f[$key] = $row[$key];
                }
            }
            $chatbot_id = (int) $row['id'];

            // Descriptografa senhas para exibição
            try {
                if (!empty($f['admin_password'])) {
                    $f['admin_password'] = \Toolbox::sodiumDecrypt($f['admin_password']);
                }
            } catch (\Throwable $e) {}
            try {
                if (!empty($f['superadmin_password'])) {
                    $f['superadmin_password'] = \Toolbox::sodiumDecrypt($f['superadmin_password']);
                }
            } catch (\Throwable $e) {}
        }

        // Carrega sub-tabelas como arrays simples para o Twig
        $mass_comm = ($chatbot_id > 0)
            ? iterator_to_array($DB->request([
                'FROM'  => self::TABLE_MASS_COMM,
                'WHERE' => ['chatbot_id' => $chatbot_id],
                'ORDER' => 'id ASC',
              ]))
            : [];

        $wa_restrictions = ($chatbot_id > 0)
            ? iterator_to_array($DB->request([
                'FROM'  => self::TABLE_WA_RESTRICTIONS,
                'WHERE' => ['chatbot_id' => $chatbot_id],
                'ORDER' => 'restriction_date DESC',
              ]))
            : [];

        $users = ($chatbot_id > 0)
            ? iterator_to_array($DB->request([
                'FROM'  => self::TABLE_USERS,
                'WHERE' => ['chatbot_id' => $chatbot_id],
                'ORDER' => 'user_name ASC',
              ]))
            : [];

        TemplateRenderer::getInstance()->display(
            '@newmanagement/chatbot/tab.html.twig',
            [
                'f'               => $f,
                'chatbot_id'      => $chatbot_id,
                'companies_id'    => $companies_id,
                'csrf'            => \Session::getNewCSRFToken(),
                'action_url'      => \Plugin::getWebDir('newmanagement') . '/ajax/chatbot_sub.php',
                'form_action'     => $chatbot_id > 0 ? 'update_chatbot' : 'add_chatbot',
                'can_write'       => $can_write,
                'can_delete'      => $can_delete,
                'mass_comm'       => $mass_comm,
                'wa_restrictions' => $wa_restrictions,
                'users'           => $users,
            ]
        );
    }

    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        $this->showFormButtons($options);
        return true;
    }
}
