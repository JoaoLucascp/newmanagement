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
 * fix(CSRF-01): nmGetCsrfToken() lê meta[property="glpi:csrf_token"] como
 *   fonte primária (GLPI 11). nmRefreshCsrfToken() sincroniza tudo.
 *
 * fix(DELEGATE-01): todos os handlers registrados UMA VEZ no document via
 *   nmEnsureIpbxDelegated(). MutationObserver garante re-registro se o GLPI
 *   recriar o contexto da aba.
 *
 * fix(UI-02): abas horizontais — clique delegado no document (não no tabBar).
 *   Isso garante funcionamento mesmo quando o GLPI substitui o DOM da aba.
 *   nmInitIpbxTabs() apenas garante o estado inicial (painel ativo visível).
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
// ---------------------------------------------------------------------------

function nmGetCsrfToken() {
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content');

    const hidden = document.querySelector('input[name="_glpi_csrf_token"]');
    if (hidden && hidden.value) return hidden.value;

    const ipbxHidden = document.getElementById('nm-ipbx-csrf');
    if (ipbxHidden && ipbxHidden.value) return ipbxHidden.value;

    const chatbotHidden = document.getElementById('nm-chatbot-csrf');
    if (chatbotHidden && chatbotHidden.value) return chatbotHidden.value;

    return '';
}

function nmRefreshCsrfToken(newToken) {
    if (!newToken) return;
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    if (meta) meta.setAttribute('content', newToken);
    const ipbxHidden = document.getElementById('nm-ipbx-csrf');
    if (ipbxHidden) ipbxHidden.value = newToken;
    const chatbotHidden = document.getElementById('nm-chatbot-csrf');
    if (chatbotHidden) chatbotHidden.value = newToken;
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
            try { const json = JSON.parse(text); msg = json.error || json.message || msg; } catch { }
        } catch { }
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
    const tab = ctx ? ctx.closest('.nm-ipbx-tab') : document.querySelector('.nm-ipbx-tab');
    return tab?.dataset.actionUrl || nmAjaxUrl();
}

function nmGetIpbxCompaniesId(ctx) {
    const tab = ctx ? ctx.closest('.nm-ipbx-tab') : document.querySelector('.nm-ipbx-tab');
    return tab?.dataset.companiesId || '';
}

function nmUpdateIpbxId(newId) {
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
//
// fix(UI-02): O listener de clique é registrado NO DOCUMENT (delegação),
// não no tabBar. Isso é OBRIGATÓRIO porque o GLPI pode substituir o DOM
// inteiro da aba via AJAX, destruindo qualquer listener colado diretamente
// no elemento. Com delegação no document, o handler sobrevive a qualquer
// reinjeção de HTML.
//
// nmInitIpbxTabs() apenas garante que o painel ativo (Ramais) seja visível
// ao carregar — não registra listeners.
// ---------------------------------------------------------------------------

function nmInitIpbxTabs() {
    // Garante que o primeiro painel esteja visível ao carregar
    // (o HTML já vem com class="active" e sem [hidden], mas reforçamos)
    const firstPanel = document.getElementById('nm-panel-ext');
    if (firstPanel) {
        firstPanel.removeAttribute('hidden');
        firstPanel.classList.add('active');
    }
    // Garante que os demais estejam ocultos
    ['nm-panel-dev', 'nm-panel-net'].forEach(id => {
        const p = document.getElementById(id);
        if (p) {
            p.setAttribute('hidden', '');
            p.classList.remove('active');
        }
    });
}

// ---------------------------------------------------------------------------
// Paginação AJAX — delegação de eventos
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
// IPBX — Delegação de eventos (registrada UMA ÚNICA VEZ no document)
// ---------------------------------------------------------------------------

const NM_IPBX_BOUND_KEY = '__nmIpbxHandlersBound__';

function nmEnsureIpbxDelegated() {
    if (document[NM_IPBX_BOUND_KEY]) return;
    document[NM_IPBX_BOUND_KEY] = true;
    window._nmIpbxDelegated = true;

    // -----------------------------------------------------------------------
    // fix(UI-02): Troca de abas — delegado no document.
    // Captura cliques em .nm-tab de QUALQUER .nm-tab-bar presente no DOM,
    // mesmo que o elemento seja reinjetado pelo GLPI após reload da aba.
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const tab = e.target.closest('.nm-tab');
        if (!tab) return;

        const tabBar = tab.closest('.nm-tab-bar');
        if (!tabBar) return;

        // Desativa todas as abas desta barra
        tabBar.querySelectorAll('.nm-tab').forEach(t => {
            t.classList.remove('active');
            t.setAttribute('aria-selected', 'false');
        });

        // Oculta todos os painéis do wrapper pai
        const wrapper = tabBar.closest('.nm-tabs-wrapper');
        if (wrapper) {
            wrapper.querySelectorAll('.nm-tab-panel').forEach(p => {
                p.classList.remove('active');
                p.setAttribute('hidden', '');
            });
        }

        // Ativa aba clicada
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');

        // Exibe painel correspondente
        const panelId = tab.dataset.panel;
        const panel = document.getElementById(panelId);
        if (panel) {
            panel.classList.add('active');
            panel.removeAttribute('hidden');
        }
    });

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
        if (icon) icon.className = isPassword ? 'ti ti-eye-off' : 'ti ti-eye';
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
    // Recolher/expandir seções legadas (compatibilidade)
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
    // Lixeira de linha não salva
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-row-del-btn');
        if (!btn) return;
        const row = btn.closest('tr');
        if (row) row.remove();
    });

    // -----------------------------------------------------------------------
    // Lixeira de linha salva — DELETE no servidor
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
    // + Adicionar Ramal
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-ext-add-btn');
        if (!btn) return;

        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) { alert('Salve o Servidor IPBX primeiro antes de adicionar ramais.'); return; }

        const number        = nmVal('nm-ext-number');
        const password      = nmVal('nm-ext-password');
        const device_ip     = nmVal('nm-ext-device_ip');
        const user_name     = nmVal('nm-ext-user_name');
        const records_calls = nmVal('nm-ext-records_calls') || '0';
        const department    = nmVal('nm-ext-department');

        if (!number.trim()) { alert('Informe o número do ramal.'); document.getElementById('nm-ext-number')?.focus(); return; }

        const url         = btn.dataset.url || nmGetIpbxActionUrl(btn);
        const companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, {
                action: btn.dataset.action,
                ipbx_id: ipbxId, companies_id: companiesId,
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
    // + Adicionar Dispositivo
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-dev-add-btn');
        if (!btn) return;

        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) { alert('Salve o Servidor IPBX primeiro antes de adicionar dispositivos.'); return; }

        const device_type = nmVal('nm-dev-device_type');
        const ip_address  = nmVal('nm-dev-ip_address');
        const login       = nmVal('nm-dev-login');
        const password    = nmVal('nm-dev-password');

        if (!device_type.trim()) { alert('Informe o tipo do dispositivo.'); document.getElementById('nm-dev-device_type')?.focus(); return; }

        const url         = btn.dataset.url || nmGetIpbxActionUrl(btn);
        const companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, {
                action: btn.dataset.action,
                ipbx_id: ipbxId, companies_id: companiesId,
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
    // + Adicionar Rede
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-net-add-btn');
        if (!btn) return;

        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) { alert('Salve o Servidor IPBX primeiro antes de adicionar redes.'); return; }

        const ip_network    = nmVal('nm-net-ip_network');
        const netmask       = nmVal('nm-net-netmask');
        const gateway       = nmVal('nm-net-gateway');
        const dns_primary   = nmVal('nm-net-dns_primary');
        const dns_secondary = nmVal('nm-net-dns_secondary');
        const supplier      = nmVal('nm-net-supplier');

        if (!ip_network.trim()) { alert('Informe o IP da rede.'); document.getElementById('nm-net-ip_network')?.focus(); return; }

        const url         = btn.dataset.url || nmGetIpbxActionUrl(btn);
        const companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, {
                action: btn.dataset.action,
                ipbx_id: ipbxId, companies_id: companiesId,
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
// MutationObserver — reinicia estado visual das abas quando o GLPI
// injeta o HTML da aba via AJAX.
// fix(UI-02): nmInitIpbxTabs() é chamada sempre que .nm-tab-bar aparecer
// no DOM (sem guard de flag — pode rodar múltiplas vezes sem problema,
// pois só manipula atributos, não registra listeners).
// ---------------------------------------------------------------------------

(function nmWatchForIpbxTab() {
    nmEnsureIpbxDelegated(); // registra TODOS os listeners UMA VEZ no document
    nmInitIpbxTabs();        // garante estado visual inicial

    const observer = new MutationObserver(() => {
        if (document.querySelector('.nm-ipbx-tab')) {
            nmEnsureIpbxDelegated();
        }
        if (document.querySelector('.nm-tab-bar')) {
            nmInitIpbxTabs(); // sem guard — seguro rodar múltiplas vezes
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
