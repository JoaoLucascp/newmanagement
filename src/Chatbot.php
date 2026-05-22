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

    /**
     * Retorna o nome da aba com contador de chatbots cadastrados.
     *
     * Fix [M6]: antes retornava string estática sem contador.
     * Agora usa createTabEntry() para exibir o número junto ao título,
     * seguindo o padrão nativo do GLPI (ex.: Documentos (3)).
     */
    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Company) {
            $count = countElementsInTable(
                self::getTable(),
                ['companies_id' => $item->getID(), 'is_deleted' => 0]
            );
            return self::createTabEntry(self::getTypeName(1), $count);
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

    /**
     * Exibe a aba de Chatbot dentro da ficha de uma Empresa.
     *
     * Fix [A1]: senhas NUNCA são descriptografadas nem enviadas ao template.
     *   - 'has_admin_password'      => bool (true = senha salva no banco)
     *   - 'has_superadmin_password' => bool (true = senha salva no banco)
     * O template Twig deve:
     *   - Mostrar placeholder '\u2022\u2022\u2022\u2022\u2022\u2022' quando o bool for true.
     *   - Só enviar nova senha ao servidor se o campo vier preenchido no POST.
     *   - Nunca popular value="" com a senha real.
     */
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
            'superadmin_login'        => '',
            'manager_name'            => '',
            'manager_contact'         => '',
            'manager_email'           => '',
            'social_networks'         => '',
            'comment'                 => '',
            // Fix [A1]: booleanos indicam se a senha existe no banco,
            //           sem jamais expor o valor em texto puro.
            'has_admin_password'      => false,
            'has_superadmin_password' => false,
        ];

        foreach ($rows as $row) {
            $skip = ['admin_password', 'superadmin_password'];
            foreach (array_keys($f) as $key) {
                if (!in_array($key, $skip, true) && isset($row[$key])) {
                    $f[$key] = $row[$key];
                }
            }
            $chatbot_id = (int) $row['id'];

            // Indica ao template se uma senha está salva (para mostrar placeholder)
            // sem nunca descriptografar nem enviar o valor.
            $f['has_admin_password']      = !empty($row['admin_password']);
            $f['has_superadmin_password'] = !empty($row['superadmin_password']);
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
