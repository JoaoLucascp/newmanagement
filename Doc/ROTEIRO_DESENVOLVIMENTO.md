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

- [ ] Audit completo de conformidade GLPI
- [ ] Correção de vulnerabilidades de segurança
- [ ] Otimização de performance
- [ ] Documentação técnica completa
- [ ] Testes automatizados básicos

### Médio Prazo (v1.2.0 - 1.3.0) — 60-90 dias

**Objetivo:** Implementar features solicitadas

- [ ] Validação de CNPJ em tempo real
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

### SPRINT 1: Audit & Conformidade (Semana 1-2)

**Objetivo:** Garantir 100% conformidade com GLPI 11

#### Task 1.1: Análise Arquitetural
```
[ ] Revisar namespace PSR-4
[ ] Validar herança CommonDBTM
[ ] Verificar hooks registration
[ ] Auditar segurança geral
[ ] Documentar findings
```

**Deliverables:**
- [x] DOCUMENTACAO_NEWMANAGEMENT.md
- [x] GUIA_DEBUG_OTIMIZACAO.md
- [x] PADROES_GLPI.md

#### Task 1.2: Segurança
```
[ ] Scan SQL Injection risks
[ ] Verificar XSS vulnerabilities
[ ] Validar CSRF tokens
[ ] Audit input validation
[ ] Test access control
```

**Correções esperadas:** 0-5 issues críticos

#### Task 1.3: Performance
```
[ ] Profile database queries
[ ] Identify N+1 patterns
[ ] Add missing indexes
[ ] Implement caching
[ ] Benchmark improvements
```

**Meta:** 200ms max avg response

#### Task 1.4: Code Quality
```
[ ] PHPStan level 8 check
[ ] PSR-12 compliance
[ ] Remove dead code
[ ] Add PHPDoc
[ ] Refactor duplicates
```

**Meta:** 0 errors, 80%+ test coverage

---

### SPRINT 2: Testes & Documentação (Semana 3-4)

**Objetivo:** Cobertura 80%+ de testes + docs completa

#### Task 2.1: PHPUnit Tests
```
[ ] Setup PHPUnit configuration
[ ] Create test directory structure
[ ] Write Company tests
[ ] Write Ipbx tests
[ ] Write Chatbot tests
[ ] Write FixedLine tests
[ ] Write Task tests
```

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
```
[ ] Setup PHPStan
[ ] Setup PHP_CodeSniffer
[ ] Configure GH Actions
[ ] Add pre-commit hooks
[ ] Document standards
```

#### Task 2.3: Documentation
```
[ ] API documentation
[ ] Database schema (DATABASE.md)
[ ] Security guide (SECURITY.md)
[ ] Contributing guide
[ ] CHANGELOG maintenance
```

**Arquivos a criar:**
- [x] Doc/DOCUMENTACAO_NEWMANAGEMENT.md
- [x] Doc/GUIA_DEBUG_OTIMIZACAO.md
- [x] Doc/PADROES_GLPI.md
- [ ] Doc/DATABASE.md
- [ ] Doc/SECURITY.md
- [ ] Doc/CONTRIBUTING.md
- [ ] CHANGELOG.md

#### Task 2.4: CI/CD Pipeline
```
[ ] GitHub Actions workflow
[ ] Auto tests on PR
[ ] Code coverage reports
[ ] SonarQube integration
[ ] Auto deploy on release
```

---

### SPRINT 3: Features - CNPJ & Validação (Semana 5-6)

**Objetivo:** Implementar validação de CNPJ + Email em tempo real

#### Task 3.1: CNPJ Validation
```php
// src/Common/CnpjValidator.php
[ ] Create validator class
[ ] Implement algorithm validation
[ ] Add API integration (optional)
[ ] Handle errors gracefully
[ ] Log invalid attempts
```

**Implementação:**
```php
namespace GlpiPlugin\Newmanagement\Common;

class CnpjValidator {
    public static function isValid(string $cnpj): bool {
        // Validação algoritmo
    }
    
    public static function lookup(string $cnpj): array {
        // Buscar dados em API pública
    }
}
```

#### Task 3.2: Email Validation
```
[ ] Verify email format
[ ] DNS MX record check
[ ] SMTP verification (optional)
[ ] Cache results
[ ] Handle timeouts
```

#### Task 3.3: AJAX Endpoints
```
[ ] Refactor cnpj_email.php
[ ] Add validation logic
[ ] Return structured JSON
[ ] Error handling
[ ] Rate limiting
```

#### Task 3.4: Frontend Integration
```
[ ] Add real-time validation
[ ] Show loading spinners
[ ] Display error messages
[ ] Auto-fill company data
[ ] Update on input change
```

**Arquivos a modificar:**
- ajax/cnpj_email.php (refactor completo)
- public/js/newmanagement.js (adicionar validação)
- templates/company/form.html.twig (atualizar)

---

### SPRINT 4: Tarefas & Geolocalização (Semana 7-8)

**Objetivo:** Implementar geolocalização completa em tarefas

#### Task 4.1: Geolocation Service
```php
// src/Services/GeolocationService.php
[ ] Setup Google Maps API (ou alternativa)
[ ] Request location permission
[ ] Capture lat/long
[ ] Save coordinates
[ ] Calculate distance
```

#### Task 4.2: Task Model Enhancement
```
// Add fields:
ALTER TABLE glpi_plugin_newmanagement_tasks ADD COLUMN:
- latitude DECIMAL(10, 8)
- longitude DECIMAL(11, 8)
- address VARCHAR(255)
- geofence_radius INT
- km_traveled DECIMAL(8, 2)
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
```
[ ] Embed Google Maps
[ ] Show task locations
[ ] Draw routes
[ ] Show statistics
[ ] Export route
```

**Arquivos a criar:**
- src/Services/GeolocationService.php
- src/Services/DistanceCalculator.php
- public/js/maps.js
- templates/task/map.html.twig

---

### SPRINT 5: Assinatura Digital (Semana 9-10)

**Objetivo:** Implementar assinatura digital em tarefas

#### Task 5.1: Signature Capture
```
[ ] Implement canvas signature
[ ] Save as image
[ ] Store in DB
[ ] Associate with user
[ ] Add timestamp
```

**Biblioteca:** Signature Pad ou similar

#### Task 5.2: Database Storage
```
ALTER TABLE glpi_plugin_newmanagement_tasks ADD:
- signature_path VARCHAR(255)
- signature_user_id INT
- signature_date TIMESTAMP
- signature_verified TINYINT
```

#### Task 5.3: Frontend
```
[ ] Canvas element
[ ] Draw functionality
[ ] Clear button
[ ] Save handler
[ ] Preview signature
```

#### Task 5.4: Validation & Compliance
```
[ ] Verify signature exists
[ ] Log signature action
[ ] Validate authenticity
[ ] Store audit trail
[ ] LGPD compliance
```

**Arquivos a criar:**
- public/js/signature.js
- src/Services/SignatureService.php
- templates/task/signature.html.twig

---

### SPRINT 6: API REST (Semana 11-12)

**Objetivo:** Criar API REST para integrações

#### Task 6.1: API Structure
```
GET    /api/companies             # Listar
POST   /api/companies             # Criar
GET    /api/companies/{id}        # Obter
PUT    /api/companies/{id}        # Atualizar
DELETE /api/companies/{id}        # Deletar

GET    /api/companies/{id}/ipbx   # Subitens
POST   /api/tasks/{id}/signature  # Assinatura
```

#### Task 6.2: Authentication
```
[ ] API Key auth
[ ] OAuth2 support
[ ] Rate limiting
[ ] CORS headers
[ ] Documentation
```

#### Task 6.3: Implementation
```php
// src/API/EndpointBase.php
// src/API/CompanyEndpoint.php
// src/API/IpbxEndpoint.php
// etc...
```

#### Task 6.4: Documentation
```
[ ] OpenAPI/Swagger spec
[ ] Postman collection
[ ] Example requests
[ ] Error codes
[ ] Rate limits
```

**Arquivos a criar:**
- src/API/ (novo diretório)
- Doc/API.md
- openapi.yaml

---

### SPRINT 7: Webhooks & Eventos (Semana 13-14)

**Objetivo:** Implementar sistema de webhooks

#### Task 7.1: Webhook Registry
```
[ ] Create webhook table
[ ] Store endpoints
[ ] Manage subscriptions
[ ] Queue system
[ ] Retry logic
```

#### Task 7.2: Events
```
company.created
company.updated
company.deleted
ipbx.created
task.completed
signature.added
```

#### Task 7.3: Implementation
```php
// src/Services/WebhookService.php
// src/Jobs/WebhookDispatcher.php
```

#### Task 7.4: Testing
```
[ ] Mock webhook calls
[ ] Retry verification
[ ] Error handling
[ ] Signature verification
```

---

### SPRINT 8: Dashboard (Semana 15-16)

**Objetivo:** Criar dashboard executivo

#### Task 8.1: Metrics
```
[ ] Total companies
[ ] Active contracts
[ ] Expiring contracts
[ ] Tasks completed
[ ] Teams performance
```

#### Task 8.2: Charts
```
[ ] Contract status pie chart
[ ] Tasks over time
[ ] Team productivity
[ ] Geographic distribution
```

#### Task 8.3: Integration
```
[ ] Cache metrics
[ ] Real-time updates
[ ] Export reports
[ ] Scheduled emails
```

#### Task 8.4: Frontend
```
[ ] Dashboard template
[ ] Chart library (Chart.js)
[ ] Responsive design
[ ] Mobile optimization
```

---

## 🛠️ TECNOLOGIAS RECOMENDADAS

### Backend
- **PHP:** 8.1+ (atual)
- **Framework:** GLPI 11.0.6+ (atual)
- **Database:** MySQL 8.0 / MariaDB 10.5+
- **Testing:** PHPUnit 10+
- **Analysis:** PHPStan, PHP_CodeSniffer

### Frontend
- **Templates:** Twig 3+
- **CSS:** Bootstrap 5+ (GLPI padrão)
- **JS:** Vanilla JS / Axios
- **Maps:** Google Maps API
- **Signature:** Signature Pad
- **Charts:** Chart.js

### DevOps
- **Version Control:** Git + GitHub
- **CI/CD:** GitHub Actions
- **Package Manager:** Composer
- **Docker:** Docker/Docker Compose (opcional)
- **Monitoring:** Sentry (opcional)

---

## 📊 MÉTRICAS DE SUCESSO

### Por Sprint

| Métrica | Meta | Responsável |
|---------|------|-------------|
| Tests Coverage | 80%+ | Dev Team |
| PSR-12 Compliance | 100% | Linter |
| Security Issues | 0 Critical | Security Team |
| Performance | < 200ms avg | Backend Dev |
| Documentation | 100% | Tech Writer |
| Code Review | 2+ approvals | Reviewers |

### Globais

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

### Versionamento Semântico

```
v1.0.0 — Initial Release
v1.0.1 — Bug fixes
v1.1.0 — CNPJ validation + features
v1.2.0 — Geolocation complete
v1.3.0 — Digital signature
v2.0.0 — REST API + Dashboard
```

### Release Checklist

- [ ] Tests passando (100%)
- [ ] Coverage > 80%
- [ ] Code review completo
- [ ] Documentation atualizada
- [ ] CHANGELOG preenchido
- [ ] Security audit OK
- [ ] Performance approved
- [ ] Staging teste OK
- [ ] Release notes prontas
- [ ] Tag git criada
- [ ] Publicado em marketplace
- [ ] Notificação a usuários

---

## 📞 COMUNICAÇÃO E SUPORTE

### Canais
- GitHub Issues — Bugs & features
- GitHub Discussions — Dúvidas
- Email — Support crítico
- Wiki — Documentação pública

### SLA
- **Critical:** 24h
- **High:** 48h
- **Medium:** 1 week
- **Low:** 2 weeks

---

## ✅ GO-LIVE CHECKLIST

Antes de v1.1.0 em produção:

- [ ] Todas as issues críticas fechadas
- [ ] Testes 80%+
- [ ] Security audit OK
- [ ] Performance acceptable
- [ ] Docs completas
- [ ] User training done
- [ ] Support team ready
- [ ] Monitoring setup
- [ ] Backup procedures
- [ ] Rollback plan

---

## 📅 TIMELINE ESTIMADA

```
Maio 2026
├── Sprint 1 (1-2) ✅ Audit & Conformidade
├── Sprint 2 (3-4) ✅ Testes & Docs
│
Junho 2026
├── Sprint 3 (5-6) 🔄 CNPJ Validation
├── Sprint 4 (7-8) 🔄 Geolocalização
│
Julho 2026
├── Sprint 5 (9-10) 🔄 Assinatura Digital
├── Sprint 6 (11-12) 🔄 API REST
│
Agosto 2026
├── Sprint 7 (13-14) 🔄 Webhooks
├── Sprint 8 (15-16) 🔄 Dashboard
│
Setembro 2026
└── Stabilization & v2.0.0 Release

Legenda:
✅ Concluído
🔄 Em Progresso
⏳ Agendado
```

---

## 🎓 RECURSOS DE APRENDIZADO

### Documentação Oficial
- [GLPI Documentation](https://glpi-project.org/documentation/)
- [PHP.net](https://www.php.net/)
- [Twig Documentation](https://twig.symfony.com/)
- [Bootstrap 5](https://getbootstrap.com/)

### Ferramentas
- PHPStan Docs
- PHPUnit Documentation
- GitHub Actions Guide
- Docker Documentation

### Treinamento
- GLPI Certification (opcional)
- PHP Advanced (recomendado)
- Security Best Practices
- API Design Patterns

---

## 📝 NOTAS IMPORTANTES

1. **Backward Compatibility:** Manter onde possível
2. **Data Migration:** Testar em ambiente staging antes
3. **User Feedback:** Coletar regularmente
4. **Performance:** Monitorar em produção
5. **Security:** Audit a cada release major

---

**Documento versão:** 1.0.0  
**Criado em:** 20/05/2026  
**Próxima revisão:** Após Sprint 2  
**Responsável:** Tech Lead

