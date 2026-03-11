<?php
/**
 * Admin view: Connections page — list, add/edit form, delete, test.
 *
 * @package FormBridge
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
<div class="wrap formbridge-wrap">
    <header class="formbridge-page-header">
        <h1 class="formbridge-page-title"><?php esc_html_e('Connections', 'formbridge'); ?></h1>
        <p class="formbridge-page-description"><?php esc_html_e('Manage external MySQL database connections. Add a connection, then use it when creating mappings.', 'formbridge'); ?></p>
    <?php formbridge_admin_logo(); ?>

    </header>

    <?php if (!empty($stats)) : ?>
        <?php include FORMBRIDGE_PLUGIN_DIR . 'admin/views/stat-cards.php'; ?>
    <?php endif; ?>

    <?php foreach ($notices as $notice) : ?>
        <div class="formbridge-notice formbridge-notice-<?php echo esc_attr($notice['type']); ?>">
            <p><?php echo esc_html($notice['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <div id="formbridge-test-result" class="formbridge-test-result" aria-live="polite" hidden></div>

    <?php if (count($connections) > 0) : ?>
        <section class="formbridge-card formbridge-table-card">
            <h2 class="formbridge-card-header"><?php esc_html_e('Your connections', 'formbridge'); ?></h2>
            <div class="formbridge-card-body" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Name', 'formbridge'); ?></th>
                            <th scope="col"><?php esc_html_e('Host', 'formbridge'); ?></th>
                            <th scope="col"><?php esc_html_e('Database', 'formbridge'); ?></th>
                            <th scope="col"><?php esc_html_e('Actions', 'formbridge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $conn) : ?>
                            <tr>
                                <td><strong><?php echo esc_html((string) $conn['name']); ?></strong></td>
                                <td><code><?php echo esc_html((string) $conn['db_host']); ?>:<?php echo esc_html((string) $conn['db_port']); ?></code></td>
                                <td><?php echo esc_html((string) $conn['db_name']); ?></td>
                                <td class="formbridge-actions-cell">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=formbridge&edit=' . (int) $conn['id'])); ?>" class="button button-small"><?php esc_html_e('Edit', 'formbridge'); ?></a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=formbridge&formbridge_action=test_connection&id=' . (int) $conn['id'] . '&_wpnonce=' . wp_create_nonce('formbridge_test_connection_' . (int) $conn['id']))); ?>" class="button button-small"><?php esc_html_e('Test', 'formbridge'); ?></a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=formbridge&formbridge_action=delete_connection&id=' . (int) $conn['id'] . '&_wpnonce=' . wp_create_nonce('formbridge_delete_connection_' . (int) $conn['id']))); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js(__('Delete this connection?', 'formbridge')); ?>');"><?php esc_html_e('Delete', 'formbridge'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else : ?>
        <section class="formbridge-card">
            <div class="formbridge-card-body">
                <div class="formbridge-empty-state">
                    <span class="dashicons dashicons-database-add"></span>
                    <p class="formbridge-empty-message"><?php esc_html_e('No connections yet', 'formbridge'); ?></p>
                    <p class="description"><?php esc_html_e('Add your first connection below to connect CF7 Database Connector to an external MySQL database. You can then map Contact Form 7 forms to tables on that database.', 'formbridge'); ?></p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="formbridge-card">
        <h2 class="formbridge-card-header"><?php echo $edit_id > 0 ? esc_html__('Edit connection', 'formbridge') : esc_html__('Add new connection', 'formbridge'); ?></h2>
        <div class="formbridge-card-body">
            <form method="post" action="" id="formbridge-connection-form">
                <input type="hidden" name="formbridge_action" value="save_connection" />
                <?php wp_nonce_field('formbridge_connection', '_wpnonce'); ?>
                <?php if ($edit_id > 0) : ?>
                    <input type="hidden" name="connection_id" value="<?php echo esc_attr((string) $edit_id); ?>" id="formbridge-connection-id" />
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="connection_name"><?php esc_html_e('Connection name', 'formbridge'); ?></label></th>
                        <td><input name="connection_name" id="connection_name" type="text" class="regular-text" value="<?php echo $edit ? esc_attr((string) $edit['name']) : ''; ?>" required placeholder="<?php esc_attr_e('e.g. Production DB', 'formbridge'); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="db_host"><?php esc_html_e('Host', 'formbridge'); ?></label></th>
                        <td><input name="db_host" id="db_host" type="text" class="regular-text" value="<?php echo $edit ? esc_attr((string) $edit['db_host']) : ''; ?>" required placeholder="<?php esc_attr_e('e.g. localhost or 192.168.1.10', 'formbridge'); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="db_port"><?php esc_html_e('Port', 'formbridge'); ?></label></th>
                        <td><input name="db_port" id="db_port" type="number" value="<?php echo $edit ? esc_attr((string) $edit['db_port']) : '3306'; ?>" min="1" max="65535" style="width: 100px;" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="db_name"><?php esc_html_e('Database name', 'formbridge'); ?></label></th>
                        <td><input name="db_name" id="db_name" type="text" class="regular-text" value="<?php echo $edit ? esc_attr((string) $edit['db_name']) : ''; ?>" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="db_user"><?php esc_html_e('Username', 'formbridge'); ?></label></th>
                        <td><input name="db_user" id="db_user" type="text" class="regular-text" value="<?php echo $edit ? esc_attr((string) $edit['db_user']) : ''; ?>" required autocomplete="off" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="db_pass"><?php esc_html_e('Password', 'formbridge'); ?></label></th>
                        <td>
                            <input name="db_pass" id="db_pass" type="password" class="regular-text" value="" autocomplete="new-password" />
                            <?php if ($edit_id > 0) : ?>
                                <p class="description" style="margin-top: 6px;"><?php esc_html_e('Leave blank to keep the current password.', 'formbridge'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <p class="formbridge-submit-row">
                    <button type="button" id="formbridge-test-connection-btn" class="button"><?php esc_html_e('Test connection', 'formbridge'); ?></button>
                    <button type="submit" class="button button-primary"><?php echo $edit_id > 0 ? esc_html__('Save connection', 'formbridge') : esc_html__('Add connection', 'formbridge'); ?></button>
                    <?php if ($edit_id > 0) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=formbridge')); ?>" class="button"><?php esc_html_e('Cancel', 'formbridge'); ?></a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
    </section>
</div>
