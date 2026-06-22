# 🗺️ ROTEIRO DE DESENVOLVIMENTO - ROADMAP 2026

## 📌 Visão Geral

Este documento define o caminho crítico para evolução do plugin Newmanagement de v1.0.0 até v2.0.0, com foco em:

- ✅ Conformidade 100% com GLPI 11.0.6
- ✅ Qualidade de código profissional
- ✅ Funcionalidades solicitadas
- ✅ Atendimento ao negócio

---

## 🎯 METAS ESTRATÉGICAS

### Curto Prazo (v1.0.1 - 1.1.0) — Próximos 30 dias

**Objetivo:** Estabilizar plugin atual e corrigir issues críticas

- [x] Audit completo de conformidade GLPI
- [x] Correção de vulnerabilidades de segurança
- [ ] Otimização de performance
- [x] Documentação técnica completa
- [ ] Testes automatizados básicos

### Médio Prazo (v1.2.0 - 1.3.0) — 60-90 dias

**Objetivo:** Implementar features solicitadas

- [x] Validação de CNPJ em tempo real (JS consulta BrasilAPI diretamente)
- [ ] Geolocalização completa em tarefas
- [ ] Assinatura digital
- [ ] Cálculo de quilometragem
- [ ] Webhooks e eventos

### Longo Prazo (v2.0.0) — 120+ dias

**Objetivo:** Transformação digital completa

- [ ] API REST
- [ ] Dashboard executivo
- [ ] Mobile app
- [ ] Integração com sistemas de telefonia
- [ ] SaaS ready

---

## 📋 SPRINTS DETALHADOS

### SPRINT 1: Audit & Conformidade (Semana 1-2) ✅ CONCLUÍDO

**Objetivo:** Garantir 100% conformidade com GLPI 11

#### Task 1.1: Análise Arquitetural ✅

- [x] Revisar namespace PSR-4
- [x] Validar herança CommonDBTM
- [x] Verificar hooks registration
- [x] Auditar segurança geral
- [x] Documentar findings

**Deliverables:**

- [x] DOCUMENTACAO_NEWMANAGEMENT.md
- [x] GUIA_DEBUG_OTIMIZACAO.md
- [x] PADROES_GLPI.md

#### Task 1.2: Segurança ✅

- [x] Scan SQL Injection risks
- [x] Verificar XSS vulnerabilities
- [x] Validar CSRF tokens
- [x] Audit input validation
- [x] Test access control

#### Task 1.3: Performance

- [ ] Profile database queries
- [ ] Identify N+1 patterns
- [x] Add missing indexes
- [ ] Implement caching
- [ ] Benchmark improvements

**Meta:** 200ms max avg response

#### Task 1.4: Code Quality

- [ ] PHPStan level 8 check
- [ ] PSR-12 compliance
- [x] Remove dead code (templates órfãos e cnpj_email.php removidos em 2026-06-21)
- [ ] Add PHPDoc
- [ ] Refactor duplicates

---

### SPRINT 2: Testes & Documentação (Semana 3-4) 🔄 EM PROGRESSO

**Objetivo:** Cobertura 80%+ de testes + docs completa

#### Task 2.1: PHPUnit Tests

- [ ] Setup PHPUnit configuration
- [ ] Create test directory structure
- [ ] Write Company tests
- [ ] Write Ipbx tests
- [ ] Write Chatbot tests
- [ ] Write FixedLine tests
- [ ] Write Task tests

**Arquivos a criar:**

```
tests/
├── Unit/
│   ├── CompanyTest.php
│   ├── IpbxTest.php
│   ├── ChatbotTest.php
│   ├── FixedLineTest.php
│   └── TaskTest.php
├── Integration/
│   ├── DatabaseMigrationTest.php
│   └── SecurityTest.php
└── bootstrap.php
```

#### Task 2.2: Code Analysis

- [ ] Setup PHPStan
- [ ] Setup PHP_CodeSniffer
- [ ] Configure GH Actions
- [ ] Add pre-commit hooks
- [ ] Document standards

#### Task 2.3: Documentation

- [ ] API documentation
- [ ] Database schema (DATABASE.md)
- [ ] Security guide (SECURITY.md)
- [ ] Contributing guide
- [x] CHANGELOG maintenance (iniciado em 2026-06-21)

**Arquivos a criar:**

- [x] Doc/DOCUMENTACAO_NEWMANAGEMENT.md
- [x] Doc/GUIA_DEBUG_OTIMIZACAO.md
- [x] Doc/PADROES_GLPI.md
- [ ] Doc/DATABASE.md
- [ ] Doc/SECURITY.md
- [ ] Doc/CONTRIBUTING.md
- [x] Doc/CHANGELOG.md

#### Task 2.4: CI/CD Pipeline

- [ ] GitHub Actions workflow
- [ ] Auto tests on PR
- [ ] Code coverage reports
- [ ] SonarQube integration
- [ ] Auto deploy on release

---

### SPRINT 3: Features - CNPJ & Validação (Semana 5-6) 🔄 PARCIALMENTE CONCLUÍDO

**Objetivo:** Implementar validação de CNPJ + Email em tempo real

#### Task 3.1: CNPJ Validation ✅ (abordagem alternativa)

- [x] Validação via algoritmo no backend (`Company::isValidCnpj()`)
- [x] Consulta BrasilAPI integrada diretamente no frontend JS (`public/js/company-form.js`)
- [ ] Criar classe `CnpjValidator` standalone (opcional — backend já valida)
- [ ] Log de tentativas inválidas

#### Task 3.2: Email Validation

- [ ] Verify email format
- [ ] DNS MX record check
- [ ] SMTP verification (optional)
- [ ] Cache results
- [ ] Handle timeouts

#### Task 3.3: AJAX Endpoints ~~cnpj_email.php~~ — CANCELADO

> ~~Refactor cnpj_email.php~~
> **2026-06-21:** `ajax/cnpj_email.php` foi **removido** — o JS já consultava a BrasilAPI diretamente,
> tornando o endpoint obsoleto. Nenhuma refatoração necessária.

- [x] ~~Refactor cnpj_email.php~~ → Removido (obsoleto)
- [x] Return structured JSON (BrasilAPI retorna JSON diretamente)
- [ ] Rate limiting no frontend

#### Task 3.4: Frontend Integration ✅

- [x] Add real-time validation
- [x] Show loading spinners
- [x] Display error messages
- [x] Auto-fill company data
- [x] Update on input change

---

### SPRINT 4: Tarefas & Geolocalização (Semana 7-8) ⏳ AGENDADO

**Objetivo:** Implementar geolocalização completa em tarefas

#### Task 4.1: Geolocation Service

- [ ] Setup Google Maps API (ou alternativa open-source)
- [ ] Request location permission
- [ ] Capture lat/long
- [ ] Save coordinates
- [ ] Calculate distance

#### Task 4.2: Task Model Enhancement

```sql
ALTER TABLE glpi_plugin_newmanagement_tasks ADD COLUMN
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  address VARCHAR(255),
  geofence_radius INT,
  km_traveled DECIMAL(8, 2);
```

#### Task 4.3: Distance Calculation

```php
class Task {
    public function calculateDistance(
        float $lat1, float $long1,
        float $lat2, float $long2
    ): float {
        // Haversine formula
    }
}
```

#### Task 4.4: Map Integration

- [ ] Embed mapa (Google Maps ou Leaflet)
- [ ] Show task locations
- [ ] Draw routes
- [ ] Show statistics
- [ ] Export route

**Arquivos a criar:**

- src/Services/GeolocationService.php
- src/Services/DistanceCalculator.php
- public/js/maps.js
- templates/task/map.html.twig

---

### SPRINT 5: Assinatura Digital (Semana 9-10) ⏳ AGENDADO

**Objetivo:** Implementar assinatura digital em tarefas

#### Task 5.1: Signature Capture

- [ ] Implement canvas signature (Signature Pad ou similar)
- [ ] Save as image
- [ ] Store in DB
- [ ] Associate with user
- [ ] Add timestamp

#### Task 5.2: Database Storage

```sql
ALTER TABLE glpi_plugin_newmanagement_tasks ADD
  signature_path VARCHAR(255),
  signature_user_id INT,
  signature_date TIMESTAMP,
  signature_verified TINYINT;
```

#### Task 5.3: Frontend

- [ ] Canvas element
- [ ] Draw functionality
- [ ] Clear button
- [ ] Save handler
- [ ] Preview signature

#### Task 5.4: Validation & Compliance

- [ ] Verify signature exists
- [ ] Log signature action
- [ ] Validate authenticity
- [ ] Store audit trail
- [ ] LGPD compliance

**Arquivos a criar:**

- public/js/signature.js
- src/Services/SignatureService.php
- templates/task/signature.html.twig

---

### SPRINT 6: API REST (Semana 11-12) ⏳ AGENDADO

**Objetivo:** Criar API REST para integrações

```
GET    /api/companies
POST   /api/companies
GET    /api/companies/{id}
PUT    /api/companies/{id}
DELETE /api/companies/{id}
GET    /api/companies/{id}/ipbx
POST   /api/tasks/{id}/signature
```

**Arquivos a criar:**

- src/API/ (novo diretório)
- Doc/API.md
- openapi.yaml

---

### SPRINT 7: Webhooks & Eventos (Semana 13-14) ⏳ AGENDADO

**Objetivo:** Implementar sistema de webhooks

Eventos planejados:
```
company.created / company.updated / company.deleted
ipbx.created
task.completed
signature.added
```

---

### SPRINT 8: Dashboard (Semana 15-16) ⏳ AGENDADO

**Objetivo:** Criar dashboard executivo

- [ ] KPIs: total empresas, contratos ativos, contratos expirando, tarefas concluídas
- [ ] Charts: status de contrato (pizza), tarefas por período, distribuição geográfica
- [ ] Cache de métricas + export de relatórios

---

## 🛠️ TECNOLOGIAS RECOMENDADAS

| Camada | Tecnologia |
|--------|------------|
| Backend | PHP 8.1+, GLPI 11+, MySQL 8.0 / MariaDB 10.5+ |
| Frontend | Twig 3+, Bootstrap 5 (GLPI padrão), Vanilla JS |
| Maps | Google Maps API ou Leaflet (open-source) |
| Signature | Signature Pad |
| Charts | Chart.js |
| Testing | PHPUnit 10+, PHPStan, PHP_CodeSniffer |
| DevOps | GitHub Actions, Composer, Docker (opcional) |

---

## 📊 MÉTRICAS DE SUCESSO

| Métrica | v1.0.0 | v1.1.0 | v2.0.0 |
|---------|--------|--------|--------|
| Stability | 70% | 95%+ | 99%+ |
| Test Coverage | 0% | 80%+ | 95%+ |
| Security Issues | 5+ | 0 | 0 |
| Performance (ms) | 400+ | <200 | <100 |
| Documentation | 30% | 100% | 100% |
| API Endpoints | 0 | 0 | 20+ |

---

## 🔄 VERSIONING & RELEASES

```
v1.0.0 — Initial Release (maio/2026)
v1.0.1 — Bug fixes: CSRF, toggle booleano, INSERT ramais, limpeza de órfãos (junho/2026)
v1.1.0 — CNPJ validation + features
v1.2.0 — Geolocation complete
v1.3.0 — Digital signature
v2.0.0 — REST API + Dashboard
```

---

## 📅 TIMELINE ATUALIZADA

```
Maio 2026
├── Sprint 1 (1-2) ✅ Audit & Conformidade
├── Sprint 2 (3-4) ✅ Testes & Docs (parcial)
│
Junho 2026
├── Sprint 3 (5-6) ✅ CNPJ Validation (concluído via BrasilAPI direta)
│   └── 2026-06-21: correções CSRF, toggle, INSERT, limpeza de órfãos → v1.0.1
├── Sprint 4 (7-8) ⏳ Geolocalização
│
Julho 2026
├── Sprint 5 (9-10) ⏳ Assinatura Digital
├── Sprint 6 (11-12) ⏳ API REST
│
Agosto 2026
├── Sprint 7 (13-14) ⏳ Webhooks
├── Sprint 8 (15-16) ⏳ Dashboard
│
Setembro 2026
└── Stabilization & v2.0.0 Release

Legenda: ✅ Concluído | 🔄 Em Progresso | ⏳ Agendado
```

---

**Documento versão:** 1.0.1  
**Criado em:** 20/05/2026  
**Atualizado em:** 21/06/2026  
**Próxima revisão:** Após Sprint 4
