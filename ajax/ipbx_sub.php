<?php

/**
 * Newmanagement - Handler AJAX/POST para sub-dados do IPBX
 * Ações: add/delete de ramais, dispositivos, rede e linha fixa
 * + add/update do registro principal IPBX
 *
 * Responde SEMPRE com JSON { success: bool, error?: string, id?: int, csrf?: string }
 *
 * Proteção em camadas:
 *  1. Session::checkLoginUser()   — usuário autenticado
 *  2. CSRF — compatível GLPI 10 e 11 (veja nota abaixo)
 *  3. Session::checkRight(READ)   — direito mínimo de leitura
 *  4. Por ação: checkRight(CREATE | UPDATE | DELETE) conforme necessário
 *
 * NOTA GLPI 11 — CheckCsrfListener (Symfony):
 *   O listener busca o token EM ORDEM:
 *     1. Header  X-Glpi-Csrf-Token
 *     2. Body    _glpi_csrf_token  (apenas se Content-Type adequado)
 *   Para fetch() com FormData, o Content-Type é multipart/form-data.
 *   O Symfony NÃO faz parse de multipart para _glpi_csrf_token —
 *   portanto o token DEVE vir no header X-Glpi-Csrf-Token.
 *   O JS já envia o header; mas se o listener rejeitar antes do PHP,
 *   este arquivo nem roda. A solução é garantir que o token no header
 *   seja sempre fresco (gerado na renderização da aba, não da página).
 *
 *   TOKENS SÃO SINGLE-USE no GLPI 11. Portanto:
 *   - Cada resposta JSON retorna um novo token em `csrf`.
 *   - O JS deve atualizar #nm-ipbx-csrf com esse token.
 *
 * COMPATIBILIDADE GLPI 10:
 *   Session::checkCSRF($_POST) é chamado quando não há header Symfony.
 */

include('../../../inc/includes.php');

use GlpiPlugin\Newmanagement\Ipbx;

// Impede cache — padrão para endpoints AJAX no GLPI
Html::header_nocache();
header('Content-Type: application/json; charset=utf-8');

// Camada 1 — usuário logado
Session::checkLoginUser();

// Camada 2 — CSRF compatível GLPI 10 e 11
//
// NO GLPI 11: O CheckCsrfListener do Symfony intercepta o request ANTES
// do PHP e valida o header X-Glpi-Csrf-Token. Se o listener passar,
// este código já está rodando com CSRF validado — NÃO chamamos
// Session::checkCSRF() novamente para não consumir o token duas vezes.
//
// NO GLPI 10: Não há CheckCsrfListener. O token vem em $_POST e
// Session::checkCSRF($_POST) é o mecanismo correto.
//
// Identificamos o GLPI 11 pela presença do header X-Glpi-Csrf-Token.
// Se o header estiver ausente, caímos no caminho GLPI 10.
$csrfHeader = $_SERVER['HTTP_X_GLPI_CSRF_TOKEN'] ?? '';
if ($csrfHeader === '') {
    // GLPI 10: token vem no body — valida pelo método nativo
    Session::checkCSRF($_POST);
}
// GLPI 11: Symfony já validou. Não chamar checkCSRF aqui.

// Camada 3 — direito mínimo de leitura no plugin
Session::checkRight(Ipbx::$rightname, READ);

/**
 * Resposta JSON padronizada.
 * Sempre inclui um novo token CSRF para o JS renovar #nm-ipbx-csrf,
 * evitando falhas em requests sequenciais com tokens single-use.
 */
function nmJson(bool $ok, array $extra = []): void
{
    // Gera novo token para o próximo request
    $extra['csrf'] = Session::getNewCSRFToken();
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

/** Criptografa senha; retorna null para valor vazio */
function nmEncryptPassword(string $value): ?string
{
    return $value !== '' ? \Toolbox::sodiumEncrypt($value) : null;
}

$action       = $_POST['action']       ?? '';
$companies_id = (int) ($_POST['companies_id'] ?? 0);

if ($companies_id <= 0) {
    nmJson(false, ['error' => 'companies_id inválido']);
}

$can_delete = \Session::haveRight(Ipbx::$rightname, DELETE);

global $DB;
$now = date('Y-m-d H:i:s');

try {
    switch ($action) {

        // ------------------------------------------------------------------
        // IPBX principal
        // ------------------------------------------------------------------
        case 'add_ipbx':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx  = new Ipbx();
            $newId = $ipbx->add([
                'companies_id'   => $companies_id,
                'model'          => $_POST['model']          ?? '',
                'server_version' => $_POST['server_version'] ?? '',
                'ip_local'       => $_POST['ip_local']       ?? '',
                'ip_external'    => $_POST['ip_external']    ?? '',
                'web_port'       => $_POST['web_port']       ?? '',
                'web_password'   => nmEncryptPassword($_POST['web_password'] ?? ''),
                'ssh_port'       => $_POST['ssh_port']       ?? '',
                'ssh_password'   => nmEncryptPassword($_POST['ssh_password'] ?? ''),
                'comment'        => $_POST['comment']        ?? '',
            ]);
            nmJson(true, ['id' => (int) $newId]);
            break;

        case 'update_ipbx':
            Session::checkRight(Ipbx::$rightname, UPDATE);
            $ipbxId = (int) ($_POST['id'] ?? 0);
            if ($ipbxId <= 0) {
                nmJson(false, ['error' => 'ID inválido']);
            }
            $data = [
                'id'             => $ipbxId,
                'companies_id'   => $companies_id,
                'model'          => $_POST['model']          ?? '',
                'server_version' => $_POST['server_version'] ?? '',
                'ip_local'       => $_POST['ip_local']       ?? '',
                'ip_external'    => $_POST['ip_external']    ?? '',
                'web_port'       => $_POST['web_port']       ?? '',
                'ssh_port'       => $_POST['ssh_port']       ?? '',
                'comment'        => $_POST['comment']        ?? '',
            ];
            if (($_POST['web_password'] ?? '') !== '') {
                $data['web_password'] = \Toolbox::sodiumEncrypt($_POST['web_password']);
            }
            if (($_POST['ssh_password'] ?? '') !== '') {
                $data['ssh_password'] = \Toolbox::sodiumEncrypt($_POST['ssh_password']);
            }
            $ipbx = new Ipbx();
            $ipbx->update($data);
            nmJson(true);
            break;

        // ------------------------------------------------------------------
        // Ramais
        // ------------------------------------------------------------------
        case 'add_extension':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int) ($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) {
                nmJson(false, ['error' => 'ipbx_id inválido']);
            }
            $DB->insert(Ipbx::TABLE_EXTENSIONS, [
                'ipbx_id'       => $ipbx_id,
                'companies_id'  => $companies_id,
                'number'        => $_POST['number']       ?? '',
                'password'      => nmEncryptPassword($_POST['password'] ?? ''),
                'device_ip'     => $_POST['device_ip']    ?? '',
                'user_name'     => $_POST['user_name']    ?? '',
                'records_calls' => (int) ($_POST['records_calls'] ?? 0),
                'department'    => $_POST['department']   ?? '',
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
            $rowId     = $DB->insertId();
            $row       = $DB->request(['FROM' => Ipbx::TABLE_EXTENSIONS, 'WHERE' => ['id' => $rowId]])->current();
            $csrf      = \Session::getNewCSRFToken();
            $actionUrl = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
            $html      = Ipbx::renderExtensionRow((int) $rowId, $row, $companies_id, $csrf, $actionUrl, $can_delete);
            nmJson(true, ['id' => $rowId, 'html' => $html]);
            break;

        case 'delete_extension':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete(Ipbx::TABLE_EXTENSIONS, [
                'id'           => (int) ($_POST['id'] ?? 0),
                'companies_id' => $companies_id,
            ]);
            nmJson(true);
            break;

        // ------------------------------------------------------------------
        // Dispositivos
        // ------------------------------------------------------------------
        case 'add_device':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int) ($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) {
                nmJson(false, ['error' => 'ipbx_id inválido']);
            }
            $DB->insert(Ipbx::TABLE_DEVICES, [
                'ipbx_id'       => $ipbx_id,
                'companies_id'  => $companies_id,
                'device_type'   => $_POST['device_type'] ?? '',
                'ip_address'    => $_POST['ip_address']  ?? '',
                'login'         => $_POST['login']       ?? '',
                'password'      => nmEncryptPassword($_POST['password'] ?? ''),
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
            $rowId     = $DB->insertId();
            $row       = $DB->request(['FROM' => Ipbx::TABLE_DEVICES, 'WHERE' => ['id' => $rowId]])->current();
            $csrf      = \Session::getNewCSRFToken();
            $actionUrl = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
            $html      = Ipbx::renderDeviceRow((int) $rowId, $row, $companies_id, $csrf, $actionUrl, $can_delete);
            nmJson(true, ['id' => $rowId, 'html' => $html]);
            break;

        case 'delete_device':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete(Ipbx::TABLE_DEVICES, [
                'id'           => (int) ($_POST['id'] ?? 0),
                'companies_id' => $companies_id,
            ]);
            nmJson(true);
            break;

        // ------------------------------------------------------------------
        // Rede
        // ------------------------------------------------------------------
        case 'add_network':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int) ($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) {
                nmJson(false, ['error' => 'ipbx_id inválido']);
            }
            $DB->insert(Ipbx::TABLE_NETWORK, [
                'ipbx_id'       => $ipbx_id,
                'companies_id'  => $companies_id,
                'ip_network'    => $_POST['ip_network']    ?? '',
                'netmask'       => $_POST['netmask']       ?? '',
                'gateway'       => $_POST['gateway']       ?? '',
                'dns_primary'   => $_POST['dns_primary']   ?? '',
                'dns_secondary' => $_POST['dns_secondary'] ?? '',
                'supplier'      => $_POST['supplier']      ?? '',
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
            $rowId     = $DB->insertId();
            $row       = $DB->request(['FROM' => Ipbx::TABLE_NETWORK, 'WHERE' => ['id' => $rowId]])->current();
            $csrf      = \Session::getNewCSRFToken();
            $actionUrl = \Plugin::getWebDir('newmanagement') . '/ajax/ipbx_sub.php';
            $html      = Ipbx::renderNetworkRow((int) $rowId, $row, $companies_id, $csrf, $actionUrl, $can_delete);
            nmJson(true, ['id' => $rowId, 'html' => $html]);
            break;

        case 'delete_network':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete(Ipbx::TABLE_NETWORK, [
                'id'           => (int) ($_POST['id'] ?? 0),
                'companies_id' => $companies_id,
            ]);
            nmJson(true);
            break;

        // ------------------------------------------------------------------
        // Linha Fixa
        // ------------------------------------------------------------------
        case 'add_line':
            Session::checkRight(Ipbx::$rightname, CREATE);
            $ipbx_id = (int) ($_POST['ipbx_id'] ?? 0);
            if ($ipbx_id <= 0) {
                nmJson(false, ['error' => 'ipbx_id inválido']);
            }
            $toDate = static fn(string $v): ?string => $v !== '' ? $v : null;
            $DB->insert('glpi_plugin_newmanagement_ipbx_lines', [
                'ipbx_id'           => $ipbx_id,
                'companies_id'      => $companies_id,
                'pilot_number'      => $_POST['pilot_number']      ?? '',
                'line_type'         => $_POST['line_type']         ?? '',
                'operator'          => $_POST['operator']          ?? '',
                'channels'          => (int) ($_POST['channels']   ?? 0),
                'ddr_count'         => (int) ($_POST['ddr_count']  ?? 0),
                'proxy_ip'          => $_POST['proxy_ip']          ?? '',
                'proxy_port'        => $_POST['proxy_port']        ?? '',
                'audio_ip'          => $_POST['audio_ip']          ?? '',
                'portability_date'  => $toDate($_POST['portability_date']  ?? ''),
                'previous_operator' => $_POST['previous_operator'] ?? '',
                'activation_date'   => $toDate($_POST['activation_date']   ?? ''),
                'expiration_date'   => $toDate($_POST['expiration_date']   ?? ''),
                'status'            => (int) ($_POST['status']     ?? 1),
                'comment'           => $_POST['comment']           ?? '',
                'date_creation'     => $now,
                'date_mod'          => $now,
            ]);
            $newId = $DB->insertId();
            if (!$newId) {
                nmJson(false, ['error' => 'Falha ao inserir linha fixa']);
            }
            nmJson(true, ['id' => $newId]);
            break;

        case 'update_line':
            Session::checkRight(Ipbx::$rightname, UPDATE);
            $toDate = static fn(string $v): ?string => $v !== '' ? $v : null;
            $DB->update(
                'glpi_plugin_newmanagement_ipbx_lines',
                [
                    'pilot_number'      => $_POST['pilot_number']      ?? '',
                    'line_type'         => $_POST['line_type']         ?? '',
                    'operator'          => $_POST['operator']          ?? '',
                    'channels'          => (int) ($_POST['channels']   ?? 0),
                    'ddr_count'         => (int) ($_POST['ddr_count']  ?? 0),
                    'proxy_ip'          => $_POST['proxy_ip']          ?? '',
                    'proxy_port'        => $_POST['proxy_port']        ?? '',
                    'audio_ip'          => $_POST['audio_ip']          ?? '',
                    'portability_date'  => $toDate($_POST['portability_date']  ?? ''),
                    'previous_operator' => $_POST['previous_operator'] ?? '',
                    'activation_date'   => $toDate($_POST['activation_date']   ?? ''),
                    'expiration_date'   => $toDate($_POST['expiration_date']   ?? ''),
                    'status'            => (int) ($_POST['status']     ?? 1),
                    'comment'           => $_POST['comment']           ?? '',
                    'date_mod'          => $now,
                ],
                [
                    'id'           => (int) ($_POST['id'] ?? 0),
                    'companies_id' => $companies_id,
                ]
            );
            nmJson(true);
            break;

        case 'delete_line':
            Session::checkRight(Ipbx::$rightname, DELETE);
            $DB->delete('glpi_plugin_newmanagement_ipbx_lines', [
                'id'           => (int) ($_POST['id'] ?? 0),
                'companies_id' => $companies_id,
            ]);
            nmJson(true);
            break;

        default:
            nmJson(false, ['error' => 'Ação desconhecida: ' . htmlspecialchars($action)]);
    }
} catch (\Throwable $e) {
    \Toolbox::logDebug('ipbx_sub.php error: ' . $e->getMessage());
    nmJson(false, ['error' => 'Erro interno: ' . $e->getMessage()]);
}
