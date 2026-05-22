# Changelog — Newmanagement

Todas as mudanças notáveis deste projeto são documentadas aqui.
Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

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
