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
// ---------------------------------------------------------------------------

async function nmPost(url, data) {
    const body = new FormData();
    Object.entries(data).forEach(([k, v]) => body.append(k, v));

    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-Glpi-Csrf-Token': nmGetCsrfToken() },
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
    const hiddenLines = document.querySelector('#nm-lines-form input[name="ipbx_id"]');
    if (hiddenLines) hiddenLines.value = newId;
    const hiddenAction = document.querySelector('#nm-ipbx-form input[name="action"]');
    if (hiddenAction) hiddenAction.value = 'update_ipbx';
    const hiddenId = document.querySelector('#nm-ipbx-form input[name="id"]');
    if (hiddenId) hiddenId.value = newId;
}

// ---------------------------------------------------------------------------
// Helpers de leitura de campo por ID
// ---------------------------------------------------------------------------

function nmVal(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}

function nmClear(ids) {
    ids.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
}

// ---------------------------------------------------------------------------
// Botões Adicionar / Remover — IPBX
// ---------------------------------------------------------------------------

function nmInitIpbxButtons() {
    const URL = nmGetIpbxActionUrl();

    function getBtnUrl(btn) { return btn?.dataset.url || URL; }
    function getCompaniesId(btn) { return btn?.dataset.companiesId || nmGetIpbxCompaniesId(); }
    function checkIpbxSaved(btn) {
        const id = parseInt(btn.dataset.ipbxId || '0', 10);
        if (id <= 0) {
            alert('Salve o Servidor IPBX primeiro antes de adicionar sub-itens.');
            return false;
        }
        return true;
    }

    const btnSaveAll = document.getElementById('nm-save-all');
    if (btnSaveAll) {
        btnSaveAll.addEventListener('click', async (event) => {
            event.preventDefault();
            const ipbxForm  = document.getElementById('nm-ipbx-form');
            const linesForm = document.getElementById('nm-lines-form');
            if (!ipbxForm || !linesForm) return;

            try {
                const ipbxResponse = await fetch(nmGetIpbxActionUrl(), {
                    method: 'POST',
                    headers: { 'X-Glpi-Csrf-Token': nmGetCsrfToken() },
                    body: new FormData(ipbxForm),
                });
                const ipbxData = await ipbxResponse.json();
                if (ipbxData && ipbxData.id) {
                    nmUpdateIpbxId(ipbxData.id);
                    const linesIpbx = linesForm.querySelector('input[name="ipbx_id"]');
                    if (linesIpbx) linesIpbx.value = ipbxData.id;
                }

                const lineResponse = await fetch(nmGetIpbxActionUrl(), {
                    method: 'POST',
                    headers: { 'X-Glpi-Csrf-Token': nmGetCsrfToken() },
                    body: new FormData(linesForm),
                });
                const lineData = await lineResponse.json();
                if (lineData && lineData.id) {
                    const actionInput = linesForm.querySelector('input[name="action"]');
                    if (actionInput) actionInput.value = 'update_line';
                    const idInput = linesForm.querySelector('input[name="id"]');
                    if (idInput) idInput.value = lineData.id;
                }

                btnSaveAll.classList.add('btn-success');
                btnSaveAll.classList.remove('btn-primary');
                setTimeout(() => {
                    btnSaveAll.classList.remove('btn-success');
                    btnSaveAll.classList.add('btn-primary');
                }, 2000);
            } catch (error) {
                alert('Erro ao salvar IPBX ou Linha Fixa: ' + error.message);
            }
        });
    }

    const btnExt = document.getElementById('nm-ext-add-btn');
    if (btnExt) {
        btnExt.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnExt)) return;
            const data = {
                action: btnExt.dataset.action,
                ipbx_id: btnExt.dataset.ipbxId,
                companies_id: getCompaniesId(btnExt),
                number:       nmVal('nm-ext-number'),
                password:     nmVal('nm-ext-password'),
                device_ip:    nmVal('nm-ext-device_ip'),
                user_name:    nmVal('nm-ext-user_name'),
                records_calls:nmVal('nm-ext-records_calls') || '0',
                department:   nmVal('nm-ext-department'),
            };
            try {
                const result = await nmPost(getBtnUrl(btnExt), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');
                const tbody = document.getElementById('nm-ext-tbody');
                if (tbody) {
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
                        <td><button type="button" class="btn btn-sm btn-danger nm-del-btn"
                              data-action="delete_extension" data-id="${result.id}"
                              data-row="nm-ext-row-${result.id}" data-companies-id="${getCompaniesId(btnExt)}"
                              data-url="${getBtnUrl(btnExt)}"
                              data-confirm="Remover ramal?">
                              <i class="ti ti-trash"></i></button></td>`;
                    tbody.appendChild(tr);
                }
                nmClear(['nm-ext-number','nm-ext-password','nm-ext-device_ip','nm-ext-user_name','nm-ext-department']);
                const sel = document.getElementById('nm-ext-records_calls'); if (sel) sel.value = '0';
            } catch (error) {
                alert('Erro ao adicionar ramal: ' + error.message);
            }
        });
    }

    const btnDev = document.getElementById('nm-dev-add-btn');
    if (btnDev) {
        btnDev.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnDev)) return;
            const data = {
                action: btnDev.dataset.action,
                ipbx_id: btnDev.dataset.ipbxId,
                companies_id: getCompaniesId(btnDev),
                device_type: nmVal('nm-dev-device_type'),
                ip_address:  nmVal('nm-dev-ip_address'),
                password:    nmVal('nm-dev-password'),
            };
            try {
                const result = await nmPost(getBtnUrl(btnDev), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');
                const tbody = document.getElementById('nm-dev-tbody');
                if (tbody) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-dev-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.device_type}</td>
                        <td>${data.ip_address}</td>
                        <td>••••••</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-del-btn"
                              data-action="delete_device" data-id="${result.id}"
                              data-row="nm-dev-row-${result.id}" data-companies-id="${getCompaniesId(btnDev)}"
                              data-url="${getBtnUrl(btnDev)}"
                              data-confirm="Remover dispositivo?">
                              <i class="ti ti-trash"></i></button></td>`;
                    tbody.appendChild(tr);
                }
                nmClear(['nm-dev-device_type','nm-dev-ip_address','nm-dev-password']);
            } catch (error) {
                alert('Erro ao adicionar dispositivo: ' + error.message);
            }
        });
    }

    const btnNet = document.getElementById('nm-net-add-btn');
    if (btnNet) {
        btnNet.addEventListener('click', async () => {
            if (!checkIpbxSaved(btnNet)) return;
            const data = {
                action: btnNet.dataset.action,
                ipbx_id: btnNet.dataset.ipbxId,
                companies_id: getCompaniesId(btnNet),
                ip_network:    nmVal('nm-net-ip_network'),
                netmask:       nmVal('nm-net-netmask'),
                gateway:       nmVal('nm-net-gateway'),
                dns_primary:   nmVal('nm-net-dns_primary'),
                dns_secondary: nmVal('nm-net-dns_secondary'),
            };
            try {
                const result = await nmPost(getBtnUrl(btnNet), data);
                if (!result.success) throw new Error(result.error || 'Erro desconhecido');
                const tbody = document.getElementById('nm-net-tbody');
                if (tbody) {
                    const tr = document.createElement('tr');
                    tr.id = 'nm-net-row-' + result.id;
                    tr.className = 'tab_bg_1';
                    tr.innerHTML = `
                        <td>${data.ip_network}</td>
                        <td>${data.netmask}</td>
                        <td>${data.gateway}</td>
                        <td>${data.dns_primary}</td>
                        <td>${data.dns_secondary}</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-del-btn"
                              data-action="delete_network" data-id="${result.id}"
                              data-row="nm-net-row-${result.id}" data-companies-id="${getCompaniesId(btnNet)}"
                              data-url="${getBtnUrl(btnNet)}"
                              data-confirm="Remover rede?">
                              <i class="ti ti-trash"></i></button></td>`;
                    tbody.appendChild(tr);
                }
                nmClear(['nm-net-ip_network','nm-net-netmask','nm-net-gateway','nm-net-dns_primary','nm-net-dns_secondary']);
            } catch (error) {
                alert('Erro ao adicionar rede: ' + error.message);
            }
        });
    }

    // Delegação: delete e eye para botões IPBX
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
                action, id, companies_id: getCompaniesId(btn),
            });
            if (!result.success) throw new Error(result.error || 'Erro ao remover');
            const row = document.getElementById(btn.dataset.row);
            if (row) row.remove();
        } catch (error) {
            alert('Erro ao remover: ' + error.message);
        }
    });
}

// ---------------------------------------------------------------------------
// Chatbot — salvar formulário principal via fetch()
// ---------------------------------------------------------------------------

function nmInitChatbotButtons() {
    // Salvar dados principais do chatbot
    const btnSave = document.getElementById('nm-chatbot-save');
    if (btnSave) {
        btnSave.addEventListener('click', async () => {
            const url  = btnSave.dataset.actionUrl;
            const csrf = document.getElementById('nm-chatbot-csrf')?.value   || nmGetCsrfToken();
            const data = {
                _glpi_csrf_token:         csrf,
                action:                   document.getElementById('nm-chatbot-action')?.value || 'add_chatbot',
                id:                       document.getElementById('nm-chatbot-id')?.value || '0',
                companies_id:             document.getElementById('nm-chatbot-companies-id')?.value || '0',
                model:                    nmVal('nm-chatbot-model'),
                chatbot_registration_id:  nmVal('nm-chatbot-registration_id'),
                activation_date:          nmVal('nm-chatbot-activation_date'),
                whatsapp_number:          nmVal('nm-chatbot-whatsapp'),
                access_link:              nmVal('nm-chatbot-access_link'),
                plan:                     nmVal('nm-chatbot-plan'),
                users_count:              nmVal('nm-chatbot-users_count'),
                supervisors_count:        nmVal('nm-chatbot-supervisors_count'),
                admins_count:             nmVal('nm-chatbot-admins_count'),
                social_networks:          nmVal('nm-chatbot-social_networks'),
                admin_login:              nmVal('nm-chatbot-admin_login'),
                admin_password:           nmVal('nm-chatbot-admin_password'),
                superadmin_login:         nmVal('nm-chatbot-superadmin_login'),
                superadmin_password:      nmVal('nm-chatbot-superadmin_password'),
                manager_name:             nmVal('nm-chatbot-manager_name'),
                manager_contact:          nmVal('nm-chatbot-manager_contact'),
                manager_email:            nmVal('nm-chatbot-manager_email'),
                comment:                  nmVal('nm-chatbot-comment'),
            };

            try {
                const result = await nmPost(url, data);
                if (!result.success) throw new Error(result.error || 'Erro ao salvar');
                // Atualiza action e id para update nas próximas saves
                const actionEl = document.getElementById('nm-chatbot-action');
                const idEl     = document.getElementById('nm-chatbot-id');
                if (actionEl) actionEl.value = 'update_chatbot';
                if (idEl && result.id) idEl.value = result.id;
                btnSave.classList.replace('btn-primary', 'btn-success');
                setTimeout(() => btnSave.classList.replace('btn-success', 'btn-primary'), 2000);
            } catch (error) {
                alert('Erro ao salvar Chatbot: ' + error.message);
            }
        });
    }

    // Delegação de clique para todos os botões AJAX do Chatbot (add + delete)
    document.addEventListener('click', async (e) => {
        // eye-toggle para campos de senha no Chatbot
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
                    _glpi_csrf_token: btn.dataset.csrf || nmGetCsrfToken(),
                    action:           btn.dataset.action,
                    id:               btn.dataset.id,
                    companies_id:     btn.dataset.companiesId || '',
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

    // Adicionar Comunicação em Massa
    const btnMcAdd = document.getElementById('nm-mc-add-btn');
    if (btnMcAdd) {
        btnMcAdd.addEventListener('click', async () => {
            const data = {
                _glpi_csrf_token:     btnMcAdd.dataset.csrf || nmGetCsrfToken(),
                action:               'add_mass_comm',
                chatbot_id:           btnMcAdd.dataset.chatbotId || '0',
                companies_id:         btnMcAdd.dataset.companiesId || '0',
                system_name:          nmVal('nm-mc-system_name'),
                activation_date:      nmVal('nm-mc-activation_date'),
                authenticated_number: nmVal('nm-mc-authenticated_number'),
                homologation_type:    nmVal('nm-mc-homologation_type'),
                access_link:          nmVal('nm-mc-access_link'),
                login:                nmVal('nm-mc-login'),
                manager:              nmVal('nm-mc-manager'),
            };
            try {
                const result = await nmPost(btnMcAdd.dataset.url, data);
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
                        <td>${data.manager}</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"
                            data-action="delete_mass_comm" data-id="${result.id}"
                            data-row="nm-mc-row-${result.id}"
                            data-companies-id="${data.companies_id}"
                            data-url="${btnMcAdd.dataset.url}"
                            data-csrf="${data._glpi_csrf_token}"
                            data-confirm="Remover?">
                            <i class="ti ti-trash"></i></button></td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-mc-system_name','nm-mc-activation_date','nm-mc-authenticated_number','nm-mc-homologation_type','nm-mc-access_link','nm-mc-login','nm-mc-manager']);
            } catch (error) {
                alert('Erro ao adicionar comunicação em massa: ' + error.message);
            }
        });
    }

    // Adicionar Número Restrito pela Meta
    const btnWaAdd = document.getElementById('nm-wa-add-btn');
    if (btnWaAdd) {
        btnWaAdd.addEventListener('click', async () => {
            const data = {
                _glpi_csrf_token: btnWaAdd.dataset.csrf || nmGetCsrfToken(),
                action:           'add_wa_restriction',
                chatbot_id:       btnWaAdd.dataset.chatbotId || '0',
                companies_id:     btnWaAdd.dataset.companiesId || '0',
                whatsapp_number:  nmVal('nm-wa-whatsapp_number'),
                restriction_date: nmVal('nm-wa-restriction_date'),
                restriction_time: nmVal('nm-wa-restriction_time'),
            };
            try {
                const result = await nmPost(btnWaAdd.dataset.url, data);
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
                        <td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"
                            data-action="delete_wa_restriction" data-id="${result.id}"
                            data-row="nm-wa-row-${result.id}"
                            data-companies-id="${data.companies_id}"
                            data-url="${btnWaAdd.dataset.url}"
                            data-csrf="${data._glpi_csrf_token}"
                            data-confirm="Remover?">
                            <i class="ti ti-trash"></i></button></td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-wa-whatsapp_number','nm-wa-restriction_date','nm-wa-restriction_time']);
            } catch (error) {
                alert('Erro ao adicionar restrição: ' + error.message);
            }
        });
    }

    // Adicionar Usuário do Chatbot
    const btnCuAdd = document.getElementById('nm-cu-add-btn');
    if (btnCuAdd) {
        btnCuAdd.addEventListener('click', async () => {
            const data = {
                _glpi_csrf_token: btnCuAdd.dataset.csrf || nmGetCsrfToken(),
                action:           'add_chatbot_user',
                chatbot_id:       btnCuAdd.dataset.chatbotId || '0',
                companies_id:     btnCuAdd.dataset.companiesId || '0',
                user_name:        nmVal('nm-cu-user_name'),
                login:            nmVal('nm-cu-login'),
                password:         nmVal('nm-cu-password'),
                email:            nmVal('nm-cu-email'),
                user_type:        nmVal('nm-cu-user_type'),
            };
            try {
                const result = await nmPost(btnCuAdd.dataset.url, data);
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
                            data-url="${btnCuAdd.dataset.url}"
                            data-csrf="${data._glpi_csrf_token}"
                            data-confirm="Remover usuário?">
                            <i class="ti ti-trash"></i></button></td>`;
                    addRow.parentNode.insertBefore(tr, addRow);
                }
                nmClear(['nm-cu-user_name','nm-cu-login','nm-cu-password','nm-cu-email']);
                const sel = document.getElementById('nm-cu-user_type'); if (sel) sel.value = 'usuario';
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
// FIX: scroll/touch listeners registrados com { passive: true } para
// eliminar os [Violation] "Added non-passive event listener to a
// scroll-blocking event" do console do Chrome.
// ---------------------------------------------------------------------------

function nmInit() {
    // Listeners de formulário de empresa
    const btnCnpj = document.getElementById('btn-buscar-cnpj');
    if (btnCnpj) { btnCnpj.removeAttribute('onclick'); btnCnpj.addEventListener('click', nmBuscarCNPJ); }

    const btnCep = document.getElementById('btn-buscar-cep');
    if (btnCep) { btnCep.removeAttribute('onclick'); btnCep.addEventListener('click', nmBuscarCEP); }

    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function () { this.value = nmMascaraCNPJ(this.value); });
        cnpjInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); nmBuscarCNPJ(); } });
    }

    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function () { this.value = nmMascaraCEP(this.value); });
        cepInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); nmBuscarCEP(); } });
    }

    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () { this.value = nmMascaraTelefone(this.value); });
    }

    // FIX: registra listeners de scroll com passive:true
    // O Chrome avisa quando touchstart/touchmove/wheel são registrados sem
    // { passive: true } em elementos que afetam o scroll principal.
    // Como o plugin não usa esses eventos, garantimos que qualquer binding
    // futuro sobre document/window use a flag correta.
    const passiveEvents = ['touchstart', 'touchmove', 'wheel', 'mousewheel'];
    passiveEvents.forEach(evt => {
        // Intercepta apenas se ainda não houver listener (prevenção defensiva)
        document.addEventListener(evt, () => {}, { passive: true, capture: false });
    });

    // Botões AJAX do IPBX
    nmInitIpbxButtons();

    // Botões AJAX do Chatbot
    nmInitChatbotButtons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', nmInit);
} else {
    nmInit();
}

document.addEventListener('glpi:ajaxformloaded', nmInit);
