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
// [FIX B1] Valor anterior '11.0.99' bloqueava ativação em 11.1.x+
// mesmo sem incompatibilidade comprovada. Relaxado para cobrir
// toda a linha 11.x do GLPI enquanto não houver quebra real.
define('PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION', '11.99.99');
define('PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION',  '8.1');

/**
 * Inicialização do plugin - chamado em todas as páginas do GLPI
 */
function plugin_init_newmanagement()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['newmanagement'] = true;
    $PLUGIN_HOOKS['use_massive_action']['newmanagement'] = 1;

    // CSS e JS — no GLPI 11, assets são servidos a partir de public/
    $PLUGIN_HOOKS['add_css']['newmanagement']        = 'public/css/newmanagement.css';
    $PLUGIN_HOOKS['add_javascript']['newmanagement'] = 'public/js/newmanagement.js';

    // Página de configuração
    $PLUGIN_HOOKS['config_page']['newmanagement'] = 'front/config.php';

    // -------------------------------------------------------
    // [FIX M1] Registro do namespace Twig @newmanagement via
    // hook oficial TWIG_ENV_UPDATE (disponível desde GLPI 10.0.7+).
    //
    // PROBLEMA ANTERIOR: registro feito via singleton direto em
    // plugin_init, acoplando o código à ordem de inicialização do
    // Symfony. Proxies ou sequências de boot diferentes podiam
    // causar LoaderError silencioso.
    //
    // SOLUÇÃO: TWIG_ENV_UPDATE é disparado pelo GLPI sempre que o
    // ambiente Twig é instanciado, garantindo registro consistente
    // e independente da ordem de boot.
    // -------------------------------------------------------
    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::TWIG_ENV_UPDATE]['newmanagement']
        = 'plugin_newmanagement_register_twig_namespace';

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

    // Classes filhas do IPBX On-Premise — exibidas como abas na ficha do IPBX
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

    // GLPI 11 — registra o menu lateral via MENU_TOADD.
    // A chave 'plugins' faz o menu aparecer na seção Plug-ins do topo.
    // Company::getMenuContent() define os sub-itens (Empresas, IPBX, Chatbots…).
    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::MENU_TOADD]['newmanagement'] = [
        'plugins' => [\GlpiPlugin\Newmanagement\Company::class],
    ];
}

/**
 * [FIX M1] Callback do hook TWIG_ENV_UPDATE.
 * Registra o namespace @newmanagement no loader do Twig de forma
 * declarativa, sem acoplamento ao singleton ou à ordem de boot.
 *
 * @param \Twig\Environment $twig Instância do ambiente Twig fornecida pelo GLPI.
 */
function plugin_newmanagement_register_twig_namespace(\Twig\Environment $twig): void
{
    $tpl_dir = Plugin::getPhpDir('newmanagement') . '/templates';
    if (is_dir($tpl_dir)) {
        /** @var \Twig\Loader\FilesystemLoader $loader */
        $loader = $twig->getLoader();
        if ($loader instanceof \Twig\Loader\FilesystemLoader) {
            $loader->addPath($tpl_dir, 'newmanagement');
        }
    }
}

/**
 * Retorna informações do plugin para o GLPI
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

    // Verifica versão do GLPI
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

    // Verifica versão do PHP
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
 * Verifica configuração
 */
function plugin_newmanagement_check_config($verbose = false)
{
    return true;
}
