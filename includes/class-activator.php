<?php
/**
 * Fired during plugin activation.
 *
 * Creates plugin tables and sets version option.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Activator {

    /**
     * Runs on plugin activation.
     */
    public static function activate(): void {
        self::create_tables();
        update_option('formbridge_version', FORMBRIDGE_VERSION);
    }

    /**
     * Creates plugin tables using dbDelta.
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $connections_table = $wpdb->prefix . 'formbridge_connections';
        $mappings_table    = $wpdb->prefix . 'formbridge_mappings';
        $logs_table       = $wpdb->prefix . 'formbridge_logs';

        $sql_connections = "CREATE TABLE {$connections_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            db_host VARCHAR(191) NOT NULL,
            db_port INT NOT NULL DEFAULT 3306,
            db_name VARCHAR(191) NOT NULL,
            db_user VARCHAR(191) NOT NULL,
            db_pass TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        $sql_mappings = "CREATE TABLE {$mappings_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source_type VARCHAR(50) NOT NULL,
            form_id BIGINT UNSIGNED NOT NULL,
            connection_id BIGINT UNSIGNED NOT NULL,
            destination_type VARCHAR(50) NOT NULL DEFAULT 'mysql',
            destination_table VARCHAR(191) NOT NULL,
            field_map LONGTEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        $sql_logs = "CREATE TABLE {$logs_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source_type VARCHAR(50) NOT NULL,
            form_id BIGINT UNSIGNED NOT NULL,
            mapping_id BIGINT UNSIGNED NULL,
            destination_type VARCHAR(50) NOT NULL,
            destination_table VARCHAR(191) NOT NULL,
            payload LONGTEXT NULL,
            status VARCHAR(20) NOT NULL,
            message TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_connections);
        dbDelta($sql_mappings);
        dbDelta($sql_logs);
    }
}
