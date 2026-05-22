# Guia de Instalação e Upgrade — Newmanagement

## Pré-requisitos

- GLPI 11.0.0 – 11.x instalado e funcionando
- PHP 8.1+
- Permissão de escrita na pasta `glpi/plugins/`
- Usuário GLPI com perfil **Super-Admin**

---

## Instalação

### 1. Baixar o plugin

```bash
cd /var/www/html/glpi/plugins
git clone https://github.com/JoaoLucascp/newmanagement.git newmanagement
```

Ou extraia o ZIP na pasta `plugins/` de forma que a estrutura fique:

```
glpi/
└── plugins/
    └── newmanagement/
        ├── setup.php
        ├── hook.php
        ├── src/
        └── ...
```

> **⚠️ Atenção:** A pasta deve se chamar exatamente `newmanagement` (sem espaços, sem maiúsculas).

### 2. Ajustar permissões

```bash
chown -R www-data:www-data /var/www/html/glpi/plugins/newmanagement
chmod -R 755 /var/www/html/glpi/plugins/newmanagement
```

### 3. Ativar no GLPI

1. Acesse **GLPI → Configuração → Plugins**
2. Localize **Newmanagement** na lista
3. Clique em **Instalar** (ícone de download)
4. Clique em **Ativar** (ícone de play)

O GLPI executará automaticamente `plugin_newmanagement_install()` do `hook.php`, criando todas as tabelas necessárias.

### 4. Verificar instalação

- Acesse **Plugins → Newmanagement → Empresas**
- A lista de empresas deve carregar sem erros
- Verifique os logs em `glpi/files/_log/php-errors.log` se houver problemas

---

## Upgrade

### 1. Fazer backup

```bash
# Backup do banco
mysqldump -u root -p glpi glpi_plugin_newmanagement_companies \
  glpi_plugin_newmanagement_ipbxs \
  glpi_plugin_newmanagement_chatbots \
  glpi_plugin_newmanagement_tasks \
  > backup_newmanagement_$(date +%Y%m%d).sql

# Backup dos arquivos
cp -r /var/www/html/glpi/plugins/newmanagement \
       /var/www/html/glpi/plugins/newmanagement_backup_$(date +%Y%m%d)
```

### 2. Atualizar o código

```bash
cd /var/www/html/glpi/plugins/newmanagement
git pull origin main
```

### 3. Executar upgrade no GLPI

1. Acesse **GLPI → Configuração → Plugins**
2. Se o plugin mostrar botão **Upgrade**, clique nele
3. O GLPI executará `plugin_newmanagement_upgrade()` do `hook.php`

### 4. Limpar cache

```bash
rm -rf /var/www/html/glpi/files/_cache/*
```

---

## Desinstalação

> **⚠️ Atenção:** A desinstalação remove **todos os dados** do plugin do banco de dados. Faça backup antes.

1. Acesse **GLPI → Configuração → Plugins**
2. Clique em **Desativar** e depois em **Desinstalar**
3. O GLPI executará `plugin_newmanagement_uninstall()` que remove todas as tabelas
4. Após desinstalar, você pode remover a pasta:

```bash
rm -rf /var/www/html/glpi/plugins/newmanagement
```

---

## Troubleshooting

| Problema | Causa provável | Solução |
|---|---|---|
| Plugin não aparece na lista | Pasta com nome errado | Renomear para `newmanagement` |
| Erro ao instalar | Permissão de banco | Verificar usuário MySQL tem CREATE TABLE |
| Tela em branco | Erro PHP | Ver `glpi/files/_log/php-errors.log` |
| Aba IPBX não carrega | Cache do GLPI | Limpar `files/_cache/` |
| CSRF error | Token expirado | Recarregar a página |
