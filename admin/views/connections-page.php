<?php
/**
 * Admin view: Connections page — add/edit form first, then list.
 *
 * @package CF7_Database_Connector
 *
 * @var array{connections: int, mappings: int, logs_24h: int} $stats
 * @var array<int, array<string, mixed>> $connections
 * @var int                              $edit_id
 * @var array<string, mixed>|null       $edit
 * @var array<int, array{type: string, text: string}> $notices
 * @var string $ajax_url
 * @var string $test_nonce
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap cf7db-wrap">
    <header class="cf7db-page-header">
        <h1 class="cf7db-page-title"><?php esc_html_e('Connections', 'cf7-database-connector'); ?></h1>
        <p class="cf7db-page-description"><?php esc_html_e('Securely connect WordPress to your external MySQL database. Test connections before using them in mappings.', 'cf7-database-connector'); ?></p>
        <ul class="cf7db-subnav">
            <li class="cf7db-subnav-item is-active"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector')); ?>"><?php esc_html_e('Connections', 'cf7-database-connector'); ?></a></li>
            <li class="cf7db-subnav-item"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-mappings')); ?>"><?php esc_html_e('Mappings', 'cf7-database-connector'); ?></a></li>
            <li class="cf7db-subnav-item"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-logs')); ?>"><?php esc_html_e('Logs', 'cf7-database-connector'); ?></a></li>
        </ul>
    </header>

    <?php if (!empty($stats)) : ?>
        <?php include CF7DB_PLUGIN_DIR . 'admin/views/stat-cards.php'; ?>
    <?php endif; ?>

    <?php foreach ($notices as $cf7db_notice) : ?>
        <div class="cf7db-notice cf7db-notice-<?php echo esc_attr($cf7db_notice['type']); ?>">
            <p><?php echo esc_html($cf7db_notice['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <div id="cf7db-test-result" class="cf7db-test-result" aria-live="polite" hidden></div>

    <section class="cf7db-card cf7db-form-card">
        <h2 class="cf7db-card-header"><?php echo $edit_id > 0 ? esc_html__('Edit connection', 'cf7-database-connector') : esc_html__('Add new connection', 'cf7-database-connector'); ?></h2>
        <div class="cf7db-card-body cf7db-form-with-sidebar">
            <div class="cf7db-form-main">
                <form method="post" action="" id="cf7db-connection-form">
                    <input type="hidden" name="cf7db_action" value="save_connection" />
                    <?php wp_nonce_field('cf7db_connection', '_wpnonce'); ?>
                    <?php if ($edit_id > 0) : ?>
                        <input type="hidden" name="connection_id" value="<?php echo esc_attr((string) $edit_id); ?>" id="cf7db-connection-id" />
                    <?php endif; ?>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="connection_name"><?php esc_html_e('Connection name', 'cf7-database-connector'); ?></label></th>
                            <td><input name="connection_name" id="connection_name" type="text" class="cf7db-input" value="<?php echo $edit ? esc_attr((string) $edit['name']) : ''; ?>" required placeholder="<?php esc_attr_e('e.g. Production DB', 'cf7-database-connector'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="db_host"><?php esc_html_e('Host', 'cf7-database-connector'); ?></label></th>
                            <td><input name="db_host" id="db_host" type="text" class="cf7db-input" value="<?php echo $edit ? esc_attr((string) $edit['db_host']) : ''; ?>" required placeholder="<?php esc_attr_e('e.g. localhost or 192.168.1.10', 'cf7-database-connector'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="db_port"><?php esc_html_e('Port', 'cf7-database-connector'); ?></label></th>
                            <td><input name="db_port" id="db_port" type="number" class="cf7db-input cf7db-input-port" value="<?php echo $edit ? esc_attr((string) $edit['db_port']) : '3306'; ?>" min="1" max="65535" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="db_name"><?php esc_html_e('Database name', 'cf7-database-connector'); ?></label></th>
                            <td><input name="db_name" id="db_name" type="text" class="cf7db-input" value="<?php echo $edit ? esc_attr((string) $edit['db_name']) : ''; ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="db_user"><?php esc_html_e('Username', 'cf7-database-connector'); ?></label></th>
                            <td><input name="db_user" id="db_user" type="text" class="cf7db-input" value="<?php echo $edit ? esc_attr((string) $edit['db_user']) : ''; ?>" required autocomplete="off" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="db_pass"><?php esc_html_e('Password', 'cf7-database-connector'); ?></label></th>
                            <td>
                                <input name="db_pass" id="db_pass" type="password" class="cf7db-input" value="" autocomplete="new-password" />
                                <?php if ($edit_id > 0) : ?>
                                    <p class="description cf7db-field-help"><?php esc_html_e('Leave blank to keep the current password.', 'cf7-database-connector'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <p class="cf7db-submit-row">
                        <button type="button" id="cf7db-test-connection-btn" class="button"><?php esc_html_e('Test connection', 'cf7-database-connector'); ?></button>
                        <button type="submit" class="button button-primary"><?php echo $edit_id > 0 ? esc_html__('Save connection', 'cf7-database-connector') : esc_html__('Add connection', 'cf7-database-connector'); ?></button>
                        <?php if ($edit_id > 0) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector')); ?>" class="button"><?php esc_html_e('Cancel', 'cf7-database-connector'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            <aside class="cf7db-form-sidebar">
                <div class="cf7db-tips-panel">
                    <h3 class="cf7db-tips-title"><?php esc_html_e('Tips', 'cf7-database-connector'); ?></h3>
                    <ul class="cf7db-tips-list">
                        <li><?php esc_html_e('Use "Test connection" before saving to verify credentials.', 'cf7-database-connector'); ?></li>
                        <li><?php esc_html_e('Credentials are stored securely and never shown in logs.', 'cf7-database-connector'); ?></li>
                        <li><?php esc_html_e('Default MySQL port is 3306.', 'cf7-database-connector'); ?></li>
                        <li><?php esc_html_e('After adding a connection, go to Mappings to link a CF7 form to a table.', 'cf7-database-connector'); ?></li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    <?php if (count($connections) > 0) : ?>
        <section class="cf7db-card cf7db-table-card">
            <h2 class="cf7db-card-header"><?php esc_html_e('Your connections', 'cf7-database-connector'); ?></h2>
            <div class="cf7db-card-body cf7db-table-body">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Name', 'cf7-database-connector'); ?></th>
                            <th scope="col"><?php esc_html_e('Host', 'cf7-database-connector'); ?></th>
                            <th scope="col"><?php esc_html_e('Database', 'cf7-database-connector'); ?></th>
                            <th scope="col"><?php esc_html_e('Actions', 'cf7-database-connector'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $cf7db_conn) : ?>
                            <tr>
                                <td><strong><?php echo esc_html((string) $cf7db_conn['name']); ?></strong></td>
                                <td><code><?php echo esc_html((string) $cf7db_conn['db_host']); ?>:<?php echo esc_html((string) $cf7db_conn['db_port']); ?></code></td>
                                <td><?php echo esc_html((string) $cf7db_conn['db_name']); ?></td>
                                <td class="cf7db-actions-cell">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector&edit=' . (int) $cf7db_conn['id'])); ?>" class="button button-small"><?php esc_html_e('Edit', 'cf7-database-connector'); ?></a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector&cf7db_action=test_connection&id=' . (int) $cf7db_conn['id'] . '&_wpnonce=' . wp_create_nonce('cf7db_test_connection_' . (int) $cf7db_conn['id']))); ?>" class="button button-small"><?php esc_html_e('Test', 'cf7-database-connector'); ?></a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector&cf7db_action=delete_connection&id=' . (int) $cf7db_conn['id'] . '&_wpnonce=' . wp_create_nonce('cf7db_delete_connection_' . (int) $cf7db_conn['id']))); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js(__('Delete this connection?', 'cf7-database-connector')); ?>');"><?php esc_html_e('Delete', 'cf7-database-connector'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else : ?>
        <section class="cf7db-card">
            <h2 class="cf7db-card-header"><?php esc_html_e('Your connections', 'cf7-database-connector'); ?></h2>
            <div class="cf7db-card-body">
                <div class="cf7db-empty-state">
                    <span class="dashicons dashicons-database-add"></span>
                    <p class="cf7db-empty-message"><?php esc_html_e('No connections yet', 'cf7-database-connector'); ?></p>
                    <p class="description"><?php esc_html_e('Add your first connection above. You can then map Contact Form 7 forms to tables on that database.', 'cf7-database-connector'); ?></p>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>
