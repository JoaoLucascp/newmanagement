# Relatório de Debug e Revisão — Plugin Newmanagement (GLPI)

**Repositório:** `JoaoLucascp/newmanagement`  
**Data da revisão:** 20/05/2026  
**Arquivos revisados:** `hook.php`, `setup.php`, `src/Company.php`, `src/Ipbx.php`, `src/IpbxCloud.php`, `src/FixedLine.php`, `src/Chatbot.php`, `src/Task.php`

---

## Resumo Executivo

A revisão identificou **18 problemas** distribuídos em 5 arquivos. Nenhum arquivo possui falha crítica de segurança que comprometa o GLPI globalmente, mas existem lacunas importantes de autorização, validação de entrada no backend e boas práticas de arquitetura de plugins GLPI. Os arquivos `IpbxCloud.php` e `Task.php` estão sem correções necessárias. A maior concentração de problemas está em `Ipbx.php` e `Company.php`.

---

## Classificação Geral dos Problemas

| Prioridade | Quantidade | Descrição |
|---|---|---|
| 🔴 Alta | 5 | Segurança, autorização, CSRF |
| 🟡 Média | 8 | Validação, robustez, boas práticas GLPI |
| 🟢 Baixa | 5 | Qualidade de código, UX, manutenibilidade |

---

## `src/Company.php`

### 🔴 Sem validação de CNPJ no backend

O formulário valida o comprimento do CNPJ apenas no JavaScript. Uma requisição direta (curl, Postman) pode inserir CNPJ inválido no banco. Implementar `prepareInputForAdd()` e `prepareInputForUpdate()`:

```php
public function prepareInputForAdd($input) {
    if (!empty($input['cnpj'])) {
        $cnpj = preg_replace('/\D/', '', $input['cnpj']);
        if (strlen($cnpj) !== 14) {
            \Session::addMessageAfterRedirect(
                __('CNPJ inválido.', 'newmanagement'), false, ERROR
            );
            return false;
        }
        $input['cnpj'] = $cnpj; // persiste apenas dígitos
    }
    return $input;
}

public function prepareInputForUpdate($input) {
    return $this->prepareInputForAdd($input);
}
```

### 🟡 `getMenuContent()` sem verificação de direito por módulo

Todos os itens do menu lateral são exibidos independente das permissões do usuário logado. O correto é condicionar cada entrada:

```php
if (\Session::haveRight('plugin_newmanagement_ipbx', READ)) {
    $menu['options']['ipbx'] = [
        'title' => __('IPBX On-Premise', 'newmanagement'),
        'page'  => '/plugins/newmanagement/front/ipbx.php',
        'icon'  => 'ti ti-server',
        'links' => [
            'search' => '/plugins/newmanagement/front/ipbx.php',
            'add'    => '/plugins/newmanagement/front/ipbx.php?action=add',
        ],
    ];
}
// Repetir para cada módulo (ipbxcloud, chatbot, fixedline, task)
```

### 🟡 Fetch para BrasilAPI sem timeout

O `fetch()` não possui `AbortController`. Se a BrasilAPI estiver lenta ou indisponível, o botão "Buscar" fica desabilitado indefinidamente para o usuário.

```js
// Adicionar antes de cada chamada fetch():
const controller = new AbortController();
const timer = setTimeout(() => controller.abort(), 8000);
try {
    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(timer);
    // ...restante do código
} catch (err) {
    clearTimeout(timer);
    if (err.name === 'AbortError') {
        setFeedback('cnpj-feedback', 'Tempo limite excedido.', 'error');
    } else {
        setFeedback('cnpj-feedback', 'Erro de conexão com a BrasilAPI.', 'error');
    }
}
```

### 🟢 Campo `email` com `type="text"`

```php
// ❌ atual
echo '<input type="text" id="email" name="email" ...>';

// ✅ correto — aciona teclado correto no mobile e validação nativa
echo '<input type="email" id="email" name="email" ...>';
```

### 🟢 Montagem frágil do logradouro no JS (BrasilAPI)

```js
// ❌ pode gerar " Rua X" com espaço inicial se descricao_tipo estiver vazio
data.logradouro ? data.descricao_tipo_de_logradouro + ' ' + data.logradouro : ''

// ✅ seguro
data.logradouro
    ? [data.descricao_tipo_de_logradouro, data.logradouro].filter(Boolean).join(' ')
    : ''
```

---

## `src/Ipbx.php`

### 🔴 Sem verificação de `canView()` em `showTabForCompany()`

Qualquer usuário com acesso à ficha da empresa consegue ver a aba IPBX, mesmo sem permissão de leitura do módulo.

```php
public static function showTabForCompany(Company $company, array $options = []): void
{
    // Adicionar no início do método:
    if (!\Session::haveRight(self::$rightname, READ)) {
        echo '<p class="alert alert-warning">' . __('Acesso negado.', 'newmanagement') . '</p>';
        return;
    }
    // ... restante do código
}
```

### 🔴 CSRF não propagado nas subtabelas filhas

O token CSRF é gerado e passado como parâmetro para `renderExtensions()`, `renderDevices()` e `renderNetwork()`, mas não é inserido como `data-attribute` nos botões de adição nem nas linhas das subtabelas. As chamadas AJAX de adição dependem de o JS encontrar o token correto.

**Solução:** Garantir que cada botão de adição tenha o token explicitamente:

```php
// Em renderExtensions(), renderDevices(), renderNetwork():
echo '<button type="button" class="btn btn-sm btn-primary nm-ext-add-btn"'
    . ' data-company-id="' . (int)$company_id . '"'
    . ' data-ipbx-id="' . (int)$ipbx_id . '"'
    . ' data-csrf="' . htmlspecialchars($csrf, ENT_QUOTES) . '">'  // ← adicionar isso
    . __('Adicionar Ramal', 'newmanagement') . '</button>';
```

E no JS, ler via `btn.dataset.csrf` em vez de buscar no campo global `#nm-ipbx-csrf`.

### 🟡 `rawSearchOptions()` ausente

`Ipbx` herda de `CommonDBTM` sem implementar `rawSearchOptions()`. Sem isso, o GLPI não consegue listar servidores IPBX em relatórios ou buscas globais.

```php
public function rawSearchOptions(): array
{
    $tab = [];
    $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];
    $tab[] = [
        'id'            => 1,
        'table'         => self::getTable(),
        'field'         => 'name',
        'name'          => __('Nome', 'newmanagement'),
        'datatype'      => 'itemlink',
        'massiveaction' => false,
    ];
    $tab[] = [
        'id'       => 2,
        'table'    => self::getTable(),
        'field'    => 'ip_address',
        'name'     => __('Endereço IP', 'newmanagement'),
        'datatype' => 'string',
    ];
    // Adicionar demais campos relevantes...
    return $tab;
}
```

### 🟡 Botões de deletar sem verificação de direito no front

Os botões de exclusão de ramal/dispositivo/rede são renderizados para qualquer usuário. Ocultar condicionalmente:

```php
if (\Session::haveRight(self::$rightname, DELETE)) {
    echo '<button type="button" class="btn btn-sm btn-danger nm-ext-del-btn"'
        . ' data-ext-id="' . (int)$ext['id'] . '">'
        . '<i class="ti ti-trash"></i></button>';
}
```

### 🟡 `ipbx_id = 0` nos botões filhos ao criar empresa nova

Quando a empresa ainda não foi salva, `$ipbx_id` é `0`. Os botões de adição de ramal/dispositivo/rede ficam com `data-ipbx-id="0"`, causando erros silenciosos no AJAX.

```php
// No início da renderização, se o IPBX não existe:
if ($ipbx_id <= 0) {
    echo '<p class="text-muted">'
        . __('Salve a empresa antes de adicionar ramais.', 'newmanagement')
        . '</p>';
    return;
}
```

### 🟢 Nomes de tabelas filhas hardcoded

As tabelas de ramais, dispositivos e rede estão como strings literais nas queries. Extrair para constantes de classe:

```php
class Ipbx extends \CommonDBTM
{
    const TABLE_EXTENSIONS = 'glpi_plugin_newmanagement_ipbx_extensions';
    const TABLE_DEVICES    = 'glpi_plugin_newmanagement_ipbx_devices';
    const TABLE_NETWORK    = 'glpi_plugin_newmanagement_ipbx_network';

    // Uso:
    $DB->request(['FROM' => self::TABLE_EXTENSIONS, ...]);
}
```

---

## `src/Chatbot.php`

### 🔴 Ausência de verificação de `canView()` na aba

O mesmo padrão do `Ipbx.php` — a aba Chatbot não verifica `Session::haveRight` antes de renderizar o conteúdo.

```php
public static function showTabForCompany(Company $company, array $options = []): void
{
    if (!\Session::haveRight(self::$rightname, READ)) {
        echo '<p class="alert alert-warning">' . __('Acesso negado.', 'newmanagement') . '</p>';
        return;
    }
    // ...
}
```

### 🟡 Mesmo problema de CSRF nas subtabelas

Chatbot tem subtabelas de configuração com botões AJAX cujo CSRF não é propagado via `data-attribute`. Aplicar a mesma correção descrita em `Ipbx.php`.

### 🟡 `rawSearchOptions()` ausente

Chatbot é uma entidade listável mas sem `rawSearchOptions()`. Implementar da mesma forma que `IpbxCloud.php` faz corretamente.

---

## `src/FixedLine.php`

### 🔴 Ausência de verificação de `canView()` na aba

```php
public static function showTabForCompany(Company $company, array $options = []): void
{
    if (!\Session::haveRight(self::$rightname, READ)) {
        echo '<p class="alert alert-warning">' . __('Acesso negado.', 'newmanagement') . '</p>';
        return;
    }
    // ...
}
```

### 🟡 Validação de número de telefone ausente no backend

Assim como o CNPJ em `Company.php`, números de telefone/ramal são salvos sem sanitização. Adicionar `prepareInputForAdd()` para garantir que apenas dígitos e caracteres válidos sejam persistidos.

### 🟢 `rawSearchOptions()` ausente

Linhas Fixas são entidades independentes listáveis. Implementar `rawSearchOptions()` com pelo menos campos `name`, `number`, `company_id` e datas padrão.

---

## `hook.php` e `setup.php`

### 🟡 `plugin_newmanagement_install()` sem transação de banco

A função de instalação executa múltiplos `CREATE TABLE` sem envolvê-los em transação. Se um `CREATE TABLE` falhar no meio, o banco fica em estado inconsistente (algumas tabelas criadas, outras não).

```php
function plugin_newmanagement_install(): bool
{
    global $DB;
    $DB->beginTransaction();
    try {
        // todos os CREATE TABLE aqui
        $DB->commit();
        return true;
    } catch (\Exception $e) {
        $DB->rollBack();
        \Session::addMessageAfterRedirect(
            __('Erro na instalação: ', 'newmanagement') . $e->getMessage(),
            false, ERROR
        );
        return false;
    }
}
```

### 🟢 Versão do plugin não centralizada

A versão `'1.0.0'` aparece tanto em `setup.php` quanto possivelmente em `hook.php`. Definir uma única constante:

```php
// setup.php
define('PLUGIN_NEWMANAGEMENT_VERSION', '1.0.0');

// Usar em todo lugar:
'version' => PLUGIN_NEWMANAGEMENT_VERSION,
```

---

## `src/IpbxCloud.php` e `src/Task.php`

Ambos os arquivos estão **sem correções necessárias**. Implementam corretamente `showFormHeader/Buttons`, aplicam `htmlspecialchars()` em todos os campos e seguem o padrão de herança `CommonDBTM`. `IpbxCloud.php` possui `rawSearchOptions()` completo e bem estruturado. `Task.php` poderia se beneficiar de `rawSearchOptions()` futuramente, mas não é bloqueante.

---

## Tabela Consolidada de Problemas

| Arquivo | Prioridade | Problema | Ação |
|---|---|---|---|
| `Company.php` | 🔴 Alta | Sem validação de CNPJ no backend | Implementar `prepareInputForAdd/Update` |
| `Company.php` | 🟡 Média | Menu sem verificação de direito por módulo | Condicionar com `Session::haveRight` |
| `Company.php` | 🟡 Média | Fetch sem timeout | Adicionar `AbortController` (8s) |
| `Company.php` | 🟢 Baixa | Campo email com `type="text"` | Trocar para `type="email"` |
| `Company.php` | 🟢 Baixa | Montagem frágil do logradouro no JS | Usar `.filter(Boolean).join(' ')` |
| `Ipbx.php` | 🔴 Alta | Sem `canView()` em `showTabForCompany` | Adicionar `Session::haveRight` no início |
| `Ipbx.php` | 🔴 Alta | CSRF não propagado nas subtabelas | Adicionar `data-csrf` nos botões filhos |
| `Ipbx.php` | 🟡 Média | `rawSearchOptions()` ausente | Implementar com campos principais |
| `Ipbx.php` | 🟡 Média | Botão deletar sem verificação de direito | Condicionar com `DELETE` right |
| `Ipbx.php` | 🟡 Média | `ipbx_id = 0` em empresa nova | Bloquear UI ou mostrar mensagem |
| `Ipbx.php` | 🟢 Baixa | Tabelas filhas com nomes hardcoded | Extrair para constantes de classe |
| `Chatbot.php` | 🔴 Alta | Sem `canView()` em `showTabForCompany` | Adicionar `Session::haveRight` no início |
| `Chatbot.php` | 🟡 Média | CSRF não propagado nas subtabelas | Mesmo fix do `Ipbx.php` |
| `Chatbot.php` | 🟡 Média | `rawSearchOptions()` ausente | Implementar com campos principais |
| `FixedLine.php` | 🔴 Alta | Sem `canView()` em `showTabForCompany` | Adicionar `Session::haveRight` no início |
| `FixedLine.php` | 🟡 Média | Sem validação de telefone no backend | Implementar `prepareInputForAdd` |
| `FixedLine.php` | 🟢 Baixa | `rawSearchOptions()` ausente | Implementar com campos principais |
| `hook.php` | 🟡 Média | Install sem transação de banco | Envolver em `beginTransaction/commit/rollBack` |
| `setup.php` | 🟢 Baixa | Versão do plugin não centralizada | Definir `PLUGIN_NEWMANAGEMENT_VERSION` |

---

## Ordem Sugerida de Correção

1. **Primeiro:** Corrigir todas as 5 ocorrências de `canView()` ausente (`Ipbx`, `Chatbot`, `FixedLine`) — segurança de autorização
2. **Segundo:** Implementar `prepareInputForAdd/Update` em `Company.php` e `FixedLine.php` — validação de entrada no backend
3. **Terceiro:** Corrigir propagação do CSRF nas subtabelas de `Ipbx.php` e `Chatbot.php`
4. **Quarto:** Adicionar transação em `plugin_newmanagement_install()`
5. **Quinto:** Implementar `rawSearchOptions()` nas classes que estão sem (`Ipbx`, `Chatbot`, `FixedLine`, `Task`)
6. **Sexto:** Correções de baixa prioridade — `type="email"`, constantes de tabela, versão centralizada

