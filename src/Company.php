<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Company (Empresas)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

use Glpi\Application\View\TemplateRenderer;

class Company extends \CommonDBTM
{
    public static string $rightname = 'plugin_newmanagement_company';

    const CONTRACT_NO_CONTRACT = 0;
    const CONTRACT_ACTIVE      = 1;
    const CONTRACT_CANCELLED   = 2;

    public static function getTypeName($nb = 0): string
    {
        return _n('Empresa', 'Empresas', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_companies';
    }

    public static function getContractStatusOptions(): array
    {
        return [
            self::CONTRACT_NO_CONTRACT => __('Sem contrato', 'newmanagement'),
            self::CONTRACT_ACTIVE      => __('Ativo',        'newmanagement'),
            self::CONTRACT_CANCELLED   => __('Cancelado',    'newmanagement'),
        ];
    }

    // -------------------------------------------------------
    // Validação de CNPJ (backend)
    // -------------------------------------------------------

    private static function sanitizeCnpj(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj);
    }

    public static function isValidCnpj(string $cnpj): bool
    {
        $cnpj = self::sanitizeCnpj($cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $sum    = 0;
        $weight = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weight[$i];
        }
        $remainder = $sum % 11;
        $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        $sum    = 0;
        $weight = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weight[$i];
        }
        $remainder = $sum % 11;
        $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cnpj[13] === $digit2;
    }

    private static function formatCnpj(string $cnpj): string
    {
        $digits = self::sanitizeCnpj($cnpj);
        if (strlen($digits) !== 14) {
            return $cnpj;
        }
        return vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($digits));
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    private function prepareInput(array $input)
    {
        if (isset($input['name']) && trim($input['name']) === '') {
            \Session::addMessageAfterRedirect(
                __('O campo Nome é obrigatório.', 'newmanagement'),
                true,
                ERROR
            );
            return false;
        }

        if (!empty($input['cnpj'])) {
            $cnpj = trim($input['cnpj']);

            if (!self::isValidCnpj($cnpj)) {
                \Session::addMessageAfterRedirect(
                    __('CNPJ inválido. Verifique os dígitos e tente novamente.', 'newmanagement'),
                    true,
                    ERROR
                );
                return false;
            }

            $input['cnpj'] = self::formatCnpj($cnpj);
        }

        if (!empty($input['email'])) {
            $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
            if ($email === false) {
                \Session::addMessageAfterRedirect(
                    __('E-mail inválido.', 'newmanagement'),
                    true,
                    ERROR
                );
                return false;
            }
            $input['email'] = $email;
        }

        return $input;
    }

    // -------------------------------------------------------
    // Search options, menu, tabs, formulário
    // -------------------------------------------------------

    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];

        $tab[] = [
            'id'            => 1,
            'table'         => self::getTable(),
            'field'         => 'name',
            'name'          => __('Nome', 'newmanagement'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id'            => 2,
            'table'         => self::getTable(),
            'field'         => 'cnpj',
            'name'          => __('CNPJ', 'newmanagement'),
            'datatype'      => 'string',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id'       => 3,
            'table'    => self::getTable(),
            'field'    => 'razao_social',
            'name'     => __('Razao Social', 'newmanagement'),
            'datatype' => 'string',
        ];
        $tab[] = [
            'id'       => 4,
            'table'    => self::getTable(),
            'field'    => 'phone',
            'name'     => __('Telefone', 'newmanagement'),
            'datatype' => 'string',
        ];
        $tab[] = [
            'id'       => 5,
            'table'    => self::getTable(),
            'field'    => 'email',
            'name'     => __('E-mail', 'newmanagement'),
            'datatype' => 'email',
        ];
        $tab[] = [
            'id'            => 6,
            'table'         => self::getTable(),
            'field'         => 'address',
            'name'          => __('Endereco', 'newmanagement'),
            'datatype'      => 'text',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id'       => 7,
            'table'    => self::getTable(),
            'field'    => 'contract_status',
            'name'     => __('Status do Contrato', 'newmanagement'),
            'datatype' => 'specific',
        ];
        $tab[] = [
            'id'            => 8,
            'table'         => self::getTable(),
            'field'         => 'comment',
            'name'          => __('Comentario', 'newmanagement'),
            'datatype'      => 'text',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id'            => 19,
            'table'         => self::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id'            => 121,
            'table'         => self::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    public static function getMenuContent(): array
    {
        $menu = [];

        $menu['title'] = 'Newmanagement';
        $menu['page']  = '/plugins/newmanagement/front/company.php';
        $menu['icon']  = 'ti ti-building';

        if (\Session::haveRight('plugin_newmanagement_company', READ)) {
            $menu['options']['company']['title']           = __('Empresas', 'newmanagement');
            $menu['options']['company']['page']            = '/plugins/newmanagement/front/company.php';
            $menu['options']['company']['icon']            = 'ti ti-building';
            $menu['options']['company']['links']['search'] = '/plugins/newmanagement/front/company.php';
            $menu['options']['company']['links']['add']    = '/plugins/newmanagement/front/company.php?action=add';
        }

        if (\Session::haveRight('plugin_newmanagement_ipbx', READ)) {
            $menu['options']['ipbx']['title']           = __('IPBX On-Premise', 'newmanagement');
            $menu['options']['ipbx']['page']            = '/plugins/newmanagement/front/ipbx.php';
            $menu['options']['ipbx']['icon']            = 'ti ti-server';
            $menu['options']['ipbx']['links']['search'] = '/plugins/newmanagement/front/ipbx.php';
            $menu['options']['ipbx']['links']['add']    = '/plugins/newmanagement/front/ipbx.php?action=add';
        }

        if (\Session::haveRight('plugin_newmanagement_ipbxcloud', READ)) {
            $menu['options']['ipbxcloud']['title']           = __('IPBX Cloud', 'newmanagement');
            $menu['options']['ipbxcloud']['page']            = '/plugins/newmanagement/front/ipbxcloud.php';
            $menu['options']['ipbxcloud']['icon']            = 'ti ti-cloud';
            $menu['options']['ipbxcloud']['links']['search'] = '/plugins/newmanagement/front/ipbxcloud.php';
            $menu['options']['ipbxcloud']['links']['add']    = '/plugins/newmanagement/front/ipbxcloud.php?action=add';
        }

        if (\Session::haveRight('plugin_newmanagement_chatbot', READ)) {
            $menu['options']['chatbot']['title']           = __('Chatbots', 'newmanagement');
            $menu['options']['chatbot']['page']            = '/plugins/newmanagement/front/chatbot.php';
            $menu['options']['chatbot']['icon']            = 'ti ti-robot';
            $menu['options']['chatbot']['links']['search'] = '/plugins/newmanagement/front/chatbot.php';
            $menu['options']['chatbot']['links']['add']    = '/plugins/newmanagement/front/chatbot.php?action=add';
        }

        if (\Session::haveRight('plugin_newmanagement_fixedline', READ)) {
            $menu['options']['fixedline']['title']           = __('Linhas Fixas', 'newmanagement');
            $menu['options']['fixedline']['page']            = '/plugins/newmanagement/front/fixedline.php';
            $menu['options']['fixedline']['icon']            = 'ti ti-phone';
            $menu['options']['fixedline']['links']['search'] = '/plugins/newmanagement/front/fixedline.php';
            $menu['options']['fixedline']['links']['add']    = '/plugins/newmanagement/front/fixedline.php?action=add';
        }

        if (\Session::haveRight('plugin_newmanagement_task', READ)) {
            $menu['options']['task']['title']           = __('Tarefas', 'newmanagement');
            $menu['options']['task']['page']            = '/plugins/newmanagement/front/task.php';
            $menu['options']['task']['icon']            = 'ti ti-checklist';
            $menu['options']['task']['links']['search'] = '/plugins/newmanagement/front/task.php';
            $menu['options']['task']['links']['add']    = '/plugins/newmanagement/front/task.php?action=add';
        }

        return $menu;
    }

    public function defineTabs($options = []): array
    {
        $ong = [];

        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Ipbx::class,      $ong, $options);
        $this->addStandardTab(FixedLine::class, $ong, $options);
        $this->addStandardTab(Chatbot::class,   $ong, $options);

        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('Link',          $ong, $options);
        $this->addStandardTab('Notepad',       $ong, $options);
        $this->addStandardTab('Log',           $ong, $options);

        return $ong;
    }

    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $contract_status = (int) ($this->fields['contract_status'] ?? self::CONTRACT_NO_CONTRACT);

        $id_label = $ID > 0
            ? (string) $ID
            : __('Gerado automaticamente', 'newmanagement');

        TemplateRenderer::getInstance()->display(
            '@newmanagement/company/form.html.twig',
            [
                'item'              => $this,
                'id'                => $ID,
                'id_label'          => $id_label,
                'contract_options'  => self::getContractStatusOptions(),
                'contract_selected' => $contract_status,
                'params'            => $options,
            ]
        );

        $this->showFormButtons($options);

        return true;
    }
}
