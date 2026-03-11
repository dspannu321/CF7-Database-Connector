<?php
/**
 * Admin view: Logs page — recent sync attempts with optional payload detail.
 *
 * @package CF7_Database_Connector
 *
 * @var array{connections: int, mappings: int, logs_24h: int} $stats
 * @var array<int, array<string, mixed>> $logs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pretty-prints JSON payload for display. Returns safe string.
 *
 * @param string|null $cf7db_payload
 * @return string
 */
$cf7db_format_payload = function ($cf7db_payload): string {
    if ($cf7db_payload === null || $cf7db_payload === '') {
        return '—';
    }
    $cf7db_decoded = json_decode($cf7db_payload, true);
    if ($cf7db_decoded === null && $cf7db_payload !== 'null') {
        return esc_html($cf7db_payload);
    }
    $pretty = wp_json_encode($cf7db_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $pretty !== false ? $pretty : esc_html($cf7db_payload);
};
?>
<div class="wrap cf7db-wrap">
    <header class="cf7db-page-header">
        <h1 class="cf7db-page-title"><?php esc_html_e('Logs', 'cf7-database-connector'); ?></h1>
        <p class="cf7db-page-description"><?php esc_html_e('See the latest sync attempts and inspect payloads when needed.', 'cf7-database-connector'); ?></p>
        <ul class="cf7db-subnav">
            <li class="cf7db-subnav-item"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector')); ?>"><?php esc_html_e('Connections', 'cf7-database-connector'); ?></a></li>
            <li class="cf7db-subnav-item"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-mappings')); ?>"><?php esc_html_e('Mappings', 'cf7-database-connector'); ?></a></li>
            <li class="cf7db-subnav-item is-active"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-logs')); ?>"><?php esc_html_e('Logs', 'cf7-database-connector'); ?></a></li>
        </ul>
    </header>

    <?php if (!empty($stats)) : ?>
        <?php include CF7DB_PLUGIN_DIR . 'admin/views/stat-cards.php'; ?>
    <?php endif; ?>

    <?php if (empty($logs)) : ?>
        <section class="cf7db-card">
            <div class="cf7db-card-body">
                <div class="cf7db-empty-state">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p class="cf7db-empty-message"><?php esc_html_e('No log entries yet', 'cf7-database-connector'); ?></p>
                    <p class="description"><?php esc_html_e('Logs appear here after you submit a Contact Form 7 form that has an active mapping. Create a connection and a mapping first, then submit the form to see success, failed, or skipped entries.', 'cf7-database-connector'); ?></p>
                </div>
            </div>
        </section>
    <?php else : ?>
        <section class="cf7db-card cf7db-table-card">
            <h2 class="cf7db-card-header"><?php esc_html_e('Recent sync attempts', 'cf7-database-connector'); ?></h2>
            <div class="cf7db-card-body" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped cf7db-logs-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-date"><?php esc_html_e('Date / Time', 'cf7-database-connector'); ?></th>
                            <th scope="col" class="column-source"><?php esc_html_e('Source', 'cf7-database-connector'); ?></th>
                            <th scope="col" class="column-form-id"><?php esc_html_e('Form ID', 'cf7-database-connector'); ?></th>
                            <th scope="col" class="column-destination"><?php esc_html_e('Destination Table', 'cf7-database-connector'); ?></th>
                            <th scope="col" class="column-status"><?php esc_html_e('Status', 'cf7-database-connector'); ?></th>
                            <th scope="col" class="column-message"><?php esc_html_e('Message', 'cf7-database-connector'); ?></th>
                            <th scope="col" class="column-payload-toggle"><?php esc_html_e('Payload', 'cf7-database-connector'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $cf7db_log) : ?>
                            <?php
                            $cf7db_log_id    = (int) ($cf7db_log['id'] ?? 0);
                            $cf7db_created   = isset($cf7db_log['created_at']) ? (string) $cf7db_log['created_at'] : '—';
                            $cf7db_source    = isset($cf7db_log['source_type']) ? (string) $cf7db_log['source_type'] : '—';
                            $cf7db_form_id   = isset($cf7db_log['form_id']) ? (int) $cf7db_log['form_id'] : 0;
                            $cf7db_dest      = isset($cf7db_log['destination_table']) ? (string) $cf7db_log['destination_table'] : '—';
                            $cf7db_status    = isset($cf7db_log['status']) ? (string) $cf7db_log['status'] : '';
                            $cf7db_message   = isset($cf7db_log['message']) ? (string) $cf7db_log['message'] : '—';
                            $cf7db_payload   = $cf7db_log['payload'] ?? null;
                            $cf7db_has_payload = $cf7db_payload !== null && $cf7db_payload !== '';
                            ?>
                            <tr class="cf7db-log-row" data-log-id="<?php echo esc_attr((string) $cf7db_log_id); ?>">
                                <td class="column-date"><?php echo esc_html($cf7db_created); ?></td>
                                <td class="column-source"><?php echo esc_html($cf7db_source); ?></td>
                                <td class="column-form-id"><?php echo esc_html((string) $cf7db_form_id); ?></td>
                                <td class="column-destination"><?php echo esc_html($cf7db_dest); ?></td>
                                <td class="column-status"><span class="cf7db-status cf7db-status-<?php echo esc_attr($cf7db_status); ?>"><?php echo esc_html($cf7db_status); ?></span></td>
                                <td class="column-message"><?php echo esc_html($cf7db_message); ?></td>
                                <td class="column-payload-toggle">
                                    <?php if ($cf7db_has_payload) : ?>
                                        <button type="button" class="button button-small cf7db-toggle-payload" aria-expanded="false" data-log-id="<?php echo esc_attr((string) $cf7db_log_id); ?>"><?php esc_html_e('View payload', 'cf7-database-connector'); ?></button>
                                    <?php else : ?>
                                        <span aria-hidden="true">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($cf7db_has_payload) : ?>
                                <tr class="cf7db-log-payload cf7db-payload-row" id="cf7db-payload-<?php echo esc_attr((string) $cf7db_log_id); ?>" data-log-id="<?php echo esc_attr((string) $cf7db_log_id); ?>" hidden>
                                    <td colspan="7" class="cf7db-payload-cell">
                                        <pre class="cf7db-payload-pre"><?php echo esc_html($cf7db_format_payload($cf7db_payload)); ?></pre>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
