<?php

/**
 * =============================================================================
 * SCRIPT DE MIGRAÇÃO — Criptografar senhas existentes de ramais e dispositivos
 * =============================================================================
 *
 * Contexto:
 *   Após o commit que passou a usar Toolbox::sodiumEncrypt() em add_extension
 *   e add_device, os registros anteriores ainda possuías senhas em texto puro.
 *   Este script detecta e criptografa apenas esses registros antigos.
 *
 * Como executar (via CLI, fora do webserver):
 *   php scripts/migrate_encrypt_ipbx_passwords.php
 *
 * Pré-requisitos:
 *   - Executar a partir da raiz do plugin (glpi/plugins/newmanagement/)
 *   - O GLPI deve estar instalado e acessível via ../../../inc/includes.php
 *   - PHP com ext-sodium habilitado (mesmo requisito do GLPI 11)
 *
 * Segurança:
 *   - O script NÃO re-encripta valores já criptografados (detecção via sodiumDecrypt)
 *   - Em caso de erro em um registro, o script continua e reporta ao final
 *   - Faça backup do banco antes de executar em produção
 * =============================================================================
 */

// Garante execução apenas via CLI
if (php_sapi_name() !== 'cli') {
    echo json_encode(['error' => 'Este script só pode ser executado via CLI.']);
    exit(1);
}

define('GLPI_ROOT', dirname(__FILE__, 4)); // glpi/
include('../../../inc/includes.php');

global $DB;

// ---------------------------------------------------------------------------
// Helper: detecta se um valor já está criptografado tentando descriptografar
// ---------------------------------------------------------------------------
function isAlreadyEncrypted(string $value): bool {
    if ($value === '') {
        return true; // vazio: não precisa processar
    }
    try {
        $result = \Toolbox::sodiumDecrypt($value);
        return $result !== false;
    } catch (\Throwable $e) {
        return false; // falhou na descriptografia = está em texto puro
    }
}

// ---------------------------------------------------------------------------
// Função genérica que processa uma tabela
// ---------------------------------------------------------------------------
function migrateTable(string $table, string $passwordColumn): array {
    global $DB;

    $updated = 0;
    $skipped = 0;
    $errors  = [];

    $rows = $DB->request(['FROM' => $table, 'FIELDS' => ['id', $passwordColumn]]);

    foreach ($rows as $row) {
        $id  = (int)$row['id'];
        $pwd = (string)($row[$passwordColumn] ?? '');

        // Pula vazios e já criptografados
        if (isAlreadyEncrypted($pwd)) {
            $skipped++;
            echo "  [SKIP] {$table}#{$id} — já criptografado ou vazio\n";
            continue;
        }

        try {
            $encrypted = \Toolbox::sodiumEncrypt($pwd);
            $DB->update(
                $table,
                [$passwordColumn => $encrypted],
                ['id'            => $id]
            );
            $updated++;
            echo "  [OK]   {$table}#{$id} — senha criptografada\n";
        } catch (\Throwable $e) {
            $errors[] = "Erro em {$table}#{$id}: " . $e->getMessage();
            echo "  [ERR]  {$table}#{$id} — " . $e->getMessage() . "\n";
        }
    }

    return compact('updated', 'skipped', 'errors');
}

// ---------------------------------------------------------------------------
// Execução
// ---------------------------------------------------------------------------
$tables = [
    'glpi_plugin_newmanagement_ipbx_extensions' => 'password',
    'glpi_plugin_newmanagement_ipbx_devices'    => 'password',
];

echo "\n=== MIGRAÇÃO: Criptografar senhas de ramais e dispositivos IPBX ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

$totalUpdated = 0;
$totalSkipped = 0;
$totalErrors  = [];

foreach ($tables as $table => $column) {
    echo "\n--- Tabela: {$table} (coluna: {$column}) ---\n";
    $result = migrateTable($table, $column);
    $totalUpdated += $result['updated'];
    $totalSkipped += $result['skipped'];
    $totalErrors   = array_merge($totalErrors, $result['errors']);
}

// ---------------------------------------------------------------------------
// Resumo final
// ---------------------------------------------------------------------------
echo "\n========================================\n";
echo "RESUMO:\n";
echo "  Registros atualizados : {$totalUpdated}\n";
echo "  Registros ignorados   : {$totalSkipped}\n";
echo "  Erros                 : " . count($totalErrors) . "\n";

if (!empty($totalErrors)) {
    echo "\nDetalhes dos erros:\n";
    foreach ($totalErrors as $err) {
        echo "  - {$err}\n";
    }
    exit(1);
}

echo "\nMigração concluída com sucesso.\n";
exit(0);
