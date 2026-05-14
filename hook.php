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
            `phone`           varchar(50)            DEFAULT NULL,
            `cep`             varchar(10)            DEFAULT NULL,
            `address`         text                   DEFAULT NULL,
            `contract_status` tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=Sem contrato,1=Ativo,2=Cancelado',
            `comment`         text                   DEFAULT NULL,
            `date_creation`   timestamp              DEFAULT NULL,
            `date_mod`        timestamp              DEFAULT NULL,
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
    // Tabela: IPBX (principal) — recriada com todos os campos
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx` (
            `id`             int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name`           varchar(255) NOT NULL DEFAULT '',
            `companies_id`   int {$default_key_sign}       DEFAULT NULL,
            `server_model`   varchar(255)           DEFAULT NULL,
            `server_version` varchar(100)           DEFAULT NULL,
            `ip_local`       varchar(45)            DEFAULT NULL,
            `ip_external`    varchar(45)            DEFAULT NULL,
            `web_port`       varchar(10)            DEFAULT NULL,
            `web_password`   varchar(255)           DEFAULT NULL,
            `ssh_port`       varchar(10)            DEFAULT NULL,
            `ssh_password`   varchar(255)           DEFAULT NULL,
            `comment`        text                   DEFAULT NULL,
            `date_creation`  timestamp              DEFAULT NULL,
            `date_mod`       timestamp              DEFAULT NULL,
            `is_deleted`     tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        // Migração de instalações antigas
        $columns = $DB->listFields('glpi_plugin_newmanagement_ipbx');
        $new_cols = [
            'companies_id'   => "int unsigned DEFAULT NULL AFTER `name`",
            'server_model'   => "varchar(255) DEFAULT NULL AFTER `companies_id`",
            'server_version' => "varchar(100) DEFAULT NULL AFTER `server_model`",
            'ip_local'       => "varchar(45) DEFAULT NULL AFTER `server_version`",
            'ip_external'    => "varchar(45) DEFAULT NULL AFTER `ip_local`",
            'web_port'       => "varchar(10) DEFAULT NULL AFTER `ip_external`",
            'web_password'   => "varchar(255) DEFAULT NULL AFTER `web_port`",
            'ssh_port'       => "varchar(10) DEFAULT NULL AFTER `web_password`",
            'ssh_password'   => "varchar(255) DEFAULT NULL AFTER `ssh_port`",
        ];
        foreach ($new_cols as $col => $def) {
            if (!isset($columns[$col])) {
                $migration->addField('glpi_plugin_newmanagement_ipbx', $col, $def);
            }
        }
    }

    // -------------------------------------------------------
    // Tabela: Ramais do IPBX
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_extensions')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_extensions` (
            `id`               int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`          int {$default_key_sign} NOT NULL,
            `extension_number` varchar(20)            DEFAULT NULL,
            `extension_pass`   varchar(255)           DEFAULT NULL,
            `device_ip`        varchar(45)            DEFAULT NULL,
            `user_name`        varchar(255)           DEFAULT NULL,
            `records_calls`    tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=Nao,1=Sim',
            `department`       varchar(255)           DEFAULT NULL,
            `date_creation`    timestamp              DEFAULT NULL,
            `date_mod`         timestamp              DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: Dispositivos do IPBX
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_devices')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_devices` (
            `id`              int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`         int {$default_key_sign} NOT NULL,
            `device_type`     varchar(100)           DEFAULT NULL,
            `device_ip`       varchar(45)            DEFAULT NULL,
            `device_password` varchar(255)           DEFAULT NULL,
            `date_creation`   timestamp              DEFAULT NULL,
            `date_mod`        timestamp              DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: Rede da empresa (1:1 com IPBX)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_network')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_network` (
            `id`           int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`      int {$default_key_sign} NOT NULL,
            `network_ip`   varchar(45)            DEFAULT NULL,
            `netmask`      varchar(45)            DEFAULT NULL,
            `gateway`      varchar(45)            DEFAULT NULL,
            `dns_primary`  varchar(45)            DEFAULT NULL,
            `dns_secondary` varchar(45)           DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: Linhas Fixas do IPBX
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_lines')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_lines` (
            `id`                int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `ipbx_id`           int {$default_key_sign} NOT NULL,
            `pilot_number`      varchar(50)            DEFAULT NULL,
            `line_type`         varchar(100)           DEFAULT NULL,
            `operator`          varchar(100)           DEFAULT NULL,
            `channels_count`    int                    DEFAULT 0,
            `ddr_count`         int                    DEFAULT 0,
            `proxy_ip`          varchar(45)            DEFAULT NULL,
            `proxy_port`        varchar(10)            DEFAULT NULL,
            `audio_ip`          varchar(45)            DEFAULT NULL,
            `portability_date`  date                   DEFAULT NULL,
            `previous_operator` varchar(100)           DEFAULT NULL,
            `activation_date`   date                   DEFAULT NULL,
            `expiration_date`   date                   DEFAULT NULL,
            `line_status`       tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=Ativo,1=Cancelado',
            `comment`           text                   DEFAULT NULL,
            `date_creation`     timestamp              DEFAULT NULL,
            `date_mod`          timestamp              DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: IPBX Cloud
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_cloud')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_cloud` (
            `id`                int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name`              varchar(255) NOT NULL DEFAULT '',
            `companies_id`      int {$default_key_sign}       DEFAULT NULL,
            `provider`          varchar(255)          DEFAULT NULL,
            `cloud_region`      varchar(100)          DEFAULT NULL,
            `sip_trunk`         varchar(255)          DEFAULT NULL,
            `extensions_count`  int                   DEFAULT 0,
            `comment`           text                  DEFAULT NULL,
            `date_creation`     timestamp             DEFAULT NULL,
            `date_mod`          timestamp             DEFAULT NULL,
            `is_deleted`        tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: Chatbot Omnichannel
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbots')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbots` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name`          varchar(255) NOT NULL DEFAULT '',
            `companies_id`  int {$default_key_sign}       DEFAULT NULL,
            `platform`      varchar(100)          DEFAULT NULL,
            `channels`      text                  DEFAULT NULL,
            `api_endpoint`  varchar(255)          DEFAULT NULL,
            `comment`       text                  DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            `is_deleted`    tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Tabela: Tarefas com Geolocalização
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_tasks')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_tasks` (
            `id`                int {$default_key_sign}  NOT NULL AUTO_INCREMENT,
            `name`              varchar(255)  NOT NULL DEFAULT '',
            `companies_id`      int {$default_key_sign}         DEFAULT NULL,
            `assigned_user_id`  int {$default_key_sign}         DEFAULT NULL,
            `status`            tinyint(1)    NOT NULL DEFAULT 0,
            `latitude`          decimal(10,7)          DEFAULT NULL,
            `longitude`         decimal(10,7)          DEFAULT NULL,
            `km_calculated`     decimal(10,2)          DEFAULT NULL,
            `digital_signature` text                   DEFAULT NULL,
            `date_due`          timestamp              DEFAULT NULL,
            `comment`           text                   DEFAULT NULL,
            `date_creation`     timestamp              DEFAULT NULL,
            `date_mod`          timestamp              DEFAULT NULL,
            `is_deleted`        tinyint(1)    NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    }

    // -------------------------------------------------------
    // Direitos de acesso
    // -------------------------------------------------------
    $rights = [
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Company',        'name' => 'plugin_newmanagement_company'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Ipbx',           'name' => 'plugin_newmanagement_ipbx'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\IpbxExtension',  'name' => 'plugin_newmanagement_ipbxextension'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\IpbxDevice',     'name' => 'plugin_newmanagement_ipbxdevice'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\IpbxNetwork',    'name' => 'plugin_newmanagement_ipbxnetwork'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\IpbxLine',       'name' => 'plugin_newmanagement_ipbxline'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\IpbxCloud',      'name' => 'plugin_newmanagement_ipbxcloud'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Chatbot',        'name' => 'plugin_newmanagement_chatbot'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Task',           'name' => 'plugin_newmanagement_task'],
    ];

    foreach ($rights as $right) {
        ProfileRight::addProfileRights([$right['name']]);
        ProfileRight::updateProfileRights(4, [$right['name'] => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE]);
    }

    $migration->executeMigration();
    return true;
}

function plugin_newmanagement_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_newmanagement_ipbx_lines',
        'glpi_plugin_newmanagement_ipbx_network',
        'glpi_plugin_newmanagement_ipbx_devices',
        'glpi_plugin_newmanagement_ipbx_extensions',
        'glpi_plugin_newmanagement_ipbx',
        'glpi_plugin_newmanagement_ipbx_cloud',
        'glpi_plugin_newmanagement_chatbots',
        'glpi_plugin_newmanagement_tasks',
        'glpi_plugin_newmanagement_companies',
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQueryOrDie("DROP TABLE `{$table}`");
        }
    }

    $rights_names = [
        'plugin_newmanagement_company',
        'plugin_newmanagement_ipbx',
        'plugin_newmanagement_ipbxextension',
        'plugin_newmanagement_ipbxdevice',
        'plugin_newmanagement_ipbxnetwork',
        'plugin_newmanagement_ipbxline',
        'plugin_newmanagement_ipbxcloud',
        'plugin_newmanagement_chatbot',
        'plugin_newmanagement_task',
    ];
    ProfileRight::deleteProfileRights($rights_names);

    return true;
}
