<?php

/**
 * Newmanagement - Plugin GLPI
 * Funções de instalação, atualização e desinstalação
 */

/**
 * Instala o plugin (criação das tabelas no banco de dados)
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
        // Migração: adiciona colunas novas em instalações antigas
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
            `id`                int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name`              varchar(255) NOT NULL DEFAULT '',
            `companies_id`      int {$default_key_sign}       DEFAULT NULL,
            `ip_address`        varchar(45)           DEFAULT NULL,
            `asterisk_version`  varchar(50)           DEFAULT NULL,
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
    // Tabela: Linhas Fixas
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
    // Registra os direitos de acesso no banco
    // -------------------------------------------------------
    $rights = [
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Company',    'name' => 'plugin_newmanagement_company'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Ipbx',       'name' => 'plugin_newmanagement_ipbx'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\IpbxCloud',  'name' => 'plugin_newmanagement_ipbxcloud'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Chatbot',    'name' => 'plugin_newmanagement_chatbot'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\FixedLine',  'name' => 'plugin_newmanagement_fixedline'],
        ['itemtype' => 'GlpiPlugin\\Newmanagement\\Task',       'name' => 'plugin_newmanagement_task'],
    ];

    foreach ($rights as $right) {
        ProfileRight::addProfileRights([$right['name']]);
        ProfileRight::updateProfileRights(4, [$right['name'] => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE]);
    }

    $migration->executeMigration();
    return true;
}

/**
 * Desinstala o plugin (remove as tabelas do banco de dados)
 */
function plugin_newmanagement_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_newmanagement_companies',
        'glpi_plugin_newmanagement_ipbx',
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

    $rights_names = [
        'plugin_newmanagement_company',
        'plugin_newmanagement_ipbx',
        'plugin_newmanagement_ipbxcloud',
        'plugin_newmanagement_chatbot',
        'plugin_newmanagement_fixedline',
        'plugin_newmanagement_task',
    ];
    ProfileRight::deleteProfileRights($rights_names);

    return true;
}
