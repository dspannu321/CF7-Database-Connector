<?php
/**
 * Fired when the plugin is deleted (not on deactivate).
 *
 * @package CF7_Database_Connector
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
delete_option('cf7db_version');
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required to remove plugin tables on uninstall.
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7db_connections');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7db_mappings');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7db_logs');
// phpcs:enable
