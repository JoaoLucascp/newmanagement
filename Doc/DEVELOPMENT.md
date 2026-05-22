# Guia de Desenvolvimento — Newmanagement

## Ambiente local

### Requisitos

- Docker + Docker Compose (recomendado)
- PHP 8.2+ com extensões: `sodium`, `pdo_mysql`, `mbstring`, `xml`, `curl`
- Composer 2.x
- GLPI 11.0.6 instalado localmente ou via container

### Setup com Docker

```bash
# Clone o GLPI
git clone https://github.com/glpi-project/glpi.git glpi
cd glpi

# Clone o plugin dentro de plugins/
git clone https://github.com/JoaoLucascp/newmanagement.git plugins/newmanagement

# Suba o ambiente
docker compose up -d

# Instale dependências do GLPI
docker compose exec app composer install

# Acesse http://localhost:8080 e instale o GLPI
# Depois: Configuração > Plugins > Newmanagement > Instalar > Ativar
```

---

## Executar testes

```bash
# Dentro da pasta do GLPI (não do plugin)
cd glpi

# Instalar PHPUnit via Composer do GLPI
composer install

# Executar testes do plugin
./vendor/bin/phpunit \
  --bootstrap tests/bootstrap.php \
  plugins/newmanagement/tests/
```

### Estrutura de testes sugerida

```
newmanagement/tests/
├── bootstrap.php           # Inclui autoloader do GLPI
├── Unit/
│   ├── CompanyTest.php     # isValidCnpj(), formatCnpj(), prepareInput()
│   ├── FixedLineTest.php   # isValidPhoneNumber()
│   └── IpbxTest.php        # fetchPage(), renderExtensionRow()
└── Integration/
    ├── CompanyCRUDTest.php  # add/update/delete via CommonDBTM
    └── IpbxTabTest.php     # showTabForCompany() sem exceções
```

---

## Padrões de contribuição

### Código

- Siga **PSR-12** para formatação
- Todos os métodos públicos devem ter **docblock** com `@param` e `@return`
- Propriedades estáticas herdadas de `CommonDBTM` devem ter tipo explícito: `public static string $rightname`
- Nunca usar `echo` diretamente em métodos de modelo — sempre via `TemplateRenderer`
- Strings visíveis ao usuário sempre via `__('texto', 'newmanagement')` para i18n

### Banco de dados

- Sempre usar `$DB->insert()`, `$DB->update()`, `$DB->delete()` — nunca SQL raw
- Novos campos devem ter migration em `hook.php` na função `plugin_newmanagement_update()`
- Nunca alterar tabelas nativas do GLPI

### Segurança

- Senhas: sempre `Toolbox::sodiumEncrypt()` para salvar, nunca descriptografar para o frontend
- Todas as ações AJAX exigem: `Session::checkLoginUser()` + CSRF + `Session::checkRight()`
- Erros de exceção: log via `Toolbox::logError()`, mensagem genérica ao cliente
- Entradas do usuário: nunca concatenar em SQL; sempre usar arrays parametrizados

### Commits

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(company): adiciona campo de website na ficha de empresa
fix(security): remove senha descriptografada do contexto Twig
docs: atualiza guia de instalação
chore: bump versão para 1.1.0
```

---

## Adicionar novo módulo

1. Criar `src/NovoModulo.php` estendendo `CommonDBTM`
2. Criar `templates/novomodulo/tab.html.twig`
3. Registrar em `setup.php` via `Plugin::registerClass()`
4. Adicionar tabela em `hook.php` nas funções `install` e `uninstall`
5. Adicionar `$rightname` e registrar direito em `hook.php`
6. Adicionar aba em `Company::defineTabs()` se vinculado à empresa

---

## Variáveis de ambiente úteis

```bash
# Ativar modo debug do GLPI (mostra erros PHP e queries)
export GLPI_ENVIRONMENT_TYPE=development

# Log do GLPI fica em:
cat /var/www/glpi/files/_log/php-errors.log
cat /var/www/glpi/files/_log/sql-errors.log
```
