<?php
/**
 * Admin view: Mappings page — list existing, then add or edit mapping.
 *
 * @package FormBridge
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

$existing_map = [];
if ($existing_mapping && !empty($existing_mapping['field_map'])) {
    $decoded = json_decode((string) $existing_mapping['field_map'], true);
    $existing_map = is_array($decoded) ? $decoded : [];
}
?>
<div class="wrap formbridge-wrap">
    <header class="formbridge-page-header">
        <h1 class="formbridge-page-title"><?php esc_html_e('Mappings', 'formbridge'); ?></h1>
        <p class="formbridge-page-description"><?php esc_html_e('Map Contact Form 7 form fields to columns in an external database table. Edit an existing mapping or add a new one.', 'formbridge'); ?></p>
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

    <?php if (count($cf7_forms) === 0) : ?>
        <section class="formbridge-card">
            <div class="formbridge-card-body">
                <div class="notice notice-warning inline">
                    <p><strong><?php esc_html_e('Contact Form 7 is not active or has no forms.', 'formbridge'); ?></strong></p>
                    <p><?php esc_html_e('Create a form in Contact Form 7 first, then return here to map its fields to your external database.', 'formbridge'); ?></p>
                </div>
            </div>
        </section>
    <?php elseif (count($connections) === 0) : ?>
        <section class="formbridge-card">
            <div class="formbridge-card-body">
                <div class="notice notice-warning inline">
                    <p><strong><?php esc_html_e('No connections yet.', 'formbridge'); ?></strong></p>
                    <p><?php esc_html_e('Add at least one connection under CF7 Database Connector → Connections, then return here to create a mapping.', 'formbridge'); ?></p>
                </div>
            </div>
        </section>
    <?php else : ?>
        <section class="formbridge-card formbridge-table-card">
            <h2 class="formbridge-card-header"><?php esc_html_e('Existing mappings', 'formbridge'); ?></h2>
            <div class="formbridge-card-body" style="padding: 0;">
                <?php if (count($mappings_list) > 0) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Form', 'formbridge'); ?></th>
                                <th scope="col"><?php esc_html_e('Connection', 'formbridge'); ?></th>
                                <th scope="col"><?php esc_html_e('Destination table', 'formbridge'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'formbridge'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mappings_list as $m) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($m['form_title']); ?></strong></td>
                                    <td><?php echo esc_html($m['connection_name']); ?></td>
                                    <td><code><?php echo esc_html($m['destination_table']); ?></code></td>
                                    <td class="formbridge-actions-cell">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=formbridge-mappings&edit_mapping=' . $m['id'])); ?>" class="button button-small button-primary"><?php esc_html_e('Edit', 'formbridge'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="formbridge-card-body">
                        <div class="formbridge-empty-state">
                            <span class="dashicons dashicons-editor-break"></span>
                            <p class="formbridge-empty-message"><?php esc_html_e('No mappings yet', 'formbridge'); ?></p>
                            <p class="description"><?php esc_html_e('Add a new mapping below. Choose a form and connection, then map fields to your database table.', 'formbridge'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($edit_mapping_id > 0 && $table !== '' && count($columns) > 0 && count($cf7_fields) > 0) : ?>
            <section class="formbridge-card">
                <h2 class="formbridge-card-header">
                    <?php esc_html_e('Edit mapping', 'formbridge'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=formbridge-mappings')); ?>" class="button button-small"><?php esc_html_e('Cancel', 'formbridge'); ?></a>
                </h2>
                <div class="formbridge-card-body">
                    <p class="description" style="margin: 0 0 16px 0;"><?php esc_html_e('Form, connection, and table are fixed when editing. Change the field mapping below and save.', 'formbridge'); ?></p>
                    <form method="post" action="">
                        <input type="hidden" name="formbridge_action" value="save_mapping" />
                        <?php wp_nonce_field('formbridge_save_mapping', '_wpnonce'); ?>
                        <input type="hidden" name="form_id" value="<?php echo esc_attr((string) $form_id); ?>" />
                        <input type="hidden" name="connection_id" value="<?php echo esc_attr((string) $connection_id); ?>" />
                        <input type="hidden" name="destination_table" value="<?php echo esc_attr($table); ?>" />

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e('CF7 field', 'formbridge'); ?></th>
                                    <th scope="col"><?php esc_html_e('Database column', 'formbridge'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cf7_fields as $field_name) : ?>
                                    <tr>
                                        <td><code><?php echo esc_html($field_name); ?></code></td>
                                        <td>
                                            <select name="field_map_<?php echo esc_attr($field_name); ?>">
                                                <option value=""><?php esc_html_e('— Do not map —', 'formbridge'); ?></option>
                                                <?php foreach ($columns as $col) : ?>
                                                    <option value="<?php echo esc_attr($col); ?>" <?php selected(isset($existing_map[ $field_name ]) ? $existing_map[ $field_name ] : '', $col); ?>><?php echo esc_html($col); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="formbridge-submit-row">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Update mapping', 'formbridge'); ?></button>
                            <?php if ($test_submission_nonce !== '') : ?>
                                <form method="post" action="" class="formbridge-inline-form" style="display: inline;">
                                    <input type="hidden" name="formbridge_action" value="test_submission" />
                                    <input type="hidden" name="mapping_id" value="<?php echo esc_attr((string) $edit_mapping_id); ?>" />
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($test_submission_nonce); ?>" />
                                    <button type="submit" class="button"><?php esc_html_e('Send test data', 'formbridge'); ?></button>
                                </form>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=formbridge-mappings')); ?>" class="button"><?php esc_html_e('Cancel', 'formbridge'); ?></a>
                        </p>
                    </form>
                </div>
            </section>
        <?php else : ?>
            <?php if ($edit_mapping_id > 0 && (count($tables) === 0 || count($cf7_fields) === 0)) : ?>
                <section class="formbridge-card">
                    <div class="formbridge-card-body">
                        <div class="notice notice-warning inline">
                            <p><?php esc_html_e('This mapping’s connection or form may have changed. You can still add a new mapping below.', 'formbridge'); ?></p>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="formbridge-card">
                <h2 class="formbridge-card-header">
                    <?php esc_html_e('Add new mapping', 'formbridge'); ?>
                    <?php if (count($mappings_list) > 0) : ?>
                        <span class="formbridge-step-label"><?php esc_html_e('Optional', 'formbridge'); ?></span>
                    <?php endif; ?>
                </h2>
                <div class="formbridge-card-body">
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                        <input type="hidden" name="page" value="formbridge-mappings" />
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="formbridge_form_id"><?php esc_html_e('Contact Form 7 form', 'formbridge'); ?></label></th>
                                <td>
                                    <select name="form_id" id="formbridge_form_id" required>
                                        <option value=""><?php esc_html_e('— Select a form —', 'formbridge'); ?></option>
                                        <?php foreach ($cf7_forms as $f) : ?>
                                            <option value="<?php echo esc_attr((string) $f['id']); ?>" <?php selected($form_id, $f['id']); ?>><?php echo esc_html($f['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="formbridge_connection_id"><?php esc_html_e('Connection', 'formbridge'); ?></label></th>
                                <td>
                                    <select name="connection_id" id="formbridge_connection_id" required>
                                        <option value=""><?php esc_html_e('— Select a connection —', 'formbridge'); ?></option>
                                        <?php foreach ($connections as $c) : ?>
                                            <option value="<?php echo esc_attr((string) $c['id']); ?>" <?php selected($connection_id, (int) $c['id']); ?>><?php echo esc_html((string) $c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php if (count($tables) > 0 && $edit_mapping_id === 0) : ?>
                                <tr>
                                    <th scope="row"><label for="formbridge_table"><?php esc_html_e('Destination table', 'formbridge'); ?></label></th>
                                    <td class="formbridge-select-row">
                                        <select name="table" id="formbridge_table">
                                            <option value=""><?php esc_html_e('— Select a table —', 'formbridge'); ?></option>
                                            <?php foreach ($tables as $t) : ?>
                                                <option value="<?php echo esc_attr($t); ?>" <?php selected($table, $t); ?>><?php echo esc_html($t); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Load mapping', 'formbridge'); ?>" />
                                    </td>
                                </tr>
                            <?php elseif ($edit_mapping_id === 0) : ?>
                                <tr>
                                    <td colspan="2">
                                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Load tables', 'formbridge'); ?>" />
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </form>
                    <?php if ($form_id === 0 && $connection_id === 0) : ?>
                        <p class="description" style="margin-top: 12px;"><?php esc_html_e('Choose a form and a connection, then click "Load tables" to select the destination table and map fields.', 'formbridge'); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($edit_mapping_id === 0 && $table !== '' && count($columns) > 0 && count($cf7_fields) > 0) : ?>
                <section class="formbridge-card formbridge-field-map-card">
                    <h2 class="formbridge-card-header"><?php esc_html_e('Field mapping', 'formbridge'); ?></h2>
                    <div class="formbridge-card-body">
                        <p class="description" style="margin: 0 0 16px 0;"><?php esc_html_e('Map each form field to a database column, or leave as "Do not map". At least one field must be mapped to save.', 'formbridge'); ?></p>
                        <form method="post" action="">
                            <input type="hidden" name="formbridge_action" value="save_mapping" />
                            <?php wp_nonce_field('formbridge_save_mapping', '_wpnonce'); ?>
                            <input type="hidden" name="form_id" value="<?php echo esc_attr((string) $form_id); ?>" />
                            <input type="hidden" name="connection_id" value="<?php echo esc_attr((string) $connection_id); ?>" />
                            <input type="hidden" name="destination_table" value="<?php echo esc_attr($table); ?>" />

                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('CF7 field', 'formbridge'); ?></th>
                                        <th scope="col"><?php esc_html_e('Database column', 'formbridge'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cf7_fields as $field_name) : ?>
                                        <tr>
                                            <td><code><?php echo esc_html($field_name); ?></code></td>
                                            <td>
                                                <select name="field_map_<?php echo esc_attr($field_name); ?>">
                                                    <option value=""><?php esc_html_e('— Do not map —', 'formbridge'); ?></option>
                                                    <?php foreach ($columns as $col) : ?>
                                                        <option value="<?php echo esc_attr($col); ?>" <?php selected(isset($existing_map[ $field_name ]) ? $existing_map[ $field_name ] : '', $col); ?>><?php echo esc_html($col); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p class="formbridge-submit-row">
                                <button type="submit" class="button button-primary"><?php esc_html_e('Save mapping', 'formbridge'); ?></button>
                            </p>
                        </form>
                    </div>
                </section>
            <?php elseif ($edit_mapping_id === 0 && $connection_id > 0 && count($tables) === 0) : ?>
                <section class="formbridge-card">
                    <div class="formbridge-card-body">
                        <p class="description"><?php esc_html_e('No tables found for this connection. Check the database and try again.', 'formbridge'); ?></p>
                    </div>
                </section>
            <?php elseif ($edit_mapping_id === 0 && $form_id > 0 && count($cf7_forms) > 0 && count($cf7_fields) === 0) : ?>
                <section class="formbridge-card">
                    <div class="formbridge-card-body">
                        <p class="description"><?php esc_html_e('No fields found in the selected form. Add form tags to the CF7 form template.', 'formbridge'); ?></p>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
