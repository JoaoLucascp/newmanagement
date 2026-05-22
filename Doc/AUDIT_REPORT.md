# Relatório de Auditoria — Newmanagement × GLPI 11.0.6

**Data:** 2026-05-22  
**Auditor:** Engenheiro Sênior GLPI (assistido por IA)  
**Versão auditada:** 1.0.0  
**GLPI alvo:** 11.0.6  
**Status:** ✅ Todos os itens resolvidos

---

## Resumo executivo

O plugin apresenta arquitetura sólida e uso correto das APIs nativas do GLPI 11. Os problemas encontrados foram majoritariamente de severidade média e baixa. Dois itens de alta severidade foram identificados e corrigidos: exposição de senhas em texto puro no template (A1) e uso de query direta ignorando o modelo (A2). Nenhum problema crítico foi detectado.

---

## Lista priorizada de problemas

### 🟠 Alta severidade

| ID | Arquivo | Problema | Correção aplicada |
|---|---|---|---|
| A1 | `src/Chatbot.php` | `sodiumDecrypt()` passava senhas em texto puro ao template Twig | Removida descriptografia; template recebe apenas booleanos `has_*_password` |
| A2 | `src/Task.php` | Query direta em `glpi_plugin_newmanagement_companies` ignorava softdelete e permissões | Substituído por `getAllDataFromTable(Company::getTable(), ...)` |

### 🟡 Média severidade

| ID | Arquivo | Problema | Correção aplicada |
|---|---|---|---|
| M1 | `setup.php` | Namespace Twig registrado diretamente no `plugin_init`, frágil à ordem de inicialização | Migrado para hook `TWIG_ENV_UPDATE` com fallback para GLPI 11.0.0–11.0.6 |
| M2 | `ajax/ipbx_sub.php` | `$e->getMessage()` retornado ao cliente — risco de information disclosure | Log no servidor via `Toolbox::logError()`; cliente recebe mensagem genérica |
| M3 | `ajax/ipbx_sub.php` | Detecção CSRF por presença de header HTTP — falso positivo em proxies que removem headers | Substituído por `version_compare(GLPI_VERSION, '11.0.0', '<')` |
| M4 | `src/Company.php` | Campos `cnpj`, `address`, `comment` sem `massiveaction: false` — edição em lote bypassava validação | Adicionado `'massiveaction' => false` nas search options |
| M5 | `src/Task.php` | Contador da aba sem `getEntitiesRestrictCriteria` — contava tarefas de outras entidades | Adicionado `getEntitiesRestrictCriteria(self::getTable())` ao `countElementsInTable` |
| M6 | `src/Chatbot.php` | `getTabNameForItem()` retornava string estática sem contador | Refatorado para usar `createTabEntry()` com `countElementsInTable()` |

### 🔵 Baixa severidade

| ID | Arquivo | Problema | Correção aplicada |
|---|---|---|---|
| B1 | `setup.php` | `MAX_GLPI_VERSION = '11.0.99'` bloqueava GLPI 11.1+ | Alterado para `'11.99.99'` |
| PHP84 | Múltiplos | Propriedades estáticas sem tipo explícito — deprecation no PHP 8.4 | Adicionado `string` em `$rightname`, `$itemtype`, `$items_id` em todas as classes |

---

## Checklist de segurança

| Item | Status | Evidência |
|---|---|---|
| Injeção SQL | ✅ OK | Uso exclusivo de `$DB->insert/update/delete` com arrays parametrizados |
| XSS | ✅ OK | Twig auto-escapa por padrão; `htmlspecialchars` nos métodos `render*Row` |
| CSRF | ✅ OK | Token single-use + `version_compare` para compatibilidade 10/11 |
| Senhas em texto puro no DOM | ✅ Corrigido (A1) | Apenas booleanos chegam ao template |
| Permissões por direito | ✅ OK | `Session::checkRight()` em todas as ações AJAX |
| Erro vaza dados internos | ✅ Corrigido (M2) | `Toolbox::logError()` + mensagem genérica ao cliente |
| Validação CNPJ no backend | ✅ OK | Algoritmo completo em `Company::isValidCnpj()` |
| Senha encriptada com Sodium | ✅ OK | `Toolbox::sodiumEncrypt` em todos os campos de senha |
| Ação massiva em campos validados | ✅ Corrigido (M4) | `massiveaction: false` nos campos com validação custom |

---

## Pontos positivos identificados

- Namespace PSR-4 (`GlpiPlugin\Newmanagement`) correto e consistente
- `TemplateRenderer::getInstance()->display('@newmanagement/...')` usado corretamente
- CSRF token single-use com renovação a cada resposta JSON
- Validação de CNPJ e telefone implementadas no backend com algoritmos completos
- `Toolbox::sodiumEncrypt` para todas as senhas
- `Plugin::getWebDir()` em vez de URLs hardcoded
- `Session::checkRight()` em todas as ações AJAX
- `defineTabs()` com abas nativas do GLPI (`Document_Item`, `Log`, `Notepad`)
- Paginação server-side nas sub-tabelas IPBX (evita N+1 e sobrecarga de memória)

---

## Compatibilidade

| Ambiente | Status |
|---|---|
| GLPI 11.0.6 + PHP 8.2 | ✅ Compatível |
| GLPI 11.0.7+ | ✅ Compatível (usa `TWIG_ENV_UPDATE`) |
| GLPI 11.1.x (futuro) | ✅ Esperado compatível (`MAX = 11.99.99`) |
| PHP 8.4 | ✅ Compatível (propriedades tipadas) |
| GLPI 10.x | ❌ Não suportado (mínimo 11.0.0) |
