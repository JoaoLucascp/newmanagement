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

        $menu['options']['company']['title']           = __('Empresas', 'newmanagement');
        $menu['options']['company']['page']            = '/plugins/newmanagement/front/company.php';
        $menu['options']['company']['icon']            = 'ti ti-building';
        $menu['options']['company']['links']['search'] = '/plugins/newmanagement/front/company.php';
        $menu['options']['company']['links']['add']    = '/plugins/newmanagement/front/company.php?action=add';

        $menu['options']['ipbx']['title']           = __('IPBX On-Premise', 'newmanagement');
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
        // Linha 1: Nome (obrigatório) | ID (somente leitura)
        // Html::autocompletionTextField() gera <input type="text"> com as
        // classes, autocomplete e atributos padrão do GLPI.
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Nome', 'newmanagement') . ' <span class="required">*</span></td>';
        echo '<td>';
        \Html::autocompletionTextField($this, 'name', [
            'required' => true,
        ]);
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
        // CNPJ precisa do botão de busca — mantido como HTML manual.
        // Razão Social é simples → usa autocompletionTextField.
        // ------------------------------------------------------------------
        $cnpj = htmlspecialchars($this->fields['cnpj'] ?? '', ENT_QUOTES);

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('CNPJ', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<div class="input-group">';
        echo '<input type="text" id="cnpj" name="cnpj" value="' . $cnpj . '"
               class="form-control" placeholder="00.000.000/0000-00" maxlength="18">';
        echo '<button type="button" class="btn btn-outline-secondary btn-sm"
                id="btn-buscar-cnpj" title="' . __('Buscar CNPJ na BrasilAPI', 'newmanagement') . '">';
        echo '<i class="ti ti-search"></i> ' . __('Buscar', 'newmanagement');
        echo '</button>';
        echo '</div>';
        echo '<span id="cnpj-feedback" class="nm-feedback"></span>';
        echo '</td>';
        echo '<td>' . __('Razao Social', 'newmanagement') . '</td>';
        echo '<td>';
        \Html::autocompletionTextField($this, 'razao_social');
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 3: E-mail | Telefone
        // autocompletionTextField cuida de value, name, id e classes.
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('E-mail', 'newmanagement') . '</td>';
        echo '<td>';
        \Html::autocompletionTextField($this, 'email');
        echo '</td>';
        echo '<td>' . __('Telefone', 'newmanagement') . '</td>';
        echo '<td>';
        \Html::autocompletionTextField($this, 'phone', [
            'placeholder' => '(00) 00000-0000',
        ]);
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 4: CEP (input-group com botão Buscar) | Status do Contrato
        // CEP mantém botão manual; Status usa Dropdown::showFromArray().
        // ------------------------------------------------------------------
        $cep             = htmlspecialchars($this->fields['cep'] ?? '', ENT_QUOTES);
        $contract_status = (int) ($this->fields['contract_status'] ?? self::CONTRACT_NO_CONTRACT);

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('CEP', 'newmanagement') . '</td>';
        echo '<td>';
        echo '<div class="input-group">';
        echo '<input type="text" id="cep" name="cep" value="' . $cep . '"
               class="form-control" placeholder="00000-000" maxlength="9">';
        echo '<button type="button" class="btn btn-outline-secondary btn-sm"
                id="btn-buscar-cep" title="' . __('Buscar CEP na BrasilAPI', 'newmanagement') . '">';
        echo '<i class="ti ti-search"></i> ' . __('Buscar', 'newmanagement');
        echo '</button>';
        echo '</div>';
        echo '<span id="cep-feedback" class="nm-feedback"></span>';
        echo '</td>';
        echo '<td>' . __('Status do Contrato', 'newmanagement') . '</td>';
        echo '<td>';
        // Dropdown::showFromArray() gera <select class="form-select"> nativo do GLPI
        \Dropdown::showFromArray('contract_status', self::getContractStatusOptions(), [
            'value'               => $contract_status,
            'display_emptychoice' => false,
        ]);
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 5: Endereço (textarea — colspan 3 para ocupar 3 colunas)
        // Html::textarea() gera <textarea class="form-control"> padrão GLPI.
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Endereco', 'newmanagement') . '</td>';
        echo '<td colspan="3">';
        \Html::textarea([
            'name'    => 'address',
            'value'   => $this->fields['address'] ?? '',
            'rows'    => 2,
            'cols'    => 80,
            'display' => true,
        ]);
        echo '</td>';
        echo '</tr>';

        // ------------------------------------------------------------------
        // Linha 6: Comentário (textarea)
        // ------------------------------------------------------------------
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentario', 'newmanagement') . '</td>';
        echo '<td colspan="3">';
        \Html::textarea([
            'name'    => 'comment',
            'value'   => $this->fields['comment'] ?? '',
            'rows'    => 3,
            'cols'    => 80,
            'display' => true,
        ]);
        echo '</td>';
        echo '</tr>';

        $this->showFormButtons($options);

        // ------------------------------------------------------------------
        // JavaScript — BrasilAPI (CNPJ + CEP)
        // Máscaras e buscas client-side; nenhum dado trafega pelo servidor.
        // ------------------------------------------------------------------
        echo <<<'JS'
<script>
(function () {
    'use strict';

    /* ── utilitários ── */
    function soDigitos(v) { return v.replace(/\D/g, ''); }

    function setFeedback(id, msg, tipo) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.className = 'nm-feedback' + (tipo ? ' nm-feedback--' + tipo : '');
    }

    function setLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = loading;
        btn.innerHTML = loading
            ? '<span class="nm-spinner"></span> Buscando…'
            : '<i class="ti ti-search"></i> Buscar';
    }

    /* ── máscara CNPJ ── */
    document.getElementById('cnpj')?.addEventListener('input', function () {
        let v = soDigitos(this.value).slice(0, 14);
        if (v.length > 12) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        else if (v.length > 8) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})/, '$1.$2.$3/$4');
        else if (v.length > 5) v = v.replace(/^(\d{2})(\d{3})(\d{3})/, '$1.$2.$3');
        else if (v.length > 2) v = v.replace(/^(\d{2})(\d+)/, '$1.$2');
        this.value = v;
    });

    /* ── máscara CEP ── */
    document.getElementById('cep')?.addEventListener('input', function () {
        let v = soDigitos(this.value).slice(0, 8);
        if (v.length > 5) v = v.replace(/^(\d{5})(\d+)/, '$1-$2');
        this.value = v;
    });

    /* ── busca CNPJ ── */
    document.getElementById('btn-buscar-cnpj')?.addEventListener('click', async function () {
        const raw = soDigitos(document.getElementById('cnpj')?.value || '');
        if (raw.length !== 14) {
            setFeedback('cnpj-feedback', 'CNPJ deve ter 14 d\u00edgitos.', 'error');
            return;
        }
        setLoading('btn-buscar-cnpj', true);
        setFeedback('cnpj-feedback', '', '');
        try {
            const res  = await fetch('https://brasilapi.com.br/api/cnpj/v1/' + raw);
            const data = await res.json();
            if (!res.ok) { setFeedback('cnpj-feedback', data.message || 'CNPJ n\u00e3o encontrado.', 'error'); return; }

            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            set('razao_social', data.razao_social);
            set('email',  data.email);
            set('phone',  data.ddd_telefone_1 ? '(' + data.ddd_telefone_1.slice(0,2) + ') ' + data.ddd_telefone_1.slice(2) : '');

            const partes = [
                data.logradouro  ? data.descricao_tipo_de_logradouro + ' ' + data.logradouro : '',
                data.numero      ? 'N\u00ba ' + data.numero : '',
                data.complemento || '',
                data.bairro      || '',
                data.municipio   ? data.municipio + (data.uf ? ' - ' + data.uf : '') : '',
            ].filter(Boolean);
            set('address', partes.join(', '));

            if (data.cep) {
                const c = soDigitos(data.cep).slice(0, 8);
                set('cep', c.length === 8 ? c.replace(/^(\d{5})(\d{3})$/, '$1-$2') : c);
            }
            setFeedback('cnpj-feedback', '\u2714 Dados preenchidos com sucesso.', 'success');
        } catch (err) {
            setFeedback('cnpj-feedback', 'Erro de conex\u00e3o com a BrasilAPI.', 'error');
            console.error('[NM] CNPJ fetch error:', err);
        } finally {
            setLoading('btn-buscar-cnpj', false);
        }
    });

    /* ── busca CEP ── */
    document.getElementById('btn-buscar-cep')?.addEventListener('click', async function () {
        const raw = soDigitos(document.getElementById('cep')?.value || '');
        if (raw.length !== 8) {
            setFeedback('cep-feedback', 'CEP deve ter 8 d\u00edgitos.', 'error');
            return;
        }
        setLoading('btn-buscar-cep', true);
        setFeedback('cep-feedback', '', '');
        try {
            const res  = await fetch('https://brasilapi.com.br/api/cep/v2/' + raw);
            const data = await res.json();
            if (!res.ok) { setFeedback('cep-feedback', data.message || 'CEP n\u00e3o encontrado.', 'error'); return; }

            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            const partes = [
                data.street       || '',
                data.neighborhood || '',
                data.city         ? data.city + (data.state ? ' - ' + data.state : '') : '',
            ].filter(Boolean);
            set('address', partes.join(', '));
            setFeedback('cep-feedback', '\u2714 Endere\u00e7o preenchido.', 'success');
        } catch (err) {
            setFeedback('cep-feedback', 'Erro de conex\u00e3o com a BrasilAPI.', 'error');
            console.error('[NM] CEP fetch error:', err);
        } finally {
            setLoading('btn-buscar-cep', false);
        }
    });
})();
</script>
JS;

        return true;
    }
}
