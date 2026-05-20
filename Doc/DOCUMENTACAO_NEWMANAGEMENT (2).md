# Plugin Newmanagement — Documentação de Auditoria e Melhorias

**Repositório:** [JoaoLucascp/newmanagement](https://github.com/JoaoLucascp/newmanagement)  
**Data da auditoria:** 20 de maio de 2026  
**Versão auditada:** `main` (commit `b3a9f9a`)  
**Pontuação geral:** 7/10 — Aprovado para produção após correções

---

## Visão Geral do Plugin

O **Newmanagement** é um plugin para o GLPI que gerencia dados técnicos e operacionais de empresas clientes. Suas principais funcionalidades incluem:

- Cadastro de empresas com consulta automática de CNPJ (BrasilAPI) e CEP
- Gerenciamento de IPBX On-Premise e Cloud
- Controle de ramais, dispositivos e redes
- Cadastro de linhas fixas (SIP/ISDN)
- Gerenciamento de chatbots e usuários associados
- Tarefas com geolocalização (latitude/longitude)

O plugin é desenvolvido em PHP 8.x para GLPI 11, utiliza Twig para templates, e segue as convenções de desenvolvimento oficial do GLPI.

---

## Resultado da Auditoria Inicial

A auditoria identificou **2 problemas críticos** e **4 problemas altos**, além de diversos pontos positivos.

### Pontos Positivos (já estava correto)

- `htmlspecialchars()` aplicado em todos os outputs antes da migração para Twig
- CSRF verificado em todas as ações POST via `Session::checkCSRF($_POST)`
- `Session::checkRight()` granular por ação (READ / UPDATE / CREATE / DELETE)
- JavaScript moderno com `async/await` e `AbortController` com timeout
- Integração BrasilAPI (CNPJ + CEP) bem implementada
- `date_creation`, `date_mod` e `is_deleted` nas entidades principais
- Charset/collation usando helpers nativos do GLPI (`DBConnection::getDefaultCharset()`)

### Problemas Identificados

| ID | Severidade | Descrição | Status Final |
|----|------------|-----------|--------------|
| C1 | 🔴 Crítico | Senhas possivelmente em texto plano no banco | ✅ Confirmado correto |
| C2 | 🔴 Crítico | Endpoints AJAX sem verificação de sessão/CSRF | ✅ Confirmado correto |
| A1 | ⚠️ Alto | HTML gerado via `echo` em vez de Twig templates | ✅ Corrigido (PRs #5–#8) |
| A2 | ⚠️ Alto | Tabelas filhas sem coluna `is_deleted` | ✅ Corrigido via migration |
| A3 | ⚠️ Alto | ~100 linhas de JavaScript inline em `Company.php` | ✅ Confirmado em `public/js/` |
| A4 | ⚠️ Alto | Campo `name` sem índice no banco | ✅ Confirmado correto |

---

## Verificação C1 — Criptografia de Senhas

### Diagnóstico

O comentário `COMMENT 'sodiumEncrypt'` nas colunas do banco indicava intenção de criptografia, mas era necessário confirmar se o PHP de fato chamava `Toolbox::sodiumEncrypt()` antes de salvar.

### Resultado da Verificação

**Ambos os arquivos ajax implementam corretamente a criptografia** via função helper centralizada:

```php
function nmEncryptPassword(string $value): ?string {
    return $value !== '' ? \Toolbox::sodiumEncrypt($value) : null;
}
```

A função retorna `null` para campos vazios, evitando que blobs criptografados inúteis sejam gravados no banco.

#### Cobertura em `ajax/ipbx_sub.php`

| Campo | Ação `add` | Ação `update` |
|-------|-----------|---------------|
| `web_password` | ✅ `nmEncryptPassword()` | ✅ `sodiumEncrypt()` direto |
| `ssh_password` | ✅ `nmEncryptPassword()` | ✅ `sodiumEncrypt()` direto |
| `password` (ramais) | ✅ `nmEncryptPassword()` | — sem campo de senha |
| `password` (devices) | ✅ `nmEncryptPassword()` | — sem campo de senha |

> **Detalhe importante:** no `update_ipbx`, a senha só é atualizada se vier preenchida no POST — comportamento correto para não apagar uma senha existente sem intenção.

#### Cobertura em `ajax/chatbot_sub.php`

| Campo | Ação `add` | Ação `update` |
|-------|-----------|---------------|
| `admin_password` | ✅ `nmEncryptPassword()` | ✅ só se preenchida |
| `superadmin_password` | ✅ `nmEncryptPassword()` | ✅ só se preenchida |
| `password` (mass_comm) | ✅ `nmEncryptPassword()` | — |
| `password` (chatbot_users) | ✅ `nmEncryptPassword()` | — |

**Conclusão:** C1 encerrado como **Confirmado correto**. Nenhuma alteração necessária.

---

## Verificação C2 — Segurança dos Endpoints AJAX

### Diagnóstico

Endpoints AJAX precisam verificar: (1) usuário autenticado, (2) token CSRF válido, (3) direito mínimo de leitura, e (4) direito específico por ação.

### Resultado da Verificação

Ambos os arquivos ajax implementam proteção em 4 camadas no topo do arquivo:

```php
Session::checkLoginUser();          // Camada 1: usuário logado
Session::checkCSRF($_POST);         // Camada 2: token CSRF válido (GLPI 11)
Session::checkRight($right, READ);  // Camada 3: direito mínimo de leitura
// Dentro de cada case:
Session::checkRight($right, CREATE | UPDATE | DELETE); // Camada 4: direito por ação
```

**Conclusão:** C2 encerrado como **Confirmado correto**. Nenhuma alteração necessária.

---

## Correção A1 — Migração de `echo` HTML para Twig

### Diagnóstico

Os 4 arquivos principais (`Company.php`, `Ipbx.php`, `Chatbot.php`, `FixedLine.php`) geravam HTML via `echo` PHP, misturando lógica e apresentação. Isso torna o código difícil de manter, impede reutilização de templates e elimina o escape automático do Twig.

### Solução Implementada

Para cada arquivo foi criado um template Twig equivalente e o método PHP foi refatorado para usar `TemplateRenderer::display()`.

**Vantagem principal:** o `htmlspecialchars()` manual que existia em todos os arquivos foi removido — o Twig faz escape automático em todos os `{{ }}`, tornando o código mais limpo e seguro por padrão.

### Pull Requests Executados

| PR | Arquivo | Branch | Linhas antes | Linhas depois | Redução |
|----|---------|--------|:------------:|:-------------:|:-------:|
| [#5](https://github.com/JoaoLucascp/newmanagement/pull/5) | `Company.php` | `fix/a1-twig-company` | ~180 | ~70 | −61% |
| [#6](https://github.com/JoaoLucascp/newmanagement/pull/6) | `Ipbx.php` | `fix/a1-twig-ipbx` | ~280 | ~100 | −64% |
| [#7](https://github.com/JoaoLucascp/newmanagement/pull/7) | `Chatbot.php` | `fix/a1-twig-chatbot` | ~370 | ~110 | −70% |
| [#8](https://github.com/JoaoLucascp/newmanagement/pull/8) | `FixedLine.php` | `fix/a1-twig-fixedline` | ~100 | ~65 | −35% |

Todos os PRs foram mergeados via **squash** na branch `main`.

### Estrutura de Templates Criada

```
templates/
├── company/
│   └── tab.html.twig
├── ipbx/
│   ├── tab.html.twig
│   └── partials/
│       ├── extensions.html.twig
│       ├── devices.html.twig
│       ├── network.html.twig
│       └── lines.html.twig
├── chatbot/
│   ├── tab.html.twig
│   └── partials/
│       ├── users.html.twig
│       ├── mass_comm.html.twig
│       └── wa_restrictions.html.twig
└── fixedline/
    └── tab.html.twig
```

### Padrão de Refatoração Aplicado

**Antes (PHP com echo):**
```php
public static function displayTabContentForCompany(int $companies_id): void {
    $canUpdate = Session::haveRight(static::$rightname, UPDATE);
    $item = /* busca no banco */;
    $v = fn($k) => htmlspecialchars($item[$k] ?? '', ENT_QUOTES);

    echo "<form method='post' ...>";
    echo "<input name='name' value='" . $v('name') . "'>";
    // ... ~150 linhas de echo
    echo "</form>";
}
```

**Depois (PHP + Twig):**
```php
public static function displayTabContentForCompany(int $companies_id): void {
    $canUpdate = Session::haveRight(static::$rightname, UPDATE);
    $item = /* busca no banco */;

    TemplateRenderer::getInstance()->display(
        '@newmanagement/company/tab.html.twig',
        [
            'item'       => $item,
            'can_update' => $canUpdate,
            'csrf_token' => Session::getNewCSRFToken(),
        ]
    );
}
```

---

## Verificação A3 — JavaScript em Arquivo Externo

### Diagnóstico

O audit inicial apontou ~100 linhas de JavaScript inline em `Company.php`. Foi necessário verificar se o código havia sido movido para `public/js/`.

### Resultado da Verificação

O JavaScript responsável pela integração com a BrasilAPI (consulta de CNPJ e CEP) já estava corretamente organizado em `public/js/`, separado do PHP. **A3 encerrado como Confirmado correto.**

---

## Verificação A4 — Índices no Banco de Dados

### Diagnóstico

O campo `name`, usado como critério principal de busca em listagens, não tinha índice declarado nas tabelas principais, causando full-table-scan em ambientes com muitos registros.

### Resultado da Verificação

O arquivo `hook.php` já continha os índices necessários tanto no `CREATE TABLE` (novas instalações) quanto no bloco `else` da migration (bancos já existentes):

#### Novas instalações

```sql
-- glpi_plugin_newmanagement_companies
PRIMARY KEY (`id`),
KEY `name` (`name`)

-- glpi_plugin_newmanagement_tasks
PRIMARY KEY (`id`),
KEY `name` (`name`),
KEY `companies_id` (`companies_id`)
```

#### Bancos já existentes (migration)

```php
// Migration idempotente — ignora se o índice já existir
$migration->addKey('glpi_plugin_newmanagement_companies', 'name');
$migration->addKey('glpi_plugin_newmanagement_tasks', 'name');
```

O método `addKey()` do GLPI é idempotente: se o índice já existir, a operação é ignorada sem erro. **A4 encerrado como Confirmado correto.**

---

## Estrutura do Banco de Dados

O plugin cria 12 tabelas no MySQL/MariaDB, todas com `ENGINE=InnoDB`, charset e collation definidos via helpers do GLPI.

| Tabela | Descrição | Chaves |
|--------|-----------|--------|
| `companies` | Entidade principal | PK `id`, KEY `name` |
| `ipbx` | IPBX On-Premise | PK `id`, FK `companies_id` |
| `ipbx_cloud` | IPBX Cloud | PK `id`, FK `companies_id` |
| `ipbx_extensions` | Ramais do IPBX | PK `id`, FK `ipbx_id`, `companies_id` |
| `ipbx_devices` | Dispositivos | PK `id`, FK `ipbx_id`, `companies_id` |
| `ipbx_network` | Configuração de rede | PK `id`, FK `ipbx_id`, `companies_id` |
| `ipbx_lines` | Linhas fixas (SIP/ISDN) | PK `id`, FK `ipbx_id`, `companies_id` |
| `tasks` | Tarefas com GPS | PK `id`, KEY `name`, FK `companies_id` |
| `chatbots` | Chatbot principal | PK `id`, FK `companies_id` |
| `chatbot_mass_comm` | Comunicação em massa | PK `id`, FK `chatbot_id`, `companies_id` |
| `chatbot_wa_restrictions` | Restrições WhatsApp | PK `id`, FK `chatbot_id`, `companies_id` |
| `chatbot_users` | Usuários do chatbot | PK `id`, FK `chatbot_id`, `companies_id` |

> **Campos padrão GLPI em todas as tabelas:** `date_creation`, `date_mod`, `is_deleted`.  
> **Campos de senha** usam tipo `TEXT` com `COMMENT 'sodiumEncrypt'` para documentar a intenção de criptografia.

---

## Arquitetura do Plugin

```
newmanagement/
├── ajax/
│   ├── ipbx_sub.php        ← CRUD AJAX: IPBX, ramais, devices, rede, linhas
│   ├── chatbot_sub.php     ← CRUD AJAX: chatbot e sub-entidades
│   └── cnpj_email.php      ← Consulta BrasilAPI (CNPJ + CEP)
├── front/                  ← Entry points para listagens e formulários GLPI
├── public/js/              ← JavaScript externo (BrasilAPI, interações UI)
├── src/
│   ├── Company.php         ← Entidade principal
│   ├── Ipbx.php            ← IPBX On-Premise
│   ├── Chatbot.php         ← Chatbot
│   └── FixedLine.php       ← Linha Fixa
├── templates/              ← Templates Twig (pós-migração A1)
│   ├── company/
│   ├── ipbx/
│   ├── chatbot/
│   └── fixedline/
├── hook.php                ← Install / Uninstall / Migration
├── setup.php               ← Definição de versão e autoload
└── locales/                ← Internacionalização
```

---

## Checklist de Segurança Final

| Verificação | Status |
|-------------|--------|
| Usuário autenticado verificado em todos os endpoints | ✅ |
| Token CSRF validado em todas as ações POST | ✅ |
| Direito mínimo READ verificado em todos os endpoints | ✅ |
| Direito granular (CREATE/UPDATE/DELETE) por ação | ✅ |
| Senhas criptografadas com `sodiumEncrypt` antes de salvar | ✅ |
| Senhas de update só alteradas quando campo preenchido | ✅ |
| Escape de output via Twig (automático) | ✅ |
| Nenhuma senha em texto plano no banco | ✅ |
| Stack trace não vazado para o cliente (apenas logado) | ✅ |

---

## Histórico de Commits e PRs

| PR | Título | Merge |
|----|--------|-------|
| [#5](https://github.com/JoaoLucascp/newmanagement/pull/5) | `fix(A1): migra Company.php de echo HTML para template Twig` | ✅ Mergeado |
| [#6](https://github.com/JoaoLucascp/newmanagement/pull/6) | `fix(A1): migra Ipbx.php de echo HTML para template Twig` | ✅ Mergeado |
| [#7](https://github.com/JoaoLucascp/newmanagement/pull/7) | `fix(A1): migra Chatbot.php de echo HTML para template Twig` | ✅ Mergeado |
| [#8](https://github.com/JoaoLucascp/newmanagement/pull/8) | `fix(A1): migra FixedLine.php de echo HTML para template Twig` | ✅ Mergeado |

---

## Conclusão

O plugin Newmanagement **está aprovado para uso em produção**. Todos os problemas identificados na auditoria foram resolvidos ou confirmados como já corretos. A migração para Twig (A1) foi a maior entrega: reduziu o código PHP em média 58%, eliminou `htmlspecialchars()` manual e estabelece uma base de templates reutilizáveis para novas funcionalidades.

### Próximos passos recomendados (backlog futuro)

- Adicionar testes automatizados com PHPUnit para as entidades principais
- Implementar paginação nas sub-tabelas (ramais, dispositivos) para empresas com muitos registros
- Adicionar log de auditoria (quem alterou qual senha e quando)
- Considerar migração do `update_chatbot` para usar `$DB->beginTransaction()` dado que ele deleta e re-insere múltiplas sub-tabelas atomicamente
