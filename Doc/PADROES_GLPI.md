# 🏗️ PADRÕES E BOAS PRÁTICAS DO GLPI 11

## 📌 Referência de Conformidade

Este documento define os padrões que o plugin Newmanagement deve seguir para máxima compatibilidade com GLPI 11.0.6.

---

## 1️⃣ ESTRUTURA DE CLASSES

### CommonDBTM Base Class

Todas as classes de modelo devem herdar de `\CommonDBTM`:

```php
namespace GlpiPlugin\Newmanagement;

class Company extends \CommonDBTM
{
    // 1. Definir direito de acesso
    public static $rightname = 'plugin_newmanagement_company';
    
    // 2. Definir nome exibível
    public static function getTypeName($nb = 0): string
    {
        return _n('Empresa', 'Empresas', $nb, 'newmanagement');
    }
    
    // 3. Definir tabela do banco
    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_companies';
    }
    
    // 4. Definir icone (opcional)
    public static function getIcon(): string
    {
        return 'ti ti-building';
    }
    
    // 5. Definir menu
    public static function getMenuContent(): array
    {
        return [
            'title' => self::getTypeName(2),
            'page'  => '/plugins/newmanagement/front/company.php',
            'links' => [
                'search' => '/plugins/newmanagement/front/company.php',
                'add'    => '/plugins/newmanagement/front/company.php?add',
            ]
        ];
    }
}
```

### Campos Obrigatórios em Tabelas

```php
// Toda tabela GLPI deve ter:
CREATE TABLE `glpi_plugin_newmanagement_companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,                    // ID único
    
    // Campos de negócio
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    
    // Auditoria
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   // Quando criado
    `date_mod` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,                        // Última modificação
    `is_deleted` TINYINT(1) DEFAULT 0,                      // Soft delete
    
    // Índices
    KEY `idx_name` (`name`),
    KEY `idx_deleted` (`is_deleted`)
);
```

---

## 2️⃣ HOOKS E EVENTOS

### Principais Hooks do GLPI 11

| Hook | Uso | Exemplo |
|------|-----|---------|
| `plugin_init_*` | Inicialização | `plugin_init_newmanagement()` |
| `plugin_*_add` | Após inserção | `plugin_newmanagement_company_add()` |
| `plugin_*_update` | Após atualização | `plugin_newmanagement_company_update()` |
| `plugin_*_purge` | Após exclusão | `plugin_newmanagement_company_purge()` |
| `pre_item_add` | Antes de inserir qualquer item | Validação |
| `post_item_add` | Depois de inserir | Notificações |

### Registrar Hooks

Em `hook.php`:

```php
function plugin_init_newmanagement()
{
    global $PLUGIN_HOOKS;
    
    // CSRF Protection
    $PLUGIN_HOOKS['csrf_compliant']['newmanagement'] = true;
    
    // Assets
    $PLUGIN_HOOKS['add_css']['newmanagement']        = 'public/css/newmanagement.css';
    $PLUGIN_HOOKS['add_javascript']['newmanagement'] = 'public/js/newmanagement.js';
    
    // Páginas de configuração
    $PLUGIN_HOOKS['config_page']['newmanagement'] = 'front/config.php';
    
    // Menu
    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::MENU_TOADD]['newmanagement'] = [
        'plugins' => [\GlpiPlugin\Newmanagement\Company::class],
    ];
    
    // Registrar classes
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Company::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Ipbx::class);
    // ... demais classes
}
```

---

## 3️⃣ TEMPLATES TWIG

### Padrão de Template

```twig
{# templates/company/list.html.twig #}
{% extends "generic_show_form.html.twig" %}

{% block content %}
    <div class="container-lg">
        <h1>{{ 'Companies'|trans }}</h1>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ 'Name'|trans }}</th>
                    <th>{{ 'CNPJ'|trans }}</th>
                    <th>{{ 'Actions'|trans }}</th>
                </tr>
            </thead>
            <tbody>
            {% for company in companies %}
                <tr>
                    <td>{{ company.name }}</td>
                    <td>{{ company.cnpj }}</td>
                    <td>
                        <a href="?id={{ company.id }}" class="btn btn-sm btn-primary">
                            {{ 'Edit'|trans }}
                        </a>
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
{% endblock %}
```

### Filtros Twig Essenciais

```twig
{# Escape automático (sempre ativo) #}
{{ variable }}

{# Pluralização com contexto #}
{{ '%s company'|trans_choice(count, count, {'%count%': count}) }}

{# Formatação de datas #}
{{ date|localizeddate('medium', 'none') }}

{# Sanitização #}
{{ html_content|striptags }}
```

---

## 4️⃣ FORMULÁRIOS

### Padrão de Formulário

```php
// Em front/company.php
use Glpi\Form\FormBuilder;

class CompanyForm {
    public static function display($id = null)
    {
        $company = new \GlpiPlugin\Newmanagement\Company();
        
        if ($id) {
            $company->getFromDB($id);
        }
        
        $form = FormBuilder::create()
            ->name('company_form')
            ->method('POST')
            ->action(static::getFormURL() . '?add')
            ->addField([
                'type'      => 'text',
                'name'      => 'name',
                'label'     => __('Company Name', 'newmanagement'),
                'value'     => $company->fields['name'] ?? '',
                'required'  => true,
            ])
            ->addField([
                'type'      => 'email',
                'name'      => 'email',
                'label'     => __('Email', 'newmanagement'),
                'value'     => $company->fields['email'] ?? '',
            ])
            ->addField([
                'type'      => 'select',
                'name'      => 'contract_status',
                'label'     => __('Contract Status', 'newmanagement'),
                'options'   => \GlpiPlugin\Newmanagement\Company::getContractStatusOptions(),
                'value'     => $company->fields['contract_status'] ?? 0,
            ])
            ->render();
        
        return $form;
    }
}
```

---

## 5️⃣ BUSCA (Search Options)

### Implementar rawSearchOptions

```php
public function rawSearchOptions(): array
{
    $tab = [];
    
    $tab[] = [
        'id'         => 'common',
        'name'       => self::getTypeName(1),
    ];
    
    $tab[] = [
        'id'            => 1,
        'table'         => self::getTable(),
        'field'         => 'name',
        'name'          => __('Name', 'newmanagement'),
        'datatype'      => 'itemlink',
        'massiveaction' => false,
    ];
    
    $tab[] = [
        'id'       => 2,
        'table'    => self::getTable(),
        'field'    => 'cnpj',
        'name'     => __('CNPJ', 'newmanagement'),
        'datatype' => 'string',
    ];
    
    $tab[] = [
        'id'       => 30,
        'table'    => self::getTable(),
        'field'    => 'id',
        'name'     => __('ID', 'newmanagement'),
        'datatype' => 'number',
        'massiveaction' => false,
    ];
    
    return $tab;
}
```

---

## 6️⃣ CONTROLE DE ACESSO

### Verificação de Direitos

```php
// Em controladores (front/company.php)
\Session::checkRight(\GlpiPlugin\Newmanagement\Company::$rightname, READ);

// Em classes
public function canView(): bool
{
    return \Session::haveRight($this::$rightname, READ);
}

public function canCreate(): bool
{
    return \Session::haveRight($this::$rightname, CREATE);
}

public function canUpdate(): bool
{
    return \Session::haveRight($this::$rightname, UPDATE);
}

public function canDelete(): bool
{
    return \Session::haveRight($this::$rightname, PURGE);
}
```

---

## 7️⃣ BANCO DE DADOS

### Migration Class

```php
// Em migrations/ ou hook.php
class Migration_1_0_0 {
    public function up() {
        global $DB;
        
        $migration = new \Migration(PLUGIN_NEWMANAGEMENT_VERSION);
        
        // Criar tabela
        if (!$DB->tableExists('glpi_plugin_newmanagement_companies')) {
            $DB->doQueryOrDie("CREATE TABLE ...");
        }
        
        // Adicionar coluna
        if (!$DB->fieldExists('glpi_plugin_newmanagement_companies', 'new_field')) {
            $migration->addField(
                'glpi_plugin_newmanagement_companies',
                'new_field',
                'VARCHAR(255)',
                ['after' => 'existing_field']
            );
        }
        
        $migration->executeMigration();
    }
}
```

### Usar Migrations GLPI

```php
// Em hook.php — function plugin_newmanagement_install()
$migration = new \Migration(PLUGIN_NEWMANAGEMENT_VERSION);

// Usar funções da classe Migration
$migration->addField('table', 'field', 'type', ['after' => 'previous']);
$migration->addKey('table', 'field', 'type');
$migration->dropField('table', 'field');
$migration->renameField('table', 'old', 'new');

$migration->executeMigration();
```

---

## 8️⃣ ASSET MANAGEMENT

### CSS/JavaScript Registro

Em `setup.php` ou `hook.php`:

```php
$PLUGIN_HOOKS['add_css']['newmanagement'] = [
    'public/css/newmanagement.css',
    'public/css/themes.css'
];

$PLUGIN_HOOKS['add_javascript']['newmanagement'] = [
    'public/js/common.js',
    'public/js/forms.js',
    'public/js/newmanagement.js'
];
```

### Estrutura de CSS

```css
/* public/css/newmanagement.css */

/* Prefix com namespace do plugin */
.glpi-newmanagement-section {
    margin-top: 20px;
}

.glpi-newmanagement-table {
    width: 100%;
}

/* Usar variáveis CSS do GLPI */
:root {
    --glpi-newmanagement-primary: var(--glpi-primary-color);
    --glpi-newmanagement-border: var(--glpi-border-color);
}
```

### Estrutura de JavaScript

```javascript
// public/js/newmanagement.js

(function() {
    'use strict';
    
    const Newmanagement = {
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupCompanyForm();
            });
        },
        
        setupCompanyForm() {
            // Lógica do formulário
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Newmanagement.init());
    } else {
        Newmanagement.init();
    }
})();
```

---

## 9️⃣ INTERNACIONALIZAÇÃO (i18n)

### Tradução de Strings

```php
// Em código PHP
__('Simple string', 'newmanagement')
_n('Singular', 'Plural', $count, 'newmanagement')
_nx('Context', 'Singular', 'Plural', $count, 'newmanagement')

// Em templates Twig
{{ 'Simple string'|trans({}, 'newmanagement') }}
{{ 'Singular'|trans_choice(count, count, {}, 'newmanagement') }}
```

### Arquivo .po (Gettext)

```
# translations/pt_BR/LC_MESSAGES/newmanagement.po

msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Language: pt_BR\n"

msgid "Company"
msgstr "Empresa"

msgid "Companies"
msgstr "Empresas"
```

---

## 🔟 VALIDAÇÃO E SANITIZAÇÃO

### Input Validation

```php
// Em métodos da classe
public function post_getFromForm(): array
{
    $input = parent::post_getFromForm();
    
    // Validar CNPJ
    if (!$this->isValidCNPJ($input['cnpj'] ?? '')) {
        $this->addError('cnpj', __('Invalid CNPJ', 'newmanagement'));
    }
    
    // Validar email
    if (!filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $this->addError('email', __('Invalid email', 'newmanagement'));
    }
    
    // Sanitizar strings
    $input['name'] = htmlspecialchars($input['name'] ?? '', ENT_QUOTES, 'UTF-8');
    
    return $input;
}

private function isValidCNPJ($cnpj): bool
{
    // Implementação de validação
    return preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', $cnpj);
}
```

### Output Escaping

```twig
{# Em templates Twig — SEMPRE use escape #}

{# Automático (padrão) #}
{{ user_input }}

{# Explícito com filtros #}
{{ user_input|escape('html') }}
{{ user_input|striptags }}

{# Raw (CUIDADO!) — só com dados confiáveis #}
{{ safe_html|raw }}
```

---

## 1️⃣1️⃣ LOGGING E AUDITORIA

### Registrar Eventos

```php
use Glpi\Toolbox\Log;

// Registrar ação
$log = new Log();
$log->history(
    $this->getID(),
    $this->getType(),
    [
        'field_changed' => [
            'name' => 'old_value',
            'value' => 'new_value'
        ]
    ]
);

// Ou usar hook
function plugin_newmanagement_company_update(\GlpiPlugin\Newmanagement\Company $company) {
    \Session::addMessageAfterRedirect(
        sprintf(__('%s updated', 'newmanagement'), $company->getName()),
        false,
        INFO
    );
}
```

---

## 1️⃣2️⃣ PERFORMANCE BEST PRACTICES

### Query Optimization

```php
// ❌ RUIM: N+1 queries
foreach ($companies as $company) {
    echo $company->fields['name'];
    foreach ($company->getIpbxes() as $ipbx) {  // Query por empresa
        echo $ipbx->fields['name'];
    }
}

// ✅ BOM: Eager loading
$companies = \GlpiPlugin\Newmanagement\Company::all();
$ipbxes = \GlpiPlugin\Newmanagement\Ipbx::getForCompanies(array_keys($companies));

foreach ($companies as $company) {
    echo $company->fields['name'];
    foreach ($ipbxes[$company->getID()] ?? [] as $ipbx) {
        echo $ipbx->fields['name'];
    }
}
```

### Caching

```php
// Usar cache do GLPI
use Glpi\Cache\CacheManager;

$cache = CacheManager::getInstance();
$key = 'newmanagement_company_' . $id;

if ($cached = $cache->get($key)) {
    return $cached;
}

// Processar dados
$data = $this->processCompanyData($id);

// Cachear por 1 hora
$cache->set($key, $data, ['lifetime' => 3600]);

return $data;
```

---

## 1️⃣3️⃣ TESTES

### PHPUnit Tests

```php
// tests/Unit/CompanyTest.php
namespace GlpiPlugin\Newmanagement\Tests\Unit;

use PHPUnit\Framework\TestCase;
use GlpiPlugin\Newmanagement\Company;

class CompanyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->company = new Company();
    }
    
    public function testCompanyCreation(): void
    {
        $input = [
            'name' => 'Test Company',
            'cnpj' => '12.345.678/0001-99'
        ];
        
        $id = $this->company->add($input);
        $this->assertIsInt($id);
        $this->assertNotFalse($id);
    }
    
    public function testCompanyValidation(): void
    {
        $input = [
            'name' => '',  // Inválido
            'cnpj' => 'invalid'
        ];
        
        $result = $this->company->add($input);
        $this->assertFalse($result);
    }
}
```

---

## 1️⃣4️⃣ VERSIONAMENTO

### semantic versioning

```
v1.0.0
 │ │ └─ PATCH (bug fixes, small updates)
 │ └─── MINOR (features, backward compatible)
 └───── MAJOR (breaking changes)

Versão: 1.0.0
```

### Changelog

```markdown
# Changelog

## [1.0.0] - 2026-05-20

### Added
- Initial release
- Company management
- IPBX documentation

### Fixed
- CSRF token validation

### Changed
- Updated GLPI compatibility to 11.0.6
```

---

## ✅ CHECKLIST DE CONFORMIDADE

Ao finalizar qualquer componente:

- [ ] Namespace correto (GlpiPlugin\Newmanagement\*)
- [ ] CommonDBTM herdado
- [ ] Direitos definidos ($rightname)
- [ ] Tabela com soft delete e timestamps
- [ ] rawSearchOptions() implementado
- [ ] Templates Twig em templates/
- [ ] Assets em public/
- [ ] Strings traduzidas
- [ ] Hooks registrados
- [ ] Validação de entrada
- [ ] Escape de saída
- [ ] Testes implementados
- [ ] PHPDoc completo
- [ ] PSR-12 compliance

---

**Documento versão:** 1.0.0  
**Última atualização:** 20/05/2026

