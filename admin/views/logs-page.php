<?php
/**
 * Admin view: Logs page — recent sync attempts with optional payload detail.
 *
 * @package FormBridge
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
 * @param string|null $payload
 * @return string
 */
$format_payload = function ($payload): string {
    if ($payload === null || $payload === '') {
        return '—';
    }
    $decoded = json_decode($payload, true);
    if ($decoded === null && $payload !== 'null') {
        return esc_html($payload);
    }
    $pretty = wp_json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $pretty !== false ? $pretty : esc_html($payload);
};
?>
<div class="wrap formbridge-wrap">
    <header class="formbridge-page-header">
        <h1 class="formbridge-page-title"><?php esc_html_e('Logs', 'formbridge'); ?></h1>
        <p class="formbridge-page-description"><?php esc_html_e('Recent form sync attempts. Newest first. Use "View payload" to inspect the data sent for each attempt.', 'formbridge'); ?></p>
    <?php formbridge_admin_logo(); ?>
    </header>

    <?php if (!empty($stats)) : ?>
        <?php include FORMBRIDGE_PLUGIN_DIR . 'admin/views/stat-cards.php'; ?>
    <?php endif; ?>

    <?php if (empty($logs)) : ?>
        <section class="formbridge-card">
            <div class="formbridge-card-body">
                <div class="formbridge-empty-state">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p class="formbridge-empty-message"><?php esc_html_e('No log entries yet', 'formbridge'); ?></p>
                    <p class="description"><?php esc_html_e('Logs appear here after you submit a Contact Form 7 form that has an active mapping. Create a connection and a mapping first, then submit the form to see success, failed, or skipped entries.', 'formbridge'); ?></p>
                </div>
            </div>
        </section>
    <?php else : ?>
        <section class="formbridge-card formbridge-table-card">
            <h2 class="formbridge-card-header"><?php esc_html_e('Recent sync attempts', 'formbridge'); ?></h2>
            <div class="formbridge-card-body" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped formbridge-logs-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-date"><?php esc_html_e('Date / Time', 'formbridge'); ?></th>
                            <th scope="col" class="column-source"><?php esc_html_e('Source', 'formbridge'); ?></th>
                            <th scope="col" class="column-form-id"><?php esc_html_e('Form ID', 'formbridge'); ?></th>
                            <th scope="col" class="column-destination"><?php esc_html_e('Destination Table', 'formbridge'); ?></th>
                            <th scope="col" class="column-status"><?php esc_html_e('Status', 'formbridge'); ?></th>
                            <th scope="col" class="column-message"><?php esc_html_e('Message', 'formbridge'); ?></th>
                            <th scope="col" class="column-payload-toggle"><?php esc_html_e('Payload', 'formbridge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <?php
                            $log_id    = (int) ($log['id'] ?? 0);
                            $created   = isset($log['created_at']) ? esc_html((string) $log['created_at']) : '—';
                            $source    = isset($log['source_type']) ? esc_html((string) $log['source_type']) : '—';
                            $form_id   = isset($log['form_id']) ? (int) $log['form_id'] : 0;
                            $dest      = isset($log['destination_table']) ? esc_html((string) $log['destination_table']) : '—';
                            $status    = isset($log['status']) ? (string) $log['status'] : '';
                            $message   = isset($log['message']) ? esc_html((string) $log['message']) : '—';
                            $payload   = $log['payload'] ?? null;
                            $has_payload = $payload !== null && $payload !== '';
                            ?>
                            <tr class="formbridge-log-row" data-log-id="<?php echo esc_attr((string) $log_id); ?>">
                                <td class="column-date"><?php echo $created; ?></td>
                                <td class="column-source"><?php echo $source; ?></td>
                                <td class="column-form-id"><?php echo esc_html((string) $form_id); ?></td>
                                <td class="column-destination"><?php echo $dest; ?></td>
                                <td class="column-status"><span class="formbridge-status formbridge-status-<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></span></td>
                                <td class="column-message"><?php echo $message; ?></td>
                                <td class="column-payload-toggle">
                                    <?php if ($has_payload) : ?>
                                        <button type="button" class="button button-small formbridge-toggle-payload" aria-expanded="false" data-log-id="<?php echo esc_attr((string) $log_id); ?>"><?php esc_html_e('View payload', 'formbridge'); ?></button>
                                    <?php else : ?>
                                        <span aria-hidden="true">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($has_payload) : ?>
                                <tr class="formbridge-log-payload formbridge-payload-row" id="formbridge-payload-<?php echo esc_attr((string) $log_id); ?>" data-log-id="<?php echo esc_attr((string) $log_id); ?>" hidden>
                                    <td colspan="7" class="formbridge-payload-cell">
                                        <pre class="formbridge-payload-pre"><?php echo esc_html($format_payload($payload)); ?></pre>
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
