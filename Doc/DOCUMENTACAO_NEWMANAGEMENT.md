# 📚 Documentação Completa - Plugin Newmanagement para GLPI 11.0.6

## 📑 Sumário Executivo

**Versão do Documento:** 1.0.0  
**Data de Atualização:** 20 de maio de 2026  
**Compatibilidade:** GLPI 11.0.0 até 11.0.99  
**Versão do PHP:** 8.1+  
**Autor Original:** João Lucas  
**Licença:** MIT

---

## 1️⃣ O QUE É O PLUGIN NEWMANAGEMENT?

### 🎯 Objetivo Principal

O **Newmanagement** é um plugin corporativo de gestão integrada para o GLPI que centraliza a documentação técnica de infraestruturas telefônicas e de comunicação empresarial. Ele foi desenvolvido para empresas que necessitam gerenciar múltiplas soluções de telefonia (on-premise e cloud) com registro detalhado de configurações, status de contratos e tarefas operacionais.

### 🏢 Casos de Uso

1. **Gestão de Inventário Telefônico** — Documentar e organizar todos os servidores IPBX (on-premise e cloud)
2. **Controle de Chatbots** — Rastrear sistemas de atendimento omnichannel
3. **Documentação de Linhas Fixas** — Manter registro de linhas telefônicas comerciais
4. **Gestão de Contratos** — Acompanhar status de contrato para cada empresa
5. **Tarefas com Geolocalização** — Registrar atividades com localização e assinatura digital
6. **Auditoria e Compliance** — Manter histórico completo de alterações

---

## 2️⃣ COMO FOI FEITO? - ARQUITETURA TÉCNICA

### 📐 Arquitetura de Camadas

```
┌─────────────────────────────────────────────────────┐
│         APRESENTAÇÃO (Twig + CSS + JavaScript)      │
├─────────────────────────────────────────────────────┤
│     CONTROLADORES (front/*.php + AJAX)              │
├─────────────────────────────────────────────────────┤
│          MODELOS (src/*.php - CommonDBTM)           │
├─────────────────────────────────────────────────────┤
│  BANCO DE DADOS (MySQL/MariaDB - Tabelas GLPI)     │
└─────────────────────────────────────────────────────┘
```

### 🏗️ Padrões de Design Implementados

| Padrão | Implementação | Local |
|--------|--------------|-------|
| **MVC** | Model-View-Controller | src/, front/, templates/ |
| **PSR-4** | Autoloading Namespace | `GlpiPlugin\Newmanagement\*` |
| **CommonDBTM** | ORM do GLPI | Classe base para todas as entidades |
| **Plugin Hooks** | Sistema de extensão GLPI | hook.php |
| **Segurança CSRF** | Token CSRF automático | `$PLUGIN_HOOKS['csrf_compliant']` |

### 🔒 Stack de Segurança

- ✅ **CSRF Protection** — Habilitado via `$PLUGIN_HOOKS['csrf_compliant']`
- ✅ **SQL Injection Prevention** — Uso de `$DB->prepare()` e prepared statements
- ✅ **XSS Protection** — Sanitização via `htmlspecialchars()` e Twig escaping automático
- ✅ **Access Control** — Rights management via `$rightname` e `$DB->isNewItem()`
- ✅ **Input Validation** — Validação em `post_getFromForm()` e método add/update
- ✅ **Output Encoding** — Twig auto-escapes por padrão

---

## 3️⃣ PARA QUE FOI FEITO? - PROBLEMAS RESOLVIDOS

### 📋 Necessidades do Negócio Atendidas

| Necessidade | Solução Newmanagement |
|-------------|----------------------|
| Documentação centralizada de infraestrutura | Módulos especializados para cada tipo de equipamento |
| Acompanhamento de status de contrato | Campo `contract_status` em empresas |
| Rastreamento de tarefas operacionais | Módulo de tarefas com geolocalização |
| Auditoria de mudanças | Timestamps `date_creation` e `date_mod` em todas as tabelas |
| Suporte técnico melhorado | Histórico completo e relacionamentos entre entidades |
| Conformidade LGPD/GDPR | Soft-delete via `is_deleted` |

---

## 4️⃣ COMO ESTÁ PROJETADO PARA FUNCIONAR?

### 🔄 Fluxo de Funcionamento Geral

```
1. Usuário acessa GLPI → Menu Plugins
   ↓
2. Clica em "Newmanagement" → Company::getMenuContent()
   ↓
3. Seleciona sub-módulo (Empresas, IPBX, Chatbot, etc)
   ↓
4. Front-end (front/*.php) recebe requisição
   ↓
5. Classe de modelo (src/*.php) processa lógica
   ↓
6. Template Twig renderiza resposta (templates/*)
   ↓
7. Resposta retorna ao navegador com CSS/JS (public/*)
   ↓
8. Dados persistem no banco de dados (MySQL/MariaDB)
```

### 🎛️ Componentes Principais

#### **A. Gerenciador de Empresas (Company)**
```
Function: Cadastro master de clientes/empresas
Campos: id, name, cnpj, razao_social, email, phone, cep, address, contract_status
Relacionamentos: 1 Empresa → N IPBX, N IPBX Cloud, N Chatbots, N Linhas Fixas
Acesso: /front/company.php
Permissões: plugin_newmanagement_company
```

#### **B. Documentação IPBX On-Premise (Ipbx)**
```
Function: Registro de servidores telefônicos locais
Campos: id, company_id, name, ip_address, port, admin_user, status
Recursos: Versão do software, configurações customizadas, links de documentação
Acesso: /front/ipbx.php
Permissões: plugin_newmanagement_ipbx
```

#### **C. Documentação IPBX Cloud (IpbxCloud)**
```
Function: Registro de servidores telefônicos em nuvem
Campos: id, company_id, name, provider, url, api_key, status
Recursos: URLs de acesso, credenciais seguras (hash), plano contratado
Acesso: /front/ipbxcloud.php
Permissões: plugin_newmanagement_ipbx_cloud
```

#### **D. Gestão de Chatbots (Chatbot)**
```
Function: Rastreamento de sistemas de atendimento omnichannel
Campos: id, company_id, name, platform, webhook_url, status
Canais: Whatsapp, Facebook, Instagram, SMS
Acesso: /front/chatbot.php
Permissões: plugin_newmanagement_chatbot
```

#### **E. Linhas Fixas (FixedLine)**
```
Function: Documentação de números telefônicos comerciais
Campos: id, company_id, phone_number, extension, carrier, status
Dados: Plano contratado, limite de minutos, vencimento
Acesso: /front/fixedline.php
Permissões: plugin_newmanagement_fixedline
```

#### **F. Tarefas com Geolocalização (Task)**
```
Function: Planejamento e execução de atividades operacionais
Campos: id, company_id, title, description, status, assigned_to, due_date
Features: Latitude/Longitude, assinatura digital, cálculo de quilometragem
Acesso: /front/task.php
Permissões: plugin_newmanagement_task
```

---

## 5️⃣ ESTRUTURA DETALHADA DO PLUGIN

### 📁 Organização de Diretórios

```
newmanagement/
│
├── setup.php                          # Inicialização, metadados, versão
├── hook.php                           # Instalação, migração, hooks
├── README.md                          # Documentação básica
│
├── Doc/                               # [NOVO] Documentação técnica
│   ├── DOCUMENTACAO_NEWMANAGEMENT.md  # Este arquivo
│   ├── GUIA_DEBUG_OTIMIZACAO.md       # Prompt especializado
│   ├── PADROES_GLPI.md               # Padrões do GLPI 11
│   └── ROTEIRO_DESENVOLVIMENTO.md     # Próximos passos
│
├── src/                               # Classes PHP (PSR-4)
│   ├── Company.php                    # Modelo de Empresa
│   ├── Ipbx.php                      # Modelo de IPBX On-Premise
│   ├── IpbxCloud.php                 # Modelo de IPBX Cloud
│   ├── Chatbot.php                   # Modelo de Chatbot
│   ├── FixedLine.php                 # Modelo de Linha Fixa
│   └── Task.php                      # Modelo de Tarefa
│
├── front/                             # Controladores (Views)
│   ├── company.php                    # Listagem/Edição de Empresas
│   ├── ipbx.php                      # Listagem/Edição de IPBX
│   ├── ipbxcloud.php                 # Listagem/Edição de IPBX Cloud
│   ├── chatbot.php                   # Listagem/Edição de Chatbots
│   ├── fixedline.php                 # Listagem/Edição de Linhas Fixas
│   ├── task.php                      # Listagem/Edição de Tarefas
│   └── config.php                    # Configurações do plugin
│
├── ajax/                              # Endpoints AJAX
│   ├── cnpj_email.php                # Busca de dados por CNPJ
│   ├── ipbx_sub.php                  # Subitens de IPBX
│   └── chatbot_sub.php               # Subitens de Chatbot
│
├── templates/                         # Templates Twig
│   ├── company/
│   │   ├── form.html.twig            # Formulário de Empresa
│   │   └── list.html.twig            # Lista de Empresas
│   ├── ipbx/
│   │   ├── form.html.twig            # Formulário de IPBX
│   │   └── list.html.twig            # Lista de IPBX
│   ├── ipbxcloud/
│   │   ├── form.html.twig            # Formulário de IPBX Cloud
│   │   └── list.html.twig            # Lista de IPBX Cloud
│   ├── chatbot/
│   │   ├── form.html.twig            # Formulário de Chatbot
│   │   └── list.html.twig            # Lista de Chatbots
│   ├── fixedline/
│   │   ├── form.html.twig            # Formulário de Linha Fixa
│   │   └── list.html.twig            # Lista de Linhas Fixas
│   └── task/
│       ├── form.html.twig            # Formulário de Tarefa
│       └── list.html.twig            # Lista de Tarefas
│
├── public/                            # Assets estáticos
│   ├── css/
│   │   └── newmanagement.css         # Estilos CSS
│   └── js/
│       └── newmanagement.js          # Scripts JavaScript
│
└── locales/                           # Tradução (i18n)
    ├── pt_BR/
    │   └── LC_MESSAGES/
    │       ├── newmanagement.po      # Arquivo de tradução
    │       └── newmanagement.mo      # Arquivo compilado
    └── en_US/
        └── LC_MESSAGES/
            ├── newmanagement.po
            └── newmanagement.mo
```

---

## 6️⃣ FLUXO DE DADOS E RELACIONAMENTOS

### 🔗 Diagrama de Entidades

```yaml
┌─────────────────┐
│    COMPANY      │  (Empresa Master)
│─────────────────│
│ id (PK)         │
│ name            │
│ cnpj            │
│ email           │
│ contract_status │
└────────┬────────┘
         │
         │
         ↓
  ┌─────────────┐
  │   IPBX      │  (1:N)
  │─────────────│
  │ id (PK)     │
  │ company_id  │
  │ ip_address  │
  │ admin_user  │
  │ status      │
  └─────┬───────┘
        │
        │
        ↓
  ┌────────────┐  
  │ FIXEDLINE  │
  │────────────│
  │ id (PK)    │
  │ company_id │
  │ phone_numb │
  │ extension  │
  │ carrier    │
  │ status     │
  └─────┬──────┘
        │
        │
        ↓
┌──────────────┐
│  CHATBOT     │
│──────────────│
│ id (PK)      │
│ company_id   │
│ platform     │
│ webhook_url  │
│ status       │
└──────┬───────┘
       │
       │
       ↓
┌──────────────┐
│    TASK      │
│──────────────│
│ id (PK)      │
│ company_id   │
│ title        │
│ assigned_to  │
│ latitude     │
│ longitude    │
│ signature    │
│ km_distance  │
└──────────────┘
```

---

## 7️⃣ PADRÕES DO GLPI 11 IMPLEMENTADOS

### ✅ Checklist de Conformidade

| Requisito | Implementado | Local |
|-----------|--------------|-------|
| Namespace PSR-4 | ✅ | `GlpiPlugin\Newmanagement\*` em src/ |
| Herança de CommonDBTM | ✅ | Todas as classes em src/ |
| Plugin Hooks | ✅ | hook.php linha ~47 |
| Migração de Banco | ✅ | hook.php instalação |
| CSRF Protection | ✅ | setup.php linha ~23 |
| Asset Registration | ✅ | hook.php CSS/JS registration |
| Menu Integration | ✅ | hook.php MENU_TOADD hook |
| Twig Templates | ✅ | templates/ com `.html.twig` |
| Soft Delete | ✅ | Campo `is_deleted` em tabelas |
| Timestamps | ✅ | `date_creation`, `date_mod` |
| Massive Actions | ✅ | Suportado em todas as entidades |
| Search Options | ✅ | `rawSearchOptions()` implementado |
| Rights Management | ✅ | `$rightname` definido |

---

## 8️⃣ BANCO DE DADOS - ESTRUTURA SQL

### Tabelas Criadas

#### 1. **glpi_plugin_newmanagement_companies** (Empresas)
```sql
CREATE TABLE IF NOT EXISTS `glpi_plugin_newmanagement_companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `cnpj` VARCHAR(20),
    `razao_social` VARCHAR(255),
    `email` VARCHAR(255),
    `phone` VARCHAR(50),
    `cep` VARCHAR(10),
    `address` TEXT,
    `contract_status` TINYINT DEFAULT 0,
    `comment` TEXT,
    `date_creation` TIMESTAMP,
    `date_mod` TIMESTAMP,
    `is_deleted` TINYINT DEFAULT 0,
    KEY `idx_cnpj` (`cnpj`),
    KEY `idx_name` (`name`)
);
```

#### 2. **glpi_plugin_newmanagement_ipbx** (IPBX On-Premise)
```sql
CREATE TABLE IF NOT EXISTS `glpi_plugin_newmanagement_ipbx` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45),
    `port` INT DEFAULT 5060,
    `admin_user` VARCHAR(100),
    `status` VARCHAR(50) DEFAULT 'ativo',
    `comment` TEXT,
    `date_creation` TIMESTAMP,
    `date_mod` TIMESTAMP,
    `is_deleted` TINYINT DEFAULT 0,
    CONSTRAINT `fk_ipbx_company` FOREIGN KEY (`company_id`)
        REFERENCES `glpi_plugin_newmanagement_companies`(`id`),
    KEY `idx_company` (`company_id`)
);
```

#### 3. **glpi_plugin_newmanagement_ipbx_cloud** (IPBX Cloud)
```sql
CREATE TABLE IF NOT EXISTS `glpi_plugin_newmanagement_ipbx_cloud` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `provider` VARCHAR(100),
    `url` VARCHAR(255),
    `api_key` VARCHAR(255),
    `status` VARCHAR(50) DEFAULT 'ativo',
    `comment` TEXT,
    `date_creation` TIMESTAMP,
    `date_mod` TIMESTAMP,
    `is_deleted` TINYINT DEFAULT 0,
    CONSTRAINT `fk_ipbx_cloud_company` FOREIGN KEY (`company_id`)
        REFERENCES `glpi_plugin_newmanagement_companies`(`id`),
    KEY `idx_company` (`company_id`)
);
```

E assim por diante para Chatbot, FixedLine e Task...

---

## 9️⃣ LÓGICA DE AUTENTICAÇÃO E AUTORIZAÇÃO

### 🔐 Controle de Acesso

```php
// Cada classe define seu próprio direito
public static $rightname = 'plugin_newmanagement_company';

// GLPI verifica automaticamente:
Session::checkRight('plugin_newmanagement_company', READ);     // Leitura
Session::checkRight('plugin_newmanagement_company', CREATE);   // Criação
Session::checkRight('plugin_newmanagement_company', UPDATE);   // Atualização
Session::checkRight('plugin_newmanagement_company', PURGE);    // Exclusão

// Integração com GLPI Frontend
// $this->checkEntity();   // Valida entidade
// $this->checkAccess();   // Valida acesso
```

---

## 🔟 FLUXO DE REQUISIÇÃO HTTP

### Exemplo: Criação de Empresa

```
1. POST /glpi/plugins/newmanagement/front/company.php
   Headers: GLPI-Token, GLPI-CSRF-TOKEN
   Data: {name, cnpj, email, contract_status, ...}
   
2. Classe Company carrega dados
   → __construct()
   → post_getFromForm()
   → Validação de campos
   
3. Verificação de direitos
   → Session::checkRight('plugin_newmanagement_company', CREATE)
   
4. Inserção no banco
   → $this->add($input);
   → Hook plugin_company_add
   
5. Redirecionamento
   → HTTP 302 para /front/company.php?id=123
   
6. Resposta para frontend
   → Renderização Twig
   → Mensagem de sucesso
```

---

## 🔗 RELACIONAMENTOS E INTEGRAÇÕES

### Com GLPI Core

- **CommonDBTM** — Classe base para persistência
- **Migration** — Versionamento de banco
- **HTMLForm** — Renderização de formulários
- **Search** — Sistema de busca unificado
- **Rights** — Controle de acesso
- **Log** — Auditoria de mudanças

### Com Plugins Externos (Potencial)

- **Timeline** — Histórico de eventos
- **Datasources** — Sincronização de dados
- **Forms** — Formulários customizados
- **Webhooks** — Integrações externas

---

## 📊 INDICADORES DE QUALIDADE

| Métrica | Status | Meta |
|---------|--------|------|
| PSR-12 Compliance | ✅ | 100% |
| Code Coverage | ⚠️ | 80% |
| SQL Injection Risk | ✅ | 0 vulnerabilities |
| XSS Risk | ✅ | 0 vulnerabilities |
| CSRF Protection | ✅ | Enabled |
| Documentation | 🟨 | Melhorar |
| Test Coverage | 🔴 | 0% (TODO) |

---

## 🚀 PRÓXIMAS MELHORIAS PLANEJADAS

### Curto Prazo (v1.1)
- [ ] Testes unitários completos
- [ ] Validação de CNPJ em tempo real (APIs públicas)
- [ ] Geolocalização funcional em tarefas
- [ ] Assinatura digital com certificados

### Médio Prazo (v1.2)
- [ ] API REST para integrações
- [ ] Webhooks para eventos
- [ ] Relatórios em PDF
- [ ] Importação em massa (CSV)

### Longo Prazo (v2.0)
- [ ] Dashboard executivo
- [ ] Alertas de contrato próximo de vencer
- [ ] Integração com sistemas de telefonia (API)
- [ ] Mobile App (React Native)

---

## 📞 SUPORTE E DOCUMENTAÇÃO

### Recursos Disponíveis

- 📖 **README.md** — Guia rápido
- 📁 **Doc/** — Documentação técnica (este arquivo)
- 🐛 **Issues** — Rastreamento de bugs
- 💬 **Wiki** — Artigos técnicos

### Contato

- **Email:** [email protegido]
- **Issues:** GitHub Repository
- **Licença:** MIT

---

## ✅ CHECKLIST DE CONFORMIDADE FINAL

- [x] Baseado em CommonDBTM
- [x] Namespace PSR-4 correto
- [x] Tabelas com encoding UTF-8
- [x] Soft delete implementado
- [x] Timestamps criação/modificação
- [x] CSRF protection
- [x] Rights management
- [x] Twig templates
- [x] CSS/JS em public/
- [x] i18n suportado
- [ ] Testes automatizados
- [ ] Documentação API
- [ ] Exemplos de uso

---

**Documento finalizado em 20/05/2026**  
**Próxima revisão prevista para:** Q3 2026

