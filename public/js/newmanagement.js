/**
 * Newmanagement - Plugin GLPI
 * Mascaras, busca CNPJ e CEP via BrasilAPI
 * Botões AJAX do formulário IPBX (com token CSRF do GLPI 11)
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
// O token fica no meta tag injetado pelo GLPI ou no campo hidden do form
// ---------------------------------------------------------------------------

function nmGetCsrfToken() {
    // 1) meta tag padrão do GLPI 11
    const meta = document.querySelector('meta[name="glpi-csrf-token"]');
    if (meta) return meta.getAttribute('content');

    // 2) campo hidden dentro do form IPBX (fallback)
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

    const token = nmGetCsrfToken();

    const res = await fetch(url, {
        method: 'POST',
        headers: {
            // Header obrigatório pelo CheckCsrfListener do GLPI 11
            'X-Glpi-Csrf-Token': token,
        },
        body,
    });

    if (!res.ok) {
        throw new Error('HTTP ' + res.status);
    }

    return res.json();
}

// ---------------------------------------------------------------------------
// URL base do endpoint AJAX
// ---------------------------------------------------------------------------

function nmAjaxUrl() {
    const root = (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.root_doc)
        ? CFG_GLPI.root_doc
        : '';
    return root + '/plugins/newmanagement/ajax/ipbx_sub.php';
}

// ---------------------------------------------------------------------------
// Botões Adicionar / Remover — IPBX Company Form
// ---------------------------------------------------------------------------

function nmInitIpbxButtons() {
    const URL = nmAjaxUrl();

    // ---- Adicionar Ramal ----
    const btnRamal = document.getElementById('nm-btn-add-ramal');
    if (btnRamal) {
        btnRamal.addEventListener('click', async () => {
            const ipbxId      = document.getElementById('nm-ipbx-id')?.value || 0;
            const companiesId = document.getElementById('nm-companies-id')?.value || 0;
            const number      = document.getElementById('nm-ext-number')?.value.trim();
            const password    = document.getElementById('nm-ext-password')?.value.trim();
            const deviceIp    = document.getElementById('nm-ext-ip')?.value.trim();
            const userName    = document.getElementById('nm-ext-user')?.value.trim();
            const records     = document.getElementById('nm-ext-records')?.value || 0;
            const department  = document.getElementById('nm-ext-dept')?.value.trim();

            if (!number) { alert('Informe o número do ramal.'); return; }

            try {
                const data = await nmPost(URL, {
                    action: 'add_extension',
                    ipbx_id: ipbxId,
                    companies_id: companiesId,
                    number, password, device_ip: deviceIp,
                    user_name: userName, records_calls: records, department,
                });

                if (!data.success) throw new Error(data.error || 'Erro desconhecido');

                // Adiciona linha na tabela sem reload
                const tbody = document.getElementById('nm-ext-tbody');
                if (tbody) {
                    const tr = document.createElement('tr');
                    tr.dataset.id = data.id;
                    tr.innerHTML = `
                        <td>${number}</td>
                        <td>${password}</td>
                        <td>${deviceIp}</td>
                        <td>${userName}</td>
                        <td>${parseInt(records) ? 'Sim' : 'Não'}</td>
                        <td>${department}</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-del-ext"
                              data-id="${data.id}">Remover</button></td>`;
                    tbody.appendChild(tr);
                }

                // Limpa campos
                ['nm-ext-number','nm-ext-password','nm-ext-ip','nm-ext-user','nm-ext-dept']
                    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

            } catch (e) {
                alert('Erro ao adicionar ramal: ' + e.message);
            }
        });
    }

    // ---- Adicionar Dispositivo ----
    const btnDev = document.getElementById('nm-btn-add-device');
    if (btnDev) {
        btnDev.addEventListener('click', async () => {
            const ipbxId      = document.getElementById('nm-ipbx-id')?.value || 0;
            const companiesId = document.getElementById('nm-companies-id')?.value || 0;
            const deviceType  = document.getElementById('nm-dev-type')?.value.trim();
            const ipAddress   = document.getElementById('nm-dev-ip')?.value.trim();
            const password    = document.getElementById('nm-dev-password')?.value.trim();

            if (!deviceType) { alert('Informe o tipo do dispositivo.'); return; }

            try {
                const data = await nmPost(URL, {
                    action: 'add_device',
                    ipbx_id: ipbxId,
                    companies_id: companiesId,
                    device_type: deviceType, ip_address: ipAddress, password,
                });

                if (!data.success) throw new Error(data.error || 'Erro desconhecido');

                const tbody = document.getElementById('nm-dev-tbody');
                if (tbody) {
                    const tr = document.createElement('tr');
                    tr.dataset.id = data.id;
                    tr.innerHTML = `
                        <td>${deviceType}</td>
                        <td>${ipAddress}</td>
                        <td>${password}</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-del-dev"
                              data-id="${data.id}">Remover</button></td>`;
                    tbody.appendChild(tr);
                }

                ['nm-dev-type','nm-dev-ip','nm-dev-password']
                    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

            } catch (e) {
                alert('Erro ao adicionar dispositivo: ' + e.message);
            }
        });
    }

    // ---- Adicionar Rede ----
    const btnNet = document.getElementById('nm-btn-add-network');
    if (btnNet) {
        btnNet.addEventListener('click', async () => {
            const ipbxId      = document.getElementById('nm-ipbx-id')?.value || 0;
            const companiesId = document.getElementById('nm-companies-id')?.value || 0;
            const ipNetwork   = document.getElementById('nm-net-ip')?.value.trim();
            const netmask     = document.getElementById('nm-net-mask')?.value.trim();
            const gateway     = document.getElementById('nm-net-gw')?.value.trim();
            const dnsPrimary  = document.getElementById('nm-net-dns1')?.value.trim();
            const dnsSecondary = document.getElementById('nm-net-dns2')?.value.trim();

            if (!ipNetwork) { alert('Informe o IP da rede.'); return; }

            try {
                const data = await nmPost(URL, {
                    action: 'add_network',
                    ipbx_id: ipbxId,
                    companies_id: companiesId,
                    ip_network: ipNetwork, netmask, gateway,
                    dns_primary: dnsPrimary, dns_secondary: dnsSecondary,
                });

                if (!data.success) throw new Error(data.error || 'Erro desconhecido');

                const tbody = document.getElementById('nm-net-tbody');
                if (tbody) {
                    const tr = document.createElement('tr');
                    tr.dataset.id = data.id;
                    tr.innerHTML = `
                        <td>${ipNetwork}</td>
                        <td>${netmask}</td>
                        <td>${gateway}</td>
                        <td>${dnsPrimary}</td>
                        <td>${dnsSecondary}</td>
                        <td><button type="button" class="btn btn-sm btn-danger nm-del-net"
                              data-id="${data.id}">Remover</button></td>`;
                    tbody.appendChild(tr);
                }

                ['nm-net-ip','nm-net-mask','nm-net-gw','nm-net-dns1','nm-net-dns2']
                    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

            } catch (e) {
                alert('Erro ao adicionar rede: ' + e.message);
            }
        });
    }

    // ---- Delegação para botões Remover (criados dinamicamente) ----
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.nm-del-ext, .nm-del-dev, .nm-del-net');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        let actionName;
        if (btn.classList.contains('nm-del-ext')) actionName = 'delete_extension';
        else if (btn.classList.contains('nm-del-dev')) actionName = 'delete_device';
        else actionName = 'delete_network';

        if (!confirm('Deseja remover este item?')) return;

        try {
            const data = await nmPost(URL, { action: actionName, id });
            if (!data.success) throw new Error(data.error || 'Erro ao remover');
            btn.closest('tr').remove();
        } catch (e) {
            alert('Erro ao remover: ' + e.message);
        }
    });
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

    // Botões AJAX do IPBX
    nmInitIpbxButtons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', nmInit);
} else {
    nmInit();
}

document.addEventListener('glpi:ajaxformloaded', nmInit);
