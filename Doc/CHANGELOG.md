# Changelog — Newmanagement

Todas as mudanças notáveis deste projeto são documentadas aqui.
Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

---

## [Unreleased] — 2026-06-21

### Corrigido
- `[CSRF-1]` `templates/chatbot/form.html.twig` — `{{ csrf_token() }}` (função inexistente no Twig do GLPI) substituído por `{{ csrf_token|e }}` (variável passada via `showForm()`); token estava chegando vazio e o GLPI rejeitava o POST silenciosamente
- `[CSRF-2]` `templates/fixedline/form.html.twig` — mesma correção
- `[CSRF-3]` `templates/chatbot/tab.html.twig` — adicionado filtro `|e` no `{{ csrf }}`
- `[CSRF-4]` `templates/ipbx/form.html.twig` — correção aplicada antes da remoção do arquivo
- `[TOGGLE]` `public/js/newmanagement.js` — flag `window._nmToggleBoolDelegated` renomeada para `document._nmToggleBoolDelegated`, padronizando com `partials/extensions.html.twig`; divergência causava registro duplicado do listener `change`, disparando o toggle booleano duas vezes por clique
- `[INSERT]` `ajax/ipbx_sub.php` action `add_extension` — INSERT omitia as 6 colunas booleanas (`lof`, `loc`, `ddf`, `ddc`, `ddi`, `srv`); colunas adicionadas com `intval()` para garantir `0/1`

### Adicionado
- `[TOGGLE-INLINE]` `ajax/ipbx_sub.php` — nova action `update_extension_field` para atualizar campo booleano individual do ramal via toggle inline; inclui whitelist de campos seguros e verificação de ownership por `companies_id`

### Removido
- `[CLEANUP-1]` `templates/ipbx/form.html.twig` — órfão; fluxo do IPBX é 100% por abas, template nunca era chamado
- `[CLEANUP-2]` `templates/ipbx/tab_devices.html.twig` — órfão; substituído por `partials/devices.html.twig`
- `[CLEANUP-3]` `templates/ipbx/tab_extensions.html.twig` — órfão; substituído por `partials/extensions.html.twig`
- `[CLEANUP-4]` `templates/ipbx/tab_network.html.twig` — órfão; substituído por `partials/network.html.twig`
- `[CLEANUP-5]` `ajax/cnpj_email.php` — órfão; o JS já consultava a BrasilAPI diretamente, arquivo nunca era chamado

---

## [1.0.0] — 2026-05-22

### Adicionado
- Módulo **Empresas** com CNPJ validado, razão social, contato e status de contrato
- Módulo **IPBX On-Premise** com ramais, dispositivos e redes paginados (server-side, 20 por página)
- Módulo **Linha Fixa** com validação de número de telefone brasileiro (DDD + 8/9 dígitos)
- Módulo **Chatbot** com usuários, comunicações em massa e restrições WhatsApp
- Módulo **Tarefas** com geolocalização vinculado à empresa
- Proteção CSRF single-use token compatível com GLPI 10 e 11
- Senhas armazenadas com `Toolbox::sodiumEncrypt()` — nunca expostas ao frontend
- Suporte a GLPI 11.0.0 – 11.99.99

### Segurança
- `[A1]` Senhas nunca descriptografadas para o contexto Twig — apenas booleanos `has_*_password`
- `[A2]` Queries de company usam `getAllDataFromTable()` em vez de SQL direto
- `[M2]` Exceções logadas no servidor; cliente recebe mensagem genérica
- `[M3]` CSRF detectado via `version_compare(GLPI_VERSION, '11.0.0', '<')` — não por header HTTP
- `[M4]` Campos com validação custom (`cnpj`, `address`, `comment`) têm `massiveaction: false`

### Correções de arquitetura
- `[M1]` Namespace Twig `@newmanagement` registrado via hook `TWIG_ENV_UPDATE` (GLPI ≥ 11.0.7) com fallback seguro para 11.0.0–11.0.6
- `[M5]` Contador de aba de Tarefas inclui escopo de entidade (`getEntitiesRestrictCriteria`)
- `[M6]` Aba Chatbot exibe contador de registros via `createTabEntry()`
- `[B1]` Versão máxima do GLPI alterada de `11.0.99` para `11.99.99`
- `[PHP84]` Propriedades estáticas `$rightname`, `$itemtype`, `$items_id` tipadas como `string` em todas as classes
