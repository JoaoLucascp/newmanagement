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
        if (!isset($columns['razao_social']))
            $migration->addField('glpi_plugin_newmanagement_companies', 'razao_social', 'varchar(255) DEFAULT NULL', ['after' => 'cnpj']);
        if (!isset($columns['cep']))
            $migration->addField('glpi_plugin_newmanagement_companies', 'cep', 'varchar(10) DEFAULT NULL', ['after' => 'phone']);
        if (!isset($columns['contract_status']))
            $migration->addField('glpi_plugin_newmanagement_companies', 'contract_status', "tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Sem contrato,1=Ativo,2=Cancelado'", ['after' => 'address']);
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
            `web_password`    text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
            `ssh_port`        varchar(10)           DEFAULT NULL,
            `ssh_password`    text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
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
        if (!isset($cols['model']))          $migration->addField('glpi_plugin_newmanagement_ipbx', 'model',          'varchar(255) DEFAULT NULL', ['after' => 'companies_id']);
        if (!isset($cols['server_version'])) $migration->addField('glpi_plugin_newmanagement_ipbx', 'server_version', 'varchar(50)  DEFAULT NULL', ['after' => 'model']);
        if (!isset($cols['ip_local']))       $migration->addField('glpi_plugin_newmanagement_ipbx', 'ip_local',       'varchar(45)  DEFAULT NULL', ['after' => 'server_version']);
        if (!isset($cols['ip_external']))    $migration->addField('glpi_plugin_newmanagement_ipbx', 'ip_external',    'varchar(45)  DEFAULT NULL', ['after' => 'ip_local']);
        if (!isset($cols['web_port']))       $migration->addField('glpi_plugin_newmanagement_ipbx', 'web_port',       'varchar(10)  DEFAULT NULL', ['after' => 'ip_external']);
        if (!isset($cols['ssh_port']))       $migration->addField('glpi_plugin_newmanagement_ipbx', 'ssh_port',       'varchar(10)  DEFAULT NULL', ['after' => 'web_password']);
        if (!isset($cols['ssh_password']))   $migration->addField('glpi_plugin_newmanagement_ipbx', 'ssh_password',   'text DEFAULT NULL',         ['after' => 'ssh_port']);
        // [FIX] Garantir web_password se ainda nao existir (com tipo correto)
        if (!isset($cols['web_password']))
            $migration->addField('glpi_plugin_newmanagement_ipbx', 'web_password', 'text DEFAULT NULL', ['after' => 'web_port']);
        // [FIX] Ampliar colunas de senha de varchar(255) para text (sodiumEncrypt pode ultrapassar 255 chars)
        if (isset($cols['web_password']) && $cols['web_password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_ipbx', 'web_password', 'web_password', 'text DEFAULT NULL');
        if (isset($cols['ssh_password']) && $cols['ssh_password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_ipbx', 'ssh_password', 'ssh_password', 'text DEFAULT NULL');
    }

    // -------------------------------------------------------
    // Tabela: IPBX Cloud
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_ipbx_cloud')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_ipbx_cloud` (
            `id`              int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `companies_id`    int {$default_key_sign} NOT NULL DEFAULT 0,
            `provider`        varchar(255)          DEFAULT NULL,
            `plan`            varchar(100)          DEFAULT NULL,
            `url`             varchar(255)          DEFAULT NULL,
            `login`           varchar(255)          DEFAULT NULL,
            `password`        text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
            `api_token`       text                  DEFAULT NULL,
            `status`          tinyint(1)   NOT NULL DEFAULT 1 COMMENT '1=Ativo,2=Cancelado',
            `comment`         text                  DEFAULT NULL,
            `date_creation`   timestamp             DEFAULT NULL,
            `date_mod`        timestamp             DEFAULT NULL,
            `is_deleted`      tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        // [FIX] Bloco else para migration de colunas futuras em ipbx_cloud
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_cloud');
        if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_ipbx_cloud', 'password', 'password', 'text DEFAULT NULL');
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
            `password`       text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
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
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_extensions');
        // [FIX] Ampliar coluna de senha de varchar(255) para text
        if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_ipbx_extensions', 'password', 'password', 'text DEFAULT NULL');
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
            `password`      text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `ipbx_id` (`ipbx_id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_devices');
        if (!isset($cols['login']))
            $migration->addField('glpi_plugin_newmanagement_ipbx_devices', 'login', 'varchar(255) DEFAULT NULL', ['after' => 'ip_address']);
        // [FIX] Ampliar coluna de senha de varchar(255) para text
        if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_ipbx_devices', 'password', 'password', 'text DEFAULT NULL');
        // [FIX] Adicionar indice companies_id se ainda nao existir
        $migration->addKey('glpi_plugin_newmanagement_ipbx_devices', 'companies_id');
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
            KEY `ipbx_id` (`ipbx_id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_network');
        if (!isset($cols['supplier']))
            $migration->addField('glpi_plugin_newmanagement_ipbx_network', 'supplier', 'varchar(255) DEFAULT NULL', ['after' => 'dns_secondary']);
        // [FIX] Adicionar indice companies_id se ainda nao existir
        $migration->addKey('glpi_plugin_newmanagement_ipbx_network', 'companies_id');
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
            KEY `ipbx_id` (`ipbx_id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_ipbx_lines');
        if (!isset($cols['pilot_number']))      $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'pilot_number',      'varchar(50)  DEFAULT NULL', ['after' => 'companies_id']);
        if (!isset($cols['line_type']))         $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'line_type',         'varchar(100) DEFAULT NULL', ['after' => 'pilot_number']);
        if (!isset($cols['operator']))          $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'operator',          'varchar(100) DEFAULT NULL', ['after' => 'line_type']);
        if (!isset($cols['channels']))          $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'channels',          'int DEFAULT 0',             ['after' => 'operator']);
        if (!isset($cols['ddr_count']))         $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'ddr_count',         'int DEFAULT 0',             ['after' => 'channels']);
        if (!isset($cols['proxy_ip']))          $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'proxy_ip',          'varchar(45)  DEFAULT NULL', ['after' => 'ddr_count']);
        if (!isset($cols['proxy_port']))        $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'proxy_port',        'varchar(10)  DEFAULT NULL', ['after' => 'proxy_ip']);
        if (!isset($cols['audio_ip']))          $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'audio_ip',          'varchar(45)  DEFAULT NULL', ['after' => 'proxy_port']);
        if (!isset($cols['portability_date']))  $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'portability_date',  'date DEFAULT NULL',         ['after' => 'audio_ip']);
        if (!isset($cols['previous_operator'])) $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'previous_operator', 'varchar(100) DEFAULT NULL', ['after' => 'portability_date']);
        if (!isset($cols['activation_date']))   $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'activation_date',   'date DEFAULT NULL',         ['after' => 'previous_operator']);
        if (!isset($cols['expiration_date']))   $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'expiration_date',   'date DEFAULT NULL',         ['after' => 'activation_date']);
        if (!isset($cols['status']))            $migration->addField('glpi_plugin_newmanagement_ipbx_lines', 'status',            "tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Ativo,2=Cancelado'", ['after' => 'expiration_date']);
        // [FIX] Adicionar indice companies_id se ainda nao existir
        $migration->addKey('glpi_plugin_newmanagement_ipbx_lines', 'companies_id');
    }

    // -------------------------------------------------------
    // Tabela: Tasks
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_tasks')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_tasks` (
            `id`            int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name`          varchar(255) NOT NULL DEFAULT '',
            `companies_id`  int {$default_key_sign} NOT NULL DEFAULT 0,
            `status`        tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=Pendente,1=Em andamento,2=Concluida',
            `date_due`      datetime              DEFAULT NULL,
            `km_calculated` decimal(10,2)         DEFAULT NULL,
            `latitude`      decimal(10,6)         DEFAULT NULL,
            `longitude`     decimal(10,6)         DEFAULT NULL,
            `comment`       text                  DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            `is_deleted`    tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        // [FIX] Bloco else reservado para migration de colunas futuras em tasks
        $cols = $DB->listFields('glpi_plugin_newmanagement_tasks');
    }

    // -------------------------------------------------------
    // Tabela: Chatbot
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbots')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbots` (
            `id`                      int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `companies_id`            int {$default_key_sign} NOT NULL DEFAULT 0,
            `model`                   varchar(255)          DEFAULT NULL,
            `chatbot_registration_id` varchar(255)          DEFAULT NULL,
            `activation_date`         date                  DEFAULT NULL,
            `whatsapp_number`         varchar(50)           DEFAULT NULL,
            `access_link`             varchar(255)          DEFAULT NULL,
            `plan`                    varchar(100)          DEFAULT NULL,
            `users_count`             int                   DEFAULT 0,
            `supervisors_count`       int                   DEFAULT 0,
            `admins_count`            int                   DEFAULT 0,
            `admin_login`             varchar(255)          DEFAULT NULL,
            `admin_password`          text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
            `superadmin_login`        varchar(255)          DEFAULT NULL,
            `superadmin_password`     text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
            `manager_name`            varchar(255)          DEFAULT NULL,
            `manager_contact`         varchar(100)          DEFAULT NULL,
            `manager_email`           varchar(255)          DEFAULT NULL,
            `social_networks`         text                  DEFAULT NULL,
            `comment`                 text                  DEFAULT NULL,
            `date_creation`           timestamp             DEFAULT NULL,
            `date_mod`                timestamp             DEFAULT NULL,
            `is_deleted`              tinyint(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_chatbots');
        if (!isset($cols['model']))                   $migration->addField('glpi_plugin_newmanagement_chatbots', 'model',                   'varchar(255) DEFAULT NULL',     ['after' => 'companies_id']);
        if (!isset($cols['chatbot_registration_id'])) $migration->addField('glpi_plugin_newmanagement_chatbots', 'chatbot_registration_id', 'varchar(255) DEFAULT NULL',     ['after' => 'model']);
        if (!isset($cols['activation_date']))         $migration->addField('glpi_plugin_newmanagement_chatbots', 'activation_date',         'date DEFAULT NULL',             ['after' => 'chatbot_registration_id']);
        if (!isset($cols['whatsapp_number']))         $migration->addField('glpi_plugin_newmanagement_chatbots', 'whatsapp_number',         'varchar(50) DEFAULT NULL',      ['after' => 'activation_date']);
        if (!isset($cols['access_link']))             $migration->addField('glpi_plugin_newmanagement_chatbots', 'access_link',             'varchar(255) DEFAULT NULL',     ['after' => 'whatsapp_number']);
        if (!isset($cols['plan']))                    $migration->addField('glpi_plugin_newmanagement_chatbots', 'plan',                    'varchar(100) DEFAULT NULL',     ['after' => 'access_link']);
        if (!isset($cols['users_count']))             $migration->addField('glpi_plugin_newmanagement_chatbots', 'users_count',             'int DEFAULT 0',                 ['after' => 'plan']);
        if (!isset($cols['supervisors_count']))       $migration->addField('glpi_plugin_newmanagement_chatbots', 'supervisors_count',       'int DEFAULT 0',                 ['after' => 'users_count']);
        if (!isset($cols['admins_count']))            $migration->addField('glpi_plugin_newmanagement_chatbots', 'admins_count',            'int DEFAULT 0',                 ['after' => 'supervisors_count']);
        if (!isset($cols['admin_login']))             $migration->addField('glpi_plugin_newmanagement_chatbots', 'admin_login',             'varchar(255) DEFAULT NULL',     ['after' => 'admins_count']);
        if (!isset($cols['superadmin_login']))        $migration->addField('glpi_plugin_newmanagement_chatbots', 'superadmin_login',        'varchar(255) DEFAULT NULL',     ['after' => 'admin_password']);
        if (!isset($cols['manager_name']))            $migration->addField('glpi_plugin_newmanagement_chatbots', 'manager_name',            'varchar(255) DEFAULT NULL',     ['after' => 'superadmin_password']);
        if (!isset($cols['manager_contact']))         $migration->addField('glpi_plugin_newmanagement_chatbots', 'manager_contact',         'varchar(100) DEFAULT NULL',     ['after' => 'manager_name']);
        if (!isset($cols['manager_email']))           $migration->addField('glpi_plugin_newmanagement_chatbots', 'manager_email',           'varchar(255) DEFAULT NULL',     ['after' => 'manager_contact']);
        if (!isset($cols['social_networks']))         $migration->addField('glpi_plugin_newmanagement_chatbots', 'social_networks',         'text DEFAULT NULL',             ['after' => 'manager_email']);
        if (!isset($cols['is_deleted']))              $migration->addField('glpi_plugin_newmanagement_chatbots', 'is_deleted',              'tinyint(1) NOT NULL DEFAULT 0', ['after' => 'date_mod']);
        // [FIX] Adicionar colunas de senha se nao existirem (com tipo text correto)
        if (!isset($cols['admin_password']))
            $migration->addField('glpi_plugin_newmanagement_chatbots', 'admin_password',      'text DEFAULT NULL', ['after' => 'admin_login']);
        if (!isset($cols['superadmin_password']))
            $migration->addField('glpi_plugin_newmanagement_chatbots', 'superadmin_password', 'text DEFAULT NULL', ['after' => 'superadmin_login']);
        // [FIX] Ampliar colunas de senha de varchar(255) para text
        if (isset($cols['admin_password'])      && $cols['admin_password']['Type']      === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_chatbots', 'admin_password',      'admin_password',      'text DEFAULT NULL');
        if (isset($cols['superadmin_password']) && $cols['superadmin_password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_chatbots', 'superadmin_password', 'superadmin_password', 'text DEFAULT NULL');
    }

    // -------------------------------------------------------
    // Tabela: Mass Communication (Chatbot filha)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_mass_comm')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbot_mass_comm` (
            `id`                   int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `chatbot_id`           int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`         int {$default_key_sign} NOT NULL DEFAULT 0,
            `system_name`          varchar(255)          DEFAULT NULL,
            `activation_date`      date                  DEFAULT NULL,
            `authenticated_number` varchar(50)           DEFAULT NULL,
            `homologation_type`    varchar(100)          DEFAULT NULL,
            `access_link`          varchar(255)          DEFAULT NULL,
            `login`                varchar(255)          DEFAULT NULL,
            `password`             text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
            `manager`              varchar(255)          DEFAULT NULL,
            `date_creation`        timestamp             DEFAULT NULL,
            `date_mod`             timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `chatbot_id` (`chatbot_id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_chatbot_mass_comm');
        if (!isset($cols['system_name']))          $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'system_name',          'varchar(255) DEFAULT NULL', ['after' => 'companies_id']);
        if (!isset($cols['activation_date']))      $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'activation_date',      'date DEFAULT NULL',         ['after' => 'system_name']);
        if (!isset($cols['authenticated_number'])) $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'authenticated_number', 'varchar(50) DEFAULT NULL',  ['after' => 'activation_date']);
        if (!isset($cols['homologation_type']))    $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'homologation_type',    'varchar(100) DEFAULT NULL', ['after' => 'authenticated_number']);
        if (!isset($cols['access_link']))          $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'access_link',          'varchar(255) DEFAULT NULL', ['after' => 'homologation_type']);
        if (!isset($cols['login']))                $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'login',                'varchar(255) DEFAULT NULL', ['after' => 'access_link']);
        if (!isset($cols['manager']))              $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'manager',              'varchar(255) DEFAULT NULL', ['after' => 'password']);
        // [FIX] Adicionar/ampliar coluna de senha
        if (!isset($cols['password']))
            $migration->addField('glpi_plugin_newmanagement_chatbot_mass_comm', 'password', 'text DEFAULT NULL', ['after' => 'login']);
        if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_chatbot_mass_comm', 'password', 'password', 'text DEFAULT NULL');
        // [FIX] Adicionar indice companies_id
        $migration->addKey('glpi_plugin_newmanagement_chatbot_mass_comm', 'companies_id');
    }

    // -------------------------------------------------------
    // Tabela: WhatsApp Restrictions (Chatbot filha)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbot_wa_restrictions')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbot_wa_restrictions` (
            `id`               int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `chatbot_id`       int {$default_key_sign} NOT NULL DEFAULT 0,
            `companies_id`     int {$default_key_sign} NOT NULL DEFAULT 0,
            `whatsapp_number`  varchar(50)           DEFAULT NULL,
            `restriction_date` date                  DEFAULT NULL,
            `restriction_time` varchar(50)           DEFAULT NULL,
            `end_date`         date                  DEFAULT NULL,
            `date_creation`    timestamp             DEFAULT NULL,
            `date_mod`         timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `chatbot_id` (`chatbot_id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_chatbot_wa_restrictions');
        if (!isset($cols['whatsapp_number']))  $migration->addField('glpi_plugin_newmanagement_chatbot_wa_restrictions', 'whatsapp_number',  'varchar(50) DEFAULT NULL', ['after' => 'companies_id']);
        if (!isset($cols['restriction_date'])) $migration->addField('glpi_plugin_newmanagement_chatbot_wa_restrictions', 'restriction_date', 'date DEFAULT NULL',        ['after' => 'whatsapp_number']);
        if (!isset($cols['restriction_time'])) $migration->addField('glpi_plugin_newmanagement_chatbot_wa_restrictions', 'restriction_time', 'varchar(50) DEFAULT NULL', ['after' => 'restriction_date']);
        if (!isset($cols['end_date']))         $migration->addField('glpi_plugin_newmanagement_chatbot_wa_restrictions', 'end_date',         'date DEFAULT NULL',        ['after' => 'restriction_time']);
        // [FIX] Adicionar indice companies_id
        $migration->addKey('glpi_plugin_newmanagement_chatbot_wa_restrictions', 'companies_id');
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
            `login`         varchar(255)          DEFAULT NULL,
            `password`      text                  DEFAULT NULL COMMENT 'sodiumEncrypt',
            `email`         varchar(255)          DEFAULT NULL,
            `user_type`     varchar(100)          DEFAULT NULL,
            `date_creation` timestamp             DEFAULT NULL,
            `date_mod`      timestamp             DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `chatbot_id` (`chatbot_id`),
            KEY `companies_id` (`companies_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query);
    } else {
        $cols = $DB->listFields('glpi_plugin_newmanagement_chatbot_users');
        if (!isset($cols['login']))     $migration->addField('glpi_plugin_newmanagement_chatbot_users', 'login',     'varchar(255) DEFAULT NULL', ['after' => 'user_name']);
        if (!isset($cols['email']))     $migration->addField('glpi_plugin_newmanagement_chatbot_users', 'email',     'varchar(255) DEFAULT NULL', ['after' => 'password']);
        if (!isset($cols['user_type'])) $migration->addField('glpi_plugin_newmanagement_chatbot_users', 'user_type', 'varchar(100) DEFAULT NULL', ['after' => 'email']);
        // [FIX] Adicionar/ampliar coluna de senha
        if (!isset($cols['password']))
            $migration->addField('glpi_plugin_newmanagement_chatbot_users', 'password', 'text DEFAULT NULL', ['after' => 'login']);
        if (isset($cols['password']) && $cols['password']['Type'] === 'varchar(255)')
            $migration->changeField('glpi_plugin_newmanagement_chatbot_users', 'password', 'password', 'text DEFAULT NULL');
        // [FIX] Adicionar indice companies_id
        $migration->addKey('glpi_plugin_newmanagement_chatbot_users', 'companies_id');
    }

    $migration->executeMigration();

    return true;
}

function plugin_newmanagement_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_newmanagement_companies',
        'glpi_plugin_newmanagement_ipbx',
        'glpi_plugin_newmanagement_ipbx_cloud',
        'glpi_plugin_newmanagement_ipbx_extensions',
        'glpi_plugin_newmanagement_ipbx_devices',
        'glpi_plugin_newmanagement_ipbx_network',
        'glpi_plugin_newmanagement_ipbx_lines',
        'glpi_plugin_newmanagement_tasks',
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
