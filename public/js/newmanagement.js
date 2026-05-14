/**
 * Newmanagement - Plugin GLPI
 * JavaScript: máscaras, busca de CNPJ e CEP via BrasilAPI
 */

// ---------------------------------------------------------------------------
// Máscaras
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
    el.className = 'nm-feedback nm-feedback--' + tipo;
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
        nmFeedback('cnpj-feedback', 'Digite um CNPJ completo (14 dígitos).', 'error');
        return;
    }

    nmFeedback('cnpj-feedback', '', '');
    nmSetLoading('btn-buscar-cnpj', true);

    try {
        const response = await fetch('https://brasilapi.com.br/api/cnpj/v1/' + cnpj);

        if (!response.ok) {
            nmFeedback('cnpj-feedback', 'CNPJ não encontrado ou inválido.', 'error');
            return;
        }

        const data = await response.json();

        // Razão Social
        const razaoSocialInput = document.getElementById('razao_social');
        if (razaoSocialInput && data.razao_social) {
            razaoSocialInput.value = data.razao_social;
        }

        // Nome fantasia (preenche o campo Nome se estiver vazio)
        const nomeInput = document.getElementById('name');
        if (nomeInput && !nomeInput.value && data.nome_fantasia) {
            nomeInput.value = data.nome_fantasia;
        }

        // E-mail
        const emailInput = document.getElementById('email');
        if (emailInput && data.email) {
            emailInput.value = data.email;
        }

        // Telefone
        const phoneInput = document.getElementById('phone');
        if (phoneInput && data.ddd_telefone_1) {
            phoneInput.value = nmMascaraTelefone(data.ddd_telefone_1);
        }

        // CEP e Endereço
        if (data.cep) {
            const cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.value = nmMascaraCEP(data.cep);
            }
        }

        // Monta endereço completo
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

        nmFeedback('cnpj-feedback', '✓ Dados preenchidos com sucesso!', 'success');

    } catch (err) {
        nmFeedback('cnpj-feedback', 'Erro ao consultar BrasilAPI. Verifique sua conexão.', 'error');
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
        nmFeedback('cep-feedback', 'Digite um CEP completo (8 dígitos).', 'error');
        return;
    }

    nmFeedback('cep-feedback', '', '');
    nmSetLoading('btn-buscar-cep', true);

    try {
        const response = await fetch('https://brasilapi.com.br/api/cep/v1/' + cep);

        if (!response.ok) {
            nmFeedback('cep-feedback', 'CEP não encontrado.', 'error');
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

        nmFeedback('cep-feedback', '✓ Endereço preenchido!', 'success');

    } catch (err) {
        nmFeedback('cep-feedback', 'Erro ao consultar BrasilAPI. Verifique sua conexão.', 'error');
    } finally {
        nmSetLoading('btn-buscar-cep', false);
    }
}

// ---------------------------------------------------------------------------
// Aplica máscaras nos campos ao digitar
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function () {
            this.value = nmMascaraCNPJ(this.value);
        });
        cnpjInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                nmBuscarCNPJ();
            }
        });
    }

    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function () {
            this.value = nmMascaraCEP(this.value);
        });
        cepInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                nmBuscarCEP();
            }
        });
    }

    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            this.value = nmMascaraTelefone(this.value);
        });
    }
});
