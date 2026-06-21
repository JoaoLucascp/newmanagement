/**
 * Newmanagement – Company form
 * Máscaras CNPJ/CEP + busca BrasilAPI (CNPJ e CEP)
 *
 * Extraído de src/Company.php (fix A3 – sem JS inline)
 */
(function () {
    'use strict';

    /* ── utilitários ── */
    function soDigitos(v) { return v.replace(/\D/g, ''); }

    function setFeedback(id, msg, tipo) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.className = 'nm-feedback' + (tipo ? ' nm-feedback--' + tipo : '');
    }

    function setLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = loading;
        btn.innerHTML = loading
            ? '<span class="nm-spinner"></span> Buscando\u2026'
            : '<i class="ti ti-search"></i> Buscar';
    }

    /* fetch com timeout via AbortController */
    function fetchComTimeout(url, timeoutMs) {
        timeoutMs = timeoutMs || 8000;
        const ctrl = new AbortController();
        const timer = setTimeout(function () { ctrl.abort(); }, timeoutMs);
        return fetch(url, { signal: ctrl.signal }).finally(function () { clearTimeout(timer); });
    }

    /* helper: preenche campo pelo id */
    function set(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val || '';
    }

    /* ── máscara CNPJ ── */
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function () {
            let v = soDigitos(this.value).slice(0, 14);
            if (v.length > 12)      v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
            else if (v.length > 8)  v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})/,        '$1.$2.$3/$4');
            else if (v.length > 5)  v = v.replace(/^(\d{2})(\d{3})(\d{3})/,               '$1.$2.$3');
            else if (v.length > 2)  v = v.replace(/^(\d{2})(\d+)/,                        '$1.$2');
            this.value = v;
        });
    }

    /* ── máscara CEP ── */
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function () {
            let v = soDigitos(this.value).slice(0, 8);
            if (v.length > 5) v = v.replace(/^(\d{5})(\d+)/, '$1-$2');
            this.value = v;
        });
    }

    /* ── busca CNPJ ── */
    const btnCnpj = document.getElementById('btn-buscar-cnpj');
    if (btnCnpj) {
        btnCnpj.addEventListener('click', async function () {
            const raw = soDigitos(document.getElementById('cnpj')?.value || '');
            if (raw.length !== 14) {
                setFeedback('cnpj-feedback', 'CNPJ deve ter 14 d\u00edgitos.', 'error');
                return;
            }
            setLoading('btn-buscar-cnpj', true);
            setFeedback('cnpj-feedback', '', '');
            try {
                const res  = await fetchComTimeout('https://brasilapi.com.br/api/cnpj/v1/' + raw);
                const data = await res.json();
                if (!res.ok) {
                    setFeedback('cnpj-feedback', data.message || 'CNPJ n\u00e3o encontrado.', 'error');
                    return;
                }

                // fix: 'name' = Nome Fantasia; usa Razão Social como fallback
                // pois muitos CNPJs não possuem Nome Fantasia cadastrado na Receita.
                set('name',        data.nome_fantasia || data.razao_social);
                set('razao_social', data.razao_social);
                set('email',       data.email || '');
                set('phone',       data.ddd_telefone_1
                    ? '(' + data.ddd_telefone_1.slice(0, 2) + ') ' + data.ddd_telefone_1.slice(2)
                    : '');

                const partes = [
                    data.logradouro  ? (data.descricao_tipo_de_logradouro + ' ' + data.logradouro) : '',
                    data.numero      ? 'N\u00ba ' + data.numero : '',
                    data.complemento || '',
                    data.bairro      || '',
                    data.municipio   ? data.municipio + (data.uf ? ' - ' + data.uf : '') : '',
                ].filter(Boolean);
                set('address', partes.join(', '));

                if (data.cep) {
                    const c = soDigitos(data.cep).slice(0, 8);
                    set('cep', c.length === 8 ? c.replace(/^(\d{5})(\d{3})$/, '$1-$2') : c);
                }
                setFeedback('cnpj-feedback', '\u2714 Dados preenchidos com sucesso.', 'success');
            } catch (err) {
                const msg = err.name === 'AbortError'
                    ? 'Tempo limite excedido. Tente novamente.'
                    : 'Erro de conex\u00e3o com a BrasilAPI.';
                setFeedback('cnpj-feedback', msg, 'error');
                console.error('[NM] CNPJ fetch error:', err);
            } finally {
                setLoading('btn-buscar-cnpj', false);
            }
        });
    }

    /* ── busca CEP ── */
    const btnCep = document.getElementById('btn-buscar-cep');
    if (btnCep) {
        btnCep.addEventListener('click', async function () {
            const raw = soDigitos(document.getElementById('cep')?.value || '');
            if (raw.length !== 8) {
                setFeedback('cep-feedback', 'CEP deve ter 8 d\u00edgitos.', 'error');
                return;
            }
            setLoading('btn-buscar-cep', true);
            setFeedback('cep-feedback', '', '');
            try {
                const res  = await fetchComTimeout('https://brasilapi.com.br/api/cep/v2/' + raw);
                const data = await res.json();
                if (!res.ok) {
                    setFeedback('cep-feedback', data.message || 'CEP n\u00e3o encontrado.', 'error');
                    return;
                }
                const partes = [
                    data.street       || '',
                    data.neighborhood || '',
                    data.city         ? data.city + (data.state ? ' - ' + data.state : '') : '',
                ].filter(Boolean);
                set('address', partes.join(', '));
                setFeedback('cep-feedback', '\u2714 Endere\u00e7o preenchido.', 'success');
            } catch (err) {
                const msg = err.name === 'AbortError'
                    ? 'Tempo limite excedido. Tente novamente.'
                    : 'Erro de conex\u00e3o com a BrasilAPI.';
                setFeedback('cep-feedback', msg, 'error');
                console.error('[NM] CEP fetch error:', err);
            } finally {
                setLoading('btn-buscar-cep', false);
            }
        });
    }
})();
