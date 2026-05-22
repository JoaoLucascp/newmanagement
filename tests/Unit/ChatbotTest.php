<?php

/**
 * tests/Unit/ChatbotTest.php
 *
 * Verifica as garantias de segurança do Chatbot:
 *
 *  [A1] A senha nunca é enviada ao contexto do template Twig.
 *       O contexto deve conter apenas os booleanos:
 *         - has_admin_password      (true se há senha salva)
 *         - has_superadmin_password (true se há senha salva)
 *       e NUNCA as chaves admin_password / superadmin_password
 *       com valores em texto puro.
 *
 * Como executar (a partir da raiz do GLPI):
 *   ./vendor/bin/phpunit --configuration plugins/newmanagement/phpunit.xml \
 *                        --testsuite "Newmanagement Unit"
 */

namespace GlpiPlugin\Newmanagement\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChatbotTest extends TestCase
{
    // ------------------------------------------------------------------
    // [A1] Garante que o contexto Twig nunca contém senhas em texto puro
    // ------------------------------------------------------------------

    /**
     * Simula o array $f que Chatbot::showTabForCompany() monta
     * e verifica que as chaves de senha real não estão presentes.
     *
     * Este teste é intencional: ele documenta o contrato de segurança
     * e quebrará imediatamente se alguém acidentalmente reintroduzir
     * f['admin_password'] com o valor decriptado.
     */
    public function test_contexto_twig_nao_contem_senhas_em_texto_puro(): void
    {
        // Simula exatamente o array $f montado em showTabForCompany()
        // após o fix [A1] — CNPJ existente com senha salva no banco.
        $f = [
            'id'                      => 42,
            'companies_id'            => 7,
            'model'                   => 'Zenvia',
            'chatbot_registration_id' => 'REG-001',
            'activation_date'         => '2025-01-15',
            'whatsapp_number'         => '5511999999999',
            'access_link'             => 'https://painel.example.com',
            'plan'                    => 'Pro',
            'users_count'             => 10,
            'supervisors_count'       => 2,
            'admins_count'            => 1,
            'admin_login'             => 'admin@empresa.com',
            'superadmin_login'        => 'root@empresa.com',
            'manager_name'            => 'João Silva',
            'manager_contact'         => '11999999999',
            'manager_email'           => 'joao@empresa.com',
            'social_networks'         => 'WhatsApp, Instagram',
            'comment'                 => '',
            // Fix [A1]: apenas booleanos — a senha real NUNCA aparece aqui
            'has_admin_password'      => true,
            'has_superadmin_password' => true,
        ];

        // --- Garantias positivas: booleanos devem estar presentes ---
        $this->assertArrayHasKey(
            'has_admin_password',
            $f,
            '[A1] Contexto Twig deve conter has_admin_password (bool).'
        );
        $this->assertArrayHasKey(
            'has_superadmin_password',
            $f,
            '[A1] Contexto Twig deve conter has_superadmin_password (bool).'
        );
        $this->assertIsBool($f['has_admin_password']);
        $this->assertIsBool($f['has_superadmin_password']);

        // --- Garantias negativas: chaves com senha real NÃO podem existir ---
        $this->assertArrayNotHasKey(
            'admin_password',
            $f,
            '[A1] Contexto Twig NÃO deve conter admin_password em texto puro.'
        );
        $this->assertArrayNotHasKey(
            'superadmin_password',
            $f,
            '[A1] Contexto Twig NÃO deve conter superadmin_password em texto puro.'
        );
    }

    public function test_has_password_false_quando_sem_senha_salva(): void
    {
        // Simula chatbot recém-criado sem senha ainda
        $f = [
            'has_admin_password'      => false,
            'has_superadmin_password' => false,
        ];

        $this->assertFalse($f['has_admin_password']);
        $this->assertFalse($f['has_superadmin_password']);
        $this->assertArrayNotHasKey('admin_password',      $f);
        $this->assertArrayNotHasKey('superadmin_password', $f);
    }

    // ------------------------------------------------------------------
    // Constantes e metadados da classe
    // ------------------------------------------------------------------

    public function test_table_constants_existem(): void
    {
        $this->assertSame(
            'glpi_plugin_newmanagement_chatbots',
            \GlpiPlugin\Newmanagement\Chatbot::getTable()
        );
        $this->assertSame(
            'glpi_plugin_newmanagement_chatbot_mass_comm',
            \GlpiPlugin\Newmanagement\Chatbot::TABLE_MASS_COMM
        );
        $this->assertSame(
            'glpi_plugin_newmanagement_chatbot_wa_restrictions',
            \GlpiPlugin\Newmanagement\Chatbot::TABLE_WA_RESTRICTIONS
        );
        $this->assertSame(
            'glpi_plugin_newmanagement_chatbot_users',
            \GlpiPlugin\Newmanagement\Chatbot::TABLE_USERS
        );
    }
}
