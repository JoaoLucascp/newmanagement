# CHANGELOG — Newmanagement

Todas as mudanças notáveis neste projeto são documentadas aqui.
Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

---

## [Unreleased] — branch `refactor/hook-modules`

### Corrigido

- **[A1]** `src/Chatbot.php` — senha decriptada não é mais enviada ao template Twig;
  o front-end recebe apenas `has_admin_password` (bool) para indicar existência da senha.
- **[A2]** `src/Task.php` — query direta em `glpi_plugin_newmanagement_companies`
  substituída por `getAllDataFromTable(Company::getTable(), ...)` com respeito ao modelo.
- **[M1]** `setup.php` — namespace Twig `@newmanagement` registrado via hook
  `TWIG_ENV_UPDATE` (GLPI ≥ 11.0.7) com fallback para GLPI 11.0.0–11.0.6.
  Função `plugin_newmanagement_register_twig_namespace()` extraída para evitar duplicação.
- **[M2]** `ajax/ipbx_sub.php` — bloco `catch` final não mais expõe `$e->getMessage()`
  ao cliente. Detalhe do erro vai para `Toolbox::logError()`; cliente recebe mensagem
  genérica internacionalizada.
- **[M3]** `ajax/ipbx_sub.php` — detecção de CSRF alterada de heurística por header HTTP
  para `version_compare(GLPI_VERSION, '11.0.0', '<')`, eliminando falso-positivo
  em ambientes com proxies que removem headers customizados.
- **[M4]** `src/Company.php` — `'massiveaction' => false'` adicionado nos campos
  `cnpj`, `address` e `comment` do `rawSearchOptions()` para impedir edição em lote
  em campos com validação customizada.
- **[M5]** `src/Task.php` — `getTabNameForItem` agora filtra por entidade via
  `getEntitiesRestrictCriteria()` no contador da aba.
- **[M6]** `src/Chatbot.php` — `getTabNameForItem` retorna contador de itens
  via `self::createTabEntry()`, alinhado com o padrão das demais abas do plugin.
- **[B1]** `setup.php` — `PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION` alterado de
  `'11.0.99'` para `'11.99.99'` para não bloquear ativação em GLPI 11.1+.
- **[B2]** `hook.php` — lógica de install/uninstall/upgrade separada em
  arquivos modulares em `hook/`.
- **[B3]** `templates/.gitkeep` removido.
- **[B4]** `Doc/DOCUMENTACAO_NEWMANAGEMENT (2).md` removido; conteúdo consolidado
  nesta pasta `Doc/`.

### Adicionado

- Pasta `Doc/` com documentação técnica completa:
  `README.md`, `ARCHITECTURE.md`, `INSTALL.md`, `DEVELOPMENT.md`,
  `CHANGELOG.md`, `AUDIT_REPORT.md`.

---

## [1.0.0] — versão inicial

### Adicionado

- Módulo **Empresa** com CNPJ, contato, endereço e documentos anexos
- Módulo **IPBX** com ramais, dispositivos SIP, configuração de rede e linha fixa
- Módulo **Chatbot** com credenciais criptografadas via libsodium
- Módulo **Tarefas** vinculadas à empresa
- Controle de permissões por perfil GLPI
- Compatibilidade com GLPI 11.0.0
- Scripts de install, upgrade e uninstall via `hook.php`
- CSRF token single-use com renovação a cada resposta JSON
