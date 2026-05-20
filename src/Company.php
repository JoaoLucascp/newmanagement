<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Company (Empresas)
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Company extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_company';

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
            'id'       => 2,
            'table'    => self::getTable(),
            'field'    => 'cnpj',
            'name'     => __('CNPJ', 'newmanagement'),
            'datatype' => 'string',
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
            'id'       => 6,
            'table'    => self::getTable(),
            'field'    => 'address',
            'name'     => __('Endereco', 'newmanagement'),
            'datatype' => 'text',
        ];
        $tab[] = [
            'id'       => 7,
            'table'    => self::getTable(),
            'field'    => 'contract_status',
            'name'     => __('Status do Contrato', 'newmanagement'),
            'datatype' => 'specific',
        ];
        $tab[] = [
            'id'       => 8,
            'table'    => self::getTable(),
            'field'    => 'comment',
            'name'     => __('Comentario', 'newmanagement'),
            'datatype' => 'text',
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

    /**
     * Abas da ficha de Empresa.
     * Ordem: Empresa → IPBX → Linha Fixa → Chatbot
     *        → Documentos → Links → Notas → Histórico
     */
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

        // ------------------------------------------------------------------
        // [FIX A3] Registra o script externo via helper nativo do GLPI.
        // Isso garante que o JS seja carregado no <head> de forma correta,
        // sem bloco <script> inline dentro do método showForm().
        // ------------------------------------------------------------------
        \Html::requireJs('newmanagement_company_form');

        // ------------------------------------------------------------------
        // Linha 1: Nome (obrigatório) | ID (somente leitura)
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Nome', 'newmanagement') . ' <span class="required">*</span></td>';
        echo '<td>';
        echo '<input type="text" id="name" name="name"'
            . ' value="' . htmlspecialchars($this->fields['name'] ?? '', ENT_QUOTES) . '"'
            . ' class="form-control" required>';
        echo '</td>';
        echo '<td>' . __('ID', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<input type="text" class="form-control" value="'
            . ($ID > 0 ? $ID : __('Gerado automaticamente', 'newmanagement'))
            . '" disabled>';
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 2: CNPJ (input-group com botão Buscar) | Razão Social
        // ------------------------------------------------------------------
        $cnpj = htmlspecialchars($this->fields['cnpj'] ?? '', ENT_QUOTES);

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('CNPJ', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<div class="input-group">';
        echo '<input type="text" id="cnpj" name="cnpj" value="' . $cnpj . '"'
            . ' class="form-control" placeholder="00.000.000/0000-00" maxlength="18">';
        echo '<button type="button" class="btn btn-outline-secondary btn-sm"'
            . ' id="btn-buscar-cnpj" title="' . __('Buscar CNPJ na BrasilAPI', 'newmanagement') . '">';
        echo '<i class="ti ti-search"></i> ' . __('Buscar', 'newmanagement');
        echo '</button>';
        echo '</div>';
        echo '<span id="cnpj-feedback" class="nm-feedback"></span>';
        echo '</td>';
        echo '<td>' . __('Razao Social', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<input type="text" id="razao_social" name="razao_social"'
            . ' value="' . htmlspecialchars($this->fields['razao_social'] ?? '', ENT_QUOTES) . '"'
            . ' class="form-control">';
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 3: E-mail | Telefone
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('E-mail', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<input type="email" id="email" name="email"'
            . ' value="' . htmlspecialchars($this->fields['email'] ?? '', ENT_QUOTES) . '"'
            . ' class="form-control">';
        echo '</td>';
        echo '<td>' . __('Telefone', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<input type="text" id="phone" name="phone"'
            . ' value="' . htmlspecialchars($this->fields['phone'] ?? '', ENT_QUOTES) . '"'
            . ' class="form-control" placeholder="(00) 00000-0000">';
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 4: CEP (input-group com botão Buscar) | Status do Contrato
        // ------------------------------------------------------------------
        $cep             = htmlspecialchars($this->fields['cep'] ?? '', ENT_QUOTES);
        $contract_status = (int) ($this->fields['contract_status'] ?? self::CONTRACT_NO_CONTRACT);

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('CEP', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<div class="input-group">';
        echo '<input type="text" id="cep" name="cep" value="' . $cep . '"'
            . ' class="form-control" placeholder="00000-000" maxlength="9">';
        echo '<button type="button" class="btn btn-outline-secondary btn-sm"'
            . ' id="btn-buscar-cep" title="' . __('Buscar CEP na BrasilAPI', 'newmanagement') . '">';
        echo '<i class="ti ti-search"></i> ' . __('Buscar', 'newmanagement');
        echo '</button>';
        echo '</div>';
        echo '<span id="cep-feedback" class="nm-feedback"></span>';
        echo '</td>';
        echo '<td>' . __('Status do Contrato', 'newmanagement') . '</td>';
        echo '<td>';
        \Dropdown::showFromArray('contract_status', self::getContractStatusOptions(), [
            'value'               => $contract_status,
            'display_emptychoice' => false,
        ]);
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 5: Endereço
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Endereco', 'newmanagement') . '</td>';
        echo '<td colspan="3">';
        echo '<textarea id="address" name="address" class="form-control" rows="2">';
        echo htmlspecialchars($this->fields['address'] ?? '', ENT_QUOTES);
        echo '</textarea>';
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 6: Comentário
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentario', 'newmanagement') . '</td>';
        echo '<td colspan="3">';
        echo '<textarea id="comment" name="comment" class="form-control" rows="3">';
        echo htmlspecialchars($this->fields['comment'] ?? '', ENT_QUOTES);
        echo '</textarea>';
        echo '</td>';
        echo '</tr>';

        $this->showFormButtons($options);

        return true;
    }
}
