<?php

/**
 * Newmanagement - Plugin GLPI
 * Funções de instalação, atualização e desinstalação
 */

function plugin_newmanagement_install() {
    global $DB;

    $migration = new Migration(PLUGIN_NEWMANAGEMENT_VERSION);

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    // -------------------------------------------------------
    // Tabela: Empresas
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_companies')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_companies` (
            `id`              int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name`            varchar(255) NOT NULL DEFAULT '',
            `cnpj`            varchar(20)           DEFAULT NULL,
            `razao_social`    varchar(255)          DEFAULT NULL,
            `email`           varchar(255)          DEFAULT NULL,
            `phone`           varchar(50)           DEFAULT NULL,
            `cep`             varchar(10)           DEFAULT NULL,
            `address`         text                  DEFAULT NULL,
            `contract_status` tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=Sem contrato,1=Ativo,2=Cancelado',
            `comment`         text                  DEFAULT NULL,
            `date_creation`   timestamp             DEFAULT NULL,
            `date_mod`        timestamp             DEFAULT NULL,
            `is_deleted`      tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $columns = $DB->listFields('glpi_plugin_newmanagement_companies');
        if (!isset($columns['razao_social'])) {
            $migration->addField('glpi_plugin_newmanagement_companies', 'razao_social', 'varchar(255) DEFAULT NULL', ['after' => 'cnpj']);
        }
        if (!isset($columns['cep'])) {
            $migration->addField('glpi_plugin_newmanagement_companies', 'cep', 'varchar(10) DEFAULT NULL', ['after' => 'phone']);
        }
        if (!isset($columns['contract_status'])) {
            $migration->addField('glpi_plugin_newmanagement_companies', 'contract_status', "tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Sem contrato,1=Ativo,2=Cancelado'", ['after' => 'address']);
        }
    }

    // -------------------------------------------------------
    // Tabela: IPBX On-Premise
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx` (
            `id`              int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `companies_id`    int {$default_key_sign} NOT NULL DEFAULT 0,
            `model`           varchar(255)          DEFAULT NULL,
            `server_version`  varchar(50)           DEFAULT NULL,
            `ip_local`        varchar(45)           DEFAULT NULL,
            `ip_external`     varchar(45)           DEFAULT NULL,
            `web_port`        varchar(10)           DEFAULT NULL,
            `web_password`    varchar(255)          DEFAULT NULL,
            `ssh_port`        varchar(10)           DEFAULT NULL,
            `ssh_password`    varchar(255)          DEFAULT NULL,
            `comment`         text                  DEFAULT NULL,
            `date_creation`   timestamp             DEFAULT NULL,
            `date_mod`        timestamp             DEFAULT NULL,
            `is_deleted`      tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx');
        if (!isset($cols['model']))           $migration->addField('glpi_plugin_newmanagement_ipbx', 'model',          'varchar(255) DEFAULT NULL', ['after' => 'companies_id']);
        if (!isset($cols['server_version']))  $migration->addField('glpi_plugin_newmanagement_ipbx', 'server_version', 'varchar(50)  DEFAULT NULL', ['after' => 'model']);
        if (!isset($cols['ip_local']))        $migration->addField('glpi_plugin_newmanagement_ipbx', 'ip_local',       'varchar(45)  DEFAULT NULL', ['after' => 'server_version']);
        if (!isset($cols['ip_external']))     $migration->addField('glpi_plugin_newmanagement_ipbx', 'ip_external',    'varchar(45)  DEFAULT NULL', ['after' => 'ip_local']);
        if (!isset($cols['web_port']))        $migration->addField('glpi_plugin_newmanagement_ipbx', 'web_port',       'varchar(10)  DEFAULT NULL', ['after' => 'ip_external']);
        if (!isset($cols['web_password']))    $migration->addField('glpi_plugin_newmanagement_ipbx', 'web_password',   'varchar(255) DEFAULT NULL', ['after' => 'web_port']);
        if (!isset($cols['ssh_port']))        $migration->addField('glpi_plugin_newmanagement_ipbx', 'ssh_port',       'varchar(10)  DEFAULT NULL', ['after' => 'web_password']);
        if (!isset($cols['ssh_password']))    $migration->addField('glpi_plugin_newmanagement_ipbx', 'ssh_password',   'varchar(255) DEFAULT NULL', ['after' => 'ssh_port']);
    }

    // -------------------------------------------------------
    // Tabela: Ramais do IPBX
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_extensions')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_extensions` (
            `id`             int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`        int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`   int {$default_key_sign} NOT NULL DEFAULT 0,
            `number`         varchar(20)           DEFAULT NULL,
            `password`       varchar(255)          DEFAULT NULL,
            `device_ip`      varchar(45)           DEFAULT NULL,
            `user_name`      varchar(255)          DEFAULT NULL,
            `records_calls`  tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=Nao,1=Sim',
            `department`     varchar(255)          DEFAULT NULL,
            `date_creation`  timestamp             DEFAULT NULL,
            `date_mod`       timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: Dispositivos do IPBX
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_devices')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_devices` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`       int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`  int {$default_key_sign} NOT NULL DEFAULT 0,
            `device_type`   varchar(100)          DEFAULT NULL,
            `ip_address`    varchar(45)           DEFAULT NULL,
            `login`         varchar(255)          DEFAULT NULL,
            `password`      varchar(255)          DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_devices');
        if (!isset($cols['login'])) {
            $migration->addField('glpi_plugin_newmanagement_ipbx_devices', 'login', 'varchar(255) DEFAULT NULL', ['after' => 'ip_address']);
        }
    }

    // -------------------------------------------------------
    // Tabela: Rede da Empresa
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_network')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_network` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`       int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`  int {$default_key_sign} NOT NULL DEFAULT 0,
            `ip_network`    varchar(45)           DEFAULT NULL,
            `netmask`       varchar(45)           DEFAULT NULL,
            `gateway`       varchar(45)           DEFAULT NULL,
            `dns_primary`   varchar(45)           DEFAULT NULL,
            `dns_secondary` varchar(45)           DEFAULT NULL,
            `supplier`      varchar(255)          DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_network');
        if (!isset($cols['supplier'])) {
            $migration->addField('glpi_plugin_newmanagement_ipbx_network', 'supplier', 'varchar(255) DEFAULT NULL', ['after' => 'dns_secondary']);
        }
    }

    // -------------------------------------------------------
    // Tabela: Linha Fixa da Empresa
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_lines')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_lines` (
            `id`               int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`          int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`     int {$default_key_sign} NOT NULL DEFAULT 0,
            `pilot_number`     varchar(50)           DEFAULT NULL,
            `line_type`        varchar(100)          DEFAULT NULL,
            `operator`         varchar(100)          DEFAULT NULL,
            `channels`         int                   DEFAULT 0,
            `ddr_count`        int                   DEFAULT 0,
            `proxy_ip`         varchar(45)           DEFAULT NULL,
            `proxy_port`       varchar(10)           DEFAULT NULL,
            `audio_ip`         varchar(45)           DEFAULT NULL,
            `portability_date`      date             DEFAULT NULL,
            `previous_operator`     varchar(100)     DEFAULT NULL,
            `activation_date`       date             DEFAULT NULL,
            `expiration_date`       date             DEFAULT NULL,
            `status`           tinyint(1)   NOT NULL DEFAULT 1 COMMENT '1=Ativo,2=Cancelado',
            `comment`          text                  DEFAULT NULL,
            `date_creation`    timestamp             DEFAULT NULL,
            `date_mod`         timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_lines');
        if (!isset($cols['pilot_number']))       $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'pilot_number',      'varchar(50)  DEFAULT NULL', ['after' => 'companies_id']);
        if (!isset($cols['line_type']))          $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'line_type',         'varchar(100) DEFAULT NULL', ['after' => 'pilot_number']);
        if (!isset($cols['operator']))           $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'operator',          'varchar(100) DEFAULT NULL', ['after' => 'line_type']);
        if (!isset($cols['channels']))           $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'channels',          'int DEFAULT 0',             ['after' => 'operator']);
        if (!isset($cols['ddr_count']))          $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'ddr_count',         'int DEFAULT 0',             ['after' => 'channels']);
        if (!isset($cols['proxy_ip']))           $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'proxy_ip',          'varchar(45)  DEFAULT NULL', ['after' => 'ddr_count']);
        if (!isset($cols['proxy_port']))         $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'proxy_port',        'varchar(10)  DEFAULT NULL', ['after' => 'proxy_ip']);
        if (!isset($cols['audio_ip']))           $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'audio_ip',          'varchar(45)  DEFAULT NULL', ['after' => 'proxy_port']);
        if (!isset($cols['portability_date']))   $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'portability_date',  'date DEFAULT NULL',         ['after' => 'audio_ip']);
        if (!isset($cols['previous_operator']))  $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'previous_operator', 'varchar(100) DEFAULT NULL', ['after' => 'portability_date']);
        if (!isset($cols['activation_date']))    $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'activation_date',   'date DEFAULT NULL',         ['after' => 'previous_operator']);
        if (!isset($cols['expiration_date']))    $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'expiration_date',   'date DEFAULT NULL',         ['after' => 'activation_date']);
        if (!isset($cols['status']))             $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'status',            "tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Ativo,2=Cancelado'", ['after' => 'expiration_date']);
    }

    // -------------------------------------------------------
    // Tabela: Chatbot
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbots')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbots` (
            `id`                    int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `companies_id`          int {$default_key_sign} NOT NULL DEFAULT 0,
            `platform`              varchar(100)          DEFAULT NULL,
            `phone_number`          varchar(50)           DEFAULT NULL,
            `account_id`            varchar(255)          DEFAULT NULL,
            `api_token`             text                  DEFAULT NULL,
            `webhook_url`           varchar(255)          DEFAULT NULL,
            `status`                tinyint(1)   NOT NULL DEFAULT 1,
            `responsible_name`      varchar(255)          DEFAULT NULL,
            `responsible_email`     varchar(255)          DEFAULT NULL,
            `responsible_phone`     varchar(50)           DEFAULT NULL,
            `facebook_page`         varchar(255)          DEFAULT NULL,
            `instagram_account`     varchar(255)          DEFAULT NULL,
            `comment`               text                  DEFAULT NULL,
            `date_creation`         timestamp             DEFAULT NULL,
            `date_mod`              timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_chatbots');
        if (!isset($cols['platform']))           $migration->addField('glpi_plugin_newmanagement_chatbots', 'platform',           'varchar(100) DEFAULT NULL',  ['after' => 'companies_id']);
        if (!isset($cols['phone_number']))       $migration->addField('glpi_plugin_newmanagement_chatbots', 'phone_number',       'varchar(50) DEFAULT NULL',   ['after' => 'platform']);
        if (!isset($cols['account_id']))         $migration->addField('glpi_plugin_newmanagement_chatbots', 'account_id',         'varchar(255) DEFAULT NULL',  ['after' => 'phone_number']);
        if (!isset($cols['api_token']))          $migration->addField('glpi_plugin_newmanagement_chatbots', 'api_token',          'text DEFAULT NULL',          ['after' => 'account_id']);
        if (!isset($cols['webhook_url']))        $migration->addField('glpi_plugin_newmanagement_chatbots', 'webhook_url',        'varchar(255) DEFAULT NULL',  ['after' => 'api_token']);
        if (!isset($cols['status']))             $migration->addField('glpi_plugin_newmanagement_chatbots', 'status',             'tinyint(1) NOT NULL DEFAULT 1', ['after' => 'webhook_url']);
        if (!isset($cols['responsible_name']))   $migration->addField('glpi_plugin_newmanagement_chatbots', 'responsible_name',  'varchar(255) DEFAULT NULL',  ['after' => 'status']);
        if (!isset($cols['responsible_email']))  $migration->addField('glpi_plugin_newmanagement_chatbots', 'responsible_email', 'varchar(255) DEFAULT NULL',  ['after' => 'responsible_name']);
        if (!isset($cols['responsible_phone']))  $migration->addField('glpi_plugin_newmanagement_chatbots', 'responsible_phone', 'varchar(50) DEFAULT NULL',   ['after' => 'responsible_email']);
        if (!isset($cols['facebook_page']))      $migration->addField('glpi_plugin_newmanagement_chatbots', 'facebook_page',     'varchar(255) DEFAULT NULL',  ['after' => 'responsible_phone']);
        if (!isset($cols['instagram_account']))  $migration->addField('glpi_plugin_newmanagement_chatbots', 'instagram_account', 'varchar(255) DEFAULT NULL',  ['after' => 'facebook_page']);
    }

    // -------------------------------------------------------
    // Tabela: Mass Communication (Chatbot filha)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_mass_comm')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbot_mass_comm` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `chatbot_id`    int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`  int {$default_key_sign} NOT NULL DEFAULT 0,
            `name`          varchar(255)          DEFAULT NULL,
            `type`          varchar(100)          DEFAULT NULL,
            `status`        tinyint(1)   NOT NULL DEFAULT 1,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `chatbot_id` (`chatbot_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: WhatsApp Restrictions (Chatbot filha)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_wa_restrictions')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbot_wa_restrictions` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `chatbot_id`    int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`  int {$default_key_sign} NOT NULL DEFAULT 0,
            `phone_number`  varchar(50)           DEFAULT NULL,
            `reason`        varchar(255)          DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `chatbot_id` (`chatbot_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: Chatbot Users (Chatbot filha)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_users')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbot_users` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `chatbot_id`    int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`  int {$default_key_sign} NOT NULL DEFAULT 0,
            `user_name`     varchar(255)          DEFAULT NULL,
            `user_login`    varchar(255)          DEFAULT NULL,
            `role`          varchar(100)          DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `chatbot_id` (`chatbot_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    $migration->executeMigration();

    return true;
}

function plugin_newmanagement_uninstall() {
    global $DB;

    $tables = [
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
    ];

    foreach ($tables as $table) {
        $DB->doQuery("DROP TABLE IF EXISTS `$table`");
    }

    return true;
}

function plugin_newmanagement_getDropdown() {
    return [];
}
