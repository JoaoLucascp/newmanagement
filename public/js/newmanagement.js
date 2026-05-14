/**
 * Newmanagement - Plugin GLPI
 * Mascaras, busca CNPJ e CEP via BrasilAPI
 *
 * IMPORTANTE: O GLPI 11 carrega este arquivo em escopo isolado (modulo).
 * Funcoes nao ficam em window automaticamente via onclick="".
 * Solucao: registrar via addEventListener nos botoes E expor em window como fallback.
 */

console.log('Newmanagement Plugin carregado.');

// ---------------------------------------------------------------------------
// Mascaras
// ---------------------------------------------------------------------------

function nmMascaraCNPJ(valor) {
    return valor
        .replace(/\D/g, '')
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{2}\.(\d{3}))(\d)/, '$1.$2')
        .replace(/(\.\d{3})(\d)/, '$1/$2')
        .replace(/(\/\d{4})(\d)/, '$1-$2')
        .slice(0, 18);
}

function nmMascaraCEP(valor) {
    return valor
        .replace(/\D/g, '')
        .replace(/(\d{5})(\d)/, '$1-$2')
        .slice(0, 9);
}

function nmMascaraTelefone(valor) {
    const v = valor.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        return v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    }
    return v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
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
// Busca CNPJ via BrasilAPI
// ---------------------------------------------------------------------------

async function nmBuscarCNPJ() {
    const input = document.getElementById('cnpj');
    if (!input) return;

    const cnpj = input.value.replace(/\D/g, '');

    if (cnpj.length !== 14) {
        nmFeedback('cnpj-feedback', 'Digite um CNPJ completo (14 digitos).', 'error');
        return;
    }

    nmFeedback('cnpj-feedback', '', '');
    nmSetLoading('btn-buscar-cnpj', true);

    try {
        const response = await fetch('https://brasilapi.com.br/api/cnpj/v1/' + cnpj);

        if (!response.ok) {
            nmFeedback('cnpj-feedback', 'CNPJ nao encontrado ou invalido.', 'error');
            return;
        }

        const data = await response.json();

        const razaoSocialInput = document.getElementById('razao_social');
        if (razaoSocialInput && data.razao_social) {
            razaoSocialInput.value = data.razao_social;
        }

        const nomeInput = document.getElementById('name');
        if (nomeInput && !nomeInput.value && data.nome_fantasia) {
            nomeInput.value = data.nome_fantasia;
        }

        const emailInput = document.getElementById('email');
        if (emailInput && data.email) {
            emailInput.value = data.email;
        }

        const phoneInput = document.getElementById('phone');
        if (phoneInput && data.ddd_telefone_1) {
            phoneInput.value = nmMascaraTelefone(data.ddd_telefone_1);
        }

        if (data.cep) {
            const cepInput = document.getElementById('cep');
            if (cepInput) cepInput.value = nmMascaraCEP(data.cep);
        }

        const partes = [
            data.logradouro,
            data.numero,
            data.complemento,
            data.bairro,
            data.municipio,
            data.uf,
        ].filter(Boolean);

        const addressInput = document.getElementById('address');
        if (addressInput && partes.length > 0) {
            addressInput.value = partes.join(', ');
        }

        nmFeedback('cnpj-feedback', '\u2713 Dados preenchidos com sucesso!', 'success');

    } catch (err) {
        nmFeedback('cnpj-feedback', 'Erro ao consultar BrasilAPI. Verifique sua conexao.', 'error');
    } finally {
        nmSetLoading('btn-buscar-cnpj', false);
    }
}

// ---------------------------------------------------------------------------
// Busca CEP via BrasilAPI
// ---------------------------------------------------------------------------

async function nmBuscarCEP() {
    const input = document.getElementById('cep');
    if (!input) return;

    const cep = input.value.replace(/\D/g, '');

    if (cep.length !== 8) {
        nmFeedback('cep-feedback', 'Digite um CEP completo (8 digitos).', 'error');
        return;
    }

    nmFeedback('cep-feedback', '', '');
    nmSetLoading('btn-buscar-cep', true);

    try {
        const response = await fetch('https://brasilapi.com.br/api/cep/v1/' + cep);

        if (!response.ok) {
            nmFeedback('cep-feedback', 'CEP nao encontrado.', 'error');
            return;
        }

        const data = await response.json();

        const partes = [
            data.street,
            data.neighborhood,
            data.city,
            data.state,
        ].filter(Boolean);

        const addressInput = document.getElementById('address');
        if (addressInput && partes.length > 0) {
            addressInput.value = partes.join(', ');
        }

        nmFeedback('cep-feedback', '\u2713 Endereco preenchido!', 'success');

    } catch (err) {
        nmFeedback('cep-feedback', 'Erro ao consultar BrasilAPI. Verifique sua conexao.', 'error');
    } finally {
        nmSetLoading('btn-buscar-cep', false);
    }
}

// ---------------------------------------------------------------------------
// Expoe as funcoes em window (fallback para onclick inline em paginas legadas)
// ---------------------------------------------------------------------------
window.nmBuscarCNPJ = nmBuscarCNPJ;
window.nmBuscarCEP  = nmBuscarCEP;

// ---------------------------------------------------------------------------
// Registra event listeners assim que o DOM estiver pronto
// Estrategia principal: nao depende de onclick inline no HTML
// ---------------------------------------------------------------------------

function nmInitFormListeners() {
    // --- Botao buscar CNPJ ---
    const btnCnpj = document.getElementById('btn-buscar-cnpj');
    if (btnCnpj) {
        // Remove onclick legado se existir e usa addEventListener
        btnCnpj.removeAttribute('onclick');
        btnCnpj.addEventListener('click', nmBuscarCNPJ);
    }

    // --- Botao buscar CEP ---
    const btnCep = document.getElementById('btn-buscar-cep');
    if (btnCep) {
        btnCep.removeAttribute('onclick');
        btnCep.addEventListener('click', nmBuscarCEP);
    }

    // --- Mascara CNPJ ---
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function () {
            this.value = nmMascaraCNPJ(this.value);
        });
        cnpjInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); nmBuscarCNPJ(); }
        });
    }

    // --- Mascara CEP ---
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function () {
            this.value = nmMascaraCEP(this.value);
        });
        cepInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); nmBuscarCEP(); }
        });
    }

    // --- Mascara Telefone ---
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            this.value = nmMascaraTelefone(this.value);
        });
    }
}

// Aguarda DOM pronto (DOMContentLoaded ou imediato se ja carregou)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', nmInitFormListeners);
} else {
    // DOM ja esta pronto (script carregado defer/async)
    nmInitFormListeners();
}

// Seguranca extra: o GLPI as vezes renderiza formularios via AJAX apos o load.
// Escuta o evento personalizado do GLPI para re-inicializar os listeners.
document.addEventListener('glpi:ajaxformloaded', nmInitFormListeners);
