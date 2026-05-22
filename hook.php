<?php

/**
 * Newmanagement — Orquestrador de hooks de ciclo de vida
 *
 * Este arquivo é o ponto de entrada chamado pelo GLPI para
 * install, uninstall e upgrade do plugin.
 *
 * A lógica real está nos módulos em hook/:
 *   hook/install.php   — CREATE TABLE e migrações
 *   hook/uninstall.php — DROP TABLE
 *   hook/upgrade.php   — upgrades entre versões
 *
 * Manter este arquivo enxuto garante que cada módulo possa
 * ser revisado, testado e versionado de forma independente.
 */

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

// Carrega os módulos de ciclo de vida
require_once __DIR__ . '/hook/install.php';
require_once __DIR__ . '/hook/uninstall.php';
require_once __DIR__ . '/hook/upgrade.php';

/**
 * Instalação do plugin.
 * Chamado pelo GLPI ao ativar o plugin pela primeira vez.
 */
function plugin_newmanagement_install(): bool
{
    return plugin_newmanagement_install_tables();
}

/**
 * Desinstalação do plugin.
 * Chamado pelo GLPI ao desinstalar o plugin.
 * ⚠️  Remove todas as tabelas e dados do plugin.
 */
function plugin_newmanagement_uninstall(): bool
{
    return plugin_newmanagement_uninstall_tables();
}

/**
 * Upgrade do plugin.
 * Chamado pelo GLPI quando detecta que a versão instalada
 * é diferente da versão atual do plugin.
 *
 * @param string $old_version Versão anterior instalada no banco.
 */
function plugin_newmanagement_upgrade(string $old_version = '0.0.0'): bool
{
    return plugin_newmanagement_run_upgrade($old_version);
}

/**
 * Retorna lista de dropdowns (não utilizado atualmente).
 */
function plugin_newmanagement_getDropdown(): array
{
    return [];
}
