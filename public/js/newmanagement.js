/**
 * Newmanagement - Plugin GLPI
 * Mascaras, busca CNPJ e CEP via BrasilAPI
 * Botões AJAX do formulário IPBX e Chatbot (com token CSRF do GLPI 11)
 *
 * fix(UI-01): botões + Adicionar agora clonam a linha de input pré-adicionada.
 *   - Linha base (nm-*-add-row) sempre visível na tabela.
 *   - Clicar em + Adicionar lê os dados da linha ativa, faz POST,
 *     insere linha de leitura com lixeira e reseta a linha base.
 *   - nm-row-del-btn remove a própria linha (linhas pendentes sem id salvo).
 *   - Ao salvar com sucesso a lixeira usa nm-del-btn (DELETE real no servidor).
 */

console.log('Newmanagement Plugin carregado.');

// ---------------------------------------------------------------------------
// Mascaras
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
    const ipbxHidden = document.getElementById('nm-ipbx-csrf');
    if (ipbxHidden && ipbxHidden.value) return ipbxHidden.value;

    const chatbotHidden = document.getElementById('nm-chatbot-csrf');
    if (chatbotHidden && chatbotHidden.value) return chatbotHidden.value;

    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    if (meta) return meta.getAttribute('content');

    const hidden = document.querySelector('input[name="_glpi_csrf_token"]');
    if (hidden) return hidden.value;

    return '';
}

function nmRefreshCsrfToken(newToken) {
    if (!newToken) return;
    const ipbxHidden = document.getElementById('nm-ipbx-csrf');
    if (ipbxHidden) ipbxHidden.value = newToken;
    const chatbotHidden = document.getElementById('nm-chatbot-csrf');
    if (chatbotHidden) chatbotHidden.value = newToken;
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    if (meta) meta.setAttribute('content', newToken);
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

function nmGetIpbxActionUrl() {
    return document.querySelector('.nm-ipbx-tab')?.dataset.actionUrl || nmAjaxUrl();
}

function nmGetIpbxCompaniesId() {
    return document.querySelector('.nm-ipbx-tab')?.dataset.companiesId || '';
}

function nmUpdateIpbxId(newId) {
    ['nm-ext-add-btn', 'nm-dev-add-btn', 'nm-net-add-btn'].forEach((id) => {
        const btn = document.getElementById(id);
        if (btn) btn.dataset.ipbxId = newId;
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
// IPBX — Botões Adicionar / Remover
//
// fix(UI-01): arquitetura corrigida
//   1. Linha de input (nm-*-add-row) sempre visível — pré-adicionada pelo Twig.
//   2. Botão + Adicionar lê os campos da linha base, faz POST AJAX,
//      insere linha de leitura com lixeira (DELETE real) acima da linha base
//      e limpa os campos da linha base.
//   3. nm-row-del-btn (lixeira da linha base ou de cópias pendentes)
//      remove a linha diretamente sem chamar o servidor.
//   4. nm-del-btn (lixeira de linhas salvas) faz DELETE real no servidor.
// ---------------------------------------------------------------------------

let nmDelegatedListenersRegistered = false;

function nmInitIpbxButtons() {
    const URL = nmGetIpbxActionUrl();

    function getBtnUrl(btn)      { return btn?.dataset.url        || URL; }
    function getCompaniesId(btn) { return btn?.dataset.companiesId || nmGetIpbxCompaniesId(); }
    function checkIpbxSaved(btn) {
        const id = parseInt(btn.dataset.ipbxId || '0', 10);
        if (id <= 0) {
            alert('Salve o Servidor IPBX primeiro antes de adicionar sub-itens.');
            return false;
        }
        return true;
    }

    // --- Salvar IPBX principal ---
    if (!window._nmIpbxSaveDelegated) {
        window._nmIpbxSaveDelegated = true;

        document.addEventListener('click', async (e) => {
            const btnSaveAll = e.target.closest('#nm-save-all');
            if (!btnSaveAll) return;

            const actionUrl = btnSaveAll.dataset.actionUrl || nmGetIpbxActionUrl();

            const ipbxData = {
                action:         nmVal('nm-ipbx-action')       || 'add_ipbx',
                id:             nmVal('nm-ipbx-id')           || '0',
                companies_id:   nmVal('nm-ipbx-companies-id') || nmGetIpbxCompaniesId(),
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

            btnSaveAll.disabled = true;
            const originalHtml = btnSaveAll.innerHTML;
            btnSaveAll.innerHTML = '<span class="nm-spinner"></span> Salvando...';

            try {
                const result = await nmPost(actionUrl, ipbxData);

                if (result && result.id) {
                    nmUpdateIpbxId(result.id);
                    // Exibe linhas de input pré-adicionadas que estavam ocultas
                    // (ipbx_id era 0 ao renderizar — linhas não foram geradas pelo Twig)
                    // Neste caso o usuário precisa recarregar; apenas mostramos feedback.
                }

                btnSaveAll.innerHTML = '<i class="ti ti-check"></i> Salvo!';
                btnSaveAll.classList.replace('btn-primary', 'btn-success');
                setTimeout(() => {
                    btnSaveAll.innerHTML = originalHtml;
                    btnSaveAll.classList.replace('btn-success', 'btn-primary');
                    btnSaveAll.disabled = false;
                }, 2000);
            } catch (error) {
                console.error('[NM] Erro ao salvar IPBX:', error.message);
                alert('Erro ao salvar IPBX: ' + error.message);
                btnSaveAll.innerHTML = originalHtml;
                btnSaveAll.disabled = false;
            }
        });
    }

    // -----------------------------------------------------------------------
    // Helper: insere linha de leitura acima da linha de input e limpa campos
    // -----------------------------------------------------------------------
    function nmInsertReadRow(addRowId, newTr) {
        const addRow = document.getElementById(addRowId);
        if (addRow) {
            addRow.parentNode.insertBefore(newTr, addRow);
        } else {
            // fallback: append no tbody
            const tbody = newTr.closest ? null : null;
        }
    }

    // --- Adicionar Ramal ---
    const btnExt = document.getElementById('nm-ext-add-btn');
    if (btnExt && !btnExt._nmBound) {
        btnExt._nmBound = true;
        btnExt.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnExt)) return;

            const number       = nmVal('nm-ext-number');
            const password     = nmVal('nm-ext-password');
            const device_ip    = nmVal('nm-ext-device_ip');
            const user_name    = nmVal('nm-ext-user_name');
            const records_calls = nmVal('nm-ext-records_calls') || '0';
            const department   = nmVal('nm-ext-department');

            if (!number.trim()) {
                alert('Informe o número do ramal.');
                document.getElementById('nm-ext-number')?.focus();
                return;
            }

            const data = {
                action:        btnExt.dataset.action,
                ipbx_id:       btnExt.dataset.ipbxId,
                companies_id:  getCompaniesId(btnExt),
                number, password, device_ip, user_name, records_calls, department,
            };

            const originalHtml = btnExt.innerHTML;
            btnExt.disabled = true;
            btnExt.innerHTML = '<span class="nm-spinner"></span>';

            try {
                const result = await nmPost(getBtnUrl(btnExt), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');

                // Linha de leitura com lixeira (DELETE real)
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
                    <td>${nmDelBtn('delete_extension', result.id, 'nm-ext-row-' + result.id, getCompaniesId(btnExt), getBtnUrl(btnExt), 'Remover ramal?')}</td>`;
                nmInsertReadRow('nm-ext-add-row', tr);

                // Limpa campos da linha base
                nmClearRow('nm-ext-add-row', ['nm-ext-number','nm-ext-password','nm-ext-device_ip','nm-ext-user_name','nm-ext-department','nm-ext-records_calls']);

                // Remove mensagem "nenhum cadastrado" se existir
                document.getElementById('nm-ext-empty')?.remove();

            } catch (error) {
                console.error('[NM] Erro ao adicionar ramal:', error.message);
                alert('Erro ao adicionar ramal: ' + error.message);
            } finally {
                btnExt.innerHTML = originalHtml;
                btnExt.disabled = false;
            }
        });
    }

    // --- Adicionar Dispositivo ---
    const btnDev = document.getElementById('nm-dev-add-btn');
    if (btnDev && !btnDev._nmBound) {
        btnDev._nmBound = true;
        btnDev.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnDev)) return;

            const device_type = nmVal('nm-dev-device_type');
            const ip_address  = nmVal('nm-dev-ip_address');
            const login       = nmVal('nm-dev-login');
            const password    = nmVal('nm-dev-password');

            if (!device_type.trim()) {
                alert('Informe o tipo do dispositivo.');
                document.getElementById('nm-dev-device_type')?.focus();
                return;
            }

            const data = {
                action:       btnDev.dataset.action,
                ipbx_id:      btnDev.dataset.ipbxId,
                companies_id: getCompaniesId(btnDev),
                device_type, ip_address, login, password,
            };

            const originalHtml = btnDev.innerHTML;
            btnDev.disabled = true;
            btnDev.innerHTML = '<span class="nm-spinner"></span>';

            try {
                const result = await nmPost(getBtnUrl(btnDev), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');

                const tr = document.createElement('tr');
                tr.id = 'nm-dev-row-' + result.id;
                tr.className = 'tab_bg_1';
                tr.innerHTML = `
                    <td>${nmEsc(device_type)}</td>
                    <td>${nmEsc(ip_address)}</td>
                    <td>${nmEsc(login)}</td>
                    <td>••••••</td>
                    <td>${nmDelBtn('delete_device', result.id, 'nm-dev-row-' + result.id, getCompaniesId(btnDev), getBtnUrl(btnDev), 'Remover dispositivo?')}</td>`;
                nmInsertReadRow('nm-dev-add-row', tr);

                nmClearRow('nm-dev-add-row', ['nm-dev-device_type','nm-dev-ip_address','nm-dev-login','nm-dev-password']);
                document.getElementById('nm-dev-empty')?.remove();

            } catch (error) {
                console.error('[NM] Erro ao adicionar dispositivo:', error.message);
                alert('Erro ao adicionar dispositivo: ' + error.message);
            } finally {
                btnDev.innerHTML = originalHtml;
                btnDev.disabled = false;
            }
        });
    }

    // --- Adicionar Rede ---
    const btnNet = document.getElementById('nm-net-add-btn');
    if (btnNet && !btnNet._nmBound) {
        btnNet._nmBound = true;
        btnNet.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnNet)) return;

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

            const data = {
                action:        btnNet.dataset.action,
                ipbx_id:       btnNet.dataset.ipbxId,
                companies_id:  getCompaniesId(btnNet),
                ip_network, netmask, gateway, dns_primary, dns_secondary, supplier,
            };

            const originalHtml = btnNet.innerHTML;
            btnNet.disabled = true;
            btnNet.innerHTML = '<span class="nm-spinner"></span>';

            try {
                const result = await nmPost(getBtnUrl(btnNet), data);
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
                    <td>${nmDelBtn('delete_network', result.id, 'nm-net-row-' + result.id, getCompaniesId(btnNet), getBtnUrl(btnNet), 'Remover rede?')}</td>`;
                nmInsertReadRow('nm-net-add-row', tr);

                nmClearRow('nm-net-add-row', ['nm-net-ip_network','nm-net-netmask','nm-net-gateway','nm-net-dns_primary','nm-net-dns_secondary','nm-net-supplier']);
                document.getElementById('nm-net-empty')?.remove();

            } catch (error) {
                console.error('[NM] Erro ao adicionar rede:', error.message);
                alert('Erro ao adicionar rede: ' + error.message);
            } finally {
                btnNet.innerHTML = originalHtml;
                btnNet.disabled = false;
            }
        });
    }

    // --- Listeners delegados (delete + eye + toggle + nm-row-del-btn) ---
    if (!nmDelegatedListenersRegistered) {
        nmDelegatedListenersRegistered = true;

        // Recolher/Expandir seções
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.nm-toggle-section');
            if (!btn) return;
            const targetId = btn.dataset.target;
            const tbody = document.getElementById(targetId);
            if (!tbody) return;
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            tbody.style.display = isExpanded ? 'none' : '';
            btn.setAttribute('aria-expanded', String(!isExpanded));
            const icon = btn.querySelector('i');
            if (icon) icon.className = isExpanded ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
        });

        // Lixeira linha de input (sem id salvo) — remove somente do DOM
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.nm-row-del-btn');
            if (!btn) return;
            const row = btn.closest('tr');
            if (!row) return;
            // Não remove a linha base (que tem id nm-*-add-row)
            if (row.id && row.id.match(/^nm-(ext|dev|net)-add-row$/)) {
                // Apenas limpa os campos em vez de remover a linha base
                row.querySelectorAll('input').forEach(el => el.value = '');
                row.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
                return;
            }
            row.remove();
        });

        // Delete real (linhas salvas) + toggle olho senha
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.nm-del-btn, .nm-btn-eye');
            if (!btn) return;

            if (btn.classList.contains('nm-btn-eye')) {
                const target = document.getElementById(btn.dataset.target);
                if (!target) return;
                target.type = target.type === 'password' ? 'text' : 'password';
                const icon = btn.querySelector('i');
                if (icon) icon.className = target.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
                return;
            }

            const action = btn.dataset.action;
            const id     = btn.dataset.id;
            if (!action || !id) return;
            if (!confirm(btn.dataset.confirm || 'Deseja remover este item?')) return;

            try {
                const result = await nmPost(getBtnUrl(btn), {
                    action, id, companies_id: btn.dataset.companiesId || nmGetIpbxCompaniesId(),
                });
                if (!result.success) throw new Error(result.error || 'Erro ao remover');
                const row = document.getElementById(btn.dataset.row);
                if (row) row.remove();
            } catch (error) {
                alert('Erro ao remover: ' + error.message);
            }
        });
    }
}

// ---------------------------------------------------------------------------
// Escape HTML — previne XSS ao inserir dados do usuário via innerHTML
// ---------------------------------------------------------------------------
function nmEsc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ---------------------------------------------------------------------------
// Chatbot — handlers via delegação
// ---------------------------------------------------------------------------

function nmInitChatbotButtons() {
    if (!window._nmChatbotDelegated) {
        window._nmChatbotDelegated = true;

        document.addEventListener('click', async (e) => {
            const btnSave = e.target.closest('#nm-chatbot-save');
            if (!btnSave) return;

            const url  = btnSave.dataset.actionUrl;
            const data = {
                action:                  nmVal('nm-chatbot-action')       || 'add_chatbot',
                id:                      nmVal('nm-chatbot-id')           || '0',
                companies_id:            nmVal('nm-chatbot-companies-id') || '0',
                model:                   nmVal('nm-chatbot-model'),
                chatbot_registration_id: nmVal('nm-chatbot-registration_id'),
                activation_date:         nmVal('nm-chatbot-activation_date'),
                whatsapp_number:         nmVal('nm-chatbot-whatsapp'),
                access_link:             nmVal('nm-chatbot-access_link'),
                plan:                    nmVal('nm-chatbot-plan'),
                users_count:             nmVal('nm-chatbot-users_count'),
                supervisors_count:       nmVal('nm-chatbot-supervisors_count'),
                admins_count:            nmVal('nm-chatbot-admins_count'),
                social_networks:         nmVal('nm-chatbot-social_networks'),
                admin_login:             nmVal('nm-chatbot-admin_login'),
                admin_password:          nmVal('nm-chatbot-admin_password'),
                superadmin_login:        nmVal('nm-chatbot-superadmin_login'),
                superadmin_password:     nmVal('nm-chatbot-superadmin_password'),
                manager_name:            nmVal('nm-chatbot-manager_name'),
                manager_contact:         nmVal('nm-chatbot-manager_contact'),
                manager_email:           nmVal('nm-chatbot-manager_email'),
                comment:                 nmVal('nm-chatbot-comment'),
            };

            const collect = (selector) => Array.from(document.querySelectorAll(selector)).map(i => i.value || '');
            const user_name_arr = collect('input[name="chatbot_users[user_name][]"]');
            if (user_name_arr.length) {
                data['chatbot_users[user_name][]'] = user_name_arr;
                data['chatbot_users[login][]']     = collect('input[name="chatbot_users[login][]"]');
                data['chatbot_users[password][]']  = collect('input[name="chatbot_users[password][]"]');
                data['chatbot_users[email][]']     = collect('input[name="chatbot_users[email][]"]');
                data['chatbot_users[user_type][]'] = collect('select[name="chatbot_users[user_type][]"]');
            }
            const mc_names = collect('input[name="chatbot_mass_comm[system_name][]"]');
            if (mc_names.length) {
                data['chatbot_mass_comm[system_name][]'] = mc_names;
                data['chatbot_mass_comm[activation_date][]'] = collect('input[name="chatbot_mass_comm[activation_date][]"]');
                data['chatbot_mass_comm[authenticated_number][]'] = collect('input[name="chatbot_mass_comm[authenticated_number][]"]');
                data['chatbot_mass_comm[homologation_type][]'] = collect('input[name="chatbot_mass_comm[homologation_type][]"]');
                data['chatbot_mass_comm[access_link][]'] = collect('input[name="chatbot_mass_comm[access_link][]"]');
                data['chatbot_mass_comm[login][]'] = collect('input[name="chatbot_mass_comm[login][]"]');
                data['chatbot_mass_comm[password][]'] = collect('input[name="chatbot_mass_comm[password][]"]');
            }
            const wa_nums = collect('input[name="chatbot_wa_restrictions[whatsapp_number][]"]');
            if (wa_nums.length) {
                data['chatbot_wa_restrictions[whatsapp_number][]'] = wa_nums;
                data['chatbot_wa_restrictions[restriction_date][]'] = collect('input[name="chatbot_wa_restrictions[restriction_date][]"]');
                data['chatbot_wa_restrictions[restriction_time][]'] = collect('input[name="chatbot_wa_restrictions[restriction_time][]"]');
                data['chatbot_wa_restrictions[end_date][]'] = collect('input[name="chatbot_wa_restrictions[end_date][]"]');
            }

            try {
                const result = await nmPost(url, data);
                if (!result.success) throw new Error(result.error || 'Erro ao salvar');

                const actionEl = document.getElementById('nm-chatbot-action');
                const idEl     = document.getElementById('nm-chatbot-id');
                if (actionEl) actionEl.value = 'update_chatbot';
                if (idEl && result.id) {
                    idEl.value = result.id;
                    ['nm-mc-add-btn', 'nm-wa-add-btn', 'nm-cu-add-btn'].forEach(btnId => {
                        const b = document.getElementById(btnId);
                        if (b) b.dataset.chatbotId = result.id;
                    });
                }

                btnSave.classList.replace('btn-primary', 'btn-success');
                setTimeout(() => btnSave.classList.replace('btn-success', 'btn-primary'), 2000);
            } catch (error) {
                alert('Erro ao salvar Chatbot: ' + error.message);
            }
        });

        document.addEventListener('click', async (e) => {
            const eyeBtn = e.target.closest('.nm-btn-eye');
            if (eyeBtn) {
                const target = document.getElementById(eyeBtn.dataset.target);
                if (!target) return;
                target.type = target.type === 'password' ? 'text' : 'password';
                const icon = eyeBtn.querySelector('i');
                if (icon) icon.className = target.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
                return;
            }

            const btn = e.target.closest('.nm-chatbot-del');
            if (btn) {
                if (!confirm(btn.dataset.confirm || 'Remover?')) return;
                try {
                    const result = await nmPost(btn.dataset.url, {
                        action:       btn.dataset.action,
                        id:           btn.dataset.id,
                        companies_id: btn.dataset.companiesId || '',
                    });
                    if (!result.success) throw new Error(result.error || 'Erro ao remover');
                    const row = document.getElementById(btn.dataset.row);
                    if (row) row.remove();
                } catch (error) {
                    alert('Erro ao remover: ' + error.message);
                }
                return;
            }
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#nm-mc-add-btn');
            if (!btn) return;
            const template = document.getElementById('nm-mc-add-row');
            if (!template) return;
            const clone = template.cloneNode(true);
            clone.id = '';
            clone.classList.remove('nm-add-row');
            const idx = Date.now() + Math.floor(Math.random() * 1000);
            clone.querySelectorAll('input, select').forEach((el) => { if (el.id) el.id = el.id.replace(/_0$/, '_' + idx); });
            template.parentNode.insertBefore(clone, template);
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#nm-wa-add-btn');
            if (!btn) return;
            const template = document.getElementById('nm-wa-add-row');
            if (!template) return;
            const clone = template.cloneNode(true);
            clone.id = '';
            clone.classList.remove('nm-add-row');
            const idx = Date.now() + Math.floor(Math.random() * 1000);
            clone.querySelectorAll('input, select').forEach((el) => { if (el.id) el.id = el.id.replace(/_0$/, '_' + idx); });
            template.parentNode.insertBefore(clone, template);
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#nm-cu-add-btn');
            if (!btn) return;
            const tbody = document.getElementById('nm-cu-tbody');
            const template = document.getElementById('nm-cu-add-row');
            if (!tbody || !template) return;
            const clone = template.cloneNode(true);
            clone.id = '';
            clone.classList.remove('nm-add-row');
            const idx = Date.now();
            clone.querySelectorAll('input, select').forEach((el) => {
                if (el.id) el.id = el.id.replace(/_0$/, '_' + idx);
            });
            template.parentNode.insertBefore(clone, template);
        });
    }
}

// ---------------------------------------------------------------------------
// E-mail CNPJ via proxy PHP
// ---------------------------------------------------------------------------

async function nmBuscarEmailProxy(cnpj) {
    try {
        const url = (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.root_doc)
            ? CFG_GLPI.root_doc + '/plugins/newmanagement/ajax/cnpj_email.php?cnpj=' + cnpj
            : '/plugins/newmanagement/ajax/cnpj_email.php?cnpj=' + cnpj;
        const response = await fetch(url);
        if (!response.ok) return null;
        const data = await response.json();
        return data.email || null;
    } catch {
        return null;
    }
}

// ---------------------------------------------------------------------------
// Busca CNPJ
// ---------------------------------------------------------------------------

async function nmBuscarCNPJ() {
    const input = document.getElementById('cnpj');
    if (!input) return;
    const cnpj = input.value.replace(/\D/g, '');
    if (cnpj.length !== 14) { nmFeedback('cnpj-feedback', 'Digite um CNPJ completo (14 digitos).', 'error'); return; }
    if (!nmValidarCNPJ(cnpj)) { nmFeedback('cnpj-feedback', 'CNPJ inválido.', 'error'); return; }
    nmFeedback('cnpj-feedback', '', '');
    nmSetLoading('btn-buscar-cnpj', true);
    try {
        const [brasilResponse, emailProxy] = await Promise.all([
            fetch('https://brasilapi.com.br/api/cnpj/v1/' + cnpj),
            nmBuscarEmailProxy(cnpj),
        ]);
        if (!brasilResponse.ok) {
            const err = await brasilResponse.json().catch(() => ({}));
            nmFeedback('cnpj-feedback', err.message || 'CNPJ não encontrado.', 'error');
            return;
        }
        const data = await brasilResponse.json();
        const set = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };
        set('razao_social', data.razao_social);
        if (!document.getElementById('name')?.value) set('name', data.nome_fantasia || data.razao_social);
        const emailFinal = (data.email?.trim()) || emailProxy;
        if (emailFinal) set('email', emailFinal);
        if (data.ddd_telefone_1) set('phone', nmMascaraTelefone(data.ddd_telefone_1));
        if (data.cep) set('cep', nmMascaraCEP(data.cep));
        const partes = [data.logradouro, data.numero, data.complemento, data.bairro, data.municipio, data.uf].filter(Boolean);
        if (partes.length) set('address', partes.join(', '));
        nmFeedback('cnpj-feedback', '✓ Dados preenchidos com sucesso!', 'success');
    } catch {
        nmFeedback('cnpj-feedback', 'Erro ao consultar BrasilAPI.', 'error');
    } finally {
        nmSetLoading('btn-buscar-cnpj', false);
    }
}

// ---------------------------------------------------------------------------
// Busca CEP
// ---------------------------------------------------------------------------

async function nmBuscarCEP() {
    const input = document.getElementById('cep');
    if (!input) return;
    const cep = input.value.replace(/\D/g, '');
    if (cep.length !== 8) { nmFeedback('cep-feedback', 'Digite um CEP completo (8 dígitos).', 'error'); return; }
    nmFeedback('cep-feedback', '', '');
    nmSetLoading('btn-buscar-cep', true);
    try {
        const response = await fetch('https://brasilapi.com.br/api/cep/v1/' + cep);
        if (!response.ok) { nmFeedback('cep-feedback', 'CEP não encontrado.', 'error'); return; }
        const data = await response.json();
        const partes = [data.street, data.neighborhood, data.city, data.state].filter(Boolean);
        const addressInput = document.getElementById('address');
        if (addressInput && partes.length) addressInput.value = partes.join(', ');
        nmFeedback('cep-feedback', '✓ Endereço preenchido!', 'success');
    } catch {
        nmFeedback('cep-feedback', 'Erro ao consultar BrasilAPI.', 'error');
    } finally {
        nmSetLoading('btn-buscar-cep', false);
    }
}

// ---------------------------------------------------------------------------
// Expõe funções globalmente
// ---------------------------------------------------------------------------
window.nmBuscarCNPJ = nmBuscarCNPJ;
window.nmBuscarCEP  = nmBuscarCEP;

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

function nmInit() {
    const btnCnpj = document.getElementById('btn-buscar-cnpj');
    if (btnCnpj && !btnCnpj._nmBound) {
        btnCnpj._nmBound = true;
        btnCnpj.removeAttribute('onclick');
        btnCnpj.addEventListener('click', nmBuscarCNPJ);
    }

    const btnCep = document.getElementById('btn-buscar-cep');
    if (btnCep && !btnCep._nmBound) {
        btnCep._nmBound = true;
        btnCep.removeAttribute('onclick');
        btnCep.addEventListener('click', nmBuscarCEP);
    }

    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput && !cnpjInput._nmBound) {
        cnpjInput._nmBound = true;
        cnpjInput.addEventListener('input', function () { this.value = nmMascaraCNPJ(this.value); });
        cnpjInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); nmBuscarCNPJ(); } });
    }

    const cepInput = document.getElementById('cep');
    if (cepInput && !cepInput._nmBound) {
        cepInput._nmBound = true;
        cepInput.addEventListener('input', function () { this.value = nmMascaraCEP(this.value); });
        cepInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); nmBuscarCEP(); } });
    }

    const phoneInput = document.getElementById('phone');
    if (phoneInput && !phoneInput._nmBound) {
        phoneInput._nmBound = true;
        phoneInput.addEventListener('input', function () { this.value = nmMascaraTelefone(this.value); });
    }

    const passiveEvents = ['touchstart', 'touchmove', 'wheel', 'mousewheel'];
    passiveEvents.forEach(evt => {
        document.addEventListener(evt, () => {}, { passive: true, capture: false });
    });

    nmInitIpbxButtons();
    nmInitChatbotButtons();
    nmInitPagination();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', nmInit);
} else {
    nmInit();
}

document.addEventListener('glpi:ajaxformloaded', nmInit);
