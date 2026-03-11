<?php
/**
 * Admin view: Mappings page — list existing, then add or edit mapping.
 *
 * @package CF7_Database_Connector
 *
 * @var array<int, array<string, mixed>> $connections
 * @var array<int, array{id: int, title: string}> $cf7_forms
 * @var array<int, array{id: int, form_id: int, connection_id: int, form_title: string, connection_name: string, destination_table: string}> $mappings_list
 * @var int $edit_mapping_id
 * @var int $form_id
 * @var int $connection_id
 * @var string $table
 * @var array<int, string> $tables
 * @var array<int, string> $columns
 * @var array<int, string> $cf7_fields
 * @var array<string, mixed>|null $existing_mapping
 * @var array<int, array{type: string, text: string}> $notices
 * @var array{connections: int, mappings: int, logs_24h: int} $stats
 * @var string $test_submission_nonce
 */

if (!defined('ABSPATH')) {
    exit;
}

$cf7db_existing_map = [];
if ($existing_mapping && !empty($existing_mapping['field_map'])) {
    $cf7db_decoded = json_decode((string) $existing_mapping['field_map'], true);
    $cf7db_existing_map = is_array($cf7db_decoded) ? $cf7db_decoded : [];
}
?>
<div class="wrap cf7db-wrap">
    <header class="cf7db-page-header">
        <h1 class="cf7db-page-title"><?php esc_html_e('Mappings', 'cf7-database-connector'); ?></h1>
        <p class="cf7db-page-description"><?php esc_html_e('Connect Contact Form 7 fields to database columns.', 'cf7-database-connector'); ?></p>
        <ul class="cf7db-subnav">
            <li class="cf7db-subnav-item"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector')); ?>"><?php esc_html_e('Connections', 'cf7-database-connector'); ?></a></li>
            <li class="cf7db-subnav-item is-active"><a class="cf7db-subnav-link" href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-mappings')); ?>"><?php esc_html_e('Mappings', 'cf7-database-connector'); ?></a></li>
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

    <?php if (count($cf7_forms) === 0) : ?>
        <section class="cf7db-card">
            <div class="cf7db-card-body">
                <div class="notice notice-warning inline">
                    <p><strong><?php esc_html_e('Contact Form 7 is not active or has no forms.', 'cf7-database-connector'); ?></strong></p>
                    <p><?php esc_html_e('Create a form in Contact Form 7 first, then return here to map its fields to your external database.', 'cf7-database-connector'); ?></p>
                </div>
            </div>
        </section>
    <?php elseif (count($connections) === 0) : ?>
        <section class="cf7db-card">
            <div class="cf7db-card-body">
                <div class="notice notice-warning inline">
                    <p><strong><?php esc_html_e('No connections yet.', 'cf7-database-connector'); ?></strong></p>
                    <p><?php esc_html_e('Add at least one connection under CF7 Database Connector → Connections, then return here to create a mapping.', 'cf7-database-connector'); ?></p>
                </div>
            </div>
        </section>
    <?php else : ?>
        <?php if ($edit_mapping_id > 0 && $table !== '' && count($columns) > 0 && count($cf7_fields) > 0) : ?>
            <section class="cf7db-card">
                <h2 class="cf7db-card-header">
                    <?php esc_html_e('Edit mapping', 'cf7-database-connector'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-mappings')); ?>" class="button button-small"><?php esc_html_e('Cancel', 'cf7-database-connector'); ?></a>
                </h2>
                <div class="cf7db-card-body">
                    <p class="description" style="margin: 0 0 16px 0;"><?php esc_html_e('Form, connection, and table are fixed when editing. Change the field mapping below and save.', 'cf7-database-connector'); ?></p>
                    <form method="post" action="">
                        <input type="hidden" name="cf7db_action" value="save_mapping" />
                        <?php wp_nonce_field('cf7db_save_mapping', '_wpnonce'); ?>
                        <input type="hidden" name="form_id" value="<?php echo esc_attr((string) $form_id); ?>" />
                        <input type="hidden" name="connection_id" value="<?php echo esc_attr((string) $connection_id); ?>" />
                        <input type="hidden" name="destination_table" value="<?php echo esc_attr($table); ?>" />

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e('CF7 field', 'cf7-database-connector'); ?></th>
                                    <th scope="col"><?php esc_html_e('Database column', 'cf7-database-connector'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cf7_fields as $cf7db_field_name) : ?>
                                    <tr>
                                        <td><code><?php echo esc_html($cf7db_field_name); ?></code></td>
                                        <td>
                                            <select name="field_map_<?php echo esc_attr($cf7db_field_name); ?>">
                                                <option value=""><?php esc_html_e('— Do not map —', 'cf7-database-connector'); ?></option>
                                                <?php foreach ($columns as $cf7db_col) : ?>
                                                    <option value="<?php echo esc_attr($cf7db_col); ?>" <?php selected(isset($cf7db_existing_map[ $cf7db_field_name ]) ? $cf7db_existing_map[ $cf7db_field_name ] : '', $cf7db_col); ?>><?php echo esc_html($cf7db_col); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="cf7db-submit-row">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Update mapping', 'cf7-database-connector'); ?></button>
                            <?php if ($test_submission_nonce !== '') : ?>
                                <form method="post" action="" class="cf7db-inline-form" style="display: inline;">
                                    <input type="hidden" name="cf7db_action" value="test_submission" />
                                    <input type="hidden" name="mapping_id" value="<?php echo esc_attr((string) $edit_mapping_id); ?>" />
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($test_submission_nonce); ?>" />
                                    <button type="submit" class="button"><?php esc_html_e('Send test data', 'cf7-database-connector'); ?></button>
                                </form>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-mappings')); ?>" class="button"><?php esc_html_e('Cancel', 'cf7-database-connector'); ?></a>
                        </p>
                    </form>
                </div>
            </section>
        <?php else : ?>
            <?php if ($edit_mapping_id > 0 && (count($tables) === 0 || count($cf7_fields) === 0)) : ?>
                <section class="cf7db-card">
                    <div class="cf7db-card-body">
                        <div class="notice notice-warning inline">
                            <p><?php esc_html_e('This mapping’s connection or form may have changed. You can still add a new mapping below.', 'cf7-database-connector'); ?></p>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="cf7db-card">
                <h2 class="cf7db-card-header">
                    <?php esc_html_e('Add new mapping', 'cf7-database-connector'); ?>
                    <?php if (count($mappings_list) > 0) : ?>
                        <span class="cf7db-step-label"><?php esc_html_e('Optional', 'cf7-database-connector'); ?></span>
                    <?php endif; ?>
                </h2>
                <div class="cf7db-card-body">
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                        <input type="hidden" name="page" value="cf7-database-connector-mappings" />
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="cf7db_form_id"><?php esc_html_e('Contact Form 7 form', 'cf7-database-connector'); ?></label></th>
                                <td>
                                    <select name="form_id" id="cf7db_form_id" required>
                                        <option value=""><?php esc_html_e('— Select a form —', 'cf7-database-connector'); ?></option>
                                        <?php foreach ($cf7_forms as $cf7db_f) : ?>
                                            <option value="<?php echo esc_attr((string) $cf7db_f['id']); ?>" <?php selected($form_id, $cf7db_f['id']); ?>><?php echo esc_html($cf7db_f['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cf7db_connection_id"><?php esc_html_e('Connection', 'cf7-database-connector'); ?></label></th>
                                <td>
                                    <select name="connection_id" id="cf7db_connection_id" required>
                                        <option value=""><?php esc_html_e('— Select a connection —', 'cf7-database-connector'); ?></option>
                                        <?php foreach ($connections as $cf7db_c) : ?>
                                            <option value="<?php echo esc_attr((string) $cf7db_c['id']); ?>" <?php selected($connection_id, (int) $cf7db_c['id']); ?>><?php echo esc_html((string) $cf7db_c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php if (count($tables) > 0 && $edit_mapping_id === 0) : ?>
                                <tr>
                                    <th scope="row"><label for="cf7db_table"><?php esc_html_e('Destination table', 'cf7-database-connector'); ?></label></th>
                                    <td class="cf7db-select-row">
                                        <select name="table" id="cf7db_table">
                                            <option value=""><?php esc_html_e('— Select a table —', 'cf7-database-connector'); ?></option>
                                            <?php foreach ($tables as $cf7db_t) : ?>
                                                <option value="<?php echo esc_attr($cf7db_t); ?>" <?php selected($table, $cf7db_t); ?>><?php echo esc_html($cf7db_t); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Load mapping', 'cf7-database-connector'); ?>" />
                                    </td>
                                </tr>
                            <?php elseif ($edit_mapping_id === 0) : ?>
                                <tr>
                                    <td colspan="2">
                                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Load tables', 'cf7-database-connector'); ?>" />
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </form>
                    <?php if ($form_id === 0 && $connection_id === 0) : ?>
                        <p class="description" style="margin-top: 12px;"><?php esc_html_e('Choose a form and a connection, then click "Load tables" to select the destination table and map fields.', 'cf7-database-connector'); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($edit_mapping_id === 0 && $table !== '' && count($columns) > 0 && count($cf7_fields) > 0) : ?>
                <section class="cf7db-card cf7db-field-map-card">
                    <h2 class="cf7db-card-header"><?php esc_html_e('Field mapping', 'cf7-database-connector'); ?></h2>
                    <div class="cf7db-card-body">
                        <p class="description" style="margin: 0 0 16px 0;"><?php esc_html_e('Map each form field to a database column, or leave as "Do not map". At least one field must be mapped to save.', 'cf7-database-connector'); ?></p>
                        <form method="post" action="">
                            <input type="hidden" name="cf7db_action" value="save_mapping" />
                            <?php wp_nonce_field('cf7db_save_mapping', '_wpnonce'); ?>
                            <input type="hidden" name="form_id" value="<?php echo esc_attr((string) $form_id); ?>" />
                            <input type="hidden" name="connection_id" value="<?php echo esc_attr((string) $connection_id); ?>" />
                            <input type="hidden" name="destination_table" value="<?php echo esc_attr($table); ?>" />

                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('CF7 field', 'cf7-database-connector'); ?></th>
                                        <th scope="col"><?php esc_html_e('Database column', 'cf7-database-connector'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cf7_fields as $cf7db_field_name) : ?>
                                        <tr>
                                            <td><code><?php echo esc_html($cf7db_field_name); ?></code></td>
                                            <td>
                                                <select name="field_map_<?php echo esc_attr($cf7db_field_name); ?>">
                                                    <option value=""><?php esc_html_e('— Do not map —', 'cf7-database-connector'); ?></option>
                                                    <?php foreach ($columns as $cf7db_col) : ?>
                                                        <option value="<?php echo esc_attr($cf7db_col); ?>" <?php selected(isset($cf7db_existing_map[ $cf7db_field_name ]) ? $cf7db_existing_map[ $cf7db_field_name ] : '', $cf7db_col); ?>><?php echo esc_html($cf7db_col); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p class="cf7db-submit-row">
                                <button type="submit" class="button button-primary"><?php esc_html_e('Save mapping', 'cf7-database-connector'); ?></button>
                            </p>
                        </form>
                    </div>
                </section>
            <?php elseif ($edit_mapping_id === 0 && $connection_id > 0 && count($tables) === 0) : ?>
                <section class="cf7db-card">
                    <div class="cf7db-card-body">
                        <p class="description"><?php esc_html_e('No tables found for this connection. Check the database and try again.', 'cf7-database-connector'); ?></p>
                    </div>
                </section>
            <?php elseif ($edit_mapping_id === 0 && $form_id > 0 && count($cf7_forms) > 0 && count($cf7_fields) === 0) : ?>
                <section class="cf7db-card">
                    <div class="cf7db-card-body">
                        <p class="description"><?php esc_html_e('No fields found in the selected form. Add form tags to the CF7 form template.', 'cf7-database-connector'); ?></p>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>

        <section class="cf7db-card cf7db-table-card">
            <h2 class="cf7db-card-header"><?php esc_html_e('Existing mappings', 'cf7-database-connector'); ?></h2>
            <div class="cf7db-card-body cf7db-table-body">
                <?php if (count($mappings_list) > 0) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Form', 'cf7-database-connector'); ?></th>
                                <th scope="col"><?php esc_html_e('Connection', 'cf7-database-connector'); ?></th>
                                <th scope="col"><?php esc_html_e('Destination table', 'cf7-database-connector'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'cf7-database-connector'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mappings_list as $m) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($m['form_title']); ?></strong></td>
                                    <td><?php echo esc_html($m['connection_name']); ?></td>
                                    <td><code><?php echo esc_html($m['destination_table']); ?></code></td>
                                    <td class="cf7db-actions-cell">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-database-connector-mappings&edit_mapping=' . $m['id'])); ?>" class="button button-small button-primary"><?php esc_html_e('Edit', 'cf7-database-connector'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="cf7db-empty-state">
                        <span class="dashicons dashicons-editor-break"></span>
                        <p class="cf7db-empty-message"><?php esc_html_e('No mappings yet', 'cf7-database-connector'); ?></p>
                        <p class="description"><?php esc_html_e('Add a new mapping above. Choose a form and connection, then map fields to your database table.', 'cf7-database-connector'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
