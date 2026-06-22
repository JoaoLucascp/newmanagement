# Newmanagement — Plugin GLPI

## O que é

O **Newmanagement** é um plugin para o GLPI 11 que centraliza a documentação técnica de empresas clientes. Ele adiciona uma entidade **Empresa** com abas dedicadas para cada módulo de infraestrutura de telecomunicações.

## Objetivo

Permitir que equipes de suporte e gestão de TI registrem, consultem e mantenham atualizada toda a documentação técnica de clientes — servidores IPBX, ramais, dispositivos, redes, chatbots, linhas fixas e tarefas com geolocalização — diretamente dentro do GLPI, sem ferramentas externas.

## Público-alvo

- Provedores de telecomunicações
- Integradores de sistemas de comunicação (IPBX, VoIP, Chatbot)
- Equipes de suporte técnico N1/N2/N3 que usam GLPI como sistema principal

## Módulos disponíveis

| Módulo | Descrição |
|---|---|
| **Empresas** | Cadastro de clientes com CNPJ, razão social, contato e status de contrato |
| **IPBX On-Premise** | Servidor IPBX com ramais, dispositivos e redes paginados |
| **Linha Fixa** | Linhas SIP/analógicas vinculadas ao IPBX |
| **Chatbot** | Plataformas de chatbot com usuários, comunicações em massa e restrições WhatsApp |
| **Tarefas** | Tarefas com geolocalização vinculadas à empresa |

## Requisitos

- GLPI 11.0.0 – 11.99.99
- PHP 8.2 ou superior
- MySQL 8.0+ ou MariaDB 10.6+

## Instalação rápida

Consulte [Doc/INSTALL.md](./INSTALL.md) para o guia completo passo a passo.

```bash
# Clone na pasta de plugins do GLPI
cd /var/www/glpi/plugins
git clone https://github.com/JoaoLucascp/newmanagement.git newmanagement

# No GLPI: Configuração > Plugins > Newmanagement > Instalar > Ativar
```

## Licença

MIT — veja [LICENSE](../LICENSE).
