<?php

/**
 * tests/Integration/InstallTest.php
 *
 * Testes de integração: instalação, upgrade e desinstalação do plugin.
 *
 * Requer GLPI totalmente inicializado com banco de dados.
 * Execute a partir da raiz do GLPI:
 *
 *   ./vendor/bin/phpunit --configuration plugins/newmanagement/phpunit.xml \
 *                        --testsuite "Newmanagement Integration"
 *
 * IMPORTANTE: estes testes são DESTRUTIVOS — instalam e desinstalam
 * o plugin real. Use um banco de dados dedicado para testes.
 */

namespace GlpiPlugin\Newmanagement\Tests\Integration;

use PHPUnit\Framework\TestCase;

class InstallTest extends TestCase
{
    /**
     * Tabelas que devem existir após a instalação.
     *
     * fix(TE-01): corrigidos nomes com plural espurio:
     *   glpi_plugin_newmanagement_ipbxs        → glpi_plugin_newmanagement_ipbx
     *   glpi_plugin_newmanagement_ipbx_networks → glpi_plugin_newmanagement_ipbx_network
     */
    private const EXPECTED_TABLES = [
        'glpi_plugin_newmanagement_companies',
        'glpi_plugin_newmanagement_ipbx',
        'glpi_plugin_newmanagement_ipbx_extensions',
        'glpi_plugin_newmanagement_ipbx_devices',
        'glpi_plugin_newmanagement_ipbx_network',
        'glpi_plugin_newmanagement_ipbx_lines',
        'glpi_plugin_newmanagement_chatbots',
        'glpi_plugin_newmanagement_chatbot_mass_comm',
        'glpi_plugin_newmanagement_chatbot_wa_restrictions',
        'glpi_plugin_newmanagement_chatbot_users',
        'glpi_plugin_newmanagement_tasks',
    ];

    // ------------------------------------------------------------------
    // Pré-condição: GLPI deve estar inicializado
    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!defined('GLPI_ROOT')) {
            $this->markTestSkipped(
                'Testes de integração requerem GLPI inicializado. '
                . 'Execute via: ./vendor/bin/phpunit com o bootstrap do GLPI.'
            );
        }
    }

    // ------------------------------------------------------------------
    // Instalação
    // ------------------------------------------------------------------

    public function test_install_cria_todas_as_tabelas(): void
    {
        global $DB;

        $result = plugin_newmanagement_install();

        $this->assertTrue(
            $result,
            'plugin_newmanagement_install() deve retornar true.'
        );

        foreach (self::EXPECTED_TABLES as $table) {
            $this->assertTrue(
                $DB->tableExists($table),
                "Tabela '$table' deveria existir após a instalação."
            );
        }
    }

    // ------------------------------------------------------------------
    // Colunas obrigatórias na tabela principal
    // ------------------------------------------------------------------

    public function test_tabela_companies_tem_colunas_obrigatorias(): void
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugin_newmanagement_companies')) {
            $this->markTestSkipped('Tabela companies não existe — execute o install primeiro.');
        }

        $required_columns = [
            'id', 'name', 'cnpj', 'razao_social', 'phone', 'email',
            'address', 'contract_status', 'comment',
            'is_deleted', 'date_creation', 'date_mod',
        ];

        foreach ($required_columns as $column) {
            $this->assertTrue(
                $DB->fieldExists('glpi_plugin_newmanagement_companies', $column),
                "Coluna '$column' deveria existir em glpi_plugin_newmanagement_companies."
            );
        }
    }

    public function test_tabela_chatbots_nao_tem_coluna_senha_em_branco(): void
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugin_newmanagement_chatbots')) {
            $this->markTestSkipped('Tabela chatbots não existe — execute o install primeiro.');
        }

        $this->assertTrue(
            $DB->fieldExists('glpi_plugin_newmanagement_chatbots', 'admin_password'),
            'Coluna admin_password deve existir na tabela chatbots (armazena valor encriptado).'
        );
        $this->assertTrue(
            $DB->fieldExists('glpi_plugin_newmanagement_chatbots', 'superadmin_password'),
            'Coluna superadmin_password deve existir na tabela chatbots (armazena valor encriptado).'
        );
    }

    /**
     * fix(DB-01 / DB-02): verifica que assigned_user_id e digital_signature
     * foram criados na tabela tasks pelo install.php.
     */
    public function test_tabela_tasks_tem_campos_assigned_e_assinatura(): void
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugin_newmanagement_tasks')) {
            $this->markTestSkipped('Tabela tasks não existe — execute o install primeiro.');
        }

        $this->assertTrue(
            $DB->fieldExists('glpi_plugin_newmanagement_tasks', 'assigned_user_id'),
            'Coluna assigned_user_id deve existir na tabela tasks.'
        );
        $this->assertTrue(
            $DB->fieldExists('glpi_plugin_newmanagement_tasks', 'digital_signature'),
            'Coluna digital_signature deve existir na tabela tasks.'
        );
    }

    // ------------------------------------------------------------------
    // Desinstalação
    // ------------------------------------------------------------------

    public function test_uninstall_remove_todas_as_tabelas(): void
    {
        global $DB;

        $result = plugin_newmanagement_uninstall();

        $this->assertTrue(
            $result,
            'plugin_newmanagement_uninstall() deve retornar true.'
        );

        foreach (self::EXPECTED_TABLES as $table) {
            $this->assertFalse(
                $DB->tableExists($table),
                "Tabela '$table' deveria ter sido removida após a desinstalação."
            );
        }
    }

    // ------------------------------------------------------------------
    // Idempotência: instalar duas vezes não deve gerar erro
    // ------------------------------------------------------------------

    public function test_install_idempotente(): void
    {
        $first = plugin_newmanagement_install();
        $this->assertTrue($first, 'Primeira instalação deve retornar true.');

        $second = plugin_newmanagement_install();
        $this->assertTrue($second, 'Segunda instalação (idempotente) deve retornar true sem erro.');
    }
}
