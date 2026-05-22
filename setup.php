<?php

/**
 * Newmanagement - Plugin GLPI
 * Sistema completo de Gestão de Documentação de Empresas
 *
 * @author   João Lucas
 * @license  MIT
 */

// -------------------------------------------------------
// Constantes centralizadas — altere APENAS aqui ao versionar
// -------------------------------------------------------
define('PLUGIN_NEWMANAGEMENT_VERSION',         '1.0.0');
define('PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION', '11.0.0');
// Fix [B1]: era '11.0.99' — bloqueava GLPI 11.1+ sem incompatibilidade
// comprovada. Alterado para '11.99.99' para cobrir toda a série 11.x.
// Ajuste para a próxima major quando necessário.
define('PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION', '11.99.99');
define('PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION',  '8.1');

/**
 * Registra o namespace Twig @newmanagement no loader do Twig.
 *
 * Função extraída para ser chamada tanto pelo hook TWIG_ENV_UPDATE
 * (GLPI >= 11.0.7) quanto diretamente no plugin_init como fallback
 * para GLPI 11.0.0–11.0.6, sem duplicação de código.
 *
 * @param \Twig\Environment $twig Instância do Twig já criada pelo GLPI.
 */
function plugin_newmanagement_register_twig_namespace(\Twig\Environment $twig): void
{
    $tpl_dir = Plugin::getPhpDir('newmanagement') . '/templates';
    if (!is_dir($tpl_dir)) {
        return;
    }
    /** @var \Twig\Loader\FilesystemLoader $loader */
    $loader = $twig->getLoader();
    if ($loader instanceof \Twig\Loader\FilesystemLoader) {
        $loader->addPath($tpl_dir, 'newmanagement');
    }
}

/**
 * Inicialização do plugin — chamado em todas as páginas do GLPI.
 */
function plugin_init_newmanagement()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['newmanagement']    = true;
    $PLUGIN_HOOKS['use_massive_action']['newmanagement'] = 1;

    // CSS e JS — assets servidos a partir de public/ no GLPI 11
    $PLUGIN_HOOKS['add_css']['newmanagement']        = 'public/css/newmanagement.css';
    $PLUGIN_HOOKS['add_javascript']['newmanagement'] = 'public/js/newmanagement.js';

    // Página de configuração
    $PLUGIN_HOOKS['config_page']['newmanagement'] = 'front/config.php';

    // -------------------------------------------------------
    // Fix [M1]: Registro do namespace Twig @newmanagement
    //
    // CAMINHO PREFERENCIAL (GLPI >= 11.0.7):
    //   Hook TWIG_ENV_UPDATE — mecanismo declarativo oficial.
    //   O GLPI chama o callback passando o \Twig\Environment já
    //   instanciado, sem dependência de ordem de inicialização.
    //
    // FALLBACK (GLPI 11.0.0 – 11.0.6):
    //   TWIG_ENV_UPDATE não existia. Registramos diretamente via
    //   getEnvironment()->getLoader()->addPath().
    //   TemplateRenderer é lazy — instancia o Twig apenas se ainda
    //   não foi criado, portanto chamar getInstance() aqui é seguro.
    // -------------------------------------------------------
    if (
        defined('\Glpi\Plugin\Hooks::TWIG_ENV_UPDATE')
        && version_compare(GLPI_VERSION, '11.0.7', '>=')
    ) {
        $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::TWIG_ENV_UPDATE]['newmanagement']
            = 'plugin_newmanagement_register_twig_namespace';
    } else {
        // Fallback para GLPI 11.0.0 – 11.0.6
        plugin_newmanagement_register_twig_namespace(
            \Glpi\Application\View\TemplateRenderer::getInstance()->getEnvironment()
        );
    }

    // Registra as classes PSR-4
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Company::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Ipbx::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Chatbot::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\FixedLine::class);

    // Task — entidade standalone + aba na ficha de Empresa
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\Task::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Company::class]]
    );

    // Classes filhas do IPBX — exibidas como abas na ficha do IPBX
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\IpbxExtension::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Ipbx::class]]
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\IpbxDevice::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Ipbx::class]]
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\IpbxNetwork::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Ipbx::class]]
    );

    // GLPI 11 — registra o menu lateral via MENU_TOADD
    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::MENU_TOADD]['newmanagement'] = [
        'plugins' => [\GlpiPlugin\Newmanagement\Company::class],
    ];
}

/**
 * Retorna informações do plugin para o GLPI.
 */
function plugin_version_newmanagement()
{
    return [
        'name'         => 'Newmanagement',
        'version'      => PLUGIN_NEWMANAGEMENT_VERSION,
        'author'       => 'João Lucas',
        'license'      => 'MIT',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION,
                'max' => PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION,
            ],
            'php'  => [
                'min' => PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION,
            ],
        ],
    ];
}

/**
 * Verifica pré-requisitos de versão do GLPI e do PHP.
 * Chamada pelo GLPI antes de ativar o plugin.
 */
function plugin_newmanagement_check_prerequisites()
{
    $ok = true;

    if (
        version_compare(GLPI_VERSION, PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION, 'lt')
        || version_compare(GLPI_VERSION, PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION, 'gt')
    ) {
        echo sprintf(
            'Este plugin requer GLPI entre %s e %s. Versão atual: %s.',
            PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION,
            PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION,
            GLPI_VERSION
        );
        $ok = false;
    }

    if (version_compare(PHP_VERSION, PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION, 'lt')) {
        echo sprintf(
            'Este plugin requer PHP %s ou superior. Versão atual: %s.',
            PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION,
            PHP_VERSION
        );
        $ok = false;
    }

    return $ok;
}

/**
 * Verifica configuração.
 */
function plugin_newmanagement_check_config($verbose = false)
{
    return true;
}
