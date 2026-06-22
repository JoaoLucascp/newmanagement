# 📚 ÍNDICE DE DOCUMENTAÇÃO — NEWMANAGEMENT PLUGIN

## 🎯 Bem-vindo à Documentação do Plugin Newmanagement

Esta pasta contém toda a documentação técnica necessária para entender, debugar, otimizar e evoluir o plugin Newmanagement para GLPI 11.

---

## 📑 DOCUMENTOS DISPONÍVEIS

### 📖 Referência principal

| Arquivo | Propósito | Público | Quando ler |
|---------|-----------|---------|------------|
| **DOCUMENTACAO_NEWMANAGEMENT.md** | Visão geral completa do plugin | Todos | Primeira vez explorando |
| **ARCHITECTURE.md** | Estrutura de pastas, fluxo de dados, diagrama de componentes | Devs | Antes de mexer na arquitetura |
| **PADROES_GLPI.md** | Referência de conformidade com GLPI 11 | Devs | Durante desenvolvimento (consulta frequente) |
| **INSTALL.md** | Passo a passo de instalação e configuração | Todos | Ao instalar o plugin |

### 🛠️ Desenvolvimento

| Arquivo | Propósito | Quando usar |
|---------|-----------|-------------|
| **DEVELOPMENT.md** | Guia de desenvolvimento local, setup do ambiente | Ao configurar ambiente de dev |
| **GUIA_DEBUG_OTIMIZACAO.md** | Prompt especializado para debug e refatoração | Antes de iniciar debug/refatoração |
| **ROTEIRO_DESENVOLVIMENTO.md** | Roadmap de desenvolvimento até v2.0.0 (8 sprints) | Planejamento e tracking de progresso |

### 📝 Histórico e rastreabilidade

| Arquivo | Propósito | Quando ler |
|---------|-----------|------------|
| **CHANGELOG.md** | Todas as mudanças por versão (formato Keep a Changelog) | Sempre que quiser saber o que mudou |
| **AUDIT_REPORT.md** | Relatório de auditoria v1.0.0 (maio/2026) — todos os itens resolvidos | Referência histórica |
| **RELATORIO_AUDIT_NEWMANAGEMENT.md** | Relatório detalhado de auditoria e revisão de segurança | Referência histórica |
| **RELATORIO_REVISAO_NEWMANAGEMENT.md** | Revisão de código e arquitetura | Referência histórica |
| **relatorio_debug_newmanagement.md** | Sessão de debug documentada | Referência histórica |
| **DOCUMENTACAO_AUDITORIA_INICIAL.md** | ⚠️ Documento histórico — estado do plugin em maio/2026 antes das correções | Referência histórica apenas |

---

## 🎓 COMO USAR ESTES DOCUMENTOS

### Cenário 1: "Quero entender o plugin"

1. Leia: **DOCUMENTACAO_NEWMANAGEMENT.md**
2. Explore: `src/`, `front/`, `templates/`
3. Consulte: **ARCHITECTURE.md** para entender o fluxo
4. Reference: **PADROES_GLPI.md** para esclarecer padrões

### Cenário 2: "Preciso debugar e otimizar"

1. Leia: **GUIA_DEBUG_OTIMIZACAO.md**
2. Execute: Audit completo
3. Refatore: Seguindo **PADROES_GLPI.md**

### Cenário 3: "Qual é o plano futuro?"

1. Leia: **ROTEIRO_DESENVOLVIMENTO.md**
2. Veja: Sprint atual e próximas tasks

### Cenário 4: "O que mudou recentemente?"

1. Abra: **CHANGELOG.md**

### Cenário 5: "Vou fazer uma mudança no código"

1. Antes: Leia seção relevante em **PADROES_GLPI.md**
2. Durante: Use como checklist
3. Depois: Atualize **CHANGELOG.md**

---

## 📊 ESTRUTURA DA PASTA `Doc/`

```
Doc/
├── INDICE.md                          ← Você está aqui
├── CHANGELOG.md                       ← O que mudou em cada versão
│
├── — Referência —
├── DOCUMENTACAO_NEWMANAGEMENT.md      ← Comece AQUI
├── ARCHITECTURE.md                    ← Estrutura e fluxo
├── PADROES_GLPI.md                    ← Referência durante dev
├── INSTALL.md                         ← Instalação
│
├── — Desenvolvimento —
├── DEVELOPMENT.md                     ← Setup local
├── GUIA_DEBUG_OTIMIZACAO.md           ← Debug e refatoração
├── ROTEIRO_DESENVOLVIMENTO.md         ← Roadmap v1→v2
│
└── — Histórico —
    ├── AUDIT_REPORT.md
    ├── RELATORIO_AUDIT_NEWMANAGEMENT.md
    ├── RELATORIO_REVISAO_NEWMANAGEMENT.md
    ├── relatorio_debug_newmanagement.md
    └── DOCUMENTACAO_AUDITORIA_INICIAL.md  ← Estado em maio/2026
```

---

## 🔄 MANUTENÇÃO DESTES DOCUMENTOS

| Quando atualizar | Arquivos a atualizar |
|------------------|---------------------|
| A cada correção/feature | `CHANGELOG.md` |
| A cada sprint concluída | `ROTEIRO_DESENVOLVIMENTO.md` |
| Mudança de arquitetura | `ARCHITECTURE.md`, `DOCUMENTACAO_NEWMANAGEMENT.md` |
| Novo padrão GLPI identificado | `PADROES_GLPI.md` |
| Novo módulo adicionado | `DOCUMENTACAO_NEWMANAGEMENT.md`, `ARCHITECTURE.md`, `INDICE.md` |

---

## 🎓 RECURSOS EXTERNOS

- [GLPI Official Docs](https://glpi-project.org/documentation/)
- [GLPI Developer Documentation](https://glpi-developer-documentation.readthedocs.io/)
- [Twig Documentation](https://twig.symfony.com/)
- [PHP Documentation](https://www.php.net/docs.php)
- [GLPI Forum](https://forum.glpi-project.org/)

---

**Última atualização:** 21 de junho de 2026  
**Versão da documentação:** 1.1.0
