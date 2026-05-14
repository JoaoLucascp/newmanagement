/**
 * Newmanagement - Plugin GLPI
 * Mascaras, busca CNPJ e CEP via BrasilAPI + ReceitaWS (e-mail)
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
// Validador do digito verificador do CNPJ
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
// Busca e-mail na ReceitaWS (API secundaria, sem autenticacao, gratuita)
// Limite: 3 consultas/minuto no plano gratuito.
// Documentacao: https://www.receitaws.com.br/
// ---------------------------------------------------------------------------

async function nmBuscarEmailReceitaWS(cnpj) {
    try {
        const response = await fetch('https://receitaws.com.br/v1/cnpj/' + cnpj);
        if (!response.ok) return null;
        const data = await response.json();
        return (data.email && data.email.trim() !== '') ? data.email.trim() : null;
    } catch {
        return null;
    }
}

// ---------------------------------------------------------------------------
// Busca CNPJ via BrasilAPI (principal) + ReceitaWS para e-mail (secundaria)
// ---------------------------------------------------------------------------

async function nmBuscarCNPJ() {
    const input = document.getElementById('cnpj');
    if (!input) return;

    const cnpj = input.value.replace(/\D/g, '');

    if (cnpj.length !== 14) {
        nmFeedback('cnpj-feedback', 'Digite um CNPJ completo (14 digitos).', 'error');
        return;
    }

    if (!nmValidarCNPJ(cnpj)) {
        nmFeedback('cnpj-feedback', 'CNPJ invalido. Verifique os digitos digitados.', 'error');
        return;
    }

    nmFeedback('cnpj-feedback', '', '');
    nmSetLoading('btn-buscar-cnpj', true);

    try {
        // --- API principal: BrasilAPI (dados cadastrais) ---
        // --- API secundaria: ReceitaWS (e-mail) ---
        // Executa as duas em paralelo para nao aumentar o tempo de resposta
        const [brasilResponse, emailReceitaWS] = await Promise.all([
            fetch('https://brasilapi.com.br/api/cnpj/v1/' + cnpj),
            nmBuscarEmailReceitaWS(cnpj),
        ]);

        if (!brasilResponse.ok) {
            const err = await brasilResponse.json().catch(() => ({}));
            const msg = err.message || 'CNPJ nao encontrado na Receita Federal.';
            nmFeedback('cnpj-feedback', msg, 'error');
            return;
        }

        const data = await brasilResponse.json();

        // Razao Social
        const razaoSocialInput = document.getElementById('razao_social');
        if (razaoSocialInput && data.razao_social) {
            razaoSocialInput.value = data.razao_social;
        }

        // Nome (apenas se estiver vazio)
        const nomeInput = document.getElementById('name');
        if (nomeInput && !nomeInput.value) {
            const nome = data.nome_fantasia || data.razao_social || '';
            if (nome) nomeInput.value = nome;
        }

        // E-mail: BrasilAPI primeiro, ReceitaWS como fallback
        const emailInput = document.getElementById('email');
        if (emailInput) {
            const emailFinal = (data.email && data.email.trim() !== '')
                ? data.email.trim()
                : emailReceitaWS;
            if (emailFinal) emailInput.value = emailFinal;
        }

        // Telefone
        const phoneInput = document.getElementById('phone');
        if (phoneInput && data.ddd_telefone_1) {
            phoneInput.value = nmMascaraTelefone(data.ddd_telefone_1);
        }

        // CEP
        if (data.cep) {
            const cepInput = document.getElementById('cep');
            if (cepInput) cepInput.value = nmMascaraCEP(data.cep);
        }

        // Endereco
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
// Expoe em window como fallback
// ---------------------------------------------------------------------------
window.nmBuscarCNPJ = nmBuscarCNPJ;
window.nmBuscarCEP  = nmBuscarCEP;

// ---------------------------------------------------------------------------
// Registra listeners quando o DOM estiver pronto
// ---------------------------------------------------------------------------

function nmInitFormListeners() {
    const btnCnpj = document.getElementById('btn-buscar-cnpj');
    if (btnCnpj) {
        btnCnpj.removeAttribute('onclick');
        btnCnpj.addEventListener('click', nmBuscarCNPJ);
    }

    const btnCep = document.getElementById('btn-buscar-cep');
    if (btnCep) {
        btnCep.removeAttribute('onclick');
        btnCep.addEventListener('click', nmBuscarCEP);
    }

    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function () {
            this.value = nmMascaraCNPJ(this.value);
        });
        cnpjInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); nmBuscarCNPJ(); }
        });
    }

    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function () {
            this.value = nmMascaraCEP(this.value);
        });
        cepInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); nmBuscarCEP(); }
        });
    }

    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            this.value = nmMascaraTelefone(this.value);
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', nmInitFormListeners);
} else {
    nmInitFormListeners();
}

document.addEventListener('glpi:ajaxformloaded', nmInitFormListeners);
