# Relatorio de Auditoria GLPI/Security - Newmanagement

Data: 2026-06-22
Alvo: GLPI 11 local em `D:\wamp64\www\glpi-11`

## Resumo

Esta auditoria revisou os pontos de estrutura GLPI e seguranca do plugin:
hooks, rights, CSRF, endpoints AJAX/front, exportacao, credenciais, ownership
por empresa e testes unitarios.

## Achados e correcoes

| Prioridade | Area | Achado | Correcao aplicada |
| --- | --- | --- | --- |
| Alta | Compatibilidade GLPI | Classes filhas IPBX tipavam `$rightname`, mas `CommonGLPI::$rightname` no GLPI 11 local nao e tipado. | Removido o tipo de `$rightname` em `IpbxExtension`, `IpbxDevice` e `IpbxNetwork`. |
| Alta | Credenciais | Senhas de ramais eram exibidas para qualquer perfil com READ e como valor bruto do banco. | Criada politica central: READ ve mascara, UPDATE/IPBX ve senha documentada descriptografada quando possivel. |
| Alta | Exportacao | Exportacao de ramais filtrava apenas `ipbx_id`, permitindo tentativa de acesso cruzado por ID. | Exportacao agora exige `companies_id`, valida pertencimento e filtra por `ipbx_id` + `companies_id`. |
| Alta | IDOR | Acoes AJAX aceitavam `ipbx_id`, `chatbot_id` ou `task_id` sem sempre validar empresa. | Adicionados helpers de ownership para IPBX, Chatbot e Task. |
| Media | CSRF GLPI 11 | Alguns fetches nao enviavam `X-Glpi-Csrf-Token`/`X-Requested-With`. | Fetches principais agora enviam ambos e atualizam token retornado. |
| Media | Menu GLPI | Menu referenciava IPBX Cloud inexistente e rightname separado para Linha Fixa. | Removido item morto e alinhada Linha Fixa ao rightname real de IPBX. |
| Media | Tarefas | Delete usava purge permanente. | Delete AJAX passou a usar soft delete padrao do GLPI. |
| Baixa | Requisitos | `setup.php` declarava PHP 8.1 enquanto `composer.json` exigia 8.2. | Requisito minimo alinhado para PHP 8.2 na configuracao e docs operacionais. |

## Evidencias por arquivo/linha

| Area | Evidencia |
| --- | --- |
| Compatibilidade GLPI | `src/IpbxExtension.php:16`, `src/IpbxDevice.php:16` e `src/IpbxNetwork.php:16` usam `$rightname` sem tipo, compativel com `CommonGLPI`. |
| Senhas de ramais | `src/Ipbx.php:306` centraliza a regra de mascara/exibicao; `src/Ipbx.php:171` e `templates/ipbx/partials/extensions.html.twig:57` usam `password_display`; `src/Ipbx.php:325` aplica a mesma regra em linhas AJAX. |
| Exportacao de ramais | `front/ipbx_extension_export.php:42` valida pertencimento IPBX/empresa; `front/ipbx_extension_export.php:126` e `front/ipbx_extension_export.php:161` aplicam a politica de senha em PDF/CSV. |
| Ownership IPBX | `src/Ipbx.php:248` define `ipbxBelongsToCompany`; `ajax/ipbx_sub.php:116`, `ajax/ipbx_sub.php:150`, `ajax/ipbx_sub.php:242`, `ajax/ipbx_sub.php:286`, `ajax/ipbx_sub.php:325` e `ajax/ipbx_sub.php:366` bloqueiam IDs cruzados. |
| Ownership Chatbot | `ajax/chatbot_sub.php:87` define a validacao; `ajax/chatbot_sub.php:319`, `ajax/chatbot_sub.php:347`, `ajax/chatbot_sub.php:389` e `ajax/chatbot_sub.php:427` aplicam a validacao antes de alterar filhos. |
| Ownership Task | `ajax/task_action.php:39` define a validacao; `ajax/task_action.php:112` e `ajax/task_action.php:155` bloqueiam update/delete fora da empresa; `ajax/task_action.php:160` usa soft delete. |
| CSRF GLPI 11 | `public/js/newmanagement.js:143`, `templates/fixedline/tab.html.twig:213`, `templates/task/tab.html.twig:137`, `templates/task/form.html.twig:225` e `templates/task/form.html.twig:267` enviam `X-Glpi-Csrf-Token`. |
| Menu/rights | `src/Company.php:270` usa `FixedLine::$rightname`; busca final nao encontrou `plugin_newmanagement_ipbxcloud` nem `plugin_newmanagement_fixedline` no codigo ativo. |
| Requisitos PHP | `setup.php:20`, `README.md:22`, `Doc/INSTALL.md:6`, `Doc/README.md:30` e `Doc/DOCUMENTACAO_NEWMANAGEMENT.md:8` alinham PHP minimo em 8.2. |

## Politica de senhas de ramais

- Usuario com `plugin_newmanagement_ipbx` READ: ve `******`.
- Usuario com `plugin_newmanagement_ipbx` UPDATE: ve a senha documentada.
- Exportacao PDF/CSV segue a mesma regra.
- Se um valor legado estiver em texto puro, ele continua legivel apenas para UPDATE.

## Validacoes esperadas

- `php -l` em arquivos PHP fora de `vendor`.
- PHPUnit unitario sem fatal error.
- Teste manual no GLPI para Empresa, IPBX, Chatbot, Linha Fixa e Tarefa.
- Teste de permissao com perfil READ e UPDATE para senhas de ramais.
- Teste de exportacao com `ipbx_id` e `companies_id` divergentes deve falhar.
