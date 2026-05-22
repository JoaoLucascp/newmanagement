# Relatório de Auditoria Técnica — Plugin Newmanagement

**Versão auditada:** 1.0.0  
**GLPI alvo:** 11.0.6  
**Data:** 2026-05-22  
**Auditor:** Assistente Sênior de Engenharia de Software  

---

## Resumo Executivo

O plugin está em estado funcional avançado e demonstra bom conhecimento da API do GLPI 11.
A maioria das práticas críticas (CSRF, permissões, `TemplateRenderer`, `CommonDBTM`, `DBmysql`) estão corretas.
Os problemas encontrados são majoritariamente de severidade **média e baixa**, com dois pontos **altos** relacionados a segurança e arquitetura.

**Nenhum problema crítico** (injeção SQL, bypass de autenticação, RCE) foi identificado.

---

## Checklist de Segurança

| Item | Status | Evidência |
|---|---|---|
| Injeção SQL | ✅ OK | `$DB->insert/update/delete` com arrays parametrizados |
| XSS | ✅ OK | Twig auto-escapa por padrão; `htmlspecialchars` no fallback |
| CSRF | ✅ Corrigido [M3] | Token single-use + detecção por versão GLPI |
| Senhas em texto puro no DOM | ✅ Corrigido [A1] | Nunca mais envia senha decriptada ao template |
| Permissões por direito | ✅ OK | `Session::checkRight()` em todas as ações |
| Erro vaza dados internos | ✅ Corrigido [M2] | `$e->getMessage()` vai apenas para o log |
| Validação CNPJ no backend | ✅ OK | `prepareInputForAdd/Update` com algoritmo completo |
| Senha encriptada com Sodium | ✅ OK | `Toolbox::sodiumEncrypt` em todos os campos sensíveis |

---

## Lista Priorizada de Problemas

### 🟠 Alto

#### [A1] `src/Chatbot.php` — Senhas decriptadas enviadas ao front-end

- **Impacto:** Qualquer usuário com READ no perfil via a senha em texto puro no DOM
- **OWASP:** A02 — Cryptographic Failures
- **Status:** ✅ Corrigido
- **Correção:** Template recebe `has_admin_password` (bool); senha nunca sai do servidor

#### [A2] `src/Task.php` — Query direta bypassando modelo

- **Impacto:** Registros deletados ou de outras entidades podem aparecer
- **Status:** ✅ Corrigido
- **Correção:** `getAllDataFromTable(Company::getTable(), ...)` com filtros do modelo

---

### 🟡 Médio

#### [M1] `setup.php` — Registro Twig acoplado à ordem de init

- **Impacto:** Pode quebrar silenciosamente em versões futuras do GLPI
- **Status:** ✅ Corrigido
- **Correção:** Hook `TWIG_ENV_UPDATE` com fallback para GLPI 11.0.0–11.0.6

#### [M2] `ajax/ipbx_sub.php` — Erro interno vaza ao cliente

- **Impacto:** Information disclosure — nomes de tabelas, queries SQL expostos
- **OWASP:** A05 — Security Misconfiguration
- **Status:** ✅ Corrigido
- **Correção:** `Toolbox::logError()` para o servidor; mensagem genérica ao cliente

#### [M3] `ajax/ipbx_sub.php` — Heurística de CSRF frágil

- **Impacto:** Proxy remove header → proteção cai para caminho errado
- **Status:** ✅ Corrigido
- **Correção:** `version_compare(GLPI_VERSION, '11.0.0', '<')`

#### [M4] `src/Company.php` — `massiveaction` em campos com validação

- **Impacto:** Edição em lote de CNPJ sem validação do algoritmo
- **Status:** ✅ Corrigido
- **Correção:** `'massiveaction' => false` nos campos `cnpj`, `address`, `comment`

#### [M5] `src/Task.php` — Contador de aba sem escopo de entidade

- **Impacto:** Em multi-entidade, contador inclui tarefas de outras entidades
- **Status:** ✅ Corrigido
- **Correção:** `getEntitiesRestrictCriteria()` adicionado ao `countElementsInTable`

#### [M6] `src/Chatbot.php` — Aba sem contador

- **Impacto:** UX inconsistente com demais abas
- **Status:** ✅ Corrigido
- **Correção:** `self::createTabEntry(self::getTypeName(1), $count)`

---

### 🔵 Baixo

#### [B1] `setup.php` — `MAX_GLPI_VERSION` trava atualizações

- **Status:** ✅ Corrigido — `11.0.99` → `11.99.99`

#### [B2] `hook.php` — Arquivo monolítico de 37 KB

- **Status:** ✅ Corrigido — dividido em `hook/install.php`, `hook/uninstall.php`, `hook/upgrade.php`

#### [B3] `templates/.gitkeep` vazio na raiz

- **Status:** ✅ Corrigido — removido

#### [B4] `Doc/` — arquivo com nome inválido

- **Status:** ✅ Corrigido — `DOCUMENTACAO_NEWMANAGEMENT (2).md` removido; conteúdo consolidado em `Doc/`

---

## Pontos Positivos

- ✅ Namespace PSR-4 (`GlpiPlugin\Newmanagement`) correto
- ✅ `TemplateRenderer::getInstance()->display('@newmanagement/...')` usado corretamente
- ✅ CSRF token single-use com renovação a cada resposta JSON
- ✅ Validação de CNPJ implementada no backend com algoritmo completo
- ✅ `Toolbox::sodiumEncrypt` para senhas — uso correto da API nativa do GLPI
- ✅ `Plugin::getWebDir()` em vez de URLs hardcoded
- ✅ `Session::checkRight()` em todas as ações AJAX
- ✅ `defineTabs()` com abas nativas do GLPI (`Document_Item`, `Log`, `Notepad`)

---

## Relatório de Compatibilidade com GLPI 11.0.6

| Área | Compatível | Observação |
|---|---|---|
| Autoloading PSR-4 | ✅ | Namespace correto |
| TemplateRenderer / Twig | ✅ | Hook `TWIG_ENV_UPDATE` após fix M1 |
| DBmysql API | ✅ | Usa `$DB->insert/update/delete/request` |
| CommonDBTM | ✅ | Herança e métodos corretos |
| CSRF Symfony | ✅ | Após fix M3 |
| Session / Rights | ✅ | `checkRight`, `checkLoginUser` |
| Hooks GLPI 11 | ✅ | `MENU_TOADD`, `csrf_compliant` |
| Sodium encrypt | ✅ | `Toolbox::sodiumEncrypt/Decrypt` |
| Multi-entidade | ⚠️ Parcial | Fix M5 corrige contador; outras áreas a verificar |

### Recomendações para versões futuras

1. Adicionar testes de integração antes de cada release
2. Usar `GLPI_ENVIRONMENT_TYPE=development` durante desenvolvimento para mais logs
3. Revisar suporte a multi-entidade em todos os `rawSearchOptions`
4. Quando GLPI 12 for lançado, revisar breaking changes na API de hooks

---

## Comandos para Reproduzir Ambiente de Teste

```bash
# 1. Subir GLPI 11.0.6 com Docker
docker run -d --name glpi \
  -e GLPI_DB_HOST=mysql \
  -e GLPI_DB_NAME=glpi \
  -e GLPI_DB_USER=glpi \
  -e GLPI_DB_PASSWORD=glpi \
  -p 8080:80 \
  diouxx/glpi:11.0.6

# 2. Clonar plugin na branch corrigida
git clone -b refactor/hook-modules \
  https://github.com/JoaoLucascp/newmanagement.git \
  plugins/newmanagement

# 3. Executar testes
./vendor/bin/phpunit \
  --configuration plugins/newmanagement/phpunit.xml \
  plugins/newmanagement/tests/

# 4. Verificar logs
tail -f files/_log/php-errors.log
```
