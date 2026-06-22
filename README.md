# Newmanagement — Plugin GLPI

> Sistema completo de Gestão de Documentação de Empresas para GLPI 11

## 📋 Descrição

O **Newmanagement** é um plugin para o GLPI que oferece:

- 🏢 **Gestão de Empresas** — cadastro completo (CNPJ, CEP, Razão Social, Status de Contrato)
- 📞 **Documentação de IPBX On-Premise** — servidores telefônicos com abas horizontais (Extensões, Dispositivos, Rede, Linha Fixa)
- 🤖 **Documentação de Chatbot Omnichannel** — com sub-tabelas de usuários, disparos em massa e restrições WhatsApp
- 📟 **Documentação de Linha Fixa**
- ✅ **Gestão de Tarefas** com geolocalização, assinatura digital e cálculo de quilometragem

---

## ⚙️ Requisitos

| Item | Versão Mínima |
|------|---------------|
| GLPI | 11.0.0        |
| PHP  | 8.1           |

---

## 🚀 Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/JoaoLucascp/newmanagement.git
   ```
2. Mova para o diretório de plugins do GLPI:
   ```bash
   mv newmanagement /var/www/html/glpi/plugins/newmanagement
   ```
   > ⚠️ O nome da pasta **deve ser exatamente** `newmanagement` (minúsculo)

3. No GLPI: **Configuração → Plugins → Newmanagement → Instalar → Ativar**

---

## 📁 Estrutura do Projeto

```
newmanagement/
├── setup.php              # Inicialização, metadados e registro Twig
├── hook.php               # Orquestra install / uninstall / upgrade
├── hook/
│   ├── install.php        # Criação das tabelas (idempotente)
│   ├── uninstall.php      # Remoção das tabelas
│   └── upgrade.php        # Migrações de versão
├── ajax/
│   ├── ipbx_sub.php       # Handler AJAX/POST do IPBX (ramais, dispositivos, rede, linhas)
│   ├── ipbx_paginate.php  # Paginação AJAX das abas IPBX
│   ├── chatbot_sub.php    # Handler AJAX do Chatbot
│   └── task_action.php    # Ações de tarefas
├── front/                 # Controllers (listagem + formulários)
│   ├── company.php
│   ├── ipbx.php
│   ├── chatbot.php
│   ├── fixedline.php
│   ├── task.php
│   └── config.php
├── src/                   # Classes PHP (PSR-4)
│   ├── Company.php
│   ├── Ipbx.php
│   ├── IpbxExtension.php
│   ├── IpbxDevice.php
│   ├── IpbxNetwork.php
│   ├── Chatbot.php
│   ├── FixedLine.php
│   └── Task.php
├── templates/             # Templates Twig (@newmanagement/...)
│   ├── chatbot/
│   │   ├── form.html.twig
│   │   ├── list.html.twig
│   │   ├── tab.html.twig
│   │   └── partials/
│   │       ├── mass_comm.html.twig
│   │       ├── users.html.twig
│   │       └── wa_restrictions.html.twig
│   ├── company/
│   │   ├── form.html.twig
│   │   └── list.html.twig
│   ├── fixedline/
│   │   ├── form.html.twig
│   │   ├── list.html.twig
│   │   └── tab.html.twig
│   ├── ipbx/
│   │   ├── list.html.twig
│   │   ├── tab.html.twig
│   │   └── partials/
│   │       ├── extensions.html.twig
│   │       ├── devices.html.twig
│   │       └── network.html.twig
│   └── task/
│       ├── form.html.twig
│       ├── list.html.twig
│       └── tab.html.twig
├── public/
│   ├── css/newmanagement.css
│   └── js/
│       ├── newmanagement.js
│       └── company-form.js
└── locales/               # Traduções (gettext)
```

---

## 🗄️ Tabelas no Banco de Dados

| Tabela | Descrição |
|--------|-----------|
| `glpi_plugin_newmanagement_companies` | Empresas |
| `glpi_plugin_newmanagement_ipbx` | Servidores IPBX On-Premise |
| `glpi_plugin_newmanagement_ipbx_extensions` | Ramais do IPBX ¹ |
| `glpi_plugin_newmanagement_ipbx_devices` | Dispositivos do IPBX |
| `glpi_plugin_newmanagement_ipbx_network` | Redes do IPBX |
| `glpi_plugin_newmanagement_ipbx_lines` | Linhas Fixas |
| `glpi_plugin_newmanagement_chatbots` | Chatbots Omnichannel |
| `glpi_plugin_newmanagement_chatbot_users` | Usuários do Chatbot |
| `glpi_plugin_newmanagement_chatbot_mass_comm` | Disparos em Massa |
| `glpi_plugin_newmanagement_chatbot_wa_restrictions` | Restrições WhatsApp |
| `glpi_plugin_newmanagement_tasks` | Tarefas com Geolocalização |

¹ **Colunas booleanas de ramais** (`tinyint 0/1`):
`lof` (Liga p/ fora), `loc` (Liga p/ ramais), `ddf` (Desvia chamada de fora),
`ddc` (Desvia de celular), `ddi` (Permite DDI), `srv` (Acessa serviço IPBX)

---

## 🛠️ Desenvolvimento

### Status dos Módulos

| Módulo | Classe PHP | Front | Template `form` | Template `tab` | CSRF |
|--------|-----------|-------|-----------------|----------------|------|
| Company | ✅ | ✅ | ✅ | — | ✅ |
| IPBX | ✅ | ✅ | — (removido) | ✅ | ✅ |
| Chatbot | ✅ | ✅ | ✅ | ✅ | ✅ |
| FixedLine | ✅ | ✅ | ✅ | ✅ | ✅ |
| Task | ✅ | ✅ | ✅ | ✅ | ✅ |

### Checklist Geral

- [x] Classes PHP para cada módulo (`src/`)
- [x] Controllers front-end (`front/`)
- [x] CSS e JS (`public/`)
- [x] Templates Twig criados
- [x] CSRF corrigido em **todos** os templates (`Session::getNewCSRFToken()` + `{{ csrf|e }}` ou `{{ csrf_token|e }}`)
- [x] Filtro `|e` / `|e('html_attr')` aplicado consistentemente em todos os tokens CSRF
- [x] Templates órfãos removidos (`ipbx/form`, `ipbx/tab_devices`, `ipbx/tab_extensions`, `ipbx/tab_network`)
- [x] `ajax/cnpj_email.php` removido — JS consulta BrasilAPI diretamente
- [x] `showForm()` implementado em Chatbot e FixedLine
- [x] Botão submit condicional `add`/`update` nos templates
- [x] Colunas booleanas LOF/LOC/DDF/DDC/DDI/SRV nos ramais IPBX
- [x] Action `update_extension_field` no `ipbx_sub.php` (toggle inline)
- [x] Flag `document._nmToggleBoolDelegated` padronizada em `newmanagement.js` e `partials/extensions.html.twig`
- [ ] Suporte a traduções (gettext / `.po`)
- [ ] Geolocalização nas tarefas
- [ ] Assinatura digital
- [ ] Cálculo de quilometragem

### Detalhes Técnicos Relevantes

**CSRF no GLPI 11**
- O token é gerado no PHP via `Session::getNewCSRFToken()` e passado ao Twig como variável.
- Nos `form.html.twig`: `<input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token|e }}">`
- Nos `tab.html.twig` e partials: `value="{{ csrf|e }}"` ou `data-csrf="{{ csrf|e('html_attr') }}"`
- **Não existe** função `csrf_token()` no Twig do GLPI — use sempre a **variável**.
- No GLPI 11+, o `CheckCsrfListener` do Symfony valida o header `X-Glpi-Csrf-Token` antes do PHP executar.
- Tokens são **single-use**: cada resposta JSON de `ipbx_sub.php` retorna novo token em `csrf`.
- O JS deve atualizar o campo hidden após cada request AJAX.

**Toggle Booleano (`.nm-toggle-bool`)**
- O listener `change` é registrado **uma única vez** no `document`, via flag `document._nmToggleBoolDelegated`.
- Tanto `newmanagement.js` quanto `partials/extensions.html.twig` checam e escrevem **na mesma flag**.
- Usar `window._nmToggleBoolDelegated` em um e `document._nmToggleBoolDelegated` no outro causava registro duplicado do listener, disparando o toggle duas vezes por clique.

**Namespace Twig**
- Registrado em `setup.php` como `@newmanagement`.
- Uso nos templates: `TemplateRenderer::getInstance()->display('@newmanagement/modulo/arquivo.html.twig', [...])`

**Estrutura de templates do IPBX**
- O fluxo é **100% por abas** — não existe formulário standalone para IPBX.
- `ipbx/tab.html.twig` inclui os partials via `{% include '@newmanagement/ipbx/partials/...' %}`.
- Os antigos `tab_devices`, `tab_extensions` e `tab_network` foram removidos (substituídos pelos `partials/`).

**Handler AJAX `ajax/ipbx_sub.php`**

Actions disponíveis (parâmetro POST `action`):

| Action | Direito | Descrição |
|--------|---------|----------|
| `add_ipbx` | CREATE | Cria registro IPBX principal |
| `update_ipbx` | UPDATE | Atualiza registro IPBX principal |
| `add_extension` | CREATE | Adiciona ramal (inclui 6 colunas booleanas) |
| `delete_extension` | DELETE | Remove ramal |
| `update_extension_field` | UPDATE | Atualiza campo booleano individual do ramal (toggle inline) |
| `import_extensions` | CREATE | Importação em lote de ramais (JSON) |
| `add_device` | CREATE | Adiciona dispositivo |
| `delete_device` | DELETE | Remove dispositivo |
| `add_network` | CREATE | Adiciona registro de rede |
| `delete_network` | DELETE | Remove registro de rede |
| `add_line` | CREATE | Adiciona linha fixa |
| `update_line` | UPDATE | Atualiza linha fixa |
| `delete_line` | DELETE | Remove linha fixa |

Senhas são criptografadas via `GLPIKey::encrypt()` (GLPI 11) com fallback para `Toolbox::sodiumEncrypt()` (GLPI 10).

---

## 📝 Changelog

### 2026-06-21

**Correções de CSRF**
- **fix(csrf):** `templates/chatbot/form.html.twig` — substituído `{{ csrf_token() }}` por `{{ csrf_token|e }}` — [74ec512](https://github.com/JoaoLucascp/newmanagement/commit/74ec512c71372a36d5edb850196e33c32baf6ff4)
- **fix(csrf):** `templates/fixedline/form.html.twig` — mesma correção — [f579cb7](https://github.com/JoaoLucascp/newmanagement/commit/f579cb70ebe6cdc390d777705b2dbc6c628f744e)
- **fix(csrf):** `templates/chatbot/tab.html.twig` — adicionado filtro `|e` em `{{ csrf }}` — [c35d9ff](https://github.com/JoaoLucascp/newmanagement/commit/c35d9ffeead49c49fb74fe9aaf34153b046f3455)
- **fix(csrf):** `templates/ipbx/form.html.twig` — substituído `{{ csrf_token() }}` por `{{ csrf_token|e }}` (arquivo depois removido) — [e98fca5](https://github.com/JoaoLucascp/newmanagement/commit/e98fca5efbfa8ef3963bd2a0927f43067b59aab9)

**Limpeza de arquivos órfãos**
- **chore:** `templates/ipbx/form.html.twig` removido — fluxo IPBX é 100% por abas, template nunca era chamado — [a437f18](https://github.com/JoaoLucascp/newmanagement/commit/a437f183a399662646d49e0755d97e1ebb0a6d9a)
- **chore:** `templates/ipbx/tab_devices.html.twig` removido — substituído por `partials/devices.html.twig` — [aa23000](https://github.com/JoaoLucascp/newmanagement/commit/aa230006b4649a7e08380ac78a82f410364f4348)
- **chore:** `templates/ipbx/tab_extensions.html.twig` removido — substituído por `partials/extensions.html.twig` — [67078be](https://github.com/JoaoLucascp/newmanagement/commit/67078be13043c6143c3a4bda8bd6d7799b16b736)
- **chore:** `templates/ipbx/tab_network.html.twig` removido — substituído por `partials/network.html.twig` — [a3f1a8d](https://github.com/JoaoLucascp/newmanagement/commit/a3f1a8df04c8b0059420f0769f3fe1aab621aa7d)
- **chore:** `ajax/cnpj_email.php` removido — JS consome BrasilAPI diretamente, arquivo nunca era chamado — [749ad3c](https://github.com/JoaoLucascp/newmanagement/commit/749ad3cf3d688809b968a2037cfe1ea64b6a928f)

**Outras correções**
- **fix(toggle-bool):** `public/js/newmanagement.js` — flag `window._nmToggleBoolDelegated` renomeada para `document._nmToggleBoolDelegated`, padronizando com `partials/extensions.html.twig`; evita registro duplicado do listener `change` — [bb08983](https://github.com/JoaoLucascp/newmanagement/commit/bb089838b6b05d7d3abb6a4a7c914fb4dc6570b4)
- **fix(ipbx_sub):** `ajax/ipbx_sub.php` — `add_extension` incluía apenas 8 colunas no INSERT, descartando `lof`, `loc`, `ddf`, `ddc`, `ddi`, `srv`; colunas adicionadas — [fdac593](https://github.com/JoaoLucascp/newmanagement/commit/fdac5936aa934f36a9b28c91e7c20881ba5df10e)
- **feat(ipbx_sub):** `ajax/ipbx_sub.php` — adicionada action `update_extension_field` para toggle inline com whitelist de segurança — [fdac593](https://github.com/JoaoLucascp/newmanagement/commit/fdac5936aa934f36a9b28c91e7c20881ba5df10e)

---

## 📄 Licença

MIT © João Lucas
