# Guia de Arquitetura — Newmanagement

## Visão geral

O plugin segue a arquitetura padrão do GLPI 11: **CommonDBTM** para modelos, **TemplateRenderer + Twig** para views, e endpoints AJAX para operações assíncronas. Não há framework adicional — apenas as APIs nativas do GLPI.

---

## Estrutura de pastas

```
newmanagement/
├── setup.php               # Bootstrap: versão, init, hooks, menu
├── hook.php                # Install / uninstall / upgrade (DDL)
├── composer.json           # Autoload PSR-4
│
├── src/                    # Models (CommonDBTM)
│   ├── Company.php         # Entidade principal
│   ├── Ipbx.php            # Servidor IPBX + sub-tabelas
│   ├── IpbxExtension.php   # Ramais (sub-tabela)
│   ├── IpbxDevice.php      # Dispositivos (sub-tabela)
│   ├── IpbxNetwork.php     # Redes (sub-tabela)
│   ├── FixedLine.php       # Linhas fixas
│   ├── Chatbot.php         # Chatbot + sub-tabelas
│   └── Task.php            # Tarefas com geolocalização
│
├── templates/              # Views Twig (@newmanagement namespace)
│   ├── company/
│   ├── ipbx/
│   ├── fixedline/
│   ├── chatbot/
│   └── task/
│
├── ajax/                   # Endpoints AJAX (JSON)
│   ├── ipbx_sub.php        # CRUD IPBX, ramais, dispositivos, redes, linha fixa
│   ├── ipbx_paginate.php   # Paginação das sub-tabelas IPBX
│   └── chatbot_sub.php     # CRUD Chatbot e sub-tabelas
│
├── front/                  # Páginas GLPI (list + form)
│   ├── company.php
│   ├── ipbx.php
│   ├── chatbot.php
│   ├── fixedline.php
│   └── task.php
│
└── public/
    ├── css/newmanagement.css
    └── js/newmanagement.js
```

---

## Diagrama de componentes

```
┌─────────────────────────────────────────────────────────┐
│                        GLPI Core                        │
│  CommonDBTM  │  Session  │  TemplateRenderer  │  DBmysql │
└──────┬───────┴─────┬─────┴────────┬───────────┴────┬────┘
       │             │              │                │
┌──────▼─────────────▼──────────────▼────────────────▼────┐
│                   Newmanagement Plugin                   │
│                                                          │
│  Models (src/)          Views (templates/)               │
│  ┌──────────┐           ┌─────────────────┐             │
│  │ Company  │──defineTabs▶  company/form   │             │
│  │  Ipbx    │           │  ipbx/tab        │             │
│  │ Chatbot  │           │  chatbot/tab     │             │
│  │FixedLine │           │  fixedline/tab   │             │
│  │  Task    │           │  task/tab        │             │
│  └────┬─────┘           └─────────────────┘             │
│       │ AJAX                                             │
│  ┌────▼────────────┐                                     │
│  │ ajax/ipbx_sub   │  ← POST JSON (CSRF single-use)      │
│  │ ajax/chatbot_sub│  → resposta JSON + novo token        │
│  └─────────────────┘                                     │
└─────────────────────────────────────────────────────────┘
```

---

## Fluxo de dados — Aba IPBX

```
Usuário abre ficha de Empresa
        │
        ▼
GLPI chama Ipbx::displayTabContentForItem()
        │
        ▼
Ipbx::showTabForCompany()
  ├─ Lê registro IPBX principal (LIMIT 1)
  ├─ Senhas NÃO descriptografadas — apenas booleano has_*_password
  ├─ Lê sub-tabelas paginadas (PAGE_SIZE = 20)
  └─ TemplateRenderer::display('@newmanagement/ipbx/tab.html.twig', [...])
        │
        ▼
Template Twig renderiza formulário + tabelas
        │
        ▼ (ação do usuário)
fetch() → POST ajax/ipbx_sub.php
  ├─ Session::checkLoginUser()
  ├─ CSRF (version_compare GLPI_VERSION)
  ├─ Session::checkRight()
  ├─ switch($action) → DB::insert/update/delete
  └─ nmJson(true, ['csrf' => novo_token, ...])
        │
        ▼
JS atualiza token + DOM (sem reload)
```

---

## Banco de dados

### Tabelas principais

| Tabela | Descrição | Chave estrangeira |
|---|---|---|
| `glpi_plugin_newmanagement_companies` | Empresas | — |
| `glpi_plugin_newmanagement_ipbx` | Servidores IPBX | `companies_id` |
| `glpi_plugin_newmanagement_ipbx_extensions` | Ramais | `ipbx_id`, `companies_id` |
| `glpi_plugin_newmanagement_ipbx_devices` | Dispositivos | `ipbx_id`, `companies_id` |
| `glpi_plugin_newmanagement_ipbx_network` | Redes | `ipbx_id`, `companies_id` |
| `glpi_plugin_newmanagement_ipbx_lines` | Linhas fixas | `ipbx_id`, `companies_id` |
| `glpi_plugin_newmanagement_chatbots` | Chatbots | `companies_id` |
| `glpi_plugin_newmanagement_chatbot_users` | Usuários do chatbot | `chatbot_id` |
| `glpi_plugin_newmanagement_chatbot_mass_comm` | Comunicações em massa | `chatbot_id` |
| `glpi_plugin_newmanagement_chatbot_wa_restrictions` | Restrições WhatsApp | `chatbot_id` |
| `glpi_plugin_newmanagement_tasks` | Tarefas | `companies_id` |

### Campos comuns (padrão GLPI)

Todas as tabelas possuem: `id`, `is_deleted`, `date_creation`, `date_mod`.

### Segurança de senhas

Todos os campos de senha são armazenados com `Toolbox::sodiumEncrypt()` (libsodium). A chave de criptografia é a `GLPIKEY` configurada no `config/config_db.php` do GLPI. **Nunca** são enviados ao frontend — apenas booleanos `has_*_password`.

---

## Proteção CSRF

O plugin usa o sistema **single-use token** do GLPI 11:

1. Cada formulário/request recebe um token via `Session::getNewCSRFToken()`
2. O token é enviado no header `X-Glpi-Csrf-Token` (GLPI 11) ou no body `_glpi_csrf_token` (GLPI 10)
3. Após cada resposta AJAX, um novo token é retornado em `csrf` e o JS atualiza o campo hidden
4. Compatibilidade GLPI 10/11 via `version_compare(GLPI_VERSION, '11.0.0', '<')`

---

## Dependências externas

Nenhuma. O plugin usa exclusivamente:
- APIs nativas do GLPI (`CommonDBTM`, `Session`, `Toolbox`, `Plugin`, `TemplateRenderer`)
- Twig (já incluído no GLPI)
- Tabler Icons (já incluído no GLPI 11)
