<?php

/**
 * Newmanagement — Instalação / Migração de tabelas
 *
 * Chamado por plugin_newmanagement_install() em hook.php.
 * Toda a operação é atômica: se qualquer CREATE/ALTER falhar,
 * um rollback é executado e false é retornado.
 *
 * @internal Não chamar diretamente — use plugin_newmanagement_install().
 */

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

/**
 * Executa a instalação/migração de todas as tabelas do plugin.
 *
 * @return bool true em sucesso, false se alguma operação falhar.
 */
function plugin_newmanagement_install_tables(): bool
{
    global $DB;

    $DB->beginTransaction();

    try {
        $migration = new Migration(PLUGIN_NEWMANAGEMENT_VERSION);

        $cs  = DBConnection::getDefaultCharset();
        $col = DBConnection::getDefaultCollation();
        $key = DBConnection::getDefaultPrimaryKeySignOption();

        // -------------------------------------------------------
        // Empresas
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_companies')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_companies` (
                `id`              int {$key} NOT NULL AUTO_INCREMENT,
                `name`            varchar(255) NOT NULL DEFAULT '',
                `cnpj`            varchar(20)           DEFAULT NULL,
                `razao_social`    varchar(255)          DEFAULT NULL,
                `email`           varchar(255)          DEFAULT NULL,
                `phone`           varchar(50)           DEFAULT NULL,
                `cep`             varchar(10)           DEFAULT NULL,
                `address`         text                  DEFAULT NULL,
                `contract_status` tinyint(1)   NOT NULL DEFAULT 0
                                  COMMENT '0=Sem contrato,1=Ativo,2=Cancelado',
                `comment`         text                  DEFAULT NULL,
                `date_creation`   timestamp             DEFAULT NULL,
                `date_mod`        timestamp             DEFAULT NULL,
                `is_deleted`      tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_companies');
            if (!isset($cols['razao_social']))
                $migration->addField('glpi_plugin_newmanagement_companies', 'razao_social',
                    'varchar(255) DEFAULT NULL', ['after' => 'cnpj']);
            if (!isset($cols['cep']))
                $migration->addField('glpi_plugin_newmanagement_companies', 'cep',
                    'varchar(10) DEFAULT NULL', ['after' => 'phone']);
            if (!isset($cols['contract_status']))
                $migration->addField('glpi_plugin_newmanagement_companies', 'contract_status',
                    "tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Sem contrato,1=Ativo,2=Cancelado'",
                    ['after' => 'address']);
            $migration->addKey('glpi_plugin_newmanagement_companies', 'name');
        }

        // -------------------------------------------------------
        // IPBX On-Premise
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_ipbx` (
                `id`             int {$key} NOT NULL AUTO_INCREMENT,
                `companies_id`   int {$key} NOT NULL DEFAULT 0,
                `model`          varchar(255) DEFAULT NULL,
                `server_version` varchar(50)  DEFAULT NULL,
                `ip_local`       varchar(45)  DEFAULT NULL,
                `ip_external`    varchar(45)  DEFAULT NULL,
                `web_port`       varchar(10)  DEFAULT NULL,
                `web_password`   text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `ssh_port`       varchar(10)  DEFAULT NULL,
                `ssh_password`   text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `comment`        text         DEFAULT NULL,
                `date_creation`  timestamp    DEFAULT NULL,
                `date_mod`       timestamp    DEFAULT NULL,
                `is_deleted`     tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx');
            foreach ([
                'model'          => 'varchar(255) DEFAULT NULL',
                'server_version' => 'varchar(50)  DEFAULT NULL',
                'ip_local'       => 'varchar(45)  DEFAULT NULL',
                'ip_external'    => 'varchar(45)  DEFAULT NULL',
                'web_port'       => 'varchar(10)  DEFAULT NULL',
                'ssh_port'       => 'varchar(10)  DEFAULT NULL',
                'ssh_password'   => 'text DEFAULT NULL',
                'web_password'   => 'text DEFAULT NULL',
            ] as $field => $def) {
                if (!isset($cols[$field]))
                    $migration->addField('glpi_plugin_newmanagement_ipbx', $field, $def);
            }
            foreach (['web_password', 'ssh_password'] as $f) {
                if (isset($cols[$f]) && $cols[$f]['Type'] === 'varchar(255)')
                    $migration->changeField('glpi_plugin_newmanagement_ipbx', $f, $f, 'text DEFAULT NULL');
            }
        }

        // -------------------------------------------------------
        // Ramais do IPBX
        // feat: adiciona 6 colunas booleanas LOF/LOC/DDF/DDC/DDI/SRV
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_extensions')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_ipbx_extensions` (
                `id`            int {$key} NOT NULL AUTO_INCREMENT,
                `ipbx_id`       int {$key} NOT NULL DEFAULT 0,
                `companies_id`  int {$key} NOT NULL DEFAULT 0,
                `number`        varchar(20)  DEFAULT NULL,
                `password`      text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `device_ip`     varchar(45)  DEFAULT NULL,
                `user_name`     varchar(255) DEFAULT NULL,
                `records_calls` tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=Nao,1=Sim',
                `department`    varchar(255) DEFAULT NULL,
                `lof`           tinyint(1)   NOT NULL DEFAULT 0 COMMENT 'LOF: Liga para fora',
                `loc`           tinyint(1)   NOT NULL DEFAULT 0 COMMENT 'LOC: Liga para outros ramais',
                `ddf`           tinyint(1)   NOT NULL DEFAULT 0 COMMENT 'DDF: Desvia chamada de fora',
                `ddc`           tinyint(1)   NOT NULL DEFAULT 0 COMMENT 'DDC: Desvia chamada de celular',
                `ddi`           tinyint(1)   NOT NULL DEFAULT 0 COMMENT 'DDI: Permite DDI',
                `srv`           tinyint(1)   NOT NULL DEFAULT 0 COMMENT 'SRV: Acessa servico IPBX',
                `date_creation` timestamp    DEFAULT NULL,
                `date_mod`      timestamp    DEFAULT NULL,
                `is_deleted`    tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `ipbx_id` (`ipbx_id`),
                KEY `companies_id` (`companies_id`),
                KEY `number` (`number`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_extensions');
            if (!isset($cols['is_deleted']))
                $migration->addField('glpi_plugin_newmanagement_ipbx_extensions',
                    'is_deleted', 'tinyint(1) NOT NULL DEFAULT 0', ['after' => 'date_mod']);
            if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
                $migration->changeField('glpi_plugin_newmanagement_ipbx_extensions',
                    'password', 'password', 'text DEFAULT NULL');
            // feat: migração idempotente das 6 colunas booleanas
            $bool_cols = [
                'lof' => ["tinyint(1) NOT NULL DEFAULT 0 COMMENT 'LOF: Liga para fora'",           'department'],
                'loc' => ["tinyint(1) NOT NULL DEFAULT 0 COMMENT 'LOC: Liga para outros ramais'",   'lof'],
                'ddf' => ["tinyint(1) NOT NULL DEFAULT 0 COMMENT 'DDF: Desvia chamada de fora'",    'loc'],
                'ddc' => ["tinyint(1) NOT NULL DEFAULT 0 COMMENT 'DDC: Desvia chamada de celular'", 'ddf'],
                'ddi' => ["tinyint(1) NOT NULL DEFAULT 0 COMMENT 'DDI: Permite DDI'",               'ddc'],
                'srv' => ["tinyint(1) NOT NULL DEFAULT 0 COMMENT 'SRV: Acessa servico IPBX'",       'ddi'],
            ];
            foreach ($bool_cols as $field => [$def, $after]) {
                if (!isset($cols[$field]))
                    $migration->addField(
                        'glpi_plugin_newmanagement_ipbx_extensions',
                        $field,
                        $def,
                        ['after' => $after]
                    );
            }
            if (!isset($cols['number']) || !array_key_exists('number', array_flip(array_column($DB->listIndexes('glpi_plugin_newmanagement_ipbx_extensions') ?? [], 'Key_name'))))
                $migration->addKey('glpi_plugin_newmanagement_ipbx_extensions', 'number');
        }

        // -------------------------------------------------------
        // Dispositivos do IPBX
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_devices')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_ipbx_devices` (
                `id`            int {$key} NOT NULL AUTO_INCREMENT,
                `ipbx_id`       int {$key} NOT NULL DEFAULT 0,
                `companies_id`  int {$key} NOT NULL DEFAULT 0,
                `device_type`   varchar(100) DEFAULT NULL,
                `ip_address`    varchar(45)  DEFAULT NULL,
                `login`         varchar(255) DEFAULT NULL,
                `password`      text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `date_creation` timestamp    DEFAULT NULL,
                `date_mod`      timestamp    DEFAULT NULL,
                `is_deleted`    tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `ipbx_id` (`ipbx_id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_devices');
            if (!isset($cols['is_deleted']))
                $migration->addField('glpi_plugin_newmanagement_ipbx_devices',
                    'is_deleted', 'tinyint(1) NOT NULL DEFAULT 0', ['after' => 'date_mod']);
            if (!isset($cols['login']))
                $migration->addField('glpi_plugin_newmanagement_ipbx_devices',
                    'login', 'varchar(255) DEFAULT NULL', ['after' => 'ip_address']);
            if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
                $migration->changeField('glpi_plugin_newmanagement_ipbx_devices',
                    'password', 'password', 'text DEFAULT NULL');
            $migration->addKey('glpi_plugin_newmanagement_ipbx_devices', 'companies_id');
        }

        // -------------------------------------------------------
        // Rede da Empresa
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_network')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_ipbx_network` (
                `id`            int {$key} NOT NULL AUTO_INCREMENT,
                `ipbx_id`       int {$key} NOT NULL DEFAULT 0,
                `companies_id`  int {$key} NOT NULL DEFAULT 0,
                `ip_network`    varchar(45)  DEFAULT NULL,
                `netmask`       varchar(45)  DEFAULT NULL,
                `gateway`       varchar(45)  DEFAULT NULL,
                `dns_primary`   varchar(45)  DEFAULT NULL,
                `dns_secondary` varchar(45)  DEFAULT NULL,
                `supplier`      varchar(255) DEFAULT NULL,
                `date_creation` timestamp    DEFAULT NULL,
                `date_mod`      timestamp    DEFAULT NULL,
                `is_deleted`    tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `ipbx_id` (`ipbx_id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_network');
            if (!isset($cols['is_deleted']))
                $migration->addField('glpi_plugin_newmanagement_ipbx_network',
                    'is_deleted', 'tinyint(1) NOT NULL DEFAULT 0', ['after' => 'date_mod']);
            if (!isset($cols['supplier']))
                $migration->addField('glpi_plugin_newmanagement_ipbx_network',
                    'supplier', 'varchar(255) DEFAULT NULL', ['after' => 'dns_secondary']);
            $migration->addKey('glpi_plugin_newmanagement_ipbx_network', 'companies_id');
        }

        // -------------------------------------------------------
        // Linha Fixa
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_lines')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_ipbx_lines` (
                `id`                int {$key} NOT NULL AUTO_INCREMENT,
                `ipbx_id`           int {$key} NOT NULL DEFAULT 0,
                `companies_id`      int {$key} NOT NULL DEFAULT 0,
                `pilot_number`      varchar(50)  DEFAULT NULL,
                `line_type`         varchar(100) DEFAULT NULL,
                `operator`          varchar(100) DEFAULT NULL,
                `channels`          int          DEFAULT 0,
                `ddr_count`         int          DEFAULT 0,
                `proxy_ip`          varchar(45)  DEFAULT NULL,
                `proxy_port`        varchar(10)  DEFAULT NULL,
                `audio_ip`          varchar(45)  DEFAULT NULL,
                `portability_date`  date         DEFAULT NULL,
                `previous_operator` varchar(100) DEFAULT NULL,
                `activation_date`   date         DEFAULT NULL,
                `expiration_date`   date         DEFAULT NULL,
                `status`            tinyint(1)   NOT NULL DEFAULT 1 COMMENT '1=Ativo,2=Cancelado',
                `comment`           text         DEFAULT NULL,
                `date_creation`     timestamp    DEFAULT NULL,
                `date_mod`          timestamp    DEFAULT NULL,
                `is_deleted`        tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `ipbx_id` (`ipbx_id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_lines');
            $optional = [
                'is_deleted'       => 'tinyint(1) NOT NULL DEFAULT 0',
                'pilot_number'     => 'varchar(50)  DEFAULT NULL',
                'line_type'        => 'varchar(100) DEFAULT NULL',
                'operator'         => 'varchar(100) DEFAULT NULL',
                'channels'         => 'int DEFAULT 0',
                'ddr_count'        => 'int DEFAULT 0',
                'proxy_ip'         => 'varchar(45)  DEFAULT NULL',
                'proxy_port'       => 'varchar(10)  DEFAULT NULL',
                'audio_ip'         => 'varchar(45)  DEFAULT NULL',
                'portability_date' => 'date DEFAULT NULL',
                'previous_operator'=> 'varchar(100) DEFAULT NULL',
                'activation_date'  => 'date DEFAULT NULL',
                'expiration_date'  => 'date DEFAULT NULL',
                'status'           => "tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Ativo,2=Cancelado'",
            ];
            foreach ($optional as $field => $def) {
                if (!isset($cols[$field]))
                    $migration->addField('glpi_plugin_newmanagement_ipbx_lines', $field, $def);
            }
            $migration->addKey('glpi_plugin_newmanagement_ipbx_lines', 'companies_id');
        }

        // -------------------------------------------------------
        // Tasks
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_tasks')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_tasks` (
                `id`                int {$key} NOT NULL AUTO_INCREMENT,
                `name`              varchar(255) NOT NULL DEFAULT '',
                `companies_id`      int {$key} NOT NULL DEFAULT 0,
                `assigned_user_id`  int {$key}            DEFAULT NULL
                                    COMMENT 'FK glpi_users.id — usuário responsável',
                `status`            tinyint(1)   NOT NULL DEFAULT 0
                                    COMMENT '0=Pendente,1=Em andamento,2=Concluida',
                `date_due`          timestamp    DEFAULT NULL,
                `km_calculated`     decimal(10,2) DEFAULT NULL,
                `latitude`          decimal(10,6) DEFAULT NULL,
                `longitude`         decimal(10,6) DEFAULT NULL,
                `digital_signature` text         DEFAULT NULL
                                    COMMENT 'Assinatura digital / hash da conclusão',
                `comment`           text         DEFAULT NULL,
                `date_creation`     timestamp    DEFAULT NULL,
                `date_mod`          timestamp    DEFAULT NULL,
                `is_deleted`        tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `name` (`name`),
                KEY `companies_id` (`companies_id`),
                KEY `assigned_user_id` (`assigned_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_tasks');
            $migration->addKey('glpi_plugin_newmanagement_tasks', 'name');
            if (isset($cols['date_due']) && strtolower($cols['date_due']['Type']) === 'datetime')
                $migration->changeField('glpi_plugin_newmanagement_tasks',
                    'date_due', 'date_due', 'timestamp DEFAULT NULL');
            if (!isset($cols['assigned_user_id'])) {
                $migration->addField(
                    'glpi_plugin_newmanagement_tasks',
                    'assigned_user_id',
                    "int {$key} DEFAULT NULL COMMENT 'FK glpi_users.id — usuário responsável'",
                    ['after' => 'companies_id']
                );
                $migration->addKey('glpi_plugin_newmanagement_tasks', 'assigned_user_id');
            }
            if (!isset($cols['digital_signature'])) {
                $migration->addField(
                    'glpi_plugin_newmanagement_tasks',
                    'digital_signature',
                    "text DEFAULT NULL COMMENT 'Assinatura digital / hash da conclusão'",
                    ['after' => 'longitude']
                );
            }
        }

        // -------------------------------------------------------
        // Chatbot
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_chatbots')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_chatbots` (
                `id`                      int {$key} NOT NULL AUTO_INCREMENT,
                `companies_id`            int {$key} NOT NULL DEFAULT 0,
                `model`                   varchar(255) DEFAULT NULL,
                `chatbot_registration_id` varchar(255) DEFAULT NULL,
                `activation_date`         date         DEFAULT NULL,
                `whatsapp_number`         varchar(50)  DEFAULT NULL,
                `access_link`             varchar(255) DEFAULT NULL,
                `plan`                    varchar(100) DEFAULT NULL,
                `users_count`             int          DEFAULT 0,
                `supervisors_count`       int          DEFAULT 0,
                `admins_count`            int          DEFAULT 0,
                `admin_login`             varchar(255) DEFAULT NULL,
                `admin_password`          text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `superadmin_login`        varchar(255) DEFAULT NULL,
                `superadmin_password`     text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `manager_name`            varchar(255) DEFAULT NULL,
                `manager_contact`         varchar(100) DEFAULT NULL,
                `manager_email`           varchar(255) DEFAULT NULL,
                `social_networks`         text         DEFAULT NULL,
                `comment`                 text         DEFAULT NULL,
                `date_creation`           timestamp    DEFAULT NULL,
                `date_mod`                timestamp    DEFAULT NULL,
                `is_deleted`              tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_chatbots');
            $optional = [
                'model'                   => 'varchar(255) DEFAULT NULL',
                'chatbot_registration_id' => 'varchar(255) DEFAULT NULL',
                'activation_date'         => 'date DEFAULT NULL',
                'whatsapp_number'         => 'varchar(50)  DEFAULT NULL',
                'access_link'             => 'varchar(255) DEFAULT NULL',
                'plan'                    => 'varchar(100) DEFAULT NULL',
                'users_count'             => 'int DEFAULT 0',
                'supervisors_count'       => 'int DEFAULT 0',
                'admins_count'            => 'int DEFAULT 0',
                'admin_login'             => 'varchar(255) DEFAULT NULL',
                'admin_password'          => 'text DEFAULT NULL',
                'superadmin_login'        => 'varchar(255) DEFAULT NULL',
                'superadmin_password'     => 'text DEFAULT NULL',
                'manager_name'            => 'varchar(255) DEFAULT NULL',
                'manager_contact'         => 'varchar(100) DEFAULT NULL',
                'manager_email'           => 'varchar(255) DEFAULT NULL',
                'social_networks'         => 'text DEFAULT NULL',
                'is_deleted'              => 'tinyint(1) NOT NULL DEFAULT 0',
            ];
            foreach ($optional as $field => $def) {
                if (!isset($cols[$field]))
                    $migration->addField('glpi_plugin_newmanagement_chatbots', $field, $def);
            }
            foreach (['admin_password', 'superadmin_password'] as $f) {
                if (isset($cols[$f]) && $cols[$f]['Type'] === 'varchar(255)')
                    $migration->changeField('glpi_plugin_newmanagement_chatbots',
                        $f, $f, 'text DEFAULT NULL');
            }
        }

        // -------------------------------------------------------
        // Chatbot: Mass Communication
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_mass_comm')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_chatbot_mass_comm` (
                `id`                   int {$key} NOT NULL AUTO_INCREMENT,
                `chatbot_id`           int {$key} NOT NULL DEFAULT 0,
                `companies_id`         int {$key} NOT NULL DEFAULT 0,
                `system_name`          varchar(255) DEFAULT NULL,
                `activation_date`      date         DEFAULT NULL,
                `authenticated_number` varchar(50)  DEFAULT NULL,
                `homologation_type`    varchar(100) DEFAULT NULL,
                `access_link`          varchar(255) DEFAULT NULL,
                `login`                varchar(255) DEFAULT NULL,
                `password`             text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `manager`              varchar(255) DEFAULT NULL,
                `date_creation`        timestamp    DEFAULT NULL,
                `date_mod`             timestamp    DEFAULT NULL,
                `is_deleted`           tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `chatbot_id` (`chatbot_id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_chatbot_mass_comm');
            $optional = [
                'is_deleted'           => 'tinyint(1) NOT NULL DEFAULT 0',
                'system_name'          => 'varchar(255) DEFAULT NULL',
                'activation_date'      => 'date DEFAULT NULL',
                'authenticated_number' => 'varchar(50)  DEFAULT NULL',
                'homologation_type'    => 'varchar(100) DEFAULT NULL',
                'access_link'          => 'varchar(255) DEFAULT NULL',
                'login'                => 'varchar(255) DEFAULT NULL',
                'password'             => 'text DEFAULT NULL',
                'manager'              => 'varchar(255) DEFAULT NULL',
            ];
            foreach ($optional as $field => $def) {
                if (!isset($cols[$field]))
                    $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', $field, $def);
            }
            if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
                $migration->changeField('glpi_plugin_newmanagement_chatbot_mass_comm',
                    'password', 'password', 'text DEFAULT NULL');
            $migration->addKey('glpi_plugin_newmanagement_chatbot_mass_comm', 'companies_id');
        }

        // -------------------------------------------------------
        // Chatbot: WhatsApp Restrictions
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_wa_restrictions')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_chatbot_wa_restrictions` (
                `id`               int {$key} NOT NULL AUTO_INCREMENT,
                `chatbot_id`       int {$key} NOT NULL DEFAULT 0,
                `companies_id`     int {$key} NOT NULL DEFAULT 0,
                `whatsapp_number`  varchar(50) DEFAULT NULL,
                `restriction_date` date        DEFAULT NULL,
                `restriction_time` varchar(50) DEFAULT NULL,
                `end_date`         date        DEFAULT NULL,
                `date_creation`    timestamp   DEFAULT NULL,
                `date_mod`         timestamp   DEFAULT NULL,
                `is_deleted`       tinyint(1)  NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `chatbot_id` (`chatbot_id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_chatbot_wa_restrictions');
            $optional = [
                'is_deleted'       => 'tinyint(1) NOT NULL DEFAULT 0',
                'whatsapp_number'  => 'varchar(50) DEFAULT NULL',
                'restriction_date' => 'date DEFAULT NULL',
                'restriction_time' => 'varchar(50) DEFAULT NULL',
                'end_date'         => 'date DEFAULT NULL',
            ];
            foreach ($optional as $field => $dev) {
                if (!isset($cols[$field]))
                    $migration->addField('glpi_plugin_newmanagement_chatbot_wa_restrictions', $field, $dev);
            }
            $migration->addKey('glpi_plugin_newmanagement_chatbot_wa_restrictions', 'companies_id');
        }

        // -------------------------------------------------------
        // Chatbot: Users
        // -------------------------------------------------------
        if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_users')) {
            $DB->doQueryOrDie("CREATE TABLE `glpi_plugin_newmanagement_chatbot_users` (
                `id`            int {$key} NOT NULL AUTO_INCREMENT,
                `chatbot_id`    int {$key} NOT NULL DEFAULT 0,
                `companies_id`  int {$key} NOT NULL DEFAULT 0,
                `user_name`     varchar(255) DEFAULT NULL,
                `login`         varchar(255) DEFAULT NULL,
                `password`      text         DEFAULT NULL COMMENT 'sodiumEncrypt',
                `email`         varchar(255) DEFAULT NULL,
                `user_type`     varchar(100) DEFAULT NULL,
                `date_creation` timestamp    DEFAULT NULL,
                `date_mod`      timestamp    DEFAULT NULL,
                `is_deleted`    tinyint(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `chatbot_id` (`chatbot_id`),
                KEY `companies_id` (`companies_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$cs} COLLATE={$col} ROW_FORMAT=DYNAMIC");
        } else {
            $cols = $DB->listFields('glpi_plugin_newmanagement_chatbot_users');
            $optional = [
                'is_deleted' => 'tinyint(1) NOT NULL DEFAULT 0',
                'login'      => 'varchar(255) DEFAULT NULL',
                'password'   => 'text DEFAULT NULL',
                'email'      => 'varchar(255) DEFAULT NULL',
                'user_type'  => 'varchar(100) DEFAULT NULL',
            ];
            foreach ($optional as $field => $def) {
                if (!isset($cols[$field]))
                    $migration->addField('glpi_plugin_newmanagement_chatbot_users', $field, $def);
            }
            if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
                $migration->changeField('glpi_plugin_newmanagement_chatbot_users',
                    'password', 'password', 'text DEFAULT NULL');
            $migration->addKey('glpi_plugin_newmanagement_chatbot_users', 'companies_id');
        }

        $migration->executeMigration();
        $DB->commit();

    } catch (\Throwable $e) {
        $DB->rollBack();
        \Toolbox::logError(
            '[Newmanagement] Falha na instalação, rollback executado: ' . $e->getMessage()
        );
        return false;
    }

    return true;
}
