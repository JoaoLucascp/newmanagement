# Guia de Instalação e Upgrade — Newmanagement

## Pré-requisitos

- GLPI 11.0.0 ou superior instalado e funcionando
- PHP 8.2+ com extensões: `sodium`, `pdo_mysql`, `mbstring`
- Acesso SSH ao servidor ou acesso FTP à pasta `plugins/`
- Usuário GLPI com perfil **Super-Admin** para instalar plugins

---

## Instalação nova

### 1. Baixar o plugin

```bash
cd /var/www/glpi/plugins
git clone https://github.com/JoaoLucascp/newmanagement.git newmanagement
```

Ou faça o download do ZIP e extraia na pasta `plugins/newmanagement/`.

### 2. Verificar a estrutura

A pasta deve conter os arquivos:
```
newmanagement/
├── setup.php
├── hook.php
├── composer.json
├── src/
├── templates/
├── ajax/
├── front/
└── public/
```

### 3. Instalar via interface GLPI

1. Acesse **Configuração → Plugins**
2. Localize **Newmanagement** na lista
3. Clique em **Instalar** (ícone de engrenagem)
4. Após instalação, clique em **Ativar**

O GLPI executará automaticamente os scripts de criação de tabelas em `hook.php`.

### 4. Configurar permissões

1. Acesse **Administração → Perfis**
2. Para cada perfil que deve usar o plugin, vá na aba **Plugins**
3. Ative os direitos desejados:
   - `plugin_newmanagement_company` — Empresas
   - `plugin_newmanagement_ipbx` — IPBX + Linha Fixa
   - `plugin_newmanagement_chatbot` — Chatbot
   - `plugin_newmanagement_task` — Tarefas

---

## Upgrade

### 1. Fazer backup

```bash
# Backup do banco
mysqldump -u root -p glpi \
  glpi_plugin_newmanagement_companies \
  glpi_plugin_newmanagement_ipbx \
  glpi_plugin_newmanagement_ipbx_extensions \
  glpi_plugin_newmanagement_ipbx_devices \
  glpi_plugin_newmanagement_ipbx_network \
  glpi_plugin_newmanagement_ipbx_lines \
  glpi_plugin_newmanagement_chatbots \
  glpi_plugin_newmanagement_tasks \
  > backup_newmanagement_$(date +%Y%m%d).sql
```

### 2. Atualizar o código

```bash
cd /var/www/glpi/plugins/newmanagement
git pull origin main
```

### 3. Executar o upgrade no GLPI

1. Acesse **Configuração → Plugins**
2. Clique em **Atualizar** ao lado do Newmanagement (se o botão aparecer)
3. O GLPI executará os scripts de upgrade em `hook.php`

---

## Desinstalação

1. Acesse **Configuração → Plugins**
2. Clique em **Desinstalar** ao lado do Newmanagement
3. O GLPI executará `plugin_newmanagement_uninstall()` que remove todas as tabelas
4. Delete a pasta `plugins/newmanagement/` do servidor

> ⚠️ A desinstalação remove **todos os dados** do plugin. Faça backup antes.

---

## Solução de problemas

| Problema | Causa provável | Solução |
|---|---|---|
| Plugin não aparece na lista | Pasta com nome errado | Pasta deve se chamar exatamente `newmanagement` |
| Erro ao instalar | Versão do GLPI incompatível | Verifique `PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION` em `setup.php` |
| Templates não carregam | Namespace Twig não registrado | Verifique se `plugin_init_newmanagement()` rodou sem erros |
| Senhas não salvam | Extensão sodium ausente | Instale `php-sodium` e reinicie o PHP-FPM |
