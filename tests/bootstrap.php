<?php

/**
 * tests/bootstrap.php
 *
 * Bootstrap standalone para testes UNITARIOS.
 * Define as constantes minimas do GLPI para que as classes
 * do plugin possam ser carregadas sem um GLPI completo.
 *
 * NAO use este bootstrap para testes de integracao.
 * Testes de integracao requerem o bootstrap real do GLPI.
 */

declare(strict_types=1);

// Constantes minimas exigidas pelas classes do plugin
define('GLPI_ROOT',      dirname(__DIR__, 4)); // raiz do GLPI
define('GLPI_VERSION',   '11.0.6');
define('GLPI_SCHEMA_VERSION', '11.0.6@dev');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');
define('GLPI_VAR_DIR',    GLPI_ROOT . '/files');
define('GLPI_LOG_DIR',    GLPI_ROOT . '/files/_log');
define('GLPI_CACHE_DIR',  GLPI_ROOT . '/files/_cache');
define('GLPI_PLUGIN_DOC_DIR', GLPI_ROOT . '/files/_plugins');
define('PLUGINS_DIRECTORIES', [GLPI_ROOT . '/plugins']);
define('ERROR',    1);
define('WARNING',  2);
define('NOTICE',   4);
define('INFO',     8);

// Autoloader do plugin (gerado pelo composer install)
require_once __DIR__ . '/../vendor/autoload.php';

// Stub minimo de Session::addMessageAfterRedirect
// para que prepareInput() nao quebre nos testes unitarios
if (!class_exists('Session')) {
    class Session
    {
        public static array $messages = [];

        public static function addMessageAfterRedirect(
            string $message,
            bool $check_once = false,
            int $type = INFO,
            bool $displayed = false
        ): void {
            self::$messages[] = [
                'message' => $message,
                'type'    => $type,
            ];
        }

        public static function haveRight(string $right, int $value): bool
        {
            return true;
        }

        public static function getLastMessage(): ?string
        {
            $last = end(self::$messages);
            return $last ? $last['message'] : null;
        }

        public static function clearMessages(): void
        {
            self::$messages = [];
        }
    }
}

// Stub de Toolbox para nao depender do core do GLPI
if (!class_exists('Toolbox')) {
    class Toolbox
    {
        public static function sodiumEncrypt(string $value): string
        {
            return base64_encode($value); // mock simples
        }

        public static function sodiumDecrypt(string $value): string
        {
            return base64_decode($value); // mock simples
        }

        public static function logDebug(string $message): void
        {
            // silencioso nos testes
        }
    }
}
