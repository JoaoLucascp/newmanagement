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
            'id'                     => 0,
            'companies_id'           => $companies_id,
            'model'                  => '',
            'chatbot_registration_id'=> '',
            'activation_date'        => '',
            'whatsapp_number'        => '',
            'access_link'            => '',
            'plan'                   => '',
            'users_count'            => '',
            'supervisors_count'      => '',
            'admins_count'           => '',
            'admin_login'            => '',
            'admin_password'         => '',
            'superadmin_login'       => '',
            'superadmin_password'    => '',
            'manager_name'           => '',
            'manager_contact'        => '',
            'manager_email'          => '',
            'social_networks'        => '',
            'comment'                => '',
        ];

        foreach ($rows as $row) {
            foreach (array_keys($f) as $key) {
                if (isset($row[$key])) {
                    $f[$key] = $row[$key];
                }
            }
            $chatbot_id = (int) $row['id'];
            
            // Descriptografar senhas com fallback para valores legados (registros antigos sem criptografia)
            try {
                if (!empty($f['admin_password'])) {
                    $f['admin_password'] = \Toolbox::sodiumDecrypt($f['admin_password']);
                }
            } catch (\Throwable $e) {
                // Fallback: valor já é plain text (registro anterior à criptografia)
            }
            try {
                if (!empty($f['superadmin_password'])) {
                    $f['superadmin_password'] = \Toolbox::sodiumDecrypt($f['superadmin_password']);
                }
            } catch (\Throwable $e) {
                // Fallback: valor já é plain text (registro anterior à criptografia)
            }
        }

        $csrf     = \Session::getNewCSRFToken();
        $action   = \Plugin::getWebDir('newmanagement') . '/ajax/chatbot_sub.php';
        $redirect = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES);
        $form_action = $chatbot_id > 0 ? 'update_chatbot' : 'add_chatbot';

        $v = function(string $key) use ($f): string {
            return htmlspecialchars((string)($f[$key] ?? ''), ENT_QUOTES);
        };

        echo '<div class="nm-chatbot-tab">';

        // ── Formulário principal ──────────────────────────────────────────────
        echo '<form method="post" action="' . $action . '" id="nm-chatbot-form">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="' . $form_action . '">';
        echo '<input type="hidden" name="id" value="' . $chatbot_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';

        echo '<table class="tab_cadre_fixe nm-table">';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Modelo do Chatbot', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="model" value="' . $v('model') . '" class="form-control"></td>';
        echo '<td>' . __('ID de Cadastro', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="chatbot_registration_id" value="' . $v('chatbot_registration_id') . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Data de Ativação', 'newmanagement') . '</td>';
        echo '<td><input type="date" name="activation_date" value="' . $v('activation_date') . '" class="form-control"></td>';
        echo '<td>' . __('Número WhatsApp', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="whatsapp_number" value="' . $v('whatsapp_number') . '" class="form-control" placeholder="Ex: 5511999999999"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Link de Acesso', 'newmanagement') . '</td>';
        echo '<td><input type="url" name="access_link" value="' . $v('access_link') . '" class="form-control" placeholder="https://"></td>';
        echo '<td>' . __('Plano Contratado', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="plan" value="' . $v('plan') . '" class="form-control"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Qtd. Usuários', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="users_count" value="' . $v('users_count') . '" class="form-control" min="0"></td>';
        echo '<td>' . __('Qtd. Supervisores', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="supervisors_count" value="' . $v('supervisors_count') . '" class="form-control" min="0"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Qtd. Administradores', 'newmanagement') . '</td>';
        echo '<td><input type="number" name="admins_count" value="' . $v('admins_count') . '" class="form-control" min="0"></td>';
        echo '<td>' . __('Redes Sociais Ativas', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="social_networks" value="' . $v('social_networks') . '" class="form-control" placeholder="Ex: WhatsApp, Instagram, Telegram"></td>';
        echo '</tr>';

        // ── Credenciais Admin ──
        echo '<tr class="noHover"><th colspan="4">' . __('Credenciais de Administrador', 'newmanagement') . '</th></tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Login Admin', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="admin_login" value="' . $v('admin_login') . '" class="form-control" autocomplete="off"></td>';
        echo '<td>' . __('Senha Admin', 'newmanagement') . '</td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="password" id="admin_password" name="admin_password" value="' . $v('admin_password') . '" class="form-control" autocomplete="new-password">';
        echo '    <button type="button" class="nm-btn-eye" data-target="admin_password" title="Mostrar/Ocultar"><i class="ti ti-eye"></i></button>';
        echo '  </div>';
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Login Super-Admin', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="superadmin_login" value="' . $v('superadmin_login') . '" class="form-control" autocomplete="off"></td>';
        echo '<td>' . __('Senha Super-Admin', 'newmanagement') . '</td>';
        echo '<td>';
        echo '  <div class="nm-input-group">';
        echo '    <input type="password" id="superadmin_password" name="superadmin_password" value="' . $v('superadmin_password') . '" class="form-control" autocomplete="new-password">';
        echo '    <button type="button" class="nm-btn-eye" data-target="superadmin_password" title="Mostrar/Ocultar"><i class="ti ti-eye"></i></button>';
        echo '  </div>';
        echo '</td>';
        echo '</tr>';

        // ── Responsável ──
        echo '<tr class="noHover"><th colspan="4">' . __('Responsável pelo Chatbot', 'newmanagement') . '</th></tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Nome do Responsável', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="manager_name" value="' . $v('manager_name') . '" class="form-control"></td>';
        echo '<td>' . __('Contato do Responsável', 'newmanagement') . '</td>';
        echo '<td><input type="text" name="manager_contact" value="' . $v('manager_contact') . '" class="form-control" placeholder="(00) 00000-0000"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('E-mail do Responsável', 'newmanagement') . '</td>';
        echo '<td colspan="3"><input type="email" name="manager_email" value="' . $v('manager_email') . '" class="form-control"></td>';
        echo '</tr>';

        // ── Comentário ──
        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Comentário', 'newmanagement') . '</td>';
        echo '<td colspan="3"><textarea name="comment" class="form-control" rows="2">' . $v('comment') . '</textarea></td>';
        echo '</tr>';

        echo '<tr style="display:none"><td colspan="4"><button type="submit" id="nm-chatbot-submit"></button></td></tr>';
        echo '</table>';
        echo '</form>';

        // ── Comunicação em Massa ──────────────────────────────────────────────
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-send"></i> ' . __('Comunicação em Massa', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderMassCommTable($chatbot_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        // ── Números Restritos pela Meta ───────────────────────────────────────
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-ban"></i> ' . __('Números Restritos pela Meta', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderWhatsappRestrictionsTable($chatbot_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        // ── Usuários Cadastrados ──────────────────────────────────────────────
        echo '<div class="nm-subsection">';
        echo '<div class="nm-subsection-header"><h4><i class="ti ti-users"></i> ' . __('Usuários Cadastrados no Chatbot', 'newmanagement') . '</h4></div>';
        echo '<div class="nm-subsection-body">';
        $this->renderUsersTable($chatbot_id, $companies_id, $csrf, $action, $redirect);
        echo '</div></div>';

        echo '</div>'; // .nm-chatbot-tab

        // ── Botão Salvar ──
        echo '<script>';
        echo 'document.getElementById("nm-chatbot-save") && document.getElementById("nm-chatbot-save").addEventListener("click", function() { document.getElementById("nm-chatbot-submit").click(); });';
        echo '</script>';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Comunicação em Massa
    // ──────────────────────────────────────────────────────────────────────────
    private function renderMassCommTable(int $chatbot_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;
        $rows = ($chatbot_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_chatbot_mass_comm', 'WHERE' => ['chatbot_id' => $chatbot_id], 'ORDER' => 'id ASC'])
            : [];

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<tr class="noHover">';
        echo '<th>' . __('Nome do Sistema',       'newmanagement') . '</th>';
        echo '<th>' . __('Data Ativação',          'newmanagement') . '</th>';
        echo '<th>' . __('Número Autenticado',     'newmanagement') . '</th>';
        echo '<th>' . __('Tipo Homologação',       'newmanagement') . '</th>';
        echo '<th>' . __('Link de Acesso',         'newmanagement') . '</th>';
        echo '<th>' . __('Login',                  'newmanagement') . '</th>';
        echo '<th>' . __('Responsável',            'newmanagement') . '</th>';
        echo '<th>' . __('Ação',                   'newmanagement') . '</th>';
        echo '</tr>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['system_name']        ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['activation_date']    ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['authenticated_number']?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['homologation_type']  ?? '', ENT_QUOTES) . '</td>';
            echo '<td>';
            if (!empty($row['access_link'])) {
                echo '<a href="' . htmlspecialchars($row['access_link'], ENT_QUOTES) . '" target="_blank" rel="noopener"><i class="ti ti-external-link"></i></a>';
            }
            echo '</td>';
            echo '<td>' . htmlspecialchars($row['login']              ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['manager']            ?? '', ENT_QUOTES) . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_mass_comm">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        // Linha de adição
        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_mass_comm">';
        echo '<input type="hidden" name="chatbot_id" value="' . $chatbot_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<td><input type="text"  name="system_name"         class="form-control form-control-sm" placeholder="' . __('Nome', 'newmanagement') . '"></td>';
        echo '<td><input type="date"  name="activation_date"     class="form-control form-control-sm"></td>';
        echo '<td><input type="text"  name="authenticated_number" class="form-control form-control-sm" placeholder="5511..."></td>';
        echo '<td><input type="text"  name="homologation_type"   class="form-control form-control-sm" placeholder="Ex: BSP"></td>';
        echo '<td><input type="url"   name="access_link"         class="form-control form-control-sm" placeholder="https://"></td>';
        echo '<td><input type="text"  name="login"               class="form-control form-control-sm"></td>';
        echo '<td><input type="text"  name="manager"             class="form-control form-control-sm"></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar', 'newmanagement') . '</button></td>';
        echo '</form></tr>';
        echo '</table>';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Números Restritos pela Meta
    // ──────────────────────────────────────────────────────────────────────────
    private function renderWhatsappRestrictionsTable(int $chatbot_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;
        $rows = ($chatbot_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_chatbot_wa_restrictions', 'WHERE' => ['chatbot_id' => $chatbot_id], 'ORDER' => 'restriction_date DESC'])
            : [];

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<tr class="noHover">';
        echo '<th>' . __('Número WhatsApp',   'newmanagement') . '</th>';
        echo '<th>' . __('Data da Restrição', 'newmanagement') . '</th>';
        echo '<th>' . __('Tempo de Restrição','newmanagement') . '</th>';
        echo '<th>' . __('Ação',              'newmanagement') . '</th>';
        echo '</tr>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['whatsapp_number']   ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['restriction_date']  ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['restriction_time']  ?? '', ENT_QUOTES) . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_wa_restriction">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_wa_restriction">';
        echo '<input type="hidden" name="chatbot_id" value="' . $chatbot_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<td><input type="text" name="whatsapp_number"  class="form-control form-control-sm" placeholder="5511..."></td>';
        echo '<td><input type="date" name="restriction_date" class="form-control form-control-sm"></td>';
        echo '<td><input type="text" name="restriction_time" class="form-control form-control-sm" placeholder="Ex: 24h, 7 dias"></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar', 'newmanagement') . '</button></td>';
        echo '</form></tr>';
        echo '</table>';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Usuários Cadastrados no Chatbot
    // ──────────────────────────────────────────────────────────────────────────
    private function renderUsersTable(int $chatbot_id, int $companies_id, string $csrf, string $action, string $redirect): void
    {
        global $DB;
        $rows = ($chatbot_id > 0)
            ? $DB->request(['FROM' => 'glpi_plugin_newmanagement_chatbot_users', 'WHERE' => ['chatbot_id' => $chatbot_id], 'ORDER' => 'user_name ASC'])
            : [];

        echo '<table class="tab_cadre_fixehov nm-table">';
        echo '<tr class="noHover">';
        echo '<th>' . __('Nome',  'newmanagement') . '</th>';
        echo '<th>' . __('Login', 'newmanagement') . '</th>';
        echo '<th>' . __('Senha', 'newmanagement') . '</th>';
        echo '<th>' . __('E-mail','newmanagement') . '</th>';
        echo '<th>' . __('Tipo',  'newmanagement') . '</th>';
        echo '<th>' . __('Ação',  'newmanagement') . '</th>';
        echo '</tr>';

        foreach ($rows as $row) {
            $rid = (int)$row['id'];
            echo '<tr class="tab_bg_1">';
            echo '<td>' . htmlspecialchars($row['user_name']  ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['login']      ?? '', ENT_QUOTES) . '</td>';
            echo '<td>••••••</td>';
            echo '<td>' . htmlspecialchars($row['email']      ?? '', ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row['user_type']  ?? '', ENT_QUOTES) . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . $action . '" style="display:inline">';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
            echo '<input type="hidden" name="action" value="delete_chatbot_user">';
            echo '<input type="hidden" name="id" value="' . $rid . '">';
            echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
            echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'' . __('Remover usuário?', 'newmanagement') . '\')"><i class="ti ti-trash"></i></button>';
            echo '</form>';
            echo '</td></tr>';
        }

        echo '<tr class="tab_bg_2 nm-add-row">';
        echo '<form method="post" action="' . $action . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $csrf . '">';
        echo '<input type="hidden" name="action" value="add_chatbot_user">';
        echo '<input type="hidden" name="chatbot_id" value="' . $chatbot_id . '">';
        echo '<input type="hidden" name="companies_id" value="' . $companies_id . '">';
        echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
        echo '<td><input type="text"     name="user_name"  class="form-control form-control-sm" placeholder="' . __('Nome', 'newmanagement') . '"></td>';
        echo '<td><input type="text"     name="login"      class="form-control form-control-sm" placeholder="' . __('Login', 'newmanagement') . '"></td>';
        echo '<td><input type="password" name="password"   class="form-control form-control-sm" placeholder="' . __('Senha', 'newmanagement') . '" autocomplete="new-password"></td>';
        echo '<td><input type="email"    name="email"      class="form-control form-control-sm" placeholder="email@"></td>';
        echo '<td><select name="user_type" class="form-select form-select-sm">';
        echo '  <option value="usuario">'     . __('Usuário',        'newmanagement') . '</option>';
        echo '  <option value="supervisor">'  . __('Supervisor',     'newmanagement') . '</option>';
        echo '  <option value="administrador">' . __('Administrador', 'newmanagement') . '</option>';
        echo '</select></td>';
        echo '<td><button type="submit" class="btn btn-sm btn-success"><i class="ti ti-plus"></i> ' . __('Adicionar', 'newmanagement') . '</button></td>';
        echo '</form></tr>';
        echo '</table>';

        // Botão Salvar
        echo '<div style="text-align:right;padding:var(--space-4,1rem) 0">';
        echo '<button type="button" id="nm-chatbot-save" class="btn btn-primary"><i class="ti ti-device-floppy"></i> ' . __('Salvar Chatbot', 'newmanagement') . '</button>';
        echo '</div>';
    }

    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        $this->showFormButtons($options);
        return true;
    }
}
