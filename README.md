# Newmanagement — Plugin GLPI

> Sistema completo de Gestão de Documentação de Empresas para GLPI

## 📋 Descrição

O **Newmanagement** é um plugin para o GLPI que oferece:

- 🏢 **Gestão de Empresas** — cadastro e gerenciamento completo (CNPJ, CEP, Razão Social, Status de Contrato)
- 📞 **Documentação de Servidor Telefônico On-Premise** (IPBX)
- ☁️ **Documentação de Servidor Telefônico em Nuvem** (IPBX Cloud)
- 🤖 **Documentação de Sistema Chatbot Omnichannel**
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

1. Faça o download ou clone este repositório:
   ```bash
   git clone https://github.com/JoaoLucascp/newmanagement.git
   ```
2. Mova a pasta para o diretório de plugins do GLPI:
   ```bash
   mv newmanagement /var/www/html/glpi/plugins/newmanagement
   ```
   > ⚠️ O nome da pasta **deve ser em minúsculo**: `newmanagement`

3. No GLPI, acesse: **Configuração → Plugins**
4. Localize **Newmanagement** e clique em **Instalar**
5. Depois clique em **Ativar**

---

## 📁 Estrutura do Projeto

```
newmanagement/
├── setup.php          # Inicialização e metadados do plugin
├── hook.php           # Instalação, desinstalação e hooks
├── README.md          # Esta documentação
├── front/             # Páginas de listagem e formulários
├── src/               # Classes PHP (PSR-4)
├── templates/         # Templates Twig
├── public/
│   ├── css/           # Estilos CSS
│   └── js/            # Scripts JavaScript
└── locales/           # Traduções (gettext)
```

---

## 🗄️ Tabelas Criadas no Banco de Dados

| Tabela | Descrição |
|--------|----------|
| `glpi_plugin_newmanagement_companies` | Empresas |
| `glpi_plugin_newmanagement_ipbx` | Servidores IPBX On-Premise |
| `glpi_plugin_newmanagement_ipbx_cloud` | Servidores IPBX em Nuvem |
| `glpi_plugin_newmanagement_chatbots` | Chatbots Omnichannel |
| `glpi_plugin_newmanagement_fixedlines` | Linhas Fixas |
| `glpi_plugin_newmanagement_tasks` | Tarefas com Geolocalização |

---

## 🛠️ Desenvolvimento

Este plugin está em desenvolvimento ativo. Contribuições são bem-vindas!

### Próximos passos
- [x] Criar classes PHP para cada módulo (`src/`)
- [x] Criar páginas front-end (`front/`)
- [x] Criar CSS e JS (`public/`)
- [ ] Criar templates Twig
- [ ] Adicionar suporte a traduções
- [ ] Implementar geolocalização nas tarefas
- [ ] Implementar assinatura digital
- [ ] Implementar cálculo de quilometragem

---

## 📄 Licença

MIT © João Lucas
