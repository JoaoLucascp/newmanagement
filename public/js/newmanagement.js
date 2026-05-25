/**
 * Newmanagement - Plugin GLPI
 * Máscaras, busca CNPJ e CEP via BrasilAPI
 * Botões AJAX do formulário IPBX e Chatbot (com token CSRF do GLPI 11)
 *
 * fix(UI-01): TODOS os listeners usam delegação de eventos no `document`.
 *   Isso é OBRIGATÓRIO porque o GLPI carrega o conteúdo das abas via AJAX
 *   — o DOM da aba não existe no DOMContentLoaded inicial, portanto
 *   getElementById() retorna null e os bindings diretos nunca funcionam.
 *
 *   Arquitetura:
 *   - document.addEventListener('click', ...) captura todos os cliques.
 *   - e.target.closest('[seletor]') identifica o elemento alvo.
 *   - Nenhum _nmBound / getElementById binding direto nos botões de ação.
 *
 * fix(CSRF-01): nmGetCsrfToken() agora lê a meta[property="glpi:csrf_token"]
 *   como fonte primária, que é o que o GLPI 11 valida no servidor.
 *   nmRefreshCsrfToken() sincroniza meta, hiddens e todos os [data-csrf].
 *
 * fix(DELEGATE-01): nmEnsureIpbxDelegated() + MutationObserver garante que
 *   os handlers sejam registrados mesmo quando o GLPI recarrega a aba via
 *   AJAX em um novo frame/contexto, zerando window._nmIpbxDelegated.
 *
 * refactor(UI-02): abas horizontais substituem nm-toggle-section (Opção B).
 *   nmInitIpbxTabs() ativa troca de painéis sem reload.
 *   nmCounterIncrement/Decrement mantém os badges das abas sincronizados.
 */

console.log('Newmanagement Plugin carregado.');

// ---------------------------------------------------------------------------
// Máscaras
// ---------------------------------------------------------------------------

function nmMascaraCNPJ(valor) {
    const v = valor.replace(/\D/g, '').slice(0, 14);
    if (v.length <= 2)  return v;
    if (v.length <= 5)  return v.replace(/(\d{2})(\d+)/, '$1.$2');
    if (v.length <= 8)  return v.replace(/(\d{2})(\d{3})(\d+)/, '$1.$2.$3');
    if (v.length <= 12) return v.replace(/(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4');
    return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, '$1.$2.$3/$4-$5');
}

function nmMascaraCEP(valor) {
    const v = valor.replace(/\D/g, '').slice(0, 8);
    if (v.length <= 5) return v;
    return v.replace(/(\d{5})(\d+)/, '$1-$2');
}

function nmMascaraTelefone(valor) {
    const v = valor.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        return v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    }
    return v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
}

// ---------------------------------------------------------------------------
// Validador CNPJ
// ---------------------------------------------------------------------------

function nmValidarCNPJ(cnpj) {
    const v = cnpj.replace(/\D/g, '');
    if (v.length !== 14) return false;
    if (/^(\d)\1+$/.test(v)) return false;
    function calcDigito(base, peso) {
        let soma = 0;
        for (let i = 0; i < base.length; i++) {
            soma += parseInt(base[i]) * peso--;
            if (peso < 2) peso = 9;
        }
        const resto = soma % 11;
        return resto < 2 ? 0 : 11 - resto;
    }
    const d1 = calcDigito(v.slice(0, 12), 5);
    const d2 = calcDigito(v.slice(0, 13), 6);
    return d1 === parseInt(v[12]) && d2 === parseInt(v[13]);
}

// ---------------------------------------------------------------------------
// Feedback visual
// ---------------------------------------------------------------------------

function nmFeedback(elementId, mensagem, tipo) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.textContent = mensagem;
    el.className = 'nm-feedback' + (tipo ? ' nm-feedback--' + tipo : '');
}

function nmSetLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    if (loading) {
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span> Buscando...';
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-search"></i> Buscar';
    }
}

// ---------------------------------------------------------------------------
// CSRF Helper
// fix(CSRF-01): a meta[property="glpi:csrf_token"] é a fonte canônica do
// GLPI 11 — lida PRIMEIRO. Os hiddens do plugin são fallback secundário.
// ---------------------------------------------------------------------------

function nmGetCsrfToken() {
    // 1. Meta tag do GLPI 11 — fonte canônica validada pelo servidor
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content');

    // 2. Hidden input padrão do GLPI (GLPI 10 / fallback)
    const hidden = document.querySelector('input[name="_glpi_csrf_token"]');
    if (hidden && hidden.value) return hidden.value;

    // 3. Hiddens do plugin (mantidos sincronizados por nmRefreshCsrfToken)
    const ipbxHidden = document.getElementById('nm-ipbx-csrf');
    if (ipbxHidden && ipbxHidden.value) return ipbxHidden.value;

    const chatbotHidden = document.getElementById('nm-chatbot-csrf');
    if (chatbotHidden && chatbotHidden.value) return chatbotHidden.value;

    return '';
}

function nmRefreshCsrfToken(newToken) {
    if (!newToken) return;

    // 1. Atualiza meta tag do GLPI (fonte canônica)
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    if (meta) meta.setAttribute('content', newToken);

    // 2. Atualiza hiddens do plugin
    const ipbxHidden = document.getElementById('nm-ipbx-csrf');
    if (ipbxHidden) ipbxHidden.value = newToken;
    const chatbotHidden = document.getElementById('nm-chatbot-csrf');
    if (chatbotHidden) chatbotHidden.value = newToken;

    // 3. Sincroniza data-csrf em TODOS os botões filhos (evita token stale)
    document.querySelectorAll('[data-csrf]').forEach(el => {
        el.dataset.csrf = newToken;
    });
}

// ---------------------------------------------------------------------------
// nmPost — fetch com CSRF
// ---------------------------------------------------------------------------

async function nmPost(url, data) {
    const csrf = nmGetCsrfToken();
    const body = new FormData();

    body.append('_glpi_csrf_token', csrf);

    Object.entries(data).forEach(([k, v]) => {
        if (k === '_glpi_csrf_token') return;
        if (Array.isArray(v)) {
            v.forEach(item => body.append(k, item == null ? '' : item));
        } else {
            body.append(k, v == null ? '' : v);
        }
    });

    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-Glpi-Csrf-Token': csrf },
        body,
    });

    if (res.status === 403) {
        let msg = 'Token CSRF inválido ou expirado. Recarregue a página e tente novamente.';
        try {
            const text = await res.text();
            try {
                const json = JSON.parse(text);
                msg = json.error || json.message || msg;
            } catch { /* HTML de erro */ }
        } catch { /* ignora */ }
        throw new Error('HTTP 403: ' + msg);
    }

    if (!res.ok) throw new Error('HTTP ' + res.status);

    const json = await res.json();
    if (json && json.csrf) nmRefreshCsrfToken(json.csrf);
    return json;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function nmAjaxUrl() {
    const root = (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.root_doc)
        ? CFG_GLPI.root_doc : '';
    return root + '/plugins/newmanagement/ajax/ipbx_sub.php';
}

function nmGetIpbxActionUrl(ctx) {
    // ctx = qualquer elemento dentro de .nm-ipbx-tab
    const tab = ctx ? ctx.closest('.nm-ipbx-tab') : document.querySelector('.nm-ipbx-tab');
    return tab?.dataset.actionUrl || nmAjaxUrl();
}

function nmGetIpbxCompaniesId(ctx) {
    const tab = ctx ? ctx.closest('.nm-ipbx-tab') : document.querySelector('.nm-ipbx-tab');
    return tab?.dataset.companiesId || '';
}

function nmUpdateIpbxId(newId) {
    // Atualiza data-ipbx-id em todos os botões add e campos hidden
    document.querySelectorAll('[data-ipbx-id]').forEach(el => {
        el.dataset.ipbxId = newId;
    });
    const hiddenAction = document.getElementById('nm-ipbx-action');
    if (hiddenAction) hiddenAction.value = 'update_ipbx';
    const hiddenId = document.getElementById('nm-ipbx-id');
    if (hiddenId) hiddenId.value = newId;
}

function nmVal(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}

function nmClearRow(addRowId, fields) {
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });
}

function nmEsc(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Gera o HTML da lixeira para linhas já salvas no servidor (DELETE real).
 */
function nmDelBtn(action, id, rowId, companiesId, url, confirmMsg, title) {
    return `<button type="button"
        class="btn btn-sm btn-icon nm-del-btn"
        data-action="${action}"
        data-id="${id}"
        data-row="${rowId}"
        data-companies-id="${companiesId}"
        data-url="${url}"
        data-confirm="${confirmMsg}"
        title="${title || 'Remover'}">
        <i class="ti ti-trash text-danger"></i>
    </button>`;
}

// ---------------------------------------------------------------------------
// Contadores das abas IPBX
// refactor(UI-02): atualiza o badge numérico de cada aba após operações AJAX.
// ---------------------------------------------------------------------------

function nmCounterIncrement(elId) {
    const el = document.getElementById(elId);
    if (el) el.textContent = parseInt(el.textContent || '0', 10) + 1;
}

function nmCounterDecrement(elId) {
    const el = document.getElementById(elId);
    if (el) el.textContent = Math.max(0, parseInt(el.textContent || '0', 10) - 1);
}

// ---------------------------------------------------------------------------
// Abas horizontais IPBX
// refactor(UI-02): troca de painéis sem reload; delegação no tabBar.
// Usa flag _nmTabsInit para evitar double-bind em reloads de aba.
// ---------------------------------------------------------------------------

function nmInitIpbxTabs() {
    const tabBar = document.querySelector('.nm-tab-bar');
    if (!tabBar || tabBar._nmTabsInit) return;
    tabBar._nmTabsInit = true;

    tabBar.addEventListener('click', (e) => {
        const tab = e.target.closest('.nm-tab');
        if (!tab) return;

        // Desativa todas as abas e painéis
        tabBar.querySelectorAll('.nm-tab').forEach(t => {
            t.classList.remove('active');
            t.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.nm-tab-panel').forEach(p => {
            p.classList.remove('active');
            p.hidden = true;
        });

        // Ativa aba e painel clicados
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        const panel = document.getElementById(tab.dataset.panel);
        if (panel) {
            panel.classList.add('active');
            panel.hidden = false;
        }
    });
}

// ---------------------------------------------------------------------------
// Paginação AJAX — delegação de eventos (já funcionava)
// ---------------------------------------------------------------------------

function nmInitPagination() {
    if (window._nmPaginationDelegated) return;
    window._nmPaginationDelegated = true;

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.nm-page-btn');
        if (!btn || btn.disabled) return;

        const sectionId = btn.dataset.sectionId;
        const section   = document.getElementById(sectionId);
        if (!section) return;

        const currentPage = parseInt(section.dataset.page     || '1',  10);
        const pageSize    = parseInt(section.dataset.pageSize || '20', 10);
        const total       = parseInt(section.dataset.total    || '0',  10);
        const ipbxId      = section.dataset.ipbxId;
        const companiesId = section.dataset.companiesId;
        const sectionName = section.dataset.section;
        const paginateUrl = section.dataset.paginateUrl;

        const totalPages = Math.max(1, Math.ceil(total / pageSize));
        const dir        = btn.dataset.dir;
        const targetPage = dir === 'prev' ? currentPage - 1 : currentPage + 1;

        if (targetPage < 1 || targetPage > totalPages) return;

        btn.disabled = true;
        const spinner = document.createElement('span');
        spinner.className = 'nm-spinner';
        btn.prepend(spinner);

        try {
            const params = new URLSearchParams({
                section:      sectionName,
                ipbx_id:      ipbxId,
                companies_id: companiesId,
                page:         targetPage,
            });

            const res = await fetch(`${paginateUrl}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Erro ao paginar');

            const tbodyId = sectionName === 'extensions' ? 'nm-ext-tbody'
                          : sectionName === 'devices'    ? 'nm-dev-tbody'
                          :                               'nm-net-tbody';
            const paginId = sectionName === 'extensions' ? 'nm-ext-pagination'
                          : sectionName === 'devices'    ? 'nm-dev-pagination'
                          :                               'nm-net-pagination';

            const tbody = document.getElementById(tbodyId);
            if (tbody) tbody.innerHTML = data.html;

            section.dataset.page = data.page;

            const paginDiv = document.getElementById(paginId);
            if (paginDiv) {
                const from  = (data.page - 1) * data.page_size + 1;
                const to    = Math.min(data.page * data.page_size, data.total);
                const labelMap = { extensions: 'ramais', devices: 'dispositivos', network: 'redes' };
                const label = labelMap[sectionName] || 'itens';
                const counter = paginDiv.querySelector('span.text-muted');
                if (counter) counter.textContent = `Mostrando ${from}–${to} de ${data.total} ${label}`;

                const newTotalPages = Math.ceil(data.total / data.page_size);
                paginDiv.style.display = newTotalPages <= 1 ? 'none' : '';

                paginDiv.querySelectorAll('.nm-page-btn').forEach(b => {
                    if (b.dataset.dir === 'prev') b.disabled = data.page <= 1;
                    if (b.dataset.dir === 'next') b.disabled = data.page >= newTotalPages;
                });

                const pageLabel = paginDiv.querySelector('.btn.disabled');
                if (pageLabel) pageLabel.textContent = `${data.page} / ${newTotalPages}`;
            }

            if (data.csrf) nmRefreshCsrfToken(data.csrf);

        } catch (err) {
            console.error('[NM] Erro na paginação:', err.message);
            alert('Erro ao carregar página: ' + err.message);
        } finally {
            spinner.remove();
            btn.disabled = false;
        }
    });
}

// ---------------------------------------------------------------------------
// IPBX — Delegação de eventos
//
// fix(DELEGATE-01): substituído o guard simples (window._nmIpbxDelegated)
// por nmEnsureIpbxDelegated(), que verifica se os handlers já foram
// registrados no document atual e os re-registra se necessário.
// Isso resolve o problema de window._nmIpbxDelegated = false após reload
// da aba via AJAX do GLPI (novo frame/contexto de execução).
// ---------------------------------------------------------------------------

// Símbolo único colado no document para marcar que os handlers já foram
// registrados NESTE document (não na window, que pode ser reiniciada).
const NM_IPBX_BOUND_KEY = '__nmIpbxHandlersBound__';

function nmEnsureIpbxDelegated() {
    if (document[NM_IPBX_BOUND_KEY]) return;
    document[NM_IPBX_BOUND_KEY] = true;

    // Compatibilidade com código legado que cheque window._nmIpbxDelegated
    window._nmIpbxDelegated = true;

    // Inicializa abas horizontais assim que o DOM da aba estiver presente
    nmInitIpbxTabs();

    // -----------------------------------------------------------------------
    // Mostrar/ocultar senha (olho)
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-btn-eye');
        if (!btn) return;
        const targetId = btn.dataset.target;
        const input = document.getElementById(targetId);
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = isPassword ? 'ti ti-eye-off' : 'ti ti-eye';
        }
    });

    // -----------------------------------------------------------------------
    // Salvar IPBX principal
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-save-all');
        if (!btn) return;

        const actionUrl = btn.dataset.actionUrl || nmGetIpbxActionUrl(btn);

        const ipbxData = {
            action:         nmVal('nm-ipbx-action')       || 'add_ipbx',
            id:             nmVal('nm-ipbx-id')           || '0',
            companies_id:   nmVal('nm-ipbx-companies-id') || nmGetIpbxCompaniesId(btn),
            model:          nmVal('nm-ipbx-model'),
            server_version: nmVal('nm-ipbx-server_version'),
            ip_local:       nmVal('nm-ipbx-ip_local'),
            ip_external:    nmVal('nm-ipbx-ip_external'),
            web_port:       nmVal('nm-ipbx-web_port'),
            web_password:   nmVal('nm-web-password'),
            ssh_port:       nmVal('nm-ipbx-ssh_port'),
            ssh_password:   nmVal('nm-ssh-password'),
            comment:        nmVal('nm-ipbx-comment'),
        };

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="nm-spinner"></span> Salvando...';

        try {
            const result = await nmPost(actionUrl, ipbxData);
            if (result && result.id) nmUpdateIpbxId(result.id);

            btn.innerHTML = '<i class="ti ti-check"></i> Salvo!';
            btn.classList.replace('btn-primary', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.replace('btn-success', 'btn-primary');
                btn.disabled = false;
            }, 2000);
        } catch (error) {
            console.error('[NM] Erro ao salvar IPBX:', error.message);
            alert('Erro ao salvar IPBX: ' + error.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });

    // -----------------------------------------------------------------------
    // Recolher/expandir seções (mantido para compatibilidade com outras áreas)
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-toggle-section');
        if (!btn) return;
        const targetId = btn.dataset.target;
        const tbody = document.getElementById(targetId);
        if (!tbody) return;
        const isExpanded = btn.getAttribute('aria-expanded') !== 'false';
        tbody.style.display = isExpanded ? 'none' : '';
        btn.setAttribute('aria-expanded', !isExpanded);
        const icon = btn.querySelector('i');
        if (icon) icon.className = isExpanded ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
    });

    // -----------------------------------------------------------------------
    // Lixeira de linha não salva (nm-row-del-btn) — remove linha direto
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-row-del-btn');
        if (!btn) return;
        const row = btn.closest('tr');
        if (row) row.remove();
    });

    // -----------------------------------------------------------------------
    // Lixeira de linha salva (nm-del-btn) — DELETE real no servidor
    // refactor(UI-02): decrementa contador da aba correspondente após remoção.
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.nm-del-btn');
        if (!btn) return;

        const action     = btn.dataset.action;
        const id         = btn.dataset.id;
        const rowId      = btn.dataset.row;
        const url        = btn.dataset.url || nmGetIpbxActionUrl(btn);
        const confirmMsg = btn.dataset.confirm || 'Remover item?';

        if (!confirm(confirmMsg)) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, { action, id, companies_id: nmGetIpbxCompaniesId(btn) });
            if (!result.success) throw new Error(result.error || 'Erro ao remover');
            const row = document.getElementById(rowId);
            if (row) row.remove();

            // Decrementa badge da aba correspondente
            if (action === 'delete_extension') nmCounterDecrement('nm-count-ext');
            else if (action === 'delete_device')    nmCounterDecrement('nm-count-dev');
            else if (action === 'delete_network')   nmCounterDecrement('nm-count-net');

        } catch (error) {
            console.error('[NM] Erro ao remover:', error.message);
            alert('Erro ao remover: ' + error.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });

    // -----------------------------------------------------------------------
    // + Adicionar Ramal (nm-ext-add-btn)
    // refactor(UI-02): incrementa badge da aba Ramais após inserção.
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-ext-add-btn');
        if (!btn) return;

        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) {
            alert('Salve o Servidor IPBX primeiro antes de adicionar ramais.');
            return;
        }

        const number        = nmVal('nm-ext-number');
        const password      = nmVal('nm-ext-password');
        const device_ip     = nmVal('nm-ext-device_ip');
        const user_name     = nmVal('nm-ext-user_name');
        const records_calls = nmVal('nm-ext-records_calls') || '0';
        const department    = nmVal('nm-ext-department');

        if (!number.trim()) {
            alert('Informe o número do ramal.');
            document.getElementById('nm-ext-number')?.focus();
            return;
        }

        const url         = btn.dataset.url || nmGetIpbxActionUrl(btn);
        const companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, {
                action: btn.dataset.action,
                ipbx_id: ipbxId,
                companies_id: companiesId,
                number, password, device_ip, user_name, records_calls, department,
            });
            if (!result.success) throw new Error(result.error || 'Erro desconhecido');

            const tr = document.createElement('tr');
            tr.id = 'nm-ext-row-' + result.id;
            tr.className = 'tab_bg_1';
            tr.innerHTML = `
                <td>${nmEsc(number)}</td>
                <td>••••••</td>
                <td>${nmEsc(device_ip)}</td>
                <td>${nmEsc(user_name)}</td>
                <td>${parseInt(records_calls, 10) ? 'Sim' : 'Não'}</td>
                <td>${nmEsc(department)}</td>
                <td>${nmDelBtn('delete_extension', result.id, 'nm-ext-row-' + result.id, companiesId, url, 'Remover ramal?')}</td>`;

            const addRow = document.getElementById('nm-ext-add-row');
            if (addRow) addRow.parentNode.insertBefore(tr, addRow);

            nmClearRow('nm-ext-add-row', ['nm-ext-number','nm-ext-password','nm-ext-device_ip','nm-ext-user_name','nm-ext-department','nm-ext-records_calls']);
            document.getElementById('nm-ext-empty')?.remove();

            // Incrementa badge da aba Ramais
            nmCounterIncrement('nm-count-ext');

        } catch (error) {
            console.error('[NM] Erro ao adicionar ramal:', error.message);
            alert('Erro ao adicionar ramal: ' + error.message);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });

    // -----------------------------------------------------------------------
    // + Adicionar Dispositivo (nm-dev-add-btn)
    // refactor(UI-02): incrementa badge da aba Dispositivos após inserção.
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-dev-add-btn');
        if (!btn) return;

        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) {
            alert('Salve o Servidor IPBX primeiro antes de adicionar dispositivos.');
            return;
        }

        const device_type = nmVal('nm-dev-device_type');
        const ip_address  = nmVal('nm-dev-ip_address');
        const login       = nmVal('nm-dev-login');
        const password    = nmVal('nm-dev-password');

        if (!device_type.trim()) {
            alert('Informe o tipo do dispositivo.');
            document.getElementById('nm-dev-device_type')?.focus();
            return;
        }

        const url         = btn.dataset.url || nmGetIpbxActionUrl(btn);
        const companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, {
                action: btn.dataset.action,
                ipbx_id: ipbxId,
                companies_id: companiesId,
                device_type, ip_address, login, password,
            });
            if (!result.success) throw new Error(result.error || 'Erro desconhecido');

            const tr = document.createElement('tr');
            tr.id = 'nm-dev-row-' + result.id;
            tr.className = 'tab_bg_1';
            tr.innerHTML = `
                <td>${nmEsc(device_type)}</td>
                <td>${nmEsc(ip_address)}</td>
                <td>${nmEsc(login)}</td>
                <td>••••••</td>
                <td>${nmDelBtn('delete_device', result.id, 'nm-dev-row-' + result.id, companiesId, url, 'Remover dispositivo?')}</td>`;

            const addRow = document.getElementById('nm-dev-add-row');
            if (addRow) addRow.parentNode.insertBefore(tr, addRow);

            nmClearRow('nm-dev-add-row', ['nm-dev-device_type','nm-dev-ip_address','nm-dev-login','nm-dev-password']);
            document.getElementById('nm-dev-empty')?.remove();

            // Incrementa badge da aba Dispositivos
            nmCounterIncrement('nm-count-dev');

        } catch (error) {
            console.error('[NM] Erro ao adicionar dispositivo:', error.message);
            alert('Erro ao adicionar dispositivo: ' + error.message);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });

    // -----------------------------------------------------------------------
    // + Adicionar Rede (nm-net-add-btn)
    // refactor(UI-02): incrementa badge da aba Rede após inserção.
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-net-add-btn');
        if (!btn) return;

        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) {
            alert('Salve o Servidor IPBX primeiro antes de adicionar redes.');
            return;
        }

        const ip_network    = nmVal('nm-net-ip_network');
        const netmask       = nmVal('nm-net-netmask');
        const gateway       = nmVal('nm-net-gateway');
        const dns_primary   = nmVal('nm-net-dns_primary');
        const dns_secondary = nmVal('nm-net-dns_secondary');
        const supplier      = nmVal('nm-net-supplier');

        if (!ip_network.trim()) {
            alert('Informe o IP da rede.');
            document.getElementById('nm-net-ip_network')?.focus();
            return;
        }

        const url         = btn.dataset.url || nmGetIpbxActionUrl(btn);
        const companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, {
                action: btn.dataset.action,
                ipbx_id: ipbxId,
                companies_id: companiesId,
                ip_network, netmask, gateway, dns_primary, dns_secondary, supplier,
            });
            if (!result.success) throw new Error(result.error || 'Erro desconhecido');

            const tr = document.createElement('tr');
            tr.id = 'nm-net-row-' + result.id;
            tr.className = 'tab_bg_1';
            tr.innerHTML = `
                <td>${nmEsc(ip_network)}</td>
                <td>${nmEsc(netmask)}</td>
                <td>${nmEsc(gateway)}</td>
                <td>${nmEsc(dns_primary)}</td>
                <td>${nmEsc(dns_secondary)}</td>
                <td>${nmEsc(supplier)}</td>
                <td>${nmDelBtn('delete_network', result.id, 'nm-net-row-' + result.id, companiesId, url, 'Remover rede?')}</td>`;

            const addRow = document.getElementById('nm-net-add-row');
            if (addRow) addRow.parentNode.insertBefore(tr, addRow);

            nmClearRow('nm-net-add-row', ['nm-net-ip_network','nm-net-netmask','nm-net-gateway','nm-net-dns_primary','nm-net-dns_secondary','nm-net-supplier']);
            document.getElementById('nm-net-empty')?.remove();

            // Incrementa badge da aba Rede
            nmCounterIncrement('nm-count-net');

        } catch (error) {
            console.error('[NM] Erro ao adicionar rede:', error.message);
            alert('Erro ao adicionar rede: ' + error.message);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}

// ---------------------------------------------------------------------------
// MutationObserver — garante re-registro dos handlers quando o GLPI injeta
// a aba IPBX via AJAX (o conteúdo da aba é injetado no DOM após o load).
// fix(DELEGATE-01): observa inserções de .nm-ipbx-tab no document e chama
// nmEnsureIpbxDelegated() para registrar os handlers no document atual.
// refactor(UI-02): também chama nmInitIpbxTabs() quando .nm-tab-bar aparece.
// ---------------------------------------------------------------------------

(function nmWatchForIpbxTab() {
    // Registra imediatamente (caso o script seja carregado depois da aba)
    nmEnsureIpbxDelegated();
    nmInitIpbxTabs();

    // Observa mutações futuras (aba injetada pelo GLPI via AJAX)
    const observer = new MutationObserver(() => {
        if (document.querySelector('.nm-ipbx-tab')) {
            nmEnsureIpbxDelegated();
        }
        if (document.querySelector('.nm-tab-bar')) {
            nmInitIpbxTabs();
        }
    });
    observer.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true,
    });
})();

// ---------------------------------------------------------------------------
// Inicialização — paginação registrada uma única vez
// ---------------------------------------------------------------------------
nmInitPagination();
