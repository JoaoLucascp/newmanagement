# Guia de Arquitetura — Newmanagement

## Visão geral

O plugin segue a arquitetura padrão do GLPI 11: cada entidade é uma classe PHP que estende `CommonDBTM`, os formulários são renderizados via `TemplateRenderer` (Twig), e as ações AJAX são tratadas por arquivos em `ajax/`.

## Diagrama de componentes

```
┌─────────────────────────────────────────────────────────┐
│                        GLPI Core                        │
│  CommonDBTM · DBmysql · Session · TemplateRenderer      │
└───────────────────────┬─────────────────────────────────┘
                        │ estende / usa
┌───────────────────────▼─────────────────────────────────┐
│               Plugin Newmanagement                      │
│                                                         │
│  src/                                                   │
│  ├── Company.php        ← Entidade principal            │
│  ├── Ipbx.php           ← IPBX (ramais, rede, dispos.)  │
│  ├── IpbxExtension.php  ← Ramais (aba do IPBX)         │
│  ├── IpbxDevice.php     ← Dispositivos (aba do IPBX)   │
│  ├── IpbxNetwork.php    ← Rede (aba do IPBX)           │
│  ├── Chatbot.php        ← Credenciais de chatbot        │
│  ├── Task.php           ← Tarefas da empresa            │
│  └── FixedLine.php      ← Linha fixa / DDR              │
│                                                         │
│  ajax/                                                  │
│  └── ipbx_sub.php       ← Handler AJAX para IPBX        │
│                                                         │
│  templates/             ← Templates Twig (@newmanagement)│
│  front/                 ← Entry points HTTP             │
│  public/css|js/         ← Assets estáticos              │
│  hook.php               ← Install / Uninstall / Upgrade  │
│  setup.php              ← plugin_init / plugin_version  │
└─────────────────────────────────────────────────────────┘
```

## Fluxo de dados — exibição de ficha

```
Usuário → front/company.form.php
  → Company::showForm()
    → TemplateRenderer::display('@newmanagement/company.form.html.twig')
      → HTML renderizado ao navegador
```

## Fluxo de dados — ação AJAX (IPBX)

```
Navegador (JS)
  → POST ajax/ipbx_sub.php  {action, companies_id, csrf_token, ...}
    → Session::checkLoginUser()       [autenticação]
    → version_compare(GLPI_VERSION)   [CSRF: GLPI 10 ou 11]
    → Session::checkRight(READ)       [autorização mínima]
    → switch($action)
        → Session::checkRight(CREATE/UPDATE/DELETE)
        → $DB->insert/update/delete
        → nmJson(true, ['id' => ..., 'csrf' => novo_token])
  ← JSON { success, id?, csrf, error? }
```

## Namespaces e autoloading

```json
{
  "autoload": {
    "psr-4": {
      "GlpiPlugin\\Newmanagement\\": "src/"
    }
  }
}
```

Classes em `src/` seguem o namespace `GlpiPlugin\Newmanagement\`.

## Banco de dados

Todas as tabelas seguem o prefixo `glpi_plugin_newmanagement_`.

| Tabela | Descrição |
|---|---|
| `glpi_plugin_newmanagement_companies` | Empresa principal |
| `glpi_plugin_newmanagement_ipbxs` | IPBX |
| `glpi_plugin_newmanagement_ipbx_extensions` | Ramais |
| `glpi_plugin_newmanagement_ipbx_devices` | Dispositivos |
| `glpi_plugin_newmanagement_ipbx_networks` | Rede |
| `glpi_plugin_newmanagement_ipbx_lines` | Linha fixa |
| `glpi_plugin_newmanagement_chatbots` | Chatbot |
| `glpi_plugin_newmanagement_tasks` | Tarefas |

### Colunas padrão em todas as tabelas

- `id` — INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `date_creation` — DATETIME
- `date_mod` — DATETIME
- `is_deleted` — TINYINT(1) DEFAULT 0

## Segurança

- Senhas armazenadas com `Toolbox::sodiumEncrypt()` — cifra AES-256-GCM via libsodium
- Senhas **nunca** são enviadas ao template Twig em texto puro
- CSRF: GLPI 10 usa `Session::checkCSRF()`; GLPI 11 usa `CheckCsrfListener` do Symfony
- Permissões por `Session::checkRight()` antes de cada operação
- Erros internos logados em `Toolbox::logError()` — cliente recebe mensagem genérica

## Dependências externas

Nenhuma. O plugin usa exclusivamente APIs nativas do GLPI e bibliotecas já incluídas no core (Twig, Symfony, libsodium via PHP).
