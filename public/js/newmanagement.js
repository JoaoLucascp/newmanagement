/**
 * Newmanagement - Plugin GLPI
 * Máscaras, busca CNPJ e CEP via BrasilAPI
 * Botões AJAX do formulário IPBX e Chatbot (com token CSRF do GLPI 11)
 *
 * fix(CSRF-01): nmGetCsrfToken() lê meta[property="glpi:csrf_token"].
 * fix(DELEGATE-01): handlers registrados UMA VEZ via nmEnsureIpbxDelegated().
 * fix(UI-02): abas horizontais — clique delegado no document.
 * fix(UI-03): ramais usam linha pendente clonada antes de salvar via AJAX.
 * fix(TOGGLE-01): flag padronizada para document._nmToggleBoolDelegated.
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
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-Glpi-Csrf-Token': csrf,
        },
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
    const section = ctx ? ctx.closest('#nm-ext-section, #nm-dev-section, #nm-net-section, .nm-ipbx-tab') : null;
    return section?.dataset.actionUrl || document.querySelector('.nm-ipbx-tab')?.dataset.actionUrl || nmAjaxUrl();
}

function nmGetIpbxCompaniesId(ctx) {
    const section = ctx ? ctx.closest('#nm-ext-section, #nm-dev-section, #nm-net-section, .nm-ipbx-tab') : null;
    return section?.dataset.companiesId || document.querySelector('.nm-ipbx-tab')?.dataset.companiesId || '';
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
// ---------------------------------------------------------------------------

function nmInitIpbxTabs() {
    const firstPanel = document.getElementById('nm-panel-ext');
    if (firstPanel) {
        firstPanel.removeAttribute('hidden');
        firstPanel.classList.add('active');
    }
    ['nm-panel-dev', 'nm-panel-net'].forEach(id => {
        const p = document.getElementById(id);
        if (p) {
            p.setAttribute('hidden', '');
            p.classList.remove('active');
        }
    });
}

// ---------------------------------------------------------------------------
// Paginação AJAX
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

    // -----------------------------------------------------------------------
    // Troca de abas
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const tab = e.target.closest('.nm-tab');
        if (!tab) return;
        const tabBar = tab.closest('.nm-tab-bar');
        if (!tabBar) return;
        tabBar.querySelectorAll('.nm-tab').forEach(t => {
            t.classList.remove('active');
            t.setAttribute('aria-selected', 'false');
        });
        const wrapper = tabBar.closest('.nm-tabs-wrapper');
        if (wrapper) {
            wrapper.querySelectorAll('.nm-tab-panel').forEach(p => {
                p.classList.remove('active');
                p.setAttribute('hidden', '');
            });
        }
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        const panel = document.getElementById(tab.dataset.panel);
        if (panel) {
            panel.classList.add('active');
            panel.removeAttribute('hidden');
        }
    });

    // -----------------------------------------------------------------------
    // Olho — mostrar/ocultar senha
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-btn-eye');
        if (!btn) return;
        const input = document.getElementById(btn.dataset.target);
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
    // Recolher/expandir seções legadas
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-toggle-section');
        if (!btn) return;
        const tbody = document.getElementById(btn.dataset.target);
        if (!tbody) return;
        const isExpanded = btn.getAttribute('aria-expanded') !== 'false';
        tbody.style.display = isExpanded ? 'none' : '';
        btn.setAttribute('aria-expanded', !isExpanded);
        const icon = btn.querySelector('i');
        if (icon) icon.className = isExpanded ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
    });

    // -----------------------------------------------------------------------
    // Lixeira AJAX — linhas salvas (.nm-del-btn)
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.nm-del-btn');
        if (!btn) return;

        const id          = btn.dataset.id;
        const url         = btn.dataset.actionUrl || btn.dataset.url || nmGetIpbxActionUrl(btn);
        const companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);
        const confirmMsg  = btn.dataset.confirm || 'Remover item?';
        const action      = btn.dataset.action || 'delete_extension';
        const rowId       = btn.dataset.row || ('nm-ext-row-' + id);

        if (!confirm(confirmMsg)) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, { action, id, companies_id: companiesId });
            if (!result.success) throw new Error(result.error || 'Erro ao remover');
            document.getElementById(rowId)?.remove();

            if (action === 'delete_extension') {
                nmCounterDecrement('nm-count-ext');
                nmExtSyncEmptyState(btn.closest('#nm-ext-section'));
            }
            else if (action === 'delete_device')  nmCounterDecrement('nm-count-dev');
            else if (action === 'delete_network') nmCounterDecrement('nm-count-net');

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
    function nmExtSyncEmptyState(section) {
        const root = section || document.getElementById('nm-ext-section');
        const tbody = root?.querySelector('#nm-ext-tbody') || document.getElementById('nm-ext-tbody');
        if (!tbody) return;

        const hasRows = tbody.querySelector('.nm-ext-saved-row, .nm-ext-pending-row');
        const emptyRow = tbody.querySelector('#nm-ext-empty');
        if (hasRows) {
            emptyRow?.remove();
            return;
        }
        if (emptyRow) return;

        const tr = document.createElement('tr');
        tr.id = 'nm-ext-empty';
        tr.innerHTML = '<td colspan="13" class="text-center text-muted py-3">Nenhum ramal cadastrado.</td>';
        const template = tbody.querySelector('#nm-ext-add-row');
        if (template) tbody.insertBefore(tr, template);
        else tbody.appendChild(tr);
    }

    function nmExtRowValue(row, selector) {
        return row.querySelector(selector)?.value || '';
    }

    function nmExtBoolValue(row, field) {
        return row.querySelector(`.nm-f-bool[data-field="${field}"]`)?.checked ? '1' : '0';
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#nm-ext-add-btn');
        if (!btn) return;

        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) { alert('Salve o Servidor IPBX primeiro.'); return; }

        const section = btn.closest('#nm-ext-section') || document.getElementById('nm-ext-section');
        const tbody = section?.querySelector('#nm-ext-tbody') || document.getElementById('nm-ext-tbody');
        const template = tbody?.querySelector('#nm-ext-add-row') || document.getElementById('nm-ext-add-row');
        if (!tbody || !template) {
            alert('Nao foi possivel localizar a linha de cadastro de ramal.');
            return;
        }

        const row = template.cloneNode(true);
        row.removeAttribute('id');
        row.hidden = false;
        row.style.display = '';
        row.classList.remove('nm-ext-input-template');
        row.classList.add('tab_bg_1', 'nm-ext-pending-row');
        row.dataset.ipbxId = String(ipbxId);
        row.dataset.companiesId = btn.dataset.companiesId || nmGetIpbxCompaniesId(btn);

        row.querySelectorAll('input[type="text"], input[type="password"]').forEach(input => {
            input.value = '';
        });
        row.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.checked = false;
        });
        row.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });

        document.getElementById('nm-ext-empty')?.remove();
        tbody.insertBefore(row, template);
        row.querySelector('.nm-f-number')?.focus();
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-row-del-btn');
        if (!btn) return;
        const row = btn.closest('.nm-ext-pending-row');
        if (!row) return;
        const section = row.closest('#nm-ext-section');
        row.remove();
        nmExtSyncEmptyState(section);
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.nm-ext-save-row');
        if (!btn) return;

        const row = btn.closest('.nm-ext-pending-row');
        if (!row) return;

        const section = row.closest('#nm-ext-section') || document.getElementById('nm-ext-section');
        const ipbxId = parseInt(row.dataset.ipbxId || section?.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) { alert('Salve o Servidor IPBX primeiro.'); return; }

        const number = nmExtRowValue(row, '.nm-f-number').trim();
        if (!number) {
            alert('Informe o numero do ramal.');
            row.querySelector('.nm-f-number')?.focus();
            return;
        }

        const url = section?.dataset.actionUrl || nmGetIpbxActionUrl(btn);
        const companiesId = row.dataset.companiesId || section?.dataset.companiesId || nmGetIpbxCompaniesId(btn);
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="nm-spinner"></span>';

        try {
            const result = await nmPost(url, {
                action: 'add_extension',
                ipbx_id: ipbxId,
                companies_id: companiesId,
                number,
                password: nmExtRowValue(row, '.nm-f-password'),
                device_ip: nmExtRowValue(row, '.nm-f-device_ip'),
                user_name: nmExtRowValue(row, '.nm-f-user_name'),
                records_calls: nmExtRowValue(row, '.nm-f-records_calls') || '0',
                department: nmExtRowValue(row, '.nm-f-department'),
                lof: nmExtBoolValue(row, 'lof'),
                loc: nmExtBoolValue(row, 'loc'),
                ddf: nmExtBoolValue(row, 'ddf'),
                ddc: nmExtBoolValue(row, 'ddc'),
                ddi: nmExtBoolValue(row, 'ddi'),
                srv: nmExtBoolValue(row, 'srv'),
            });
            if (!result.success) throw new Error(result.error || 'Erro desconhecido');
            if (result.html) row.outerHTML = result.html;
            else row.remove();
            nmCounterIncrement('nm-count-ext');
        } catch (error) {
            console.error('[NM] Erro ao salvar ramal:', error.message);
            alert('Erro ao salvar ramal: ' + error.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            return;
        }

        const total = parseInt(section?.dataset.total || '0', 10);
        if (section) section.dataset.total = String(total + 1);
        nmExtSyncEmptyState(section);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        const row = e.target.closest?.('.nm-ext-pending-row');
        if (!row) return;
        const tag = e.target.tagName ? e.target.tagName.toLowerCase() : '';
        if (tag === 'textarea') return;
        e.preventDefault();
        row.querySelector('.nm-ext-save-row')?.click();
    });

    // -----------------------------------------------------------------------
    // + Adicionar Dispositivo
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-dev-add-btn');
        if (!btn) return;
        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) { alert('Salve o Servidor IPBX primeiro.'); return; }
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
            const result = await nmPost(url, { action: btn.dataset.action, ipbx_id: ipbxId, companies_id: companiesId, device_type, ip_address, login, password });
            if (!result.success) throw new Error(result.error || 'Erro desconhecido');
            const tr = document.createElement('tr');
            tr.id = 'nm-dev-row-' + result.id;
            tr.className = 'tab_bg_1';
            tr.innerHTML = `<td>${nmEsc(device_type)}</td><td>${nmEsc(ip_address)}</td><td>${nmEsc(login)}</td><td>••••••</td><td>${nmDelBtn('delete_device', result.id, 'nm-dev-row-' + result.id, companiesId, url, 'Remover dispositivo?')}</td>`;
            const addRow = document.getElementById('nm-dev-add-row');
            if (addRow) addRow.parentNode.insertBefore(tr, addRow);
            document.getElementById('nm-dev-empty')?.remove();
            nmCounterIncrement('nm-count-dev');
        } catch (error) {
            console.error('[NM] Erro ao adicionar dispositivo:', error.message);
            alert('Erro ao adicionar dispositivo: ' + error.message);
        } finally { btn.innerHTML = originalHtml; btn.disabled = false; }
    });

    // -----------------------------------------------------------------------
    // + Adicionar Rede
    // -----------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#nm-net-add-btn');
        if (!btn) return;
        const ipbxId = parseInt(btn.dataset.ipbxId || '0', 10);
        if (ipbxId <= 0) { alert('Salve o Servidor IPBX primeiro.'); return; }
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
            const result = await nmPost(url, { action: btn.dataset.action, ipbx_id: ipbxId, companies_id: companiesId, ip_network, netmask, gateway, dns_primary, dns_secondary, supplier });
            if (!result.success) throw new Error(result.error || 'Erro desconhecido');
            const tr = document.createElement('tr');
            tr.id = 'nm-net-row-' + result.id;
            tr.className = 'tab_bg_1';
            tr.innerHTML = `<td>${nmEsc(ip_network)}</td><td>${nmEsc(netmask)}</td><td>${nmEsc(gateway)}</td><td>${nmEsc(dns_primary)}</td><td>${nmEsc(dns_secondary)}</td><td>${nmEsc(supplier)}</td><td>${nmDelBtn('delete_network', result.id, 'nm-net-row-' + result.id, companiesId, url, 'Remover rede?')}</td>`;
            const addRow = document.getElementById('nm-net-add-row');
            if (addRow) addRow.parentNode.insertBefore(tr, addRow);
            document.getElementById('nm-net-empty')?.remove();
            nmCounterIncrement('nm-count-net');
        } catch (error) {
            console.error('[NM] Erro ao adicionar rede:', error.message);
            alert('Erro ao adicionar rede: ' + error.message);
        } finally { btn.innerHTML = originalHtml; btn.disabled = false; }
    });

    // -----------------------------------------------------------------------
    // Toggle booleano inline (nm-toggle-bool)
    // Flag padronizada: document._nmToggleBoolDelegated
    // Compartilhada com tab_extensions.html.twig — garante registro único.
    // -----------------------------------------------------------------------
    if (!document._nmToggleBoolDelegated) {
        document._nmToggleBoolDelegated = true;
        document.addEventListener('change', async (e) => {
            const cb = e.target.closest('.nm-toggle-bool');
            if (!cb) return;
            const rowId       = cb.dataset.rowId;
            const field       = cb.dataset.field;
            const value       = cb.checked ? '1' : '0';
            const url         = cb.dataset.actionUrl || nmGetIpbxActionUrl(cb);
            const companiesId = cb.dataset.companiesId || nmGetIpbxCompaniesId(cb);
            try {
                const result = await nmPost(url, {
                    action: 'update_extension_field',
                    id: rowId, field, value, companies_id: companiesId,
                });
                if (!result.success) throw new Error(result.error || 'Erro ao atualizar');
                if (result.csrf) nmRefreshCsrfToken(result.csrf);
            } catch (err) {
                console.error('[NM] Toggle bool falhou:', err.message);
                cb.checked = !cb.checked;
            }
        });
    }
}

// ---------------------------------------------------------------------------
// MutationObserver — reinicia abas quando GLPI injeta HTML via AJAX
// ---------------------------------------------------------------------------

(function nmWatchForIpbxTab() {
    nmEnsureIpbxDelegated();
    nmInitIpbxTabs();

    const observer = new MutationObserver(() => {
        if (document.querySelector('.nm-ipbx-tab')) nmEnsureIpbxDelegated();
        if (document.querySelector('.nm-tab-bar'))  nmInitIpbxTabs();
    });

    observer.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true,
    });
})();

nmInitPagination();
