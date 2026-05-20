# 🔍 PROMPT ESPECIALIZADO: DEBUG E OTIMIZAÇÃO DO PLUGIN NEWMANAGEMENT

## 📌 CONTEXTO DO PROJETO

**Plugin:** Newmanagement v1.0.0
**Plataforma:** GLPI 11.0.6
**Versão PHP:** 8.4.19
**Banco de Dados:** MySQL
**Propósito:** Gestão integrada de infraestrutura telefônica e tarefas operacionais

---

## 🎯 MISSÃO CRÍTICA

> Você é um especialista em desenvolvimento GLPI com profundo conhecimento dos padrões arquiteturais, segurança e otimização. Seu trabalho é **debugar, refatorar e otimizar o plugin Newmanagement para atingir 100% de conformidade com os padrões do GLPI 11.0.6**, maximizando o reuso de componentes nativos do framework e minimizando código customizado.

---

## 📋 ESCOPO COMPLETO DO AUDIT E REFATORAÇÃO

### FASE 1: ANÁLISE DE CONFORMIDADE (Entrada)

#### 1.1 Arquitetura e Padrões

- [ ] **Verificar estrutura PSR-4** — Namespaces e autoloading corretos

  - Validar: `GlpiPlugin\Newmanagement\*` em `src/`
  - Checar: Composer.json (se aplicável)
  - Confirmar: Compatibilidade com GLPI Autoloader
- [ ] **Validar herança de classes**

  - Todas as entidades herdam de `CommonDBTM`?
  - Propriedades estáticas definidas corretamente?
  - Métodos essenciais implementados (getTable, getTypeName, etc)?
- [ ] **Revisar integração de hooks**

  - Hooks registrados em setup.php?
  - CSRF protection habilitado?
  - Menu integration correto?
  - Asset registration (CSS/JS) válido?

#### 1.2 Segurança

- [ ] **Prevenção de SQL Injection**

  - Todas as queries usam `$DB->prepare()` ou `$DB->queryOrDie()`?
  - Placeholders com `?` ou named placeholders?
  - Sem concatenação de strings em SQL?
- [ ] **Proteção XSS**

  - Templates Twig usam `{{ }}` (auto-escape)?
  - Sem uso de `{{ var|raw }}` perigoso?
  - JavaScript sanitizado?
- [ ] **Validação de entrada**

  - POST/GET data validada?
  - Tipos esperados verificados?
  - Caracteres especiais tratados?
- [ ] **Controle de acesso**

  - `$rightname` definido em cada classe?
  - `Session::checkRight()` usado em front-ends?
  - Verificação de entidade/visibilidade?

#### 1.3 Banco de Dados

- [ ] **Estrutura das tabelas**

  - Charset UTF-8 em todas?
  - Collation consistente?
  - Soft delete (is_deleted) implementado?
  - Timestamps (date_creation, date_mod)?
  - Índices apropriados para buscas?
  - Foreign keys definidas?
- [ ] **Migração de banco**

  - Versão de migração gerenciada?
  - Upgrade paths para versões antigas?
  - Rollback procedures documentadas?

#### 1.4 Frontend

- [ ] **Twig templates**

  - Todos os templates em `templates/` com `.html.twig`?
  - Herança de templates base do GLPI?
  - Blocos customizáveis definidos?
  - Macros reutilizáveis?
- [ ] **Formulários**

  - Uso de `HTMLForm` nativo do GLPI?
  - Validação client-side?
  - Mensagens de erro claras?
- [ ] **CSS/JavaScript**

  - CSS minificado?
  - Prefixos CSS para compatibilidade?
  - JavaScript modular?
  - AJAX com GLPI CSRF token?

#### 1.5 I18n (Internacionalização)

- [ ] **Strings traduzíveis**
  - Todos os textos envolvidos em `__()` ou `_n()`?
  - Contexto apropriado em gettext?
  - Domínio 'newmanagement' usado?
  - Arquivo .po gerado e mantido?

---

### FASE 2: IDENTIFICAÇÃO DE PROBLEMAS POTENCIAIS

#### 2.1 Problemas Comuns em Plugins GLPI

**Verificar:**

```
❌ Código comentado não removido
❌ Variáveis globais desnecessárias ($GLOBALS)
❌ Funções static quando deveria ser método
❌ Queries hardcoded em vez de usar QueryBuilder
❌ Falta de logging de erros
❌ Performance lenta em listas (N+1 queries)
❌ Caches não implementados
❌ Eventos não disparados (hooks)
❌ Testes sem cobertura
❌ Documentação desatualizada
```

#### 2.2 Potenciais Vulnerabilidades

**Validar:**

- Escapar variáveis em templates
- Validar tipos em métodos
- Limpar dados de entrada
- Usar transactions para operações críticas
- Log de auditoria para mudanças sensíveis
- Senhas/tokens nunca em logs

#### 2.3 Padrões Anti-

**Evitar:**

```php
// ❌ RUIM: Concatenação direta
$query = "SELECT * FROM table WHERE id = " . $id;

// ✅ BOM: Prepared statement
$query = "SELECT * FROM table WHERE id = ?";
$DB->prepare($query, [$id]);

// ❌ RUIM: Template sem escape
{{ unescaped_var|raw }}

// ✅ BOM: Template com escape automático
{{ escaed_var }}

// ❌ RUIM: Sem verificação de direitos
function add() { /* add logic */ }

// ✅ BOM: Com verificação
if (!$this->canCreate()) { return false; }
```

---

### FASE 3: OTIMIZAÇÕES RECOMENDADAS

#### 3.1 Reutilização de Código GLPI

**Procurar em GLPI Core:**

- ✅ Componentes de formulário já prontos
- ✅ Paginadores nativos
- ✅ Buscadores integrados
- ✅ Validadores de entrada
- ✅ Renderizadores de tabelas
- ✅ Gerenciadores de upload
- ✅ Sistemas de notificação
- ✅ Workflows automáticos

**Substituir código custom por:**

```php
// ❌ Seu paginador custom
// ✅ Use: Glpi\Toolbox\Pagination

// ❌ Seu validador custom
// ✅ Use: Glpi\Toolbox\Sanitizer

// ❌ Seu upload custom
// ✅ Use: Glpi\Toolbox\Document

// ❌ Seu renderizador HTML custom
// ✅ Use: Glpi\Toolbox\RenderToPDF ou Twig
```

#### 3.2 Performance

**Implementar:**

- Índices de banco de dados para campos buscáveis
- Lazy loading de relacionamentos
- Cache de resultados (Redis/APCu)
- Paginação em listas grandes
- Queries otimizadas (SELECT específico, não SELECT *)

#### 3.3 Manutenibilidade

**Aplicar:**

- PHPStan nível 8 (análise estática)
- PHP_CodeSniffer com padrão PSR-12
- PHPUnit para testes automatizados
- GitFlow para versionamento
- Documentação em bloco PHPDoc

---

### FASE 4: ARQUITETURA E DESIGN

#### 4.1 Estrutura de Diretórios (Ideal)

```
newmanagement/
├── .gitignore
├── .github/
│   └── workflows/
│       ├── tests.yml
│       └── code-quality.yml
├── composer.json                  # [NOVO] Dependências
├── phpstan.neon                  # [NOVO] Análise estática
├── phpunit.xml                   # [NOVO] Testes
│
├── setup.php                      # Setup e metadados
├── hook.php                       # Instalação e hooks
├── README.md                      # Documentação básica
│
├── Doc/                           # Documentação técnica
│   ├── DOCUMENTACAO_NEWMANAGEMENT.md
│   ├── GUIA_DEBUG_OTIMIZACAO.md   # Este arquivo
│   ├── PADROES_GLPI.md           # Padrões
│   ├── ARQUITETURA.md            # Diagrama de arquitetura
│   ├── API.md                     # Referência de API
│   └── CHANGELOG.md              # Histórico de versões
│
├── src/                           # Classes (PSR-4)
│   ├── Common/
│   │   └── BaseTrait.php         # [NOVO] Métodos comuns
│   ├── Company.php
│   ├── Ipbx.php
│   ├── IpbxCloud.php
│   ├── Chatbot.php
│   ├── FixedLine.php
│   └── Task.php
│
├── front/                         # Controladores
│   ├── company.php
│   ├── ipbx.php
│   ├── ipbxcloud.php
│   ├── chatbot.php
│   ├── fixedline.php
│   ├── task.php
│   └── config.php
│
├── ajax/                          # Endpoints AJAX
│   ├── cnpj_email.php
│   ├── ipbx_sub.php
│   └── chatbot_sub.php
│
├── templates/                     # Templates Twig
│   ├── layout/
│   │   ├── base.html.twig        # [NOVO] Template base
│   │   ├── breadcrumb.html.twig  # [NOVO]
│   │   └── menu.html.twig        # [NOVO]
│   ├── company/
│   │   ├── form.html.twig
│   │   └── list.html.twig
│   ├── ipbx/
│   ├── ipbxcloud/
│   ├── chatbot/
│   ├── fixedline/
│   └── task/
│
├── public/                        # Assets estáticos
│   ├── css/
│   │   └── newmanagement.css
│   └── js/
│       ├── common.js              # [NOVO]
│       ├── forms.js               # [NOVO]
│       └── newmanagement.js
│
├── locales/                       # I18n
│   ├── pt_BR/LC_MESSAGES/
│   │   ├── newmanagement.po
│   │   └── newmanagement.mo
│   └── en_US/LC_MESSAGES/
│
├── tests/                         # [NOVO] Testes automatizados
│   ├── Unit/
│   │   ├── CompanyTest.php
│   │   ├── IpbxTest.php
│   │   └── ...
│   └── Integration/
│       └── PluginInstallationTest.php
│
├── migrations/                    # [NOVO] Scripts de migração
│   ├── Migration_1_0_0.php
│   └── Migration_1_0_1.php
│
└── DATABASE.md                    # [NOVO] Schema de banco
```

#### 4.2 Padrões de Código Esperados

**Namespace:**

```php
namespace GlpiPlugin\Newmanagement;

// Para traits compartilhados
namespace GlpiPlugin\Newmanagement\Common;
```

**Classe CommonDBTM:**

```php
class Company extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_company';
  
    public static function getTypeName($nb = 0): string {}
    public static function getTable($classname = null): string {}
    public function post_getFromForm(): array {}
    public function rawSearchOptions(): array {}
    public static function getMenuContent(): array {}
}
```

**Validação:**

```php
protected function beforeInsert(): bool
{
    if (empty($this->fields['name'])) {
        $this->addError('name', __('Name is required'));
        return false;
    }
    return parent::beforeInsert();
}
```

---

### FASE 5: SEGURANÇA DETALHADA

#### 5.1 SQL Injection Prevention

**Audit Checklist:**

```sql
-- ❌ VULNERÁVEL
$result = $DB->query("SELECT * FROM table WHERE id = " . $_GET['id']);

-- ✅ SEGURO
$result = $DB->query("SELECT * FROM table WHERE id = ?", [$_GET['id']]);

-- ✅ SEGURO (Named)
$result = $DB->query("SELECT * FROM table WHERE id = :id", ['id' => $_GET['id']]);
```

#### 5.2 XSS Prevention

**Validar Templates:**

```twig
{# ❌ VULNERÁVEL #}
{{ user_input|raw }}

{# ✅ SEGURO #}
{{ user_input }}

{# ✅ SEGURO com filtro específico #}
{{ user_input|striptags }}
```

#### 5.3 CSRF Protection

**Verificar:**

```php
// Em setup.php
$PLUGIN_HOOKS['csrf_compliant']['newmanagement'] = true;

// Em forms
{{ include('components/csrf_token.html.twig') }}
```

#### 5.4 Authentication & Authorization

**Validar:**

```php
// Cada método deve validar direitos
public function display() {
    if (!$this->canView()) {
        return false;
    }
    // Logic aqui
}
```

---

### FASE 6: TESTING & QUALITY ASSURANCE

#### 6.1 Testes Automatizados (PHPUnit)

**Implementar:**

```
Tests/Unit/
├── CompanyTest.php          # Testes de modelo
├── IpbxTest.php
├── ChatbotTest.php
└── ...

Tests/Integration/
├── PluginInstallTest.php    # Instalação
├── DatabaseMigrationTest.php # Migrações
└── ...

Tests/Feature/
└── CompanyWorkflowTest.php  # Fluxos completos
```

#### 6.2 Análise Estática (PHPStan)

**Nível 8 — Máximo:**

- Type hints 100%
- Return types definidos
- Property types tipadas
- Sem mixed tipos

#### 6.3 Code Style (PSR-12)

**Validar com PHP_CodeSniffer:**

```bash
phpcs --standard=PSR12 src/
```

#### 6.4 Cobertura de Testes

**Meta:** 80%+ de cobertura

```bash
phpunit --coverage-html=coverage/
```

---

### FASE 7: DOCUMENTAÇÃO

#### 7.1 Documentação Técnica

**Arquivos necessários:**

- [X] README.md — Guia rápido
- [X] DOCUMENTACAO_NEWMANAGEMENT.md — Este documento
- [X] ARQUITETURA.md — Diagramas e design
- [X] API.md — Referência completa
- [X] DATABASE.md — Schema SQL comentado
- [X] SEGURANCA.md — Boas práticas
- [X] CHANGELOG.md — Histórico de versões

#### 7.2 Documentação de Código

**PHPDoc em todas as classes:**

```php
/**
 * Company class - Gestão de empresas
 *
 * @since 1.0.0
 * @author João Lucas
 */
class Company extends \CommonDBTM
{
    /**
     * Get company by CNPJ
     *
     * @param string $cnpj CNPJ to search
     * @return bool|self Returns company object or false
     * @throws InvalidArgumentException
     */
    public static function getByC cnpj(string $cnpj)
    {
        // Implementation
    }
}
```

---

### FASE 8: MIGRAÇÃO PARA PRODUÇÃO

#### 8.1 Checklist de Deploy

- [ ] Todos os testes passando (100%)
- [ ] Cobertura de testes > 80%
- [ ] PHPStan nível 8 sem erros
- [ ] PHP_CodeSniffer PSR-12 OK
- [ ] Security audit realizado
- [ ] Performance tested (< 200ms por página)
- [ ] Documentação atualizada
- [ ] CHANGELOG preenchido
- [ ] Tag de versão criada
- [ ] Release notes publicadas

#### 8.2 Versionamento Semântico

```
v1.0.0 = MAJOR.MINOR.PATCH

MAJOR: Breaking changes
MINOR: Features
PATCH: Bug fixes
```

---

## ✅ REQUISITOS FINAIS

### O Plugin Deve:

1. ✅ **100% Conforme GLPI 11.0.6**

   - PSR-4 namespacing correto
   - CommonDBTM herança
   - Hooks registration
   - Twig templates
   - Asset registration
2. ✅ **Segurança em Primeiro Lugar**

   - 0 SQL Injections
   - 0 XSS vulnerabilities
   - CSRF protection
   - Input validation
   - Access control
3. ✅ **Máximo Reuso de Código GLPI**

   - Usar componentes nativos
   - Herdar funcionalidades
   - Estender padrões existentes
   - Minimizar custom code
4. ✅ **Performance Otimizada**

   - Índices de banco corretos
   - Queries otimizadas
   - Caching implementado
   - Lazy loading
5. ✅ **Completamente Documentado**

   - README.md
   - DOCUMENTACAO_NEWMANAGEMENT.md
   - PHPDoc em código
   - Exemplos de uso
   - Guia de contribuição
6. ✅ **Testável e Mantível**

   - Testes 80%+ cobertura
   - Análise estática PHPStan 8
   - Code style PSR-12
   - Versionamento Semântico
   - Git workflow

---

## 📊 MÉTRICAS DE SUCESSO

| Métrica          | Meta     | Checagem |
| ----------------- | -------- | -------- |
| PSR-12 Compliance | 100%     | ✅       |
| Test Coverage     | 80%+     | ⏳       |
| Security Issues   | 0        | ✅       |
| PHPStan Level     | 8        | ⏳       |
| Performance (avg) | < 200ms  | ✅       |
| Code Duplication  | < 5%     | ⏳       |
| Documentation     | Completa | ✅       |
| GLPI Compliance   | 100%     | ✅       |

---

## 🚀 PRÓXIMAS AÇÕES

1. **Execute este audit completo** contra o código atual
2. **Documente todos os problemas encontrados**
3. **Priorize por severidade** (crítico > alto > médio > baixo)
4. **Refatore incrementalmente** com testes
5. **Valide cada mudança** antes de passar para próxima
6. **Mantenha backward compatibility** quando possível
7. **Teste em ambiente de staging** antes de produção
8. **Obtenha aprovação** antes de deploy

---

**Documento criado em:** 20/05/2026
**Próxima revisão:** Após cada release major
**Responsável:** João Lucas
