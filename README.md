# Newmanagement — Plugin GLPI

> Sistema completo de Gestão de Documentação de Empresas para GLPI 11

## 📋 Descrição

O **Newmanagement** é um plugin para o GLPI que oferece:

- 🏢 **Gestão de Empresas** — cadastro completo (CNPJ, CEP, Razão Social, Status de Contrato)
- 📞 **Documentação de IPBX On-Premise** — servidores telefônicos com abas horizontais (Extensões, Dispositivos, Rede)
- ☁️ **Documentação de IPBX em Nuvem**
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
│   ├── install.php        # Criação das tabelas
│   ├── uninstall.php      # Remoção das tabelas
│   └── upgrade.php        # Migrações de versão
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
| `glpi_plugin_newmanagement_ipbx_extensions` | Ramais do IPBX |
| `glpi_plugin_newmanagement_ipbx_devices` | Dispositivos do IPBX |
| `glpi_plugin_newmanagement_ipbx_networks` | Redes do IPBX |
| `glpi_plugin_newmanagement_ipbx_lines` | Linhas Fixas |
| `glpi_plugin_newmanagement_ipbx_cloud` | Servidores IPBX em Nuvem |
| `glpi_plugin_newmanagement_chatbots` | Chatbots Omnichannel |
| `glpi_plugin_newmanagement_chatbot_users` | Usuários do Chatbot |
| `glpi_plugin_newmanagement_chatbot_mass_comm` | Disparos em Massa |
| `glpi_plugin_newmanagement_chatbot_wa_restrictions` | Restrições WhatsApp |
| `glpi_plugin_newmanagement_tasks` | Tarefas com Geolocalização |

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
- [x] `showForm()` implementado em Chatbot e FixedLine (padrão `Task::showForm()`)
- [x] Botão submit condicional `add`/`update` nos templates
- [ ] Suporte a traduções (gettext / `.po`)
- [ ] Geolocalização nas tarefas
- [ ] Assinatura digital
- [ ] Cálculo de quilometragem

### Detalhes Técnicos Relevantes

**CSRF no GLPI 11**
- O token é gerado no PHP via `Session::getNewCSRFToken()` e passado ao Twig como variável `csrf_token`.
- No template: `<input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token|e }}">` 
- Não existe função `csrf_token()` no Twig do GLPI — use sempre a variável.
- Validação no controller: `Session::checkCSRF($_POST)`

**Namespace Twig**
- Registrado em `setup.php` como `@newmanagement`.
- Uso nos templates: `TemplateRenderer::getInstance()->display('@newmanagement/modulo/arquivo.html.twig', [...])`

---

## 📄 Licença

MIT © João Lucas
