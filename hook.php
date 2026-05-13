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

    // -------------------------------------------------------
    // Tabela: Empresas
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_companies')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_companies` (
            `id`              INT(11)      NOT NULL AUTO_INCREMENT,
            `name`            VARCHAR(255) NOT NULL DEFAULT '',
            `cnpj`            VARCHAR(20)           DEFAULT NULL,
            `address`         TEXT                  DEFAULT NULL,
            `phone`           VARCHAR(50)           DEFAULT NULL,
            `email`           VARCHAR(255)          DEFAULT NULL,
            `comment`         TEXT                  DEFAULT NULL,
            `date_creation`   DATETIME              DEFAULT NULL,
            `date_mod`        DATETIME              DEFAULT NULL,
            `is_deleted`      TINYINT(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->queryOrDie($query, $DB->error());
    }

    // -------------------------------------------------------
    // Tabela: Servidores Telefônicos (Asterisk On-Premise)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_asterisk_servers')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_asterisk_servers` (
            `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
            `name`                VARCHAR(255) NOT NULL DEFAULT '',
            `companies_id`        INT(11)               DEFAULT NULL,
            `ip_address`          VARCHAR(45)           DEFAULT NULL,
            `asterisk_version`    VARCHAR(50)           DEFAULT NULL,
            `sip_trunk`           VARCHAR(255)          DEFAULT NULL,
            `extensions_count`    INT(11)               DEFAULT 0,
            `comment`             TEXT                  DEFAULT NULL,
            `date_creation`       DATETIME              DEFAULT NULL,
            `date_mod`            DATETIME              DEFAULT NULL,
            `is_deleted`          TINYINT(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->queryOrDie($query, $DB->error());
    }

    // -------------------------------------------------------
    // Tabela: Servidores Telefônicos em Nuvem (Asterisk Cloud)
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_asterisk_cloud')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_asterisk_cloud` (
            `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
            `name`                VARCHAR(255) NOT NULL DEFAULT '',
            `companies_id`        INT(11)               DEFAULT NULL,
            `provider`            VARCHAR(255)          DEFAULT NULL,
            `cloud_region`        VARCHAR(100)          DEFAULT NULL,
            `sip_trunk`           VARCHAR(255)          DEFAULT NULL,
            `extensions_count`    INT(11)               DEFAULT 0,
            `comment`             TEXT                  DEFAULT NULL,
            `date_creation`       DATETIME              DEFAULT NULL,
            `date_mod`            DATETIME              DEFAULT NULL,
            `is_deleted`          TINYINT(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->queryOrDie($query, $DB->error());
    }

    // -------------------------------------------------------
    // Tabela: Chatbot Omnichannel
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_chatbots')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_chatbots` (
            `id`              INT(11)      NOT NULL AUTO_INCREMENT,
            `name`            VARCHAR(255) NOT NULL DEFAULT '',
            `companies_id`    INT(11)               DEFAULT NULL,
            `platform`        VARCHAR(100)          DEFAULT NULL,
            `channels`        TEXT                  DEFAULT NULL,
            `api_endpoint`    VARCHAR(255)          DEFAULT NULL,
            `comment`         TEXT                  DEFAULT NULL,
            `date_creation`   DATETIME              DEFAULT NULL,
            `date_mod`        DATETIME              DEFAULT NULL,
            `is_deleted`      TINYINT(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->queryOrDie($query, $DB->error());
    }

    // -------------------------------------------------------
    // Tabela: Linhas Fixas
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_fixedlines')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_fixedlines` (
            `id`              INT(11)      NOT NULL AUTO_INCREMENT,
            `name`            VARCHAR(255) NOT NULL DEFAULT '',
            `companies_id`    INT(11)               DEFAULT NULL,
            `number`          VARCHAR(50)           DEFAULT NULL,
            `operator`        VARCHAR(100)          DEFAULT NULL,
            `contract_end`    DATE                  DEFAULT NULL,
            `comment`         TEXT                  DEFAULT NULL,
            `date_creation`   DATETIME              DEFAULT NULL,
            `date_mod`        DATETIME              DEFAULT NULL,
            `is_deleted`      TINYINT(1)   NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->queryOrDie($query, $DB->error());
    }

    // -------------------------------------------------------
    // Tabela: Tarefas com Geolocalização
    // -------------------------------------------------------
    if (!$DB->tableExists('glpi_plugin_newmanagement_tasks')) {
        $query = "CREATE TABLE `glpi_plugin_newmanagement_tasks` (
            `id`                INT(11)       NOT NULL AUTO_INCREMENT,
            `name`              VARCHAR(255)  NOT NULL DEFAULT '',
            `companies_id`      INT(11)                DEFAULT NULL,
            `assigned_user_id`  INT(11)                DEFAULT NULL,
            `status`            TINYINT(1)    NOT NULL DEFAULT 0,
            `latitude`          DECIMAL(10,7)          DEFAULT NULL,
            `longitude`         DECIMAL(10,7)          DEFAULT NULL,
            `km_calculated`     DECIMAL(10,2)          DEFAULT NULL,
            `digital_signature` TEXT                   DEFAULT NULL,
            `date_due`          DATETIME               DEFAULT NULL,
            `comment`           TEXT                   DEFAULT NULL,
            `date_creation`     DATETIME               DEFAULT NULL,
            `date_mod`          DATETIME               DEFAULT NULL,
            `is_deleted`        TINYINT(1)    NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->queryOrDie($query, $DB->error());
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
        'glpi_plugin_newmanagement_asterisk_servers',
        'glpi_plugin_newmanagement_asterisk_cloud',
        'glpi_plugin_newmanagement_chatbots',
        'glpi_plugin_newmanagement_fixedlines',
        'glpi_plugin_newmanagement_tasks',
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->queryOrDie("DROP TABLE `$table`", $DB->error());
        }
    }

    return true;
}
