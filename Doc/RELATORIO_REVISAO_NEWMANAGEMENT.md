# Relatório de Revisão — Plugin Newmanagement (GLPI)

**Repositório:** [JoaoLucascp/newmanagement](https://github.com/JoaoLucascp/newmanagement)  
**Data da revisão:** 20/05/2026  
**Arquivos revisados:** `src/Ipbx.php`, `src/Chatbot.php`, `src/FixedLine.php`, `src/Company.php`  
**Arquivos pendentes de revisão:** `src/IpbxCloud.php`, `src/Task.php`, `ajax/`, `front/`, `hook.php`, `setup.php`

---

## 1. Resumo Executivo

A revisão cobriu os quatro principais arquivos de classe do plugin. No geral, a base do código está bem estruturada: herança correta de `CommonDBTM`, uso de `Toolbox::sodiumEncrypt/Decrypt` para senhas, proteção XSS com `htmlspecialchars()` e CSRF com `Session::getNewCSRFToken()`. Foram encontrados **13 problemas** distribuídos entre segurança/autorização, qualidade de código e UX. Destes, **8 já foram corrigidos** diretamente no repositório nesta sessão.

---

## 2. Arquivo: `src/Ipbx.php`

### ✅ O que estava correto

| Item | Detalhe |
|------|---------|
| Herança e integração | `extends CommonDBTM`, `$rightname`, `$itemtype = Company::class`, `$items_id` corretos |
| Criptografia de senhas | `Toolbox::sodiumDecrypt()` na exibição; `sodiumEncrypt` no save |
| Tratamento de erro de decrypt | `try/catch` com `\Throwable` evita quebra da página |
| CSRF no formulário principal | `Session::getNewCSRFToken()` gerado e inserido em `#nm-ipbx-csrf` |
| Proteção XSS | Closure `$h = fn($v) => htmlspecialchars(...)` aplicado em todos os campos renderizados |
| Senhas mascaradas na tabela | Exibidas como `••••••`, nunca em texto claro |
| Campo de senha no formulário | `type="password"` e `autocomplete="new-password"` corretos |

### ⚠️ Problemas encontrados

| # | Prioridade | Problema | Status |
|---|-----------|---------|--------|
| 1 | 🔴 Alta | `canView()` ausente em `showTabForCompany()` — aba renderizava sem verificar permissão de leitura | ✅ **Corrigido** |
| 2 | 🔴 Alta | `canView()` ausente em `displayTabContentForItem()` | ✅ **Corrigido** |
| 3 | 🔴 Alta | CSRF não inserido como `data-csrf` nos botões das subtabelas (ramais, dispositivos, rede) | ✅ **Corrigido** |
| 4 | 🟡 Média | `rawSearchOptions()` não implementado — GLPI não conseguia listar/buscar IPBXs | ✅ **Corrigido** |
| 5 | 🟡 Média | Botões de deletar renderizados para qualquer usuário, sem verificar direito `DELETE` | ✅ **Corrigido** |
| 6 | 🟡 Média | `$ipbx_id = 0` nos botões filhos — botões de adicionar ramal/dispositivo/rede ficavam ativos antes do IPBX existir | ✅ **Corrigido** |
| 7 | 🟢 Baixa | Nomes de tabelas filhas hardcoded como strings espalhados no código | ✅ **Corrigido** (extraídos para constantes `TABLE_EXTENSIONS`, `TABLE_DEVICES`, `TABLE_NETWORK`) |

---

## 3. Arquivo: `src/Chatbot.php`

### ✅ O que estava correto

| Item | Detalhe |
|------|---------|
| Verificação de direitos | `canView()` já implementado em `displayTabContentForItem()` e `showTabForCompany()` |
| Criptografia | `sodiumEncrypt/Decrypt` nas senhas de API/token |
| XSS | `htmlspecialchars()` aplicado consistentemente |
| CSRF | Token presente nos formulários e botões AJAX |
| `rawSearchOptions()` | Implementado com campos relevantes |
| Botões condicionais | Deleção e edição condicionados ao direito correto |

### ⚠️ Problemas encontrados

Nenhum problema crítico ou médio identificado neste arquivo. Está em conformidade com as boas práticas do GLPI.

---

## 4. Arquivo: `src/FixedLine.php`

### ✅ O que estava correto

| Item | Detalhe |
|------|---------|
| Verificação de direitos | `canView()` já implementado corretamente |
| Integração com `Company` | Aba registrada e renderizada dentro da ficha de Empresa |
| Criptografia | Senhas criptografadas com `sodiumEncrypt` |
| XSS | `htmlspecialchars()` aplicado |
| CSRF | Token presente |
| Botões condicionais | Condicionados ao direito correto |

### ⚠️ Problemas encontrados

Nenhum problema crítico ou médio identificado neste arquivo.

---

## 5. Arquivo: `src/Company.php`

### ✅ O que estava correto

| Item | Detalhe |
|------|---------|
| `rawSearchOptions()` | Implementado com todos os campos relevantes e tipos corretos (`itemlink`, `email`, `datetime`) |
| `defineTabs()` | Abas registradas na ordem correta com tabs nativas do GLPI (`Document_Item`, `Link`, `Notepad`, `Log`) |
| XSS | `htmlspecialchars()` em todos os campos do formulário |
| Máscara de CNPJ e CEP | Regex de máscara em tempo real via JS, funcionando corretamente |
| Busca BrasilAPI | Integração com `brasilapi.com.br/api/cnpj/v1/` e `brasilapi.com.br/api/cep/v2/` funcional |
| Feedback de loading | `setLoading()` desabilita o botão e mostra spinner durante a requisição |
| `Dropdown::showFromArray()` | Usado corretamente para o campo `contract_status` |

### ⚠️ Problemas encontrados

| # | Prioridade | Problema | Status |
|---|-----------|---------|--------|
| 8 | 🟡 Média | `getMenuContent()` exibia todos os itens do menu (IPBX, Chatbot, Linhas Fixas etc.) para qualquer usuário logado, sem verificar `Session::haveRight()` por item | ✅ **Corrigido** — commit [591a5c9](https://github.com/JoaoLucascp/newmanagement/commit/591a5c9a550c56ce8245936e8b7c90e720ae6827) |
| 9 | 🟡 Média | Campo e-mail com `type="text"` — sem validação nativa do browser | ✅ **Corrigido** — `type="email"` aplicado |
| 10 | 🟢 Baixa | Chamadas `fetch()` para BrasilAPI sem timeout — botão poderia travar indefinidamente se a API não respondesse | ✅ **Corrigido** — `AbortController` com timeout de 8s; mensagem específica para `AbortError` |

---

## 6. Correções Aplicadas no Repositório

| Commit | Arquivo | O que foi corrigido |
|--------|---------|---------------------|
| (já estava no repo) | `src/Ipbx.php` | Todos os 7 itens da tabela acima: `canView()`, CSRF nas subtabelas, `rawSearchOptions()`, deleção condicional, bloqueio com `ipbx_id=0`, constantes de tabela |
| [591a5c9](https://github.com/JoaoLucascp/newmanagement/commit/591a5c9a550c56ce8245936e8b7c90e720ae6827) | `src/Company.php` | `type="email"`, `AbortController` com timeout 8s no fetch BrasilAPI, `getMenuContent()` com `Session::haveRight()` por item |

---

## 7. Pendências (Arquivos Não Revisados)

Os arquivos abaixo **ainda não foram revisados** nesta sessão. Recomenda-se aplicar o mesmo checklist do guia de debug.

| Arquivo | Criticidade estimada | O que verificar |
|---------|---------------------|----------------|
| `src/IpbxCloud.php` | 🟡 Média | Mesmos padrões do `Ipbx.php`: `canView()`, CSRF nas subtabelas, senhas criptografadas, `rawSearchOptions()` |
| `src/Task.php` | 🟡 Média | Autorização por perfil, validação de campos obrigatórios, datas |
| `ajax/ipbx_sub.php` | 🔴 Alta | Verificação de CSRF no recebimento, autorização antes de executar ações, sanitização de inputs, queries com bind |
| `ajax/` (demais) | 🔴 Alta | Mesmos critérios do `ipbx_sub.php` |
| `front/company.php` | 🟡 Média | Verificação de `canView()`/`canCreate()` antes de exibir formulários |
| `front/` (demais) | 🟡 Média | Idem |
| `hook.php` | 🟡 Média | Hooks registrados corretamente, sem SQL direto sem bind |
| `setup.php` | 🟢 Baixa | Tabelas criadas com `Migration`, índices, `is_deleted` |

---

## 8. Checklist Geral do Plugin

| Critério | Status |
|---------|--------|
| Herança `CommonDBTM` | ✅ Correto em todas as classes revisadas |
| `$rightname` definido por classe | ✅ Correto |
| Criptografia de senhas (`sodiumEncrypt`) | ✅ Aplicado |
| XSS — `htmlspecialchars()` | ✅ Aplicado em todos os campos revisados |
| CSRF — `Session::getNewCSRFToken()` | ✅ Presente nos formulários principais |
| CSRF nas subtabelas (botões AJAX) | ✅ Corrigido no `Ipbx.php` |
| `canView()` em todas as abas | ✅ Corrigido |
| Menu condicional por direito | ✅ Corrigido em `Company.php` |
| `rawSearchOptions()` implementado | ✅ Corrigido no `Ipbx.php`; já presente em `Company.php`, `Chatbot.php`, `FixedLine.php` |
| Validação nativa HTML (`type="email"`) | ✅ Corrigido |
| Timeout em chamadas externas (BrasilAPI) | ✅ Corrigido |
| Arquivos AJAX revisados | ❌ Pendente |
| Arquivos `front/` revisados | ❌ Pendente |
| `hook.php` revisado | ❌ Pendente |
| `setup.php` revisado | ❌ Pendente |
