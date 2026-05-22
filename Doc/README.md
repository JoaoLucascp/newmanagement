# Newmanagement — Plugin GLPI

## O que é

**Newmanagement** é um plugin para o GLPI 11 que centraliza a documentação técnica de empresas clientes, organizando em uma única ficha todas as informações que uma equipe de suporte precisa para atender um cliente: dados cadastrais, IPBX (ramais, dispositivos, rede), chatbot, linha fixa, tarefas e contratos.

## Objetivo

Eliminar o uso de planilhas e documentos dispersos para registrar configurações de clientes. Toda a documentação técnica fica dentro do GLPI, com controle de permissões, histórico de alterações e rastreabilidade nativa.

## Público-alvo

- Equipes de suporte e NOC que atendem múltiplos clientes
- Prestadores de serviço de TI e Telecom
- Administradores GLPI que precisam documentar infraestrutura de clientes

## Funcionalidades principais

| Módulo | Descrição |
|---|---|
| **Empresa** | Ficha completa com CNPJ, contato, endereço e documentos |
| **IPBX** | Ramais, dispositivos SIP, configuração de rede e linha fixa |
| **Chatbot** | Credenciais e configuração de chatbots de atendimento |
| **Tarefas** | Checklist de atividades vinculadas à empresa |
| **Linha Fixa** | Linhas DDR, portabilidade, operadora e status |

## Requisitos

- GLPI 11.0.0 ou superior (testado até 11.0.6)
- PHP 8.1 ou superior
- MySQL/MariaDB compatível com GLPI 11

## Instalação rápida

Veja o guia completo em [Doc/INSTALL.md](./INSTALL.md).

```bash
cd /var/www/html/glpi/plugins
git clone https://github.com/JoaoLucascp/newmanagement.git newmanagement
# Ativar em: GLPI → Configuração → Plugins
```

## Licença

MIT — veja [LICENSE](../LICENSE).
