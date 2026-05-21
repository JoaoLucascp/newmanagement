<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: FixedLine (Linhas Fixas)
 *
 * Nota: $rightname reutiliza 'plugin_newmanagement_ipbx' intencionalmente,
 * pois Linhas Fixas faz parte do módulo IPBX e compartilha suas permissões.
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

use Glpi\Application\View\TemplateRenderer;

class FixedLine extends \CommonDBTM
{
    /** Compartilha as permissões do módulo IPBX (intencional). */
    public static $rightname = 'plugin_newmanagement_ipbx';
    public static $itemtype  = Company::class;
    public static $items_id  = 'companies_id';

    public static function getTypeName($nb = 0): string
    {
        return _n('Linha Fixa', 'Linhas Fixas', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_ipbx_lines';
    }

    // -------------------------------------------------------
    // Validação de número de telefone (backend)
    // -------------------------------------------------------

    /**
     * Valida número de telefone brasileiro.
     * Aceita formatos: (XX) 9XXXX-XXXX, (XX) XXXX-XXXX, +55XXXXXXXXXXX
     * Retorna true se válido ou se estiver em branco (campo opcional).
     */
    public static function isValidPhoneNumber(string $phone): bool
    {
        $digits = preg_replace('/\D/', '', $phone);

        // Aceita vazio (campo opcional)
        if ($digits === '') {
            return true;
        }

        // Com DDI +55: 12 dígitos (fixo) ou 13 (celular)
        if (strlen($digits) === 12 || strlen($digits) === 13) {
            // Remove o DDI 55 e valida o restante
            $digits = substr($digits, 2);
        }

        $len = strlen($digits);

        // Fixo: DDD (2) + 8 dígitos = 10 | Celular: DDD (2) + 9 dígitos = 11
        if ($len !== 10 && $len !== 11) {
            return false;
        }

        // DDD válido: 11 a 99 (exclui 00-10 que não existem)
        $ddd = (int) substr($digits, 0, 2);
        if ($ddd < 11 || $ddd > 99) {
            return false;
        }

        // Celular com 11 dígitos deve começar com 9
        if ($len === 11 && $digits[2] !== '9') {
            return false;
        }

        // Rejeita sequências repetidas (ex: 11111111111)
        if (preg_match('/^(\d)\1+$/', $digits)) {
            return false;
        }

        return true;
    }

    /**
     * Validação chamada antes de INSERT.
     * [FIX] pilot_number validado no backend.
     */
    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    /**
     * Validação chamada antes de UPDATE.
     * [FIX] pilot_number validado no backend.
     */
    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    /**
     * Lógica comum de validação para add e update.
     */
    private function prepareInput(array $input)
    {
        if (!empty($input['pilot_number'])) {
            if (!self::isValidPhoneNumber($input['pilot_number'])) {
                \Session::addMessageAfterRedirect(
                    __('Número piloto inválido. Use o formato (DDD) XXXX-XXXX ou (DDD) 9XXXX-XXXX.', 'newmanagement'),
                    true,
                    ERROR
                );
                return false;
            }
        }

        // channels e ddr_count devem ser inteiros não negativos
        if (isset($input['channels'])) {
            $input['channels'] = max(0, (int) $input['channels']);
        }
        if (isset($input['ddr_count'])) {
            $input['ddr_count'] = max(0, (int) $input['ddr_count']);
        }

        return $input;
    }

    // -------------------------------------------------------
    // Tab / display
    // -------------------------------------------------------

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        return ($item instanceof Company) ? self::getTypeName(1) : '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Company) {
            (new self())->displayTabContentForCompany((int) $item->getID());
        }
        return true;
    }

    public function displayTabContentForCompany(int $companies_id): void
    {
        global $DB;

        if (!\Session::haveRight(self::$rightname, READ)) {
            echo __('Acesso negado.', 'newmanagement');
            return;
        }

        $can_write = \Session::haveRight(self::$rightname, UPDATE);

        // Busca o IPBX vinculado a esta empresa
        $ipbx_id  = 0;
        foreach ($DB->request([
            'FROM'  => Ipbx::getTable(),
            'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0],
            'LIMIT' => 1,
        ]) as $ipbx_row) {
            $ipbx_id = (int) $ipbx_row['id'];
        }

        $line_id = 0;
        $f = [
            'pilot_number'      => '',
            'ddr_count'         => '',
            'status'            => 1,
            'operator'          => '',
            'channels'          => '',
            'proxy_ip'          => '',
            'line_type'         => '',
            'audio_ip'          => '',
            'proxy_port'        => '',
            'portability_date'  => '',
            'previous_operator' => '',
            'activation_date'   => '',
            'expiration_date'   => '',
            'comment'           => '',
        ];

        if ($ipbx_id > 0) {
            foreach ($DB->request([
                'FROM'  => self::getTable(),
                'WHERE' => ['ipbx_id' => $ipbx_id],
                'LIMIT' => 1,
            ]) as $row) {
                $line_id = (int) $row['id'];
                foreach (array_keys($f) as $k) {
                    if (isset($row[$k])) $f[$k] = $row[$k];
                }
            }
        }

        TemplateRenderer::getInstance()->display(
            '@newmanagement/fixedline/tab.html.twig',
            [
                'f'            => $f,
                'line_id'      => $line_id,
                'ipbx_id'      => $ipbx_id,
                'companies_id' => $companies_id,
                'csrf'         => \Session::getNewCSRFToken(),
                'action_url'   => \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php',
                'form_action'  => $line_id > 0 ? 'update_line' : 'add_line',
                'can_write'    => $can_write,
                'status_opts'  => [
                    1 => __('Ativo',     'newmanagement'),
                    2 => __('Cancelado', 'newmanagement'),
                ],
            ]
        );
    }
}
