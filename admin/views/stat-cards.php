<?php
/**
 * Stat cards block for CF7 Database Connector admin pages.
 *
 * @package CF7_Database_Connector
 *
 * @var array{connections: int, mappings: int, logs_24h: int} $stats
 */

if (!defined('ABSPATH')) {
    exit;
}

$cf7db_stat_connections = (int) ($stats['connections'] ?? 0);
$cf7db_stat_mappings    = (int) ($stats['mappings'] ?? 0);
$cf7db_stat_logs_24h   = (int) ($stats['logs_24h'] ?? 0);
?>
<div class="cf7db-stat-cards">
    <div class="cf7db-stat-card">
        <span class="cf7db-stat-icon dashicons dashicons-database"></span>
        <div class="cf7db-stat-content">
            <span class="cf7db-stat-value"><?php echo esc_html((string) $cf7db_stat_connections); ?></span>
            <span class="cf7db-stat-label"><?php esc_html_e('Connections', 'cf7-database-connector'); ?></span>
        </div>
    </div>
    <div class="cf7db-stat-card">
        <span class="cf7db-stat-icon dashicons dashicons-editor-break"></span>
        <div class="cf7db-stat-content">
            <span class="cf7db-stat-value"><?php echo esc_html((string) $cf7db_stat_mappings); ?></span>
            <span class="cf7db-stat-label"><?php esc_html_e('Mappings', 'cf7-database-connector'); ?></span>
        </div>
    </div>
    <div class="cf7db-stat-card">
        <span class="cf7db-stat-icon dashicons dashicons-clipboard"></span>
        <div class="cf7db-stat-content">
            <span class="cf7db-stat-value"><?php echo esc_html((string) $cf7db_stat_logs_24h); ?></span>
            <span class="cf7db-stat-label"><?php esc_html_e('Submissions (24h)', 'cf7-database-connector'); ?></span>
        </div>
    </div>
</div>
