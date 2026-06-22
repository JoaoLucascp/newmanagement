<?php
declare(strict_types=1);

define('GLPI_ROOT',           dirname(__DIR__, 4));
define('GLPI_VERSION',        '11.0.6');
define('GLPI_SCHEMA_VERSION', '11.0.6@dev');
define('GLPI_CONFIG_DIR',     GLPI_ROOT . '/config');
define('GLPI_VAR_DIR',        GLPI_ROOT . '/files');
define('GLPI_LOG_DIR',        GLPI_ROOT . '/files/_log');
define('GLPI_CACHE_DIR',      GLPI_ROOT . '/files/_cache');
define('GLPI_PLUGIN_DOC_DIR', GLPI_ROOT . '/files/_plugins');
define('PLUGINS_DIRECTORIES', [GLPI_ROOT . '/plugins']);
define('ERROR',   1);
define('WARNING', 2);
define('NOTICE',  4);
define('INFO',    8);

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('__')) {
    function __(string $str, string $domain = ''): string { return $str; }
}
if (!function_exists('_n')) {
    function _n(string $s, string $p, int $n, string $d = ''): string { return $n === 1 ? $s : $p; }
}
if (!function_exists('getAllDataFromTable')) {
    function getAllDataFromTable(string $t, array $c = [], bool $u = false, string $o = ''): array { return []; }
}
if (!function_exists('countElementsInTable')) {
    function countElementsInTable(string $t, array $c = []): int { return 0; }
}
if (!function_exists('getEntitiesRestrictCriteria')) {
    function getEntitiesRestrictCriteria(string $t = '', string $f = '', $v = '', bool $r = false): array { return []; }
}

if (!class_exists('CommonGLPI')) {
    abstract class CommonGLPI {
        public static function getTypeName(int $nb = 0): string { return ''; }
        public static function getTable(?string $c = null): string { return ''; }
        public function getID(): int { return 0; }
    }
}

if (!class_exists('CommonDBTM')) {
    abstract class CommonDBTM extends CommonGLPI {
        public array $fields = [];
        public static $rightname = '';
        public function prepareInputForAdd($input) { return $input; }
        public function prepareInputForUpdate($input) { return $input; }
        public function initForm(int $ID, array $options = []): void {}
        public function showFormHeader(array $options = []): void {}
        public function showFormButtons(array $options = []): void {}
        public function addDefaultFormTab(array &$ong): void {}
        public function addStandardTab(string $class, array &$ong, array $options = []): void {}
        public static function createTabEntry(string $name, int $nb = 0): string {
            return $nb > 0 ? "$name ($nb)" : $name;
        }
    }
}

if (!class_exists('Session')) {
    class Session {
        public static array $messages = [];
        public static function addMessageAfterRedirect(string $message, bool $check_once = false, int $type = INFO, bool $displayed = false): void {
            self::$messages[] = ['message' => $message, 'type' => $type];
        }
        public static function haveRight(string $right, int $value): bool { return true; }
        public static function getLastMessage(): ?string {
            $last = end(self::$messages);
            return $last ? $last['message'] : null;
        }
        public static function clearMessages(): void { self::$messages = []; }
    }
}

if (!class_exists('Toolbox')) {
    class Toolbox {
        public static function sodiumEncrypt(string $value): string { return base64_encode($value); }
        public static function sodiumDecrypt(string $value): string { return base64_decode($value); }
        public static function logDebug(string $message): void {}
    }
}

if (!class_exists('Plugin')) {
    class Plugin {
        public static function getPhpDir(string $plugin): string { return GLPI_ROOT . '/plugins/' . $plugin; }
        public static function getWebDir(string $plugin): string { return '/plugins/' . $plugin; }
    }
}
