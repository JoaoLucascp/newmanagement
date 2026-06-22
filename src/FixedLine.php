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

    public static function getIcon(): string
    {
        return 'ti ti-phone';
    }

    public static function isValidPhoneNumber(string $phone): bool
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return true;
        }

        if (strlen($digits) === 12 || strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        $len = strlen($digits);

        if ($len !== 10 && $len !== 11) {
            return false;
        }

        $ddd = (int) substr($digits, 0, 2);
        if ($ddd < 11 || $ddd > 99) {
            return false;
        }

        if ($len === 11 && $digits[2] !== '9') {
            return false;
        }

        if (preg_match('/^(\d)\1+$/', $digits)) {
            return false;
        }

        return true;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

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

        if (isset($input['channels'])) {
            $input['channels'] = max(0, (int) $input['channels']);
        }
        if (isset($input['ddr_count'])) {
            $input['ddr_count'] = max(0, (int) $input['ddr_count']);
        }

        return $input;
    }

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        return ($item instanceof Company) ? self::createTabEntry(self::getTypeName(1)) : '';
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

        $can_write  = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete = \Session::haveRight(self::$rightname, DELETE);

        $ipbx_id = 0;
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
                'can_delete'   => $can_delete,
                'status_opts'  => [
                    1 => __('Ativo',     'newmanagement'),
                    2 => __('Cancelado', 'newmanagement'),
                ],
            ]
        );
    }

    /**
     * Exibe o formulário standalone de Linha Fixa (front/fixedline.php).
     *
     * Implementado seguindo o mesmo padrão de Task::showForm():
     * - Carrega lista de empresas para o <select>
     * - Passa csrf_token via Session::getNewCSRFToken()
     * - Renderiza @newmanagement/fixedline/form.html.twig
     * - Verifica permissões de escrita e exclusão
     */
    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);

        $can_write  = \Session::haveRight(self::$rightname, UPDATE);
        $can_delete = \Session::haveRight(self::$rightname, DELETE);

        $companies = getAllDataFromTable(
            Company::getTable(),
            ['is_deleted' => 0],
            false,
            'name'
        );

        TemplateRenderer::getInstance()->display(
            '@newmanagement/fixedline/form.html.twig',
            [
                'item'        => $this->fields + ['id' => $this->fields['id'] ?? 0],
                'companies'   => array_values($companies),
                'can_write'   => $can_write,
                'can_delete'  => $can_delete,
                'csrf_token'  => \Session::getNewCSRFToken(),
                'form_url'    => self::getFormURL(),
                'search_url'  => self::getSearchURL(),
            ]
        );

        return true;
    }
}
