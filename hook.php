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
    // Tabela: IPBX On-Premise (reformulada com todos os campos)
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
            `password`      varchar(255)          DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
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
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
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
    // Tabela: Linhas Fixas (legado)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_fixedlines')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_fixedlines` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name`          varchar(255) NOT NULL DEFAULT '',
            `companies_id`  int {$default_key_sign}       DEFAULT NULL,
            `number`        varchar(50)           DEFAULT NULL,
            `operator`      varchar(100)          DEFAULT NULL,
            `contract_end`  date                  DEFAULT NULL,
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
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Company',   'name' => 'plugin_newmanagement_company'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Ipbx',      'name' => 'plugin_newmanagement_ipbx'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\IpbxCloud', 'name' => 'plugin_newmanagement_ipbxcloud'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Chatbot',   'name' => 'plugin_newmanagement_chatbot'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\FixedLine', 'name' => 'plugin_newmanagement_fixedline'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Task',      'name' => 'plugin_newmanagement_task'],
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
        'glpi_plugin_newmanagement_companies',
        'glpi_plugin_newmanagement_ipbx_cloud',
        'glpi_plugin_newmanagement_chatbots',
        'glpi_plugin_newmanagement_fixedlines',
        'glpi_plugin_newmanagement_tasks',
    ];
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQueryOrDie("DROP TABLE `{$table}`");
        }
    }

    ProfileRight::deleteProfileRights([
        'plugin_newmanagement_company',
        'plugin_newmanagement_ipbx',
        'plugin_newmanagement_ipbxcloud',
        'plugin_newmanagement_chatbot',
        'plugin_newmanagement_fixedline',
        'plugin_newmanagement_task',
    ]);

    return true;
}
