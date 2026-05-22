# Guia de Desenvolvimento — Newmanagement

## Ambiente local

### Requisitos

- Docker e Docker Compose (recomendado)
- PHP 8.1+ com extensões: `sodium`, `mysqli`, `mbstring`, `gd`, `intl`, `xml`, `curl`
- Composer 2.x
- GLPI 11.0.x instalado localmente ou via Docker

### Setup com Docker

```bash
# 1. Clone o GLPI
git clone https://github.com/glpi-project/glpi.git --branch 11.0 glpi
cd glpi

# 2. Clone o plugin dentro de plugins/
cd plugins
git clone https://github.com/JoaoLucascp/newmanagement.git newmanagement
cd ..

# 3. Suba o ambiente
docker compose up -d

# 4. Instale dependências do GLPI
docker compose exec glpi composer install

# 5. Configure o GLPI (acesse http://localhost e siga o wizard)
```

### Setup manual

```bash
# Copie o plugin para a pasta plugins do GLPI
cp -r newmanagement /var/www/html/glpi/plugins/newmanagement

# Acesse o GLPI e ative o plugin em
# Configuração → Plugins → Newmanagement → Instalar → Ativar
```

---

## Estrutura de arquivos

```
newmanagement/
├── ajax/               ← Endpoints AJAX (POST)
│   └── ipbx_sub.php
├── Doc/                ← Documentação técnica
├── front/              ← Entry points HTTP (listagem, formulários)
├── hook.php            ← Install / Uninstall / Upgrade
├── locales/            ← Arquivos de tradução .po/.mo
├── public/
│   ├── css/            ← Estilos do plugin
│   └── js/             ← JavaScript do plugin
├── setup.php           ← Inicialização do plugin
├── src/                ← Classes PHP (PSR-4, namespace GlpiPlugin\Newmanagement)
└── templates/          ← Templates Twig (@newmanagement/...)
```

---

## Padrões de codificação

- **PSR-12** para formatação de código PHP
- **Namespace:** `GlpiPlugin\Newmanagement\` para todas as classes em `src/`
- **Nomes de classes:** PascalCase (`Company`, `IpbxExtension`)
- **Nomes de métodos:** camelCase (`showForm`, `prepareInputForAdd`)
- **Nomes de tabelas:** `glpi_plugin_newmanagement_{entidade}` (snake_case, plural)
- **Docblocks:** obrigatórios em todos os métodos públicos
- **i18n:** todas as strings visíveis ao usuário usam `__("string", "newmanagement")`

### Exemplo de classe modelo

```php
<?php

namespace GlpiPlugin\Newmanagement;

use CommonDBTM;

class MinhaEntidade extends CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_minhaentidade';

    public static function getTypeName($nb = 0): string
    {
        return __('Minha Entidade', 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_minhasentidades';
    }
}
```

---

## Executar testes

> Os testes usam PHPUnit, já incluído como dependência de desenvolvimento do GLPI.

```bash
# A partir da raiz do GLPI
./vendor/bin/phpunit \
  --configuration plugins/newmanagement/phpunit.xml \
  plugins/newmanagement/tests/
```

### Estrutura de testes

```
tests/
├── Unit/
│   ├── CompanyTest.php
│   ├── IpbxTest.php
│   └── ChatbotTest.php
└── Integration/
    └── InstallTest.php
```

---

## Como contribuir

1. Crie uma branch a partir de `main`:
   ```bash
   git checkout -b feat/nome-da-feature
   # ou
   git checkout -b fix/descricao-do-bug
   ```

2. Faça commits pequenos e descritivos seguindo o padrão:
   ```
   tipo(escopo): descrição curta em português

   tipo: feat | fix | docs | refactor | test | chore
   escopo: company | ipbx | chatbot | task | setup | hook | ajax
   ```

3. Abra um Pull Request para `main` com:
   - Descrição do que foi alterado
   - Como testar
   - Referência ao item do relatório de auditoria (ex: `[M3]`) se aplicável

4. O PR só é mergeado após:
   - Testes passando
   - Revisão de código aprovada
   - Sem erros nos logs do GLPI

---

## Variáveis de ambiente úteis

```bash
# Ativa modo debug do GLPI (mais detalhes nos logs)
export GLPI_ENVIRONMENT_TYPE=development

# Logs em
tail -f /var/www/html/glpi/files/_log/php-errors.log
tail -f /var/www/html/glpi/files/_log/sql-errors.log
```
