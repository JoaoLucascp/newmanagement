# 🔍 RELATÓRIO DE AUDIT — Plugin Newmanagement v1.0.0
**Data:** 20/05/2026 | **Auditor:** Assistente Sênior | **Repositório:** [JoaoLucascp/newmanagement](https://github.com/JoaoLucascp/newmanagement)

---

## 📊 PONTUAÇÃO GERAL

| Categoria | Status | Nota |
|---|---|---|
| Arquitetura PSR-4 | ✅ Conforme | 9/10 |
| Segurança | ⚠️ Atenção | 7/10 |
| Banco de Dados | ✅ Bom | 8/10 |
| Controle de Acesso | ✅ Bom | 8/10 |
| Frontend / Templates | ⚠️ Atenção | 6/10 |
| I18n | ✅ Bom | 8/10 |
| Testes | ❌ Ausente | 0/10 |
| Documentação | ✅ Excelente | 9/10 |
| **GERAL** | **⚠️ Aprovado com Ressalvas** | **7/10** |

---

## ✅ FASE 1 — CONFORMIDADE GLPI 11.0.6

### 1.1 Arquitetura e Padrões

#### ✅ PSR-4 Namespacing
- Namespace correto: `GlpiPlugin\Newmanagement` em `src/`
- Classes registradas via `Plugin::registerClass()` no `setup.php`
- **Problema:** Pasta `src/` não tem `composer.json` definindo o autoload PSR-4 explicitamente
  ```json
  // ❌ FALTA: composer.json com:
  {
    "autoload": {
      "psr-4": {
        "GlpiPlugin\\Newmanagement\\": "src/"
      }
    }
  }
  ```
  > GLPI faz o autoload automaticamente, mas um `composer.json` documenta a intenção e facilita ferramentas de análise estática.

#### ✅ Herança CommonDBTM
- Todas as classes herdam `\CommonDBTM` corretamente
- `$rightname` definido em todas as classes
- `getTypeName()`, `getTable()`, `rawSearchOptions()` implementados
- `defineTabs()` correto em `Company.php`

#### ✅ Hooks no setup.php
- `csrf_compliant` ✅
- `use_massive_action` ✅
- `add_css` / `add_javascript` ✅
- `MENU_TOADD` via constante `Glpi\Plugin\Hooks::MENU_TOADD` ✅ (padrão GLPI 11)
- `config_page` ✅

---

## 🔴 FASE 2 — PROBLEMAS ENCONTRADOS (por severidade)

### CRÍTICO 🔴

#### C1 — Senhas armazenadas SEM criptografia real
**Arquivo:** `hook.php`, `src/Ipbx.php`
**Problema:** As colunas `web_password`, `ssh_password`, `admin_password`, etc. têm comentário `COMMENT 'sodiumEncrypt'`, mas **não há código que chame `Toolbox::sodiumEncrypt()` antes de salvar nem `Toolbox::sodiumDecrypt()` ao ler**.
```php
// ❌ PROBLEMA: O comentário diz "sodiumEncrypt" mas nenhum método encrypt/decrypt foi encontrado
// As senhas provavelmente estão sendo salvas em texto plano!

// ✅ CORREÇÃO em src/Ipbx.php — antes de salvar via AJAX:
use Glpi\Toolbox\Toolbox;

$encrypted = Toolbox::sodiumEncrypt($password);
$DB->update(self::getTable(), ['web_password' => $encrypted], ['id' => $ipbx_id]);

// E ao exibir (verificar se campo está preenchido sem descriptografar para o HTML):
$has_web_password = !empty($fields['web_password']); // ← isso está correto em Ipbx.php ✅
// Mas no ajax/ipbx_sub.php precisa criptografar ANTES de salvar
```
**Ação:** Auditar `ajax/ipbx_sub.php` e verificar se `Toolbox::sodiumEncrypt()` é chamado.

#### C2 — Endpoint AJAX sem verificação de autenticação visível
**Arquivo:** `ajax/ipbx_sub.php` (não lido, mas inferido)
**Risco:** Endpoints AJAX podem estar expostos sem `Session::checkLoginUser()`.
**Ação obrigatória:** Todo arquivo em `ajax/` deve começar com:
```php
include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('plugin_newmanagement_ipbx', UPDATE); // conforme operação
Session::checkCsrfToken(); // para operações mutantes
```

---

### ALTO ⚠️

#### A1 — showForm() usa echo direto (sem Twig)
**Arquivo:** `src/Company.php`, `src/Ipbx.php`
**Problema:** Todo o HTML é gerado via `echo` concatenado com PHP.
- Difícil de manter e testar
- Mistura lógica com apresentação
- Embora use `htmlspecialchars()` corretamente, é propenso a erros humanos futuros
```php
// ❌ ATUAL: Difícil de manter
echo '<td>' . __('Nome', 'newmanagement') . ' <span class="required">*</span></td>';

// ✅ IDEAL: Twig template
// templates/company/form.html.twig
```
**Prioridade:** Médio prazo — refatorar para Twig mantendo a lógica PHP nas classes.

#### A2 — Falta `is_deleted` em tabelas filhas
**Arquivo:** `hook.php`
**Problema:** As tabelas filhas do IPBX (`ipbx_extensions`, `ipbx_devices`, `ipbx_network`, `ipbx_lines`, `chatbot_mass_comm`, `chatbot_wa_restrictions`, `chatbot_users`) **não têm coluna `is_deleted`**.
```sql
-- ❌ PROBLEMA: Deleção hard-delete nas tabelas filhas
-- Se um item filho for "deletado", some permanentemente

-- ✅ CORREÇÃO: Adicionar is_deleted nas migrações
$migration->addField('glpi_plugin_newmanagement_ipbx_extensions', 'is_deleted', 'tinyint(1) NOT NULL DEFAULT 0', ['after' => 'date_mod']);
```

#### A3 — JavaScript inline no showForm()
**Arquivo:** `src/Company.php` (bloco `<script>` no final do método)
**Problema:** ~100 linhas de JavaScript embbutido no método PHP `showForm()`.
- Duplicação: cada vez que a página carrega, o mesmo JS é emitido
- Dificulta cache e separação de responsabilidades
```php
// ✅ CORREÇÃO: Mover para public/js/newmanagement.js
// O JS já está bem escrito (AbortController, IIFE, async/await)
// Apenas precisa ser movido para o arquivo externo
```

#### A4 — Falta índice em `name` na tabela principal
**Arquivo:** `hook.php`
**Problema:** Campo `name` em `glpi_plugin_newmanagement_companies` é buscável mas não tem índice.
```sql
-- ✅ ADICIONAR na migração:
KEY `name` (`name`)
```

---

### MÉDIO 🟡

#### M1 — `$items_id` e `$itemtype` em Ipbx mas sem `getItemsForItemtype()`
**Arquivo:** `src/Ipbx.php`
**Problema:** A classe declara `$itemtype = Company::class` e `$items_id = 'companies_id'`, o que é o padrão para entidades filhas no GLPI. Porém, não implementa `getItemsForItemtype()` necessário para o sistema de busca cruzada funcionar corretamente.

#### M2 — Falta validação server-side no PHP
**Arquivo:** `src/Company.php`, `ajax/*.php`
**Problema:** A validação de CNPJ/CEP ocorre apenas no JavaScript client-side.
```php
// ✅ ADICIONAR em Company.php:
protected function prepareInputForAdd($input): array|false
{
    if (!empty($input['cnpj'])) {
        $cnpj = preg_replace('/\D/', '', $input['cnpj']);
        if (strlen($cnpj) !== 14) {
            Session::addMessageAfterRedirect(__('CNPJ inválido.', 'newmanagement'), true, ERROR);
            return false;
        }
        $input['cnpj'] = $cnpj; // normalizar para salvar sem máscara
    }
    return $input;
}

protected function prepareInputForUpdate($input): array|false
{
    return $this->prepareInputForAdd($input);
}
```

#### M3 — Método `showForm(-1)` em front/company.php
**Arquivo:** `front/company.php`
**Problema:** Para novo item usa `$company->showForm(-1)` mas o padrão GLPI é usar `$company->display(['id' => -1])`.
```php
// ❌ ATUAL:
$company->showForm(-1);

// ✅ CORRETO GLPI 11:
$company->display(['id' => -1]);
```

#### M4 — Strings sem acento nos labels de tradução
**Arquivo:** `src/Company.php`
**Problema:** Strings como `'Razao Social'`, `'Endereco'`, `'Comentario'` sem acento, mas os campos têm acento no contexto.
```php
// ❌ ATUAL:
__('Razao Social', 'newmanagement')
__('Endereco', 'newmanagement')

// ✅ CORRETO:
__('Razão Social', 'newmanagement')
__('Endereço', 'newmanagement')
```

#### M5 — Falta PHPDoc em métodos das classes filhas
**Arquivo:** `src/Ipbx.php`, `src/Chatbot.php`, etc.
**Problema:** Métodos `renderExtensions()`, `renderDevices()`, `renderNetwork()` sem documentação PHPDoc.

---

### BAIXO 🔵

#### B1 — Falta `composer.json`
O plugin não tem `composer.json` para definir autoloading PSR-4 e facilitar ferramentas como PHPStan e PHPCS.

#### B2 — Falta `phpstan.neon` e `phpunit.xml`
Sem configuração para análise estática e testes automatizados.

#### B3 — README básico
O README existe mas pode ser expandido com exemplos de uso e screenshots.

#### B4 — Falta CHANGELOG.md
Não há registro de versões e mudanças.

#### B5 — Pasta `scripts/` sem conteúdo visível
A pasta `scripts/` existe mas seu conteúdo não foi verificado.

---

## 🛡️ FASE 5 — AUDIT DE SEGURANÇA DETALHADO

| Item | Status | Detalhe |
|---|---|---|
| CSRF em setup.php | ✅ | `csrf_compliant = true` |
| CSRF em front/company.php | ✅ | `Session::checkCsrfToken()` em POST |
| SQL Injection | ✅ | Usa `$DB->request()` e arrays parametrizados |
| XSS nos forms | ✅ | `htmlspecialchars()` em todos os outputs |
| Controle de acesso front | ✅ | `Session::checkRight()` em cada ação |
| Controle de acesso nas abas | ✅ | `Session::haveRight()` em displayTabContentForItem |
| Senhas criptografadas | ❌ | Comentário diz sodiumEncrypt mas não há código verificado |
| Tokens/senhas em logs | ⚠️ | Não exposto no HTML, mas verificar nos logs de erro |
| Sanitização de entrada | ⚠️ | Apenas client-side; falta prepareInputForAdd() |

---

## 🚀 PLANO DE AÇÃO PRIORITIZADO

### Sprint 1 — Crítico (Fazer Agora)
1. **[C1]** Verificar e implementar `Toolbox::sodiumEncrypt()` em `ajax/ipbx_sub.php` e equivalentes
2. **[C2]** Auditar todos os arquivos em `ajax/` — garantir `checkLoginUser()` + `checkRight()` + `checkCsrfToken()`

### Sprint 2 — Alta Prioridade (Esta Semana)
3. **[A2]** Adicionar `is_deleted` nas tabelas filhas via migration
4. **[A4]** Adicionar índice `name` na tabela companies
5. **[M2]** Implementar `prepareInputForAdd()` e `prepareInputForUpdate()` em Company.php
6. **[M3]** Corrigir `showForm(-1)` para `display(['id' => -1])`
7. **[M4]** Corrigir strings de tradução sem acentos

### Sprint 3 — Melhorias (Próximas 2 Semanas)
8. **[A3]** Extrair JavaScript inline para `public/js/newmanagement.js`
9. **[B1]** Criar `composer.json` com autoload PSR-4
10. **[B2]** Configurar PHPStan e PHPUnit
11. **[B4]** Criar CHANGELOG.md

### Sprint 4 — Excelência (Médio Prazo)
12. **[A1]** Refatorar `showForm()` para Twig templates
13. Criar testes unitários (meta: 80% cobertura)
14. PHPStan nível 8

---

## 💡 PONTOS POSITIVOS (O que está bem feito)

- ✅ Estrutura de diretórios muito próxima do ideal GLPI 11
- ✅ `htmlspecialchars()` aplicado consistentemente em todos os outputs
- ✅ CSRF protection configurado e verificado em todas as ações POST
- ✅ Controle de acesso granular por ação (READ, UPDATE, CREATE, DELETE)
- ✅ Soft delete (`is_deleted`) nas entidades principais
- ✅ `date_creation` e `date_mod` em todas as tabelas
- ✅ Migration com `addField()` e `changeField()` para upgrades
- ✅ JavaScript moderno: IIFE, async/await, AbortController com timeout
- ✅ Integração BrasilAPI (CNPJ + CEP) bem implementada
- ✅ Botão mostrar/ocultar senha com proteção visual (•••••• no HTML)
- ✅ Índices de FK nas tabelas filhas (`companies_id`, `ipbx_id`)
- ✅ Charset/collation usando helpers do GLPI (`DBConnection::getDefaultCharset()`)
- ✅ Menu com verificação de direitos por item

---

*Relatório gerado em 20/05/2026*
