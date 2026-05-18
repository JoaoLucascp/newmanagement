<?php

/**
 * Newmanagement - Plugin GLPI
 * Classe: Chatbot — aba dentro da ficha de Empresa
 */

namespace GlpiPlugin\Newmanagement;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

class Chatbot extends \CommonDBTM
{
    public static $rightname = 'plugin_newmanagement_chatbot';

    public static $itemtype = Company::class;
    public static $items_id = 'companies_id';

    public static function getTypeName($nb = 0): string
    {
        return _n('Chatbot', 'Chatbots', $nb, 'newmanagement');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_newmanagement_chatbots';
    }

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Company) {
            return self::getTypeName(1);
        }
        return '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Company) {
            $chatbot = new self();
            $chatbot->showTabForCompany((int) $item->getID());
        }
        return true;
    }

    public function defineTabs($options = []): array
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        return $ong;
    }

    public function showTabForCompany(int $companies_id): void
    {
        global $DB;

        $rows = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['companies_id' => $companies_id, 'is_deleted' => 0],
            'LIMIT' => 1,
        ]);

        $chatbot_id = 0;
        $f = [
            'id'                      => 0,
            'companies_id'            => $companies_id,
            'model'                   => '',
            'chatbot_registration_id' => '',
            'activation_date'         => '',
            'whatsapp_number'         => '',
            'access_link'             => '',
            'plan'                    => '',
            'users_count'             => '',
            'supervisors_count'       => '',
            'admins_count'            => '',
            'admin_login'             => '',
            'admin_password'          => '',
            'superadmin_login'        => '',
            'superadmin_password'     => '',
            'manager_name'            => '',
            'manager_contact'         => '',
            'manager_email'           => '',
            'social_networks'         => '',
            'comment'                 => '',
        ];

        foreach ($rows as $row) {
            foreach (array_keys($f) as $key) {
                if (isset($row[$key])) {
                    $f[$key] = $row[$key];
                }
            }
            $chatbot_id = (int) $row['id'];

            try {
                if (!empty($f['admin_password'])) {
                    $f['admin_password'] = \Toolbox::sodiumDecrypt($f['admin_password']);
                }
            } catch (\Throwable $e) {
                // fallback: texto puro legado
            }
            try {
                if (!empty($f['superadmin_password'])) {
                    $f['superadmin_password'] = \Toolbox::sodiumDecrypt($f['superadmin_password']);
                }
            } catch (\Throwable $e) {
                // fallback: texto puro legado
            }
        }

        $csrf        = \Session::getNewCSRFToken();
        $action      = \Plugin::getWebDir('newmanagement') . '/ajax/chatbot_sub.php';
        $form_action = $chatbot_id > 0 ? 'update_chatbot' : 'add_chatbot';

        $v = function (string $key) use ($f): string {
            return htmlspecialchars((string) ($f[$key] ?? ''), ENT_QUOTES);
        };

        echo '<div class="nm-chatbot-tab" data-action-url="' . htmlspecialchars($action, ENT_QUOTES) . '" data-companies-id="' . $companies_id . '">';

        // ── Formulário principal ──────────────────────────────────────────────
        // FIX: sem method/action no <form> — o GLPI já emite um <form> externo
        // que envolve toda a página. Formulários aninhados são HTML inválido e
        // causam [DOM] Multiple forms no console do Chrome. O envio é feito via
        // fetch() no nm-chatbot-save abaixo.
        echo '<div id="nm-chatbot-form-wrapper">';
        echo '<input type="hidden" id="nm-chatbot-csrf"          value="' . $csrf . '">';
        echo '<input type="hidden" id="nm-chatbot-action"        value="' . $form_action . '">';
        echo '<input type="hidden" id="nm-chatbot-id"            value="' . $chatbot_id . '">';
        echo '<input type="hidden" id="nm-chatbot-companies-id" value="' . $companies_id . '">';

        echo '<table class="tab_cadre_fixe nm-table">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo do Chatbot', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-model" name="model" value="' . $v('model') . '" class="form-control"></td>';
        echo '<td>' . __('ID de Cadastro', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-registration_id" name="chatbot_registration_id" value="' . $v('chatbot_registration_id') . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Data de Ativação', 'newmanagement') . '</td>';
        echo '<td><input type="date" id="nm-chatbot-activation_date" name="activation_date" value="' . $v('activation_date') . '" class="form-control"></td>';
        echo '<td>' . __('Número WhatsApp', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-whatsapp" name="whatsapp_number" value="' . $v('whatsapp_number') . '" class="form-control" placeholder="Ex: 5511999999999"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Link de Acesso', 'newmanagement') . '</td>';
        echo '<td><input type="url" id="nm-chatbot-access_link" name="access_link" value="' . $v('access_link') . '" class="form-control" placeholder="https://"></td>';
        echo '<td>' . __('Plano Contratado', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-plan" name="plan" value="' . $v('plan') . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Qtd. Usuários', 'newmanagement') . '</td>';
        echo '<td><input type="number" id="nm-chatbot-users_count" name="users_count" value="' . $v('users_count') . '" class="form-control" min="0"></td>';
        echo '<td>' . __('Qtd. Supervisores', 'newmanagement') . '</td>';
        echo '<td><input type="number" id="nm-chatbot-supervisors_count" name="supervisors_count" value="' . $v('supervisors_count') . '" class="form-control" min="0"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Qtd. Administradores', 'newmanagement') . '</td>';
        echo '<td><input type="number" id="nm-chatbot-admins_count" name="admins_count" value="' . $v('admins_count') . '" class="form-control" min="0"></td>';
        echo '<td>' . __('Redes Sociais Ativas', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-social_networks" name="social_networks" value="' . $v('social_networks') . '" class="form-control" placeholder="Ex: WhatsApp, Instagram, Telegram"></td>';
        echo '</tr>';

        echo '<tr class="noHover"><th colspan="4">' . __('Credenciais de Administrador', 'newmanagement') . '</th></tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Login Admin', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-admin_login" name="admin_login" value="' . $v('admin_login') . '" class="form-control" autocomplete="off"></td>';
        echo '<td>' . __('Senha Admin', 'newmanagement') . '</td>';
        echo '<td><div class="nm-input-group">';
        echo '  <input type="password" id="nm-chatbot-admin_password" name="admin_password" value="' . $v('admin_password') . '" class="form-control" autocomplete="new-password">';
        echo '  <button type="button" class="nm-btn-eye" data-target="nm-chatbot-admin_password" title="Mostrar/Ocultar"><i class="ti ti-eye"></i></button>';
        echo '</div></td></tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Login Super-Admin', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-superadmin_login" name="superadmin_login" value="' . $v('superadmin_login') . '" class="form-control" autocomplete="off"></td>';
        echo '<td>' . __('Senha Super-Admin', 'newmanagement') . '</td>';
        echo '<td><div class="nm-input-group">';
        echo '  <input type="password" id="nm-chatbot-superadmin_password" name="superadmin_password" value="' . $v('superadmin_password') . '" class="form-control" autocomplete="new-password">';
        echo '  <button type="button" class="nm-btn-eye" data-target="nm-chatbot-superadmin_password" title="Mostrar/Ocultar"><i class="ti ti-eye"></i></button>';
        echo '</div></td></tr>';

        echo '<tr class="noHover"><th colspan="4">' . __('Responsável pelo Chatbot', 'newmanagement') . '</th></tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Nome do Responsável', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-manager_name" name="manager_name" value="' . $v('manager_name') . '" class="form-control"></td>';
        echo '<td>' . __('Contato do Responsável', 'newmanagement') . '</td>';
        echo '<td><input type="text" id="nm-chatbot-manager_contact" name="manager_contact" value="' . $v('manager_contact') . '" class="form-control" placeholder="(00) 00000-0000"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('E-mail do Responsável', 'newmanagement') . '</td>';
        echo '<td colspan="3"><input type="email" id="nm-chatbot-manager_email" name="manager_email" value="' . $v('manager_email') . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea id="nm-chatbot-comment" name="comment" class="form-control" rows="2">' . $v('comment') . '</textarea></td>';
        echo '</tr>';

        echo '</table>';
        echo '</div>'; // #nm-chatbot-form-wrapper

        // ── Comunicação em Massa ──────────────────────────────────────────────
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-send"></i> ' . __('Comunicação em Massa', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderMassCommTable($chatbot_id, $companies_id, $csrf, $action);
        echo '</div></div>';

        // ── Números Restritos pela Meta ───────────────────────────────────────
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-ban"></i> ' . __('Números Restritos pela Meta', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderWhatsappRestrictionsTable($chatbot_id, $companies_id, $csrf, $action);
        echo '</div></div>';

        // ── Usuários Cadastrados ──────────────────────────────────────────────
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-users"></i> ' . __('Usuários Cadastrados no Chatbot', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderUsersTable($chatbot_id, $companies_id, $csrf, $action);
        echo '</div></div>';

        // ── Botão Salvar ──────────────────────────────────────────────────────
        echo '<div style="text-align:right;padding:var(--space-4,1rem) 0">';
        echo '<button type="button" id="nm-chatbot-save" class="btn btn-primary"';
        echo ' data-action-url="' . htmlspecialchars($action, ENT_QUOTES) . '">';
        echo '<i class="ti ti-device-floppy"></i> ' . __('Salvar Chatbot', 'newmanagement') . '</button>';
        echo '</div>';

        echo '</div>'; // .nm-chatbot-tab
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Comunicação em Massa
    // ──────────────────────────────────────────────────────────────────────────
    private function renderMassCommTable(int $chatbot_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;
        $rows = ($chatbot_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_chatbot_mass_comm', 'WHERE' => ['chatbot_id' => $chatbot_id], 'ORDER' => 'id ASC'])
            : [];

        $h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);
        $au = $h($action);

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<tr class="noHover">';
        foreach (['Nome do Sistema','Data Ativação','Número Autenticado','Tipo Homologação','Link de Acesso','Login','Responsável','Ação'] as $th) {
            echo '<th>' . __($th, 'newmanagement') . '</th>';
        }
        echo '</tr>';

        foreach ($rows as $row) {
            $rid = (int) $row['id'];
            echo '<tr class="tab_bg_1" id="nm-mc-row-' . $rid . '">';
            echo '<td>' . $h($row['system_name']         ?? '') . '</td>';
            echo '<td>' . $h($row['activation_date']     ?? '') . '</td>';
            echo '<td>' . $h($row['authenticated_number'] ?? '') . '</td>';
            echo '<td>' . $h($row['homologation_type']   ?? '') . '</td>';
            echo '<td>' . (!empty($row['access_link']) ? '<a href="' . $h($row['access_link']) . '" target="_blank" rel="noopener"><i class="ti ti-external-link"></i></a>' : '') . '</td>';
            echo '<td>' . $h($row['login']               ?? '') . '</td>';
            echo '<td>' . $h($row['manager']             ?? '') . '</td>';
            // FIX: botão type="button" + data-* em vez de <form> aninhado
            echo '<td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"'
                . ' data-action="delete_mass_comm" data-id="' . $rid . '"'
                . ' data-row="nm-mc-row-' . $rid . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-url="' . $au . '"'
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-confirm="' . __('Remover?', 'newmanagement') . '">'
                . '<i class="ti ti-trash"></i></button></td>';
            echo '</tr>';
        }

        // Linha de adição — inputs com IDs, botão type="button"
        echo '<tr class="tab_bg_2 nm-add-row" id="nm-mc-add-row">';
        echo '<td><input type="text"  id="nm-mc-system_name"          class="form-control form-control-sm" placeholder="' . __('Nome', 'newmanagement') . '"></td>';
        echo '<td><input type="date"  id="nm-mc-activation_date"      class="form-control form-control-sm"></td>';
        echo '<td><input type="text"  id="nm-mc-authenticated_number" class="form-control form-control-sm" placeholder="5511..."></td>';
        echo '<td><input type="text"  id="nm-mc-homologation_type"    class="form-control form-control-sm" placeholder="Ex: BSP"></td>';
        echo '<td><input type="url"   id="nm-mc-access_link"          class="form-control form-control-sm" placeholder="https://"></td>';
        echo '<td><input type="text"  id="nm-mc-login"                class="form-control form-control-sm"></td>';
        echo '<td><input type="text"  id="nm-mc-manager"              class="form-control form-control-sm"></td>';
        echo '<td><button type="button" id="nm-mc-add-btn" class="btn btn-sm btn-success"'
            . ' data-action="add_mass_comm"'
            . ' data-chatbot-id="' . $chatbot_id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . $au . '"'
            . ' data-csrf="' . $h($csrf) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar', 'newmanagement') . '</button></td>';
        echo '</tr>';

        echo '</table>';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Números Restritos pela Meta
    // ──────────────────────────────────────────────────────────────────────────
    private function renderWhatsappRestrictionsTable(int $chatbot_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;
        $rows = ($chatbot_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_chatbot_wa_restrictions', 'WHERE' => ['chatbot_id' => $chatbot_id], 'ORDER' => 'restriction_date DESC'])
            : [];

        $h  = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);
        $au = $h($action);

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<tr class="noHover">';
        foreach (['Número WhatsApp','Data da Restrição','Tempo de Restrição','Ação'] as $th) {
            echo '<th>' . __($th, 'newmanagement') . '</th>';
        }
        echo '</tr>';

        foreach ($rows as $row) {
            $rid = (int) $row['id'];
            echo '<tr class="tab_bg_1" id="nm-wa-row-' . $rid . '">';
            echo '<td>' . $h($row['whatsapp_number']  ?? '') . '</td>';
            echo '<td>' . $h($row['restriction_date'] ?? '') . '</td>';
            echo '<td>' . $h($row['restriction_time'] ?? '') . '</td>';
            echo '<td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"'
                . ' data-action="delete_wa_restriction" data-id="' . $rid . '"'
                . ' data-row="nm-wa-row-' . $rid . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-url="' . $au . '"'
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-confirm="' . __('Remover?', 'newmanagement') . '">'
                . '<i class="ti ti-trash"></i></button></td>';
            echo '</tr>';
        }

        echo '<tr class="tab_bg_2 nm-add-row" id="nm-wa-add-row">';
        echo '<td><input type="text" id="nm-wa-whatsapp_number"  class="form-control form-control-sm" placeholder="5511..."></td>';
        echo '<td><input type="date" id="nm-wa-restriction_date" class="form-control form-control-sm"></td>';
        echo '<td><input type="text" id="nm-wa-restriction_time" class="form-control form-control-sm" placeholder="Ex: 24h, 7 dias"></td>';
        echo '<td><button type="button" id="nm-wa-add-btn" class="btn btn-sm btn-success"'
            . ' data-action="add_wa_restriction"'
            . ' data-chatbot-id="' . $chatbot_id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . $au . '"'
            . ' data-csrf="' . $h($csrf) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar', 'newmanagement') . '</button></td>';
        echo '</tr>';

        echo '</table>';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Usuários Cadastrados no Chatbot
    // ──────────────────────────────────────────────────────────────────────────
    private function renderUsersTable(int $chatbot_id, int $companies_id, string $csrf, string $action): void
    {
        global $DB;
        $rows = ($chatbot_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_chatbot_users', 'WHERE' => ['chatbot_id' => $chatbot_id], 'ORDER' => 'user_name ASC'])
            : [];

        $h  = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);
        $au = $h($action);

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<tr class="noHover">';
        foreach (['Nome','Login','Senha','E-mail','Tipo','Ação'] as $th) {
            echo '<th>' . __($th, 'newmanagement') . '</th>';
        }
        echo '</tr>';

        foreach ($rows as $row) {
            $rid = (int) $row['id'];
            echo '<tr class="tab_bg_1" id="nm-cu-row-' . $rid . '">';
            echo '<td>' . $h($row['user_name'] ?? '') . '</td>';
            echo '<td>' . $h($row['login']     ?? '') . '</td>';
            echo '<td>••••••</td>';
            echo '<td>' . $h($row['email']     ?? '') . '</td>';
            echo '<td>' . $h($row['user_type'] ?? '') . '</td>';
            echo '<td><button type="button" class="btn btn-sm btn-danger nm-chatbot-del"'
                . ' data-action="delete_chatbot_user" data-id="' . $rid . '"'
                . ' data-row="nm-cu-row-' . $rid . '"'
                . ' data-companies-id="' . $companies_id . '"'
                . ' data-url="' . $au . '"'
                . ' data-csrf="' . $h($csrf) . '"'
                . ' data-confirm="' . __('Remover usuário?', 'newmanagement') . '">'
                . '<i class="ti ti-trash"></i></button></td>';
            echo '</tr>';
        }

        // Linha de adição — campo password dentro de role="presentation"
        // para satisfazer o Chrome sem criar form aninhado real
        echo '<tr class="tab_bg_2 nm-add-row" id="nm-cu-add-row">';
        echo '<td><input type="text"     id="nm-cu-user_name"  class="form-control form-control-sm" placeholder="' . __('Nome', 'newmanagement') . '"></td>';
        echo '<td><input type="text"     id="nm-cu-login"      class="form-control form-control-sm" placeholder="' . __('Login', 'newmanagement') . '"></td>';
        // FIX: campo password isolado num fieldset com role="presentation"
        // evita o aviso "Password field is not contained in a form"
        echo '<td><fieldset role="presentation" style="border:none;padding:0;margin:0">';
        echo '  <input type="password" id="nm-cu-password" class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" autocomplete="new-password">';
        echo '</fieldset></td>';
        echo '<td><input type="email"    id="nm-cu-email"      class="form-control form-control-sm" placeholder="email@"></td>';
        echo '<td><select id="nm-cu-user_type" class="form-select form-select-sm">';
        echo '  <option value="usuario">'       . __('Usuário',        'newmanagement') . '</option>';
        echo '  <option value="supervisor">'    . __('Supervisor',     'newmanagement') . '</option>';
        echo '  <option value="administrador">' . __('Administrador',  'newmanagement') . '</option>';
        echo '</select></td>';
        echo '<td><button type="button" id="nm-cu-add-btn" class="btn btn-sm btn-success"'
            . ' data-action="add_chatbot_user"'
            . ' data-chatbot-id="' . $chatbot_id . '"'
            . ' data-companies-id="' . $companies_id . '"'
            . ' data-url="' . $au . '"'
            . ' data-csrf="' . $h($csrf) . '">'
            . '<i class="ti ti-plus"></i> ' . __('Adicionar', 'newmanagement') . '</button></td>';
        echo '</tr>';

        echo '</table>';
    }

    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        $this->showFormButtons($options);
        return true;
    }
}
