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

    /**
     * Status do contrato: constantes para legibilidade
     */
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

    /**
     * Retorna as opções de Status do Contrato
     */
    public static function getContractStatusOptions(): array
    {
        return [
            self::CONTRACT_NO_CONTRACT => __('Sem contrato', 'newmanagement'),
            self::CONTRACT_ACTIVE      => __('Ativo',        'newmanagement'),
            self::CONTRACT_CANCELLED   => __('Cancelado',    'newmanagement'),
        ];
    }

    /**
     * Define as colunas exibidas na listagem (Search::show)
     */
    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(1),
        ];

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
            'name'     => __('Razão Social', 'newmanagement'),
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
            'name'     => __('Endereço', 'newmanagement'),
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
            'name'     => __('Comentário', 'newmanagement'),
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

    /**
     * Menu lateral do GLPI 11
     */
    public static function getMenuContent(): array
    {
        $menu = [];

        $menu['title'] = 'Newmanagement';
        $menu['page']  = '/plugins/newmanagement/front/company.php';
        $menu['icon']  = 'ti ti-building';

        $menu['options']['company']['title']           = __('Empresas', 'newmanagement');
        $menu['options']['company']['page']            = '/plugins/newmanagement/front/company.php';
        $menu['options']['company']['icon']            = 'ti ti-building';
        $menu['options']['company']['links']['search'] = '/plugins/newmanagement/front/company.php';
        $menu['options']['company']['links']['add']    = '/plugins/newmanagement/front/company.php?action=add';

        $menu['options']['ipbx']['title']           = __('IPBX', 'newmanagement');
        $menu['options']['ipbx']['page']            = '/plugins/newmanagement/front/ipbx.php';
        $menu['options']['ipbx']['icon']            = 'ti ti-server';
        $menu['options']['ipbx']['links']['search'] = '/plugins/newmanagement/front/ipbx.php';
        $menu['options']['ipbx']['links']['add']    = '/plugins/newmanagement/front/ipbx.php?action=add';

        $menu['options']['ipbxcloud']['title']           = __('IPBX Cloud', 'newmanagement');
        $menu['options']['ipbxcloud']['page']            = '/plugins/newmanagement/front/ipbxcloud.php';
        $menu['options']['ipbxcloud']['icon']            = 'ti ti-cloud';
        $menu['options']['ipbxcloud']['links']['search'] = '/plugins/newmanagement/front/ipbxcloud.php';
        $menu['options']['ipbxcloud']['links']['add']    = '/plugins/newmanagement/front/ipbxcloud.php?action=add';

        $menu['options']['chatbot']['title']           = __('Chatbots', 'newmanagement');
        $menu['options']['chatbot']['page']            = '/plugins/newmanagement/front/chatbot.php';
        $menu['options']['chatbot']['icon']            = 'ti ti-robot';
        $menu['options']['chatbot']['links']['search'] = '/plugins/newmanagement/front/chatbot.php';
        $menu['options']['chatbot']['links']['add']    = '/plugins/newmanagement/front/chatbot.php?action=add';

        $menu['options']['fixedline']['title']           = __('Linhas Fixas', 'newmanagement');
        $menu['options']['fixedline']['page']            = '/plugins/newmanagement/front/fixedline.php';
        $menu['options']['fixedline']['icon']            = 'ti ti-phone';
        $menu['options']['fixedline']['links']['search'] = '/plugins/newmanagement/front/fixedline.php';
        $menu['options']['fixedline']['links']['add']    = '/plugins/newmanagement/front/fixedline.php?action=add';

        $menu['options']['task']['title']           = __('Tarefas', 'newmanagement');
        $menu['options']['task']['page']            = '/plugins/newmanagement/front/task.php';
        $menu['options']['task']['icon']            = 'ti ti-checklist';
        $menu['options']['task']['links']['search'] = '/plugins/newmanagement/front/task.php';
        $menu['options']['task']['links']['add']    = '/plugins/newmanagement/front/task.php?action=add';

        return $menu;
    }

    public function defineTabs($options = []): array
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        return $ong;
    }

    /**
     * Exibe o formulário de criação/edição de Empresa
     */
    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $name            = htmlspecialchars($this->fields['name']            ?? '', ENT_QUOTES);
        $cnpj            = htmlspecialchars($this->fields['cnpj']            ?? '', ENT_QUOTES);
        $razao_social    = htmlspecialchars($this->fields['razao_social']    ?? '', ENT_QUOTES);
        $email           = htmlspecialchars($this->fields['email']           ?? '', ENT_QUOTES);
        $phone           = htmlspecialchars($this->fields['phone']           ?? '', ENT_QUOTES);
        $cep             = htmlspecialchars($this->fields['cep']             ?? '', ENT_QUOTES);
        $address         = htmlspecialchars($this->fields['address']         ?? '', ENT_QUOTES);
        $comment         = htmlspecialchars($this->fields['comment']         ?? '', ENT_QUOTES);
        $contract_status = (int) ($this->fields['contract_status'] ?? self::CONTRACT_NO_CONTRACT);

        // --- Linha 1: Nome | ID ---
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="name">' . __('Nome', 'newmanagement') . ' <span style="color:var(--color-error,red)">*</span></label></td>';
        echo '<td><input type="text" id="name" name="name" value="' . $name . '" class="form-control" required></td>';
        echo '<td>' . __('ID', 'newmanagement') . '</td>';
        echo '<td><input type="text" value="' . ($ID > 0 ? $ID : __('Gerado automaticamente', 'newmanagement')) . '" class="form-control" disabled></td>';
        echo '</tr>';

        // --- Linha 2: CNPJ (com busca) | Razão Social ---
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="cnpj">' . __('CNPJ', 'newmanagement') . '</label></td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="text" id="cnpj" name="cnpj" value="' . $cnpj . '" class="form-control" placeholder="00.000.000/0000-00" maxlength="18">';
        echo '    <button type="button" class="nm-btn-search" id="btn-buscar-cnpj" onclick="nmBuscarCNPJ()" title="Buscar CNPJ na BrasilAPI">';
        echo '      <i class="ti ti-search"></i> Buscar';
        echo '    </button>';
        echo '  </div>';
        echo '  <span id="cnpj-feedback" class="nm-feedback"></span>';
        echo '</td>';
        echo '<td><label for="razao_social">' . __('Razão Social', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="razao_social" name="razao_social" value="' . $razao_social . '" class="form-control"></td>';
        echo '</tr>';

        // --- Linha 3: E-mail | Telefone ---
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="email">' . __('E-mail', 'newmanagement') . '</label></td>';
        echo '<td><input type="email" id="email" name="email" value="' . $email . '" class="form-control"></td>';
        echo '<td><label for="phone">' . __('Telefone', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="phone" name="phone" value="' . $phone . '" class="form-control" placeholder="(00) 00000-0000"></td>';
        echo '</tr>';

        // --- Linha 4: CEP (com busca) | Status do Contrato ---
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="cep">' . __('CEP', 'newmanagement') . '</label></td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="text" id="cep" name="cep" value="' . $cep . '" class="form-control" placeholder="00000-000" maxlength="9">';
        echo '    <button type="button" class="nm-btn-search" id="btn-buscar-cep" onclick="nmBuscarCEP()" title="Buscar CEP na BrasilAPI">';
        echo '      <i class="ti ti-search"></i> Buscar';
        echo '    </button>';
        echo '  </div>';
        echo '  <span id="cep-feedback" class="nm-feedback"></span>';
        echo '</td>';
        echo '<td><label for="contract_status">' . __('Status do Contrato', 'newmanagement') . '</label></td>';
        echo '<td>';
        echo '  <select id="contract_status" name="contract_status" class="form-select">';
        foreach (self::getContractStatusOptions() as $value => $label) {
            $selected = ($contract_status === $value) ? ' selected' : '';
            echo '    <option value="' . $value . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES) . '</option>';
        }
        echo '  </select>';
        echo '</td>';
        echo '</tr>';

        // --- Linha 5: Endereço (largura total) ---
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="address">' . __('Endereço', 'newmanagement') . '</label></td>';
        echo '<td colspan="3"><textarea id="address" name="address" class="form-control" rows="2">' . $address . '</textarea></td>';
        echo '</tr>';

        // --- Linha 6: Comentário (largura total) ---
        echo '<tr class="tab_bg_1">';
        echo '<td><label for="comment">' . __('Comentário', 'newmanagement') . '</label></td>';
        echo '<td colspan="3"><textarea id="comment" name="comment" class="form-control" rows="3">' . $comment . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);
        return true;
    }
}
