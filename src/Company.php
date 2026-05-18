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

        // IPBX On-Premise — entrada que estava faltando no menu lateral
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
     *
     * Ordem: Empresa → Servidor IPBX → Linha Fixa → Chatbot
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

        $name            = htmlspecialchars($this->fields['name']            ?? '', ENT_QUOTES);
        $cnpj            = htmlspecialchars($this->fields['cnpj']            ?? '', ENT_QUOTES);
        $razao_social    = htmlspecialchars($this->fields['razao_social']    ?? '', ENT_QUOTES);
        $email           = htmlspecialchars($this->fields['email']           ?? '', ENT_QUOTES);
        $phone           = htmlspecialchars($this->fields['phone']           ?? '', ENT_QUOTES);
        $cep             = htmlspecialchars($this->fields['cep']             ?? '', ENT_QUOTES);
        $address         = htmlspecialchars($this->fields['address']         ?? '', ENT_QUOTES);
        $comment         = htmlspecialchars($this->fields['comment']         ?? '', ENT_QUOTES);
        $contract_status = (int) ($this->fields['contract_status'] ?? self::CONTRACT_NO_CONTRACT);

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="name">' . __('Nome', 'newmanagement') . ' <span style="color:red">*</span></label></td>';
        echo '<td><input type="text" id="name" name="name" value="' . $name . '" class="form-control" required></td>';
        echo '<td>' . __('ID', 'newmanagement') . '</td>';
        echo '<td><input type="text" value="' . ($ID > 0 ? $ID : __('Gerado automaticamente', 'newmanagement')) . '" class="form-control" disabled></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="cnpj">' . __('CNPJ', 'newmanagement') . '</label></td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="text" id="cnpj" name="cnpj" value="' . $cnpj . '" class="form-control" placeholder="00.000.000/0000-00" maxlength="18">';
        echo '    <button type="button" class="nm-btn-search" id="btn-buscar-cnpj" title="Buscar CNPJ na BrasilAPI">';
        echo '      <i class="ti ti-search"></i> Buscar';
        echo '    </button>';
        echo '  </div>';
        echo '  <span id="cnpj-feedback" class="nm-feedback"></span>';
        echo '</td>';
        echo '<td><label for="razao_social">' . __('Razao Social', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="razao_social" name="razao_social" value="' . $razao_social . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="email">' . __('E-mail', 'newmanagement') . '</label></td>';
        echo '<td><input type="email" id="email" name="email" value="' . $email . '" class="form-control"></td>';
        echo '<td><label for="phone">' . __('Telefone', 'newmanagement') . '</label></td>';
        echo '<td><input type="text" id="phone" name="phone" value="' . $phone . '" class="form-control" placeholder="(00) 00000-0000"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="cep">' . __('CEP', 'newmanagement') . '</label></td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="text" id="cep" name="cep" value="' . $cep . '" class="form-control" placeholder="00000-000" maxlength="9">';
        echo '    <button type="button" class="nm-btn-search" id="btn-buscar-cep" title="Buscar CEP na BrasilAPI">';
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

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="address">' . __('Endereco', 'newmanagement') . '</label></td>';
        echo '<td colspan="3"><textarea id="address" name="address" class="form-control" rows="2">' . $address . '</textarea></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td><label for="comment">' . __('Comentario', 'newmanagement') . '</label></td>';
        echo '<td colspan="3"><textarea id="comment" name="comment" class="form-control" rows="3">' . $comment . '</textarea></td>';
        echo '</tr>';

        $this->showFormButtons($options);

        // -----------------------------------------------------------------------
        // Injeção dinâmica via JavaScript — BrasilAPI (CNPJ + CEP)
        // Toda a lógica de busca é client-side; nenhum dado trafega pelo servidor.
        // -----------------------------------------------------------------------
        echo <<<'JS'
<style>
.nm-input-group          { display:flex; gap:6px; align-items:center; }
.nm-btn-search           { display:inline-flex; align-items:center; gap:4px; padding:4px 10px;
                           font-size:.85rem; border-radius:4px; border:1px solid #aaa;
                           background:#f5f5f5; cursor:pointer; white-space:nowrap; }
.nm-btn-search:hover     { background:#e0e0e0; }
.nm-btn-search:disabled  { opacity:.5; cursor:not-allowed; }
.nm-feedback             { font-size:.8rem; margin-top:2px; display:block; min-height:1.2em; }
.nm-feedback.ok          { color:#2e7d32; }
.nm-feedback.erro        { color:#c62828; }
</style>

<script>
(function () {
    'use strict';

    /* ── utilitários ── */
    function soDigitos(v) { return v.replace(/\D/g, ''); }

    function setFeedback(id, msg, tipo) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.className = 'nm-feedback ' + (tipo || '');
    }

    function setLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = loading;
        btn.innerHTML = loading
            ? '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite"></i> Buscando…'
            : '<i class="ti ti-search"></i> Buscar';
    }

    /* ── máscara automática CNPJ ── */
    document.getElementById('cnpj')?.addEventListener('input', function () {
        let v = soDigitos(this.value).slice(0, 14);
        if (v.length > 12) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        else if (v.length > 8) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})/, '$1.$2.$3/$4');
        else if (v.length > 5) v = v.replace(/^(\d{2})(\d{3})(\d{3})/, '$1.$2.$3');
        else if (v.length > 2) v = v.replace(/^(\d{2})(\d+)/, '$1.$2');
        this.value = v;
    });

    /* ── máscara automática CEP ── */
    document.getElementById('cep')?.addEventListener('input', function () {
        let v = soDigitos(this.value).slice(0, 8);
        if (v.length > 5) v = v.replace(/^(\d{5})(\d+)/, '$1-$2');
        this.value = v;
    });

    /* ── busca CNPJ (BrasilAPI) ── */
    document.getElementById('btn-buscar-cnpj')?.addEventListener('click', async function () {
        const raw = soDigitos(document.getElementById('cnpj')?.value || '');
        if (raw.length !== 14) {
            setFeedback('cnpj-feedback', 'CNPJ deve ter 14 dígitos.', 'erro');
            return;
        }

        setLoading('btn-buscar-cnpj', true);
        setFeedback('cnpj-feedback', '', '');

        try {
            const res  = await fetch('https://brasilapi.com.br/api/cnpj/v1/' + raw);
            const data = await res.json();

            if (!res.ok) {
                setFeedback('cnpj-feedback', data.message || 'CNPJ não encontrado.', 'erro');
                return;
            }

            /* preenche campos */
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };

            set('razao_social', data.razao_social);
            set('email',        data.email);
            set('phone',        data.ddd_telefone_1
                                    ? '(' + data.ddd_telefone_1.slice(0,2) + ') ' + data.ddd_telefone_1.slice(2)
                                    : '');

            /* monta endereço completo */
            const partes = [
                data.logradouro  ? data.descricao_tipo_de_logradouro + ' ' + data.logradouro : '',
                data.numero      ? 'Nº ' + data.numero : '',
                data.complemento || '',
                data.bairro      || '',
                data.municipio   ? data.municipio + (data.uf ? ' - ' + data.uf : '') : '',
            ].filter(Boolean);
            set('address', partes.join(', '));

            /* aplica máscara no CEP retornado */
            if (data.cep) {
                const cepLimpo = soDigitos(data.cep).slice(0, 8);
                set('cep', cepLimpo.length === 8
                    ? cepLimpo.replace(/^(\d{5})(\d{3})$/, '$1-$2')
                    : cepLimpo);
            }

            setFeedback('cnpj-feedback', '✔ Dados preenchidos com sucesso.', 'ok');

        } catch (err) {
            setFeedback('cnpj-feedback', 'Erro de conexão com a BrasilAPI.', 'erro');
            console.error('[NM] CNPJ fetch error:', err);
        } finally {
            setLoading('btn-buscar-cnpj', false);
        }
    });

    /* ── busca CEP (BrasilAPI) ── */
    document.getElementById('btn-buscar-cep')?.addEventListener('click', async function () {
        const raw = soDigitos(document.getElementById('cep')?.value || '');
        if (raw.length !== 8) {
            setFeedback('cep-feedback', 'CEP deve ter 8 dígitos.', 'erro');
            return;
        }

        setLoading('btn-buscar-cep', true);
        setFeedback('cep-feedback', '', '');

        try {
            const res  = await fetch('https://brasilapi.com.br/api/cep/v2/' + raw);
            const data = await res.json();

            if (!res.ok) {
                setFeedback('cep-feedback', data.message || 'CEP não encontrado.', 'erro');
                return;
            }

            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };

            const partes = [
                data.street      || '',
                data.neighborhood || '',
                data.city        ? data.city + (data.state ? ' - ' + data.state : '') : '',
            ].filter(Boolean);
            set('address', partes.join(', '));

            setFeedback('cep-feedback', '✔ Endereço preenchido.', 'ok');

        } catch (err) {
            setFeedback('cep-feedback', 'Erro de conexão com a BrasilAPI.', 'erro');
            console.error('[NM] CEP fetch error:', err);
        } finally {
            setLoading('btn-buscar-cep', false);
        }
    });

    /* ── animação do ícone de loading ── */
    const style = document.createElement('style');
    style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(style);
})();
</script>
JS;

        return true;
    }
}
