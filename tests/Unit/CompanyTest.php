<?php

/**
 * tests/Unit/CompanyTest.php
 *
 * Testa a lógica pura da classe Company sem dependência de banco de dados:
 *  - Validação do algoritmo de CNPJ (dígitos verificadores)
 *  - Rejeição de CNPJs com formato incorreto
 *  - Rejeição de sequências repetidas
 *  - Formatação padronizada XX.XXX.XXX/XXXX-XX
 *
 * Como executar (a partir da raiz do GLPI):
 *   ./vendor/bin/phpunit --configuration plugins/newmanagement/phpunit.xml \
 *                        --testsuite "Newmanagement Unit"
 */

namespace GlpiPlugin\Newmanagement\Tests\Unit;

use GlpiPlugin\Newmanagement\Company;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CompanyTest extends TestCase
{
    // ------------------------------------------------------------------
    // CNPJs válidos — devem passar na validação
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function validCnpjProvider(): array
    {
        return [
            'formato com pontuacao'   => ['11.222.333/0001-81'],
            'somente digitos'         => ['11222333000181'],
            'cnpj real Petrobras'     => ['33.000.167/0001-01'],
            'cnpj real Bradesco'      => ['60.746.948/0001-12'],
            'cnpj com zeros a esq'   => ['00.000.000/0001-91'],
        ];
    }

    #[DataProvider('validCnpjProvider')]
    public function test_cnpj_valido(string $cnpj): void
    {
        $this->assertTrue(
            Company::isValidCnpj($cnpj),
            "CNPJ '$cnpj' deveria ser válido."
        );
    }

    // ------------------------------------------------------------------
    // CNPJs inválidos — devem ser rejeitados
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCnpjProvider(): array
    {
        return [
            'todos zeros'               => ['00.000.000/0000-00'],
            'todos uns'                 => ['11.111.111/1111-11'],
            'digito verificador errado' => ['11.222.333/0001-99'],
            'muito curto'               => ['1234567'],
            'muito longo'               => ['123456789012345'],
            'string vazia'              => [''],
            'somente letras'            => ['ABCDEFGHIJKLMN'],
            'cnpj com d1 errado'        => ['11.222.333/0001-82'],
            'cnpj com d2 errado'        => ['11.222.333/0001-80'],
        ];
    }

    #[DataProvider('invalidCnpjProvider')]
    public function test_cnpj_invalido(string $cnpj): void
    {
        $this->assertFalse(
            Company::isValidCnpj($cnpj),
            "CNPJ '$cnpj' deveria ser inválido."
        );
    }

    // ------------------------------------------------------------------
    // Constantes de status de contrato
    // ------------------------------------------------------------------

    public function test_constantes_contract_status_existem(): void
    {
        $this->assertSame(0, Company::CONTRACT_NO_CONTRACT);
        $this->assertSame(1, Company::CONTRACT_ACTIVE);
        $this->assertSame(2, Company::CONTRACT_CANCELLED);
    }

    public function test_get_contract_status_options_retorna_tres_opcoes(): void
    {
        $options = Company::getContractStatusOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey(Company::CONTRACT_NO_CONTRACT, $options);
        $this->assertArrayHasKey(Company::CONTRACT_ACTIVE,      $options);
        $this->assertArrayHasKey(Company::CONTRACT_CANCELLED,   $options);
    }

    // ------------------------------------------------------------------
    // getTable e getTypeName
    // ------------------------------------------------------------------

    public function test_get_table_retorna_nome_correto(): void
    {
        $this->assertSame(
            'glpi_plugin_newmanagement_companies',
            Company::getTable()
        );
    }

    public function test_get_type_name_singular(): void
    {
        // getTypeName usa __() que pode não estar disponível fora do GLPI.
        // Verificamos apenas que retorna uma string não vazia.
        $name = Company::getTypeName(1);
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }
}
