/**
 * Newmanagement - Plugin GLPI
 * Mascaras, busca CNPJ e CEP via BrasilAPI
 * Botões AJAX do formulário IPBX e Chatbot (com token CSRF do GLPI 11)
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
// CSRF Helper — GLPI 11 exige X-Glpi-Csrf-Token em todo POST
//
// IMPORTANTE: O GLPI 11 usa tokens CSRF single-use.
// Nunca armazenar o token em data-attributes ou variáveis de longa duração.
// Sempre chamar nmGetCsrfToken() NO MOMENTO do fetch para obter o token
// atual do <meta name="glpi-csrf-token">, que o GLPI atualiza a cada resposta.
// ---------------------------------------------------------------------------

function nmGetCsrfToken() {
    const meta = document.querySelector('meta[name="glpi-csrf-token"]');
    if (meta) return meta.getAttribute('content');
    const hidden = document.querySelector('input[name="_glpi_csrf_token"]');
    if (hidden) return hidden.value;
    return '';
}

// ---------------------------------------------------------------------------
// Fetch AJAX com CSRF — envia FormData e retorna JSON
//
// SOLUÇÃO A: o token CSRF é capturado AQUI, imediatamente antes do fetch,
// garantindo que seja sempre o mais fresco possível.
// Qualquer _glpi_csrf_token enviado pelo chamador é DESCARTADO — o token
// montado no objeto data pode ter envelhecido entre a montagem e a chamada,
// causando 403 no GLPI 11 (tokens single-use do CheckCsrfListener).
//
// GLPI 11 (Symfony CheckCsrfListener) valida o token de duas formas:
//   1. Header  X-Glpi-Csrf-Token  → para chamadas XHR/fetch genéricas
//   2. Body    _glpi_csrf_token   → obrigatório em endpoints ajax/*.php
//              que chamam Session::checkCSRF($_POST)
// Enviamos os dois para garantir compatibilidade com GLPI 10 e 11.
// ---------------------------------------------------------------------------

async function nmPost(url, data) {
    // Token capturado aqui — último momento antes do fetch, sempre fresco.
    // Nunca usar token vindo do chamador: pode ser stale em tokens single-use.
    const csrf = nmGetCsrfToken();
    const body = new FormData();

    // Injeta o token fresco primeiro; ignora qualquer _glpi_csrf_token do data.
    body.append('_glpi_csrf_token', csrf);

    Object.entries(data).forEach(([k, v]) => {
        if (k !== '_glpi_csrf_token') body.append(k, v);
    });

    const res = await fetch(url, {
        method: 'POST',
        // Header mantido para compatibilidade com a REST API oficial do GLPI
        headers: { 'X-Glpi-Csrf-Token': csrf },
        body,
    });

    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
}

// ---------------------------------------------------------------------------
// URL base do endpoint AJAX — IPBX
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

// ---------------------------------------------------------------------------
// Helpers de leitura/limpeza de campo por ID
// ---------------------------------------------------------------------------

function nmVal(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}

function nmClear(ids) {
    ids.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
}

// ---------------------------------------------------------------------------
// HTML do botão excluir — padrão GLPI (btn-icon, sem fundo colorido)
// Centralizado aqui para manter consistência entre PHP (renderRow) e JS (innerHTML)
// ---------------------------------------------------------------------------

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
// Botões Adicionar / Remover — IPBX
// ---------------------------------------------------------------------------

// Flag que garante registro único dos listeners delegados no document.
// Resetada a null quando o DOM é destruído (navegação entre abas do GLPI).
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

    // --- Salvar IPBX ---
    const btnSaveAll = document.getElementById('nm-save-all');
    if (btnSaveAll && !btnSaveAll._nmBound) {
        btnSaveAll._nmBound = true;
        btnSaveAll.addEventListener('click', async (event) => {
            event.preventDefault();

            const actionUrl = btnSaveAll.dataset.actionUrl || nmGetIpbxActionUrl();

            const ipbxData = {
                action:           nmVal('nm-ipbx-action')       || 'add_ipbx',
                id:               nmVal('nm-ipbx-id')           || '0',
                companies_id:     nmVal('nm-ipbx-companies-id') || nmGetIpbxCompaniesId(),
                model:            nmVal('nm-ipbx-model'),
                server_version:   nmVal('nm-ipbx-server_version'),
                ip_local:         nmVal('nm-ipbx-ip_local'),
                ip_external:      nmVal('nm-ipbx-ip_external'),
                web_port:         nmVal('nm-ipbx-web_port'),
                web_password:     nmVal('nm-web-password'),
                ssh_port:         nmVal('nm-ipbx-ssh_port'),
                ssh_password:     nmVal('nm-ssh-password'),
                comment:          nmVal('nm-ipbx-comment'),
            };

            try {
                const ipbxResult = await nmPost(actionUrl, ipbxData);
                if (ipbxResult && ipbxResult.id) {
                    nmUpdateIpbxId(ipbxResult.id);
                }

                btnSaveAll.classList.add('btn-success');
                btnSaveAll.classList.remove('btn-primary');
                setTimeout(() => {
                    btnSaveAll.classList.remove('btn-success');
                    btnSaveAll.classList.add('btn-primary');
                }, 2000);
            } catch (error) {
                alert('Erro ao salvar IPBX: ' + error.message);
            }
        });
    }

    // --- Adicionar Ramal ---
    const btnExt = document.getElementById('nm-ext-add-btn');
    if (btnExt && !btnExt._nmBound) {
        btnExt._nmBound = true;
        btnExt.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnExt)) return;
            const data = {
                action:        btnExt.dataset.action,
                ipbx_id:       btnExt.dataset.ipbxId,
                companies_id:  getCompaniesId(btnExt),
                number:        nmVal('nm-ext-number'),
                password:      nmVal('nm-ext-password'),
                device_ip:     nmVal('nm-ext-device_ip'),
                user_name:     nmVal('nm-ext-user_name'),
                records_calls: nmVal('nm-ext-records_calls') || '0',
                department:    nmVal('nm-ext-department'),
            };
            try {
                const result = await nmPost(getBtnUrl(btnExt), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');
                const addRow = document.getElementById('nm-ext-add-row');
                if (addRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-ext-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.number}</td>
                        <td>••••••</td>
                        <td>${data.device_ip}</td>
                        <td>${data.user_name}</td>
                        <td>${parseInt(data.records_calls, 10) ? 'Sim' : 'Não'}</td>
                        <td>${data.department}</td>
                        <td>${nmDelBtn('delete_extension', result.id, 'nm-ext-row-' + result.id, getCompaniesId(btnExt), getBtnUrl(btnExt), 'Remover ramal?')}</td>`;
                    // Insere ANTES da linha de adição, mantendo-a sempre por último
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-ext-number', 'nm-ext-password', 'nm-ext-device_ip', 'nm-ext-user_name', 'nm-ext-department']);
                const sel = document.getElementById('nm-ext-records_calls'); if (sel) sel.value = '0';
            } catch (error) {
                alert('Erro ao adicionar ramal: ' + error.message);
            }
        });
    }

    // --- Adicionar Dispositivo ---
    const btnDev = document.getElementById('nm-dev-add-btn');
    if (btnDev && !btnDev._nmBound) {
        btnDev._nmBound = true;
        btnDev.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnDev)) return;
            const data = {
                action:       btnDev.dataset.action,
                ipbx_id:      btnDev.dataset.ipbxId,
                companies_id: getCompaniesId(btnDev),
                device_type:  nmVal('nm-dev-device_type'),
                ip_address:   nmVal('nm-dev-ip_address'),
                login:        nmVal('nm-dev-login'),
                password:     nmVal('nm-dev-password'),
            };
            try {
                const result = await nmPost(getBtnUrl(btnDev), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');
                const addRow = document.getElementById('nm-dev-add-row');
                if (addRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-dev-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.device_type}</td>
                        <td>${data.ip_address}</td>
                        <td>${data.login}</td>
                        <td>••••••</td>
                        <td>${nmDelBtn('delete_device', result.id, 'nm-dev-row-' + result.id, getCompaniesId(btnDev), getBtnUrl(btnDev), 'Remover dispositivo?')}</td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-dev-device_type', 'nm-dev-ip_address', 'nm-dev-login', 'nm-dev-password']);
            } catch (error) {
                alert('Erro ao adicionar dispositivo: ' + error.message);
            }
        });
    }

    // --- Adicionar Rede ---
    const btnNet = document.getElementById('nm-net-add-btn');
    if (btnNet && !btnNet._nmBound) {
        btnNet._nmBound = true;
        btnNet.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnNet)) return;
            const data = {
                action:        btnNet.dataset.action,
                ipbx_id:       btnNet.dataset.ipbxId,
                companies_id:  getCompaniesId(btnNet),
                ip_network:    nmVal('nm-net-ip_network'),
                netmask:       nmVal('nm-net-netmask'),
                gateway:       nmVal('nm-net-gateway'),
                dns_primary:   nmVal('nm-net-dns_primary'),
                dns_secondary: nmVal('nm-net-dns_secondary'),
                supplier:      nmVal('nm-net-supplier'),
            };
            try {
                const result = await nmPost(getBtnUrl(btnNet), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');
                const addRow = document.getElementById('nm-net-add-row');
                if (addRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-net-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.ip_network}</td>
                        <td>${data.netmask}</td>
                        <td>${data.gateway}</td>
                        <td>${data.dns_primary}</td>
                        <td>${data.dns_secondary}</td>
                        <td>${data.supplier}</td>
                        <td>${nmDelBtn('delete_network', result.id, 'nm-net-row-' + result.id, getCompaniesId(btnNet), getBtnUrl(btnNet), 'Remover rede?')}</td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-net-ip_network', 'nm-net-netmask', 'nm-net-gateway', 'nm-net-dns_primary', 'nm-net-dns_secondary', 'nm-net-supplier']);
            } catch (error) {
                alert('Erro ao adicionar rede: ' + error.message);
            }
        });
    }

    // --- Listeners delegados no document (delete + eye + toggle) ---
    // Registrados UMA única vez via flag nmDelegatedListenersRegistered.
    // Usar o document como alvo garante que botões inseridos dinamicamente
    // também sejam capturados sem precisar re-registrar.
    if (!nmDelegatedListenersRegistered) {
        nmDelegatedListenersRegistered = true;

        // Handler: toggle recolher/expandir seções
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
            if (icon) {
                icon.className = isExpanded
                    ? 'ti ti-chevron-down'
                    : 'ti ti-chevron-up';
            }
        });

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
// Chatbot — todos os handlers via delegação no document
// Registrados UMA única vez via window._nmChatbotDelegated.
// Isso garante funcionamento mesmo quando a aba é carregada via AJAX do GLPI
// após o DOMContentLoaded (nmInit já teria rodado sem o DOM da aba presente).
// ---------------------------------------------------------------------------

function nmInitChatbotButtons() {
    if (!window._nmChatbotDelegated) {
        window._nmChatbotDelegated = true;

        // --- Salvar Chatbot (delegado) ---
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

            try {
                const result = await nmPost(url, data);
                if (!result.success) throw new Error(result.error || 'Erro ao salvar');

                const actionEl = document.getElementById('nm-chatbot-action');
                const idEl     = document.getElementById('nm-chatbot-id');
                if (actionEl) actionEl.value = 'update_chatbot';
                if (idEl && result.id) {
                    idEl.value = result.id;
                    // Propagar chatbot_id recém-salvo para os três botões add
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

        // --- Eye toggle + Delete delegados ---
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

        // --- Adicionar Comunicação em Massa (delegado) ---
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('#nm-mc-add-btn');
            if (!btn) return;
            const chatbotId = btn.dataset.chatbotId || '0';
            if (parseInt(chatbotId, 10) <= 0) {
                alert('Salve o Chatbot primeiro antes de adicionar itens.');
                return;
            }
            const data = {
                action:               'add_mass_comm',
                chatbot_id:           chatbotId,
                companies_id:         btn.dataset.companiesId || '0',
                system_name:          nmVal('nm-mc-system_name'),
                activation_date:      nmVal('nm-mc-activation_date'),
                authenticated_number: nmVal('nm-mc-authenticated_number'),
                homologation_type:    nmVal('nm-mc-homologation_type'),
                access_link:          nmVal('nm-mc-access_link'),
                login:                nmVal('nm-mc-login'),
                password:             nmVal('nm-mc-password'),
            };
            try {
                const result = await nmPost(btn.dataset.url, data);
                if (!result.success) throw new Error(result.error || 'Erro');
                const addRow = document.getElementById('nm-mc-add-row');
                if (addRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-mc-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.system_name}</td>
                        <td>${data.activation_date}</td>
                        <td>${data.authenticated_number}</td>
                        <td>${data.homologation_type}</td>
                        <td>${data.access_link ? '<a href="'+data.access_link+'" target="_blank" rel="noopener"><i class="ti ti-external-link"></i></a>' : ''}</td>
                        <td>${data.login}</td>
                        <td>••••••</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"
                            data-action="delete_mass_comm" data-id="${result.id}"
                            data-row="nm-mc-row-${result.id}"
                            data-companies-id="${data.companies_id}"
                            data-url="${btn.dataset.url}"
                            data-confirm="Remover?">
                            <i class="ti ti-trash"></i></button></td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-mc-system_name','nm-mc-activation_date','nm-mc-authenticated_number',
                         'nm-mc-homologation_type','nm-mc-access_link','nm-mc-login','nm-mc-password']);
            } catch (error) {
                alert('Erro ao adicionar comunicação em massa: ' + error.message);
            }
        });

        // --- Adicionar Restrição WA (delegado) ---
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('#nm-wa-add-btn');
            if (!btn) return;
            const chatbotId = btn.dataset.chatbotId || '0';
            if (parseInt(chatbotId, 10) <= 0) {
                alert('Salve o Chatbot primeiro antes de adicionar itens.');
                return;
            }
            const data = {
                action:           'add_wa_restriction',
                chatbot_id:       chatbotId,
                companies_id:     btn.dataset.companiesId || '0',
                whatsapp_number:  nmVal('nm-wa-whatsapp_number'),
                restriction_date: nmVal('nm-wa-restriction_date'),
                restriction_time: nmVal('nm-wa-restriction_time'),
                end_date:         nmVal('nm-wa-end_date'),
            };
            try {
                const result = await nmPost(btn.dataset.url, data);
                if (!result.success) throw new Error(result.error || 'Erro');
                const addRow = document.getElementById('nm-wa-add-row');
                if (addRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-wa-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.whatsapp_number}</td>
                        <td>${data.restriction_date}</td>
                        <td>${data.restriction_time}</td>
                        <td>${data.end_date}</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"
                            data-action="delete_wa_restriction" data-id="${result.id}"
                            data-row="nm-wa-row-${result.id}"
                            data-companies-id="${data.companies_id}"
                            data-url="${btn.dataset.url}"
                            data-confirm="Remover?">
                            <i class="ti ti-trash"></i></button></td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-wa-whatsapp_number','nm-wa-restriction_date',
                         'nm-wa-restriction_time','nm-wa-end_date']);
            } catch (error) {
                alert('Erro ao adicionar restrição: ' + error.message);
            }
        });

        // --- Adicionar Usuário Chatbot (delegado) ---
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('#nm-cu-add-btn');
            if (!btn) return;
            const chatbotId = btn.dataset.chatbotId || '0';
            if (parseInt(chatbotId, 10) <= 0) {
                alert('Salve o Chatbot primeiro antes de adicionar itens.');
                return;
            }
            const data = {
                action:       'add_chatbot_user',
                chatbot_id:   chatbotId,
                companies_id: btn.dataset.companiesId || '0',
                user_name:    nmVal('nm-cu-user_name'),
                login:        nmVal('nm-cu-login'),
                password:     nmVal('nm-cu-password'),
                email:        nmVal('nm-cu-email'),
                user_type:    nmVal('nm-cu-user_type'),
            };
            try {
                const result = await nmPost(btn.dataset.url, data);
                if (!result.success) throw new Error(result.error || 'Erro');
                const addRow = document.getElementById('nm-cu-add-row');
                if (addRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-cu-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.user_name}</td>
                        <td>${data.login}</td>
                        <td>••••••</td>
                        <td>${data.email}</td>
                        <td>${data.user_type}</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"
                            data-action="delete_chatbot_user" data-id="${result.id}"
                            data-row="nm-cu-row-${result.id}"
                            data-companies-id="${data.companies_id}"
                            data-url="${btn.dataset.url}"
                            data-confirm="Remover usuário?">
                            <i class="ti ti-trash"></i></button></td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-cu-user_name','nm-cu-login','nm-cu-password','nm-cu-email']);
                const sel = document.getElementById('nm-cu-user_type');
                if (sel) sel.value = 'usuario';
            } catch (error) {
                alert('Erro ao adicionar usuário: ' + error.message);
            }
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
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', nmInit);
} else {
    nmInit();
}

document.addEventListener('glpi:ajaxformloaded', nmInit);
