<?php

namespace GlpiPlugin\Newmanagement\Tests\Unit;

use GlpiPlugin\Newmanagement\Ipbx;
use PHPUnit\Framework\TestCase;

class IpbxTest extends TestCase
{
    public function test_format_credential_masks_password_without_update_right(): void
    {
        $this->assertSame('******', Ipbx::formatCredentialForDisplay('1234', false));
    }

    public function test_format_credential_shows_plaintext_for_update_right(): void
    {
        $this->assertSame('1234', Ipbx::formatCredentialForDisplay('1234', true));
    }

    public function test_render_extension_row_uses_mask_when_password_not_visible(): void
    {
        $html = Ipbx::renderExtensionRow(
            7,
            [
                'number'        => '1001',
                'password'      => '1234',
                'user_name'     => 'Operador',
                'device_ip'     => '192.168.0.10',
                'department'    => 'Suporte',
                'records_calls' => 1,
            ],
            3,
            'csrf',
            '/ajax/ipbx_sub.php',
            true,
            false
        );

        $this->assertStringContainsString('******', $html);
        $this->assertStringNotContainsString('<code>1234</code>', $html);
    }
}
