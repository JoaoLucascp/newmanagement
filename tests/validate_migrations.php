<?php

/**
 * Newmanagement — CI: Validação de Migrations
 *
 * Verifica que todas as tabelas declaradas em hook/install.php existem
 * no banco e que as colunas obrigatórias estão presentes.
 *
 * Uso: php tests/validate_migrations.php
 * Exit 0 = OK | Exit 1 = falha
 */

if (!defined('GLPI_ROOT')) {
    // Permite rodar tanto em CI integrado ao GLPI quanto standalone
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

if (!file_exists(GLPI_ROOT . '/inc/includes.php')) {
    fwrite(STDERR, "[SKIP] GLPI não encontrado em " . GLPI_ROOT . ". Pulando validate_migrations.\n");
    exit(0);
}

require_once GLPI_ROOT . '/inc/includes.php';

/**
 * Tabelas e colunas obrigatórias do plugin Newmanagement.
 * Formato: 'nome_tabela' => ['coluna1', 'coluna2', ...]
 */
const EXPECTED_TABLES = [
    'glpi_plugin_newmanagement_companies' => [
        'id', 'name', 'cnpj', 'razao_social', 'email', 'phone',
        'cep', 'address', 'contract_status', 'comment',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_ipbx' => [
        'id', 'companies_id', 'model', 'server_version',
        'ip_local', 'ip_external', 'web_port', 'web_password',
        'ssh_port', 'ssh_password', 'comment',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_ipbx_extensions' => [
        'id', 'ipbx_id', 'companies_id', 'number', 'password',
        'device_ip', 'user_name', 'records_calls', 'department',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_ipbx_devices' => [
        'id', 'ipbx_id', 'companies_id', 'device_type',
        'ip_address', 'login', 'password',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_ipbx_network' => [
        'id', 'ipbx_id', 'companies_id', 'ip_network', 'netmask',
        'gateway', 'dns_primary', 'dns_secondary', 'supplier',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_ipbx_lines' => [
        'id', 'ipbx_id', 'companies_id', 'pilot_number', 'line_type',
        'operator', 'channels', 'ddr_count', 'proxy_ip', 'proxy_port',
        'audio_ip', 'portability_date', 'previous_operator',
        'activation_date', 'expiration_date', 'status', 'comment',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    // fix(C1/DB-01/DB-02): assigned_user_id e digital_signature obrigatórios
    'glpi_plugin_newmanagement_tasks' => [
        'id', 'name', 'companies_id', 'assigned_user_id', 'status',
        'date_due', 'km_calculated', 'latitude', 'longitude',
        'digital_signature', 'comment',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_chatbots' => [
        'id', 'companies_id', 'model', 'chatbot_registration_id',
        'activation_date', 'whatsapp_number', 'access_link', 'plan',
        'users_count', 'supervisors_count', 'admins_count',
        'admin_login', 'admin_password', 'superadmin_login',
        'superadmin_password', 'manager_name', 'manager_contact',
        'manager_email', 'social_networks', 'comment',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_chatbot_mass_comm' => [
        'id', 'chatbot_id', 'companies_id', 'system_name',
        'activation_date', 'authenticated_number', 'homologation_type',
        'access_link', 'login', 'password', 'manager',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_chatbot_wa_restrictions' => [
        'id', 'chatbot_id', 'companies_id', 'whatsapp_number',
        'restriction_date', 'restriction_time', 'end_date',
        'date_creation', 'date_mod', 'is_deleted',
    ],
    'glpi_plugin_newmanagement_chatbot_users' => [
        'id', 'chatbot_id', 'companies_id', 'user_name', 'login',
        'password', 'email', 'user_type',
        'date_creation', 'date_mod', 'is_deleted',
    ],
];

global $DB;
$errors = [];
$ok     = 0;

foreach (EXPECTED_TABLES as $table => $expectedCols) {
    if (!$DB->tableExists($table)) {
        $errors[] = "TABELA AUSENTE: {$table}";
        continue;
    }

    $actualCols = array_keys($DB->listFields($table));
    $missing    = array_diff($expectedCols, $actualCols);

    if ($missing) {
        foreach ($missing as $col) {
            $errors[] = "COLUNA AUSENTE: {$table}.{$col}";
        }
    } else {
        $ok++;
        echo "[OK] {$table}\n";
    }
}

if ($errors) {
    foreach ($errors as $err) {
        fwrite(STDERR, "[FAIL] {$err}\n");
    }
    exit(1);
}

echo "\n[PASS] validate_migrations: {$ok}/" . count(EXPECTED_TABLES) . " tabelas OK.\n";
exit(0);
