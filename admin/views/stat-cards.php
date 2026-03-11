<?php
/**
 * Stat cards block for FormBridge admin pages.
 *
 * @package FormBridge
 *
 * @var array{connections: int, mappings: int, logs_24h: int} $stats
 */

if (!defined('ABSPATH')) {
    exit;
}

$stat_connections = (int) ($stats['connections'] ?? 0);
$stat_mappings    = (int) ($stats['mappings'] ?? 0);
$stat_logs_24h   = (int) ($stats['logs_24h'] ?? 0);
?>
<div class="formbridge-stat-cards">
    <div class="formbridge-stat-card">
        <span class="formbridge-stat-icon dashicons dashicons-database"></span>
        <div class="formbridge-stat-content">
            <span class="formbridge-stat-value"><?php echo esc_html((string) $stat_connections); ?></span>
            <span class="formbridge-stat-label"><?php esc_html_e('Connections', 'formbridge'); ?></span>
        </div>
    </div>
    <div class="formbridge-stat-card">
        <span class="formbridge-stat-icon dashicons dashicons-editor-break"></span>
        <div class="formbridge-stat-content">
            <span class="formbridge-stat-value"><?php echo esc_html((string) $stat_mappings); ?></span>
            <span class="formbridge-stat-label"><?php esc_html_e('Mappings', 'formbridge'); ?></span>
        </div>
    </div>
    <div class="formbridge-stat-card">
        <span class="formbridge-stat-icon dashicons dashicons-clipboard"></span>
        <div class="formbridge-stat-content">
            <span class="formbridge-stat-value"><?php echo esc_html((string) $stat_logs_24h); ?></span>
            <span class="formbridge-stat-label"><?php esc_html_e('Submissions (24h)', 'formbridge'); ?></span>
        </div>
    </div>
</div>
