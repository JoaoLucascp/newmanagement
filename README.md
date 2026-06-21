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
│   ├── cnpj_email.php     # Consulta CNPJ via BrasilAPI
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
│   ├── fixedline/
│   ├── ipbx/
│   ├── task/
│   └── company/
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
| IPBX | ✅ | ✅ | ✅ | ✅ | ✅ |
| Chatbot | ✅ | ✅ | ✅ | ✅ | ✅ |
| FixedLine | ✅ | ✅ | ✅ | ✅ | ✅ |
| Task | ✅ | ✅ | ✅ | — | ✅ |

### Checklist Geral

- [x] Classes PHP para cada módulo (`src/`)
- [x] Controllers front-end (`front/`)
- [x] CSS e JS (`public/`)
- [x] Templates Twig criados
- [x] CSRF corrigido em todos os formulários (`Session::getNewCSRFToken()` + `{{ csrf_token|e }}`)
- [x] `showForm()` implementado em Chatbot e FixedLine
- [x] Botão submit condicional `add`/`update` nos templates
- [x] Colunas booleanas LOF/LOC/DDF/DDC/DDI/SRV nos ramais IPBX
- [x] Action `update_extension_field` no `ipbx_sub.php` (toggle inline)
- [x] Flag `document._nmToggleBoolDelegated` padronizada em `newmanagement.js` e `tab_extensions.html.twig`
- [ ] Suporte a traduções (gettext / `.po`)
- [ ] Geolocalização nas tarefas
- [ ] Assinatura digital
- [ ] Cálculo de quilometragem

### Detalhes Técnicos Relevantes

**CSRF no GLPI 11**
- O token é gerado no PHP via `Session::getNewCSRFToken()` e passado ao Twig como variável `csrf_token`.
- No template: `<input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token|e }}">`
- **Não existe** função `csrf_token()` no Twig do GLPI — use sempre a **variável**.
- No GLPI 11+, o `CheckCsrfListener` do Symfony valida o header `X-Glpi-Csrf-Token` antes do PHP executar.
- Tokens são **single-use**: cada resposta JSON de `ipbx_sub.php` retorna novo token em `csrf`.
- O JS deve atualizar o campo hidden após cada request AJAX.

**Toggle Booleano (`.nm-toggle-bool`)**
- O listener `change` é registrado **uma única vez** no `document`, via flag `document._nmToggleBoolDelegated`.
- Tanto `newmanagement.js` quanto `tab_extensions.html.twig` checam e escrevem **na mesma flag** (`document._nmToggleBoolDelegated`).
- Usar `window._nmToggleBoolDelegated` em um e `document._nmToggleBoolDelegated` no outro causava registro duplicado do listener, disparando o toggle duas vezes por clique.

**Namespace Twig**
- Registrado em `setup.php` como `@newmanagement`.
- Uso nos templates: `TemplateRenderer::getInstance()->display('@newmanagement/modulo/arquivo.html.twig', [...])`

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

- **fix(toggle-bool):** `public/js/newmanagement.js` — flag `window._nmToggleBoolDelegated` renomeada para `document._nmToggleBoolDelegated`, padronizando com `tab_extensions.html.twig`; evita registro duplicado do listener `change` que disparava o toggle duas vezes por clique — [bb08983](https://github.com/JoaoLucascp/newmanagement/commit/bb089838b6b05d7d3abb6a4a7c914fb4dc6570b4)

### 2025-06-21

- **fix(csrf):** `templates/chatbot/form.html.twig` — substituído `{{ csrf_token() }}` (função inexistente) por `{{ csrf_token|e }}` (variável) — [74ec512](https://github.com/JoaoLucascp/newmanagement/commit/74ec512c71372a36d5edb850196e33c32baf6ff4)
- **fix(csrf):** `templates/fixedline/form.html.twig` — mesma correção — [f579cb7](https://github.com/JoaoLucascp/newmanagement/commit/f579cb70ebe6cdc390d777705b2dbc6c628f744e)
- **fix(ipbx_sub):** `ajax/ipbx_sub.php` — `add_extension` incluía apenas 8 colunas no INSERT, silenciosamente descartando `lof`, `loc`, `ddf`, `ddc`, `ddi`, `srv`; adicionadas as 6 colunas faltantes — [fdac593](https://github.com/JoaoLucascp/newmanagement/commit/fdac5936aa934f36a9b28c91e7c20881ba5df10e)
- **feat(ipbx_sub):** `ajax/ipbx_sub.php` — adicionada action `update_extension_field` para toggle inline dos campos booleanos, com whitelist de segurança e verificação de ownership (`companies_id`) — [fdac593](https://github.com/JoaoLucascp/newmanagement/commit/fdac5936aa934f36a9b28c91e7c20881ba5df10e)

---

## 📄 Licença

MIT © João Lucas
