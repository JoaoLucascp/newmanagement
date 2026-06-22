# Plugin Newmanagement — Documentação de Auditoria Inicial

> ⚠️ **Documento histórico** — Auditoria realizada em maio/2026 sobre o commit `b3a9f9a` (v1.0.0).
> Reflete o estado do plugin **antes** das correções de junho/2026.
> Para o estado atual, consulte `CHANGELOG.md` e `AUDIT_REPORT.md`.

**Repositório:** [JoaoLucascp/newmanagement](https://github.com/JoaoLucascp/newmanagement)  
**Data da auditoria:** 20 de maio de 2026  
**Versão auditada:** `main` (commit `b3a9f9a`)  
**Pontuação geral:** 7/10 — Aprovado para produção após correções

---

## Visão Geral do Plugin

O **Newmanagement** é um plugin para o GLPI que gerencia dados técnicos e operacionais de empresas clientes. Suas principais funcionalidades incluem:

- Cadastro de empresas com consulta automática de CNPJ (BrasilAPI) e CEP
- Gerenciamento de IPBX On-Premise e Cloud
- Controle de ramais, dispositivos e redes
- Cadastro de linhas fixas (SIP/ISDN)
- Gerenciamento de chatbots e usuários associados
- Tarefas com geolocalização (latitude/longitude)

O plugin é desenvolvido em PHP 8.x para GLPI 11, utiliza Twig para templates, e segue as convenções de desenvolvimento oficial do GLPI.

---

## Resultado da Auditoria Inicial

A auditoria identificou **2 problemas críticos** e **4 problemas altos**, além de diversos pontos positivos.

### Pontos Positivos (já estava correto)

- `htmlspecialchars()` aplicado em todos os outputs antes da migração para Twig
- CSRF verificado em todas as ações POST via `Session::checkCSRF($_POST)`
- `Session::checkRight()` granular por ação (READ / UPDATE / CREATE / DELETE)
- JavaScript moderno com `async/await` e `AbortController` com timeout
- Integração BrasilAPI (CNPJ + CEP) bem implementada
- `date_creation`, `date_mod` e `is_deleted` nas entidades principais
- Charset/collation usando helpers nativos do GLPI (`DBConnection::getDefaultCharset()`)

### Problemas Identificados

| ID | Severidade | Descrição | Status Final |
|----|------------|-----------|--------------|
| C1 | 🔴 Crítico | Senhas possivelmente em texto plano no banco | ✅ Confirmado correto |
| C2 | 🔴 Crítico | Endpoints AJAX sem verificação de sessão/CSRF | ✅ Confirmado correto |
| A1 | ⚠️ Alto | HTML gerado via `echo` em vez de Twig templates | ✅ Corrigido (PRs #5–#8) |
| A2 | ⚠️ Alto | Tabelas filhas sem coluna `is_deleted` | ✅ Corrigido via migration |
| A3 | ⚠️ Alto | ~100 linhas de JavaScript inline em `Company.php` | ✅ Confirmado em `public/js/` |
| A4 | ⚠️ Alto | Campo `name` sem índice no banco | ✅ Confirmado correto |

---

## Verificação C1 — Criptografia de Senhas

### Diagnóstico

O comentário `COMMENT 'sodiumEncrypt'` nas colunas do banco indicava intenção de criptografia, mas era necessário confirmar se o PHP de fato chamava `Toolbox::sodiumEncrypt()` antes de salvar.

### Resultado da Verificação

**Ambos os arquivos ajax implementam corretamente a criptografia** via função helper centralizada:

```php
function nmEncryptPassword(string $value): ?string {
    return $value !== '' ? \Toolbox::sodiumEncrypt($value) : null;
}
```

A função retorna `null` para campos vazios, evitando que blobs criptografados inúteis sejam gravados no banco.

**Conclusão:** C1 encerrado como **Confirmado correto**. Nenhuma alteração necessária.

---

## Verificação C2 — Segurança dos Endpoints AJAX

### Diagnóstico

Endpoints AJAX precisam verificar: (1) usuário autenticado, (2) token CSRF válido, (3) direito mínimo de leitura, e (4) direito específico por ação.

### Resultado da Verificação

Ambos os arquivos ajax implementam proteção em 4 camadas no topo do arquivo:

```php
Session::checkLoginUser();          // Camada 1: usuário logado
Session::checkCSRF($_POST);         // Camada 2: token CSRF válido (GLPI 11)
Session::checkRight($right, READ);  // Camada 3: direito mínimo de leitura
// Dentro de cada case:
Session::checkRight($right, CREATE | UPDATE | DELETE); // Camada 4: direito por ação
```

**Conclusão:** C2 encerrado como **Confirmado correto**. Nenhuma alteração necessária.

---

## Correção A1 — Migração de `echo` HTML para Twig

### Pull Requests Executados

| PR | Arquivo | Branch | Linhas antes | Linhas depois | Redução |
|----|---------|--------|:------------:|:-------------:|:-------:|
| [#5](https://github.com/JoaoLucascp/newmanagement/pull/5) | `Company.php` | `fix/a1-twig-company` | ~180 | ~70 | −61% |
| [#6](https://github.com/JoaoLucascp/newmanagement/pull/6) | `Ipbx.php` | `fix/a1-twig-ipbx` | ~280 | ~100 | −64% |
| [#7](https://github.com/JoaoLucascp/newmanagement/pull/7) | `Chatbot.php` | `fix/a1-twig-chatbot` | ~370 | ~110 | −70% |
| [#8](https://github.com/JoaoLucascp/newmanagement/pull/8) | `FixedLine.php` | `fix/a1-twig-fixedline` | ~100 | ~65 | −35% |

---

## Checklist de Segurança Final

| Verificação | Status |
|-------------|--------|
| Usuário autenticado verificado em todos os endpoints | ✅ |
| Token CSRF validado em todas as ações POST | ✅ |
| Direito mínimo READ verificado em todos os endpoints | ✅ |
| Direito granular (CREATE/UPDATE/DELETE) por ação | ✅ |
| Senhas criptografadas com `sodiumEncrypt` antes de salvar | ✅ |
| Senhas de update só alteradas quando campo preenchido | ✅ |
| Escape de output via Twig (automático) | ✅ |
| Nenhuma senha em texto plano no banco | ✅ |
| Stack trace não vazado para o cliente (apenas logado) | ✅ |

---

## Histórico de Commits e PRs

| PR | Título | Merge |
|----|--------|-------|
| [#5](https://github.com/JoaoLucascp/newmanagement/pull/5) | `fix(A1): migra Company.php de echo HTML para template Twig` | ✅ Mergeado |
| [#6](https://github.com/JoaoLucascp/newmanagement/pull/6) | `fix(A1): migra Ipbx.php de echo HTML para template Twig` | ✅ Mergeado |
| [#7](https://github.com/JoaoLucascp/newmanagement/pull/7) | `fix(A1): migra Chatbot.php de echo HTML para template Twig` | ✅ Mergeado |
| [#8](https://github.com/JoaoLucascp/newmanagement/pull/8) | `fix(A1): migra FixedLine.php de echo HTML para template Twig` | ✅ Mergeado |

---

## Conclusão

O plugin Newmanagement **está aprovado para uso em produção**. Todos os problemas identificados na auditoria foram resolvidos ou confirmados como já corretos.
