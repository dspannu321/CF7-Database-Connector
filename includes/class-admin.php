<?php
/**
 * Admin UI: menu, pages, and assets.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Admin {

    private const MENU_SLUG = 'formbridge';
    private const CAPABILITY = 'manage_options';
    private const CONNECTION_NONCE_ACTION = 'formbridge_connection';

    private FormBridge_Connection_Repository $connection_repository;
    private FormBridge_Connection_Manager $connection_manager;
    private FormBridge_Mapping_Repository $mapping_repository;
    private FormBridge_Log_Repository $log_repository;
    private FormBridge_Router $router;

    public function __construct(
        FormBridge_Connection_Repository $connection_repository,
        FormBridge_Connection_Manager $connection_manager,
        FormBridge_Mapping_Repository $mapping_repository,
        FormBridge_Log_Repository $log_repository,
        FormBridge_Router $router
    ) {
        $this->connection_repository = $connection_repository;
        $this->connection_manager    = $connection_manager;
        $this->mapping_repository    = $mapping_repository;
        $this->log_repository        = $log_repository;
        $this->router                = $router;
    }

    /**
     * Registers admin hooks.
     */
    public function register_hooks(): void {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'handle_connection_actions']);
        add_action('admin_init', [$this, 'handle_mapping_actions']);
        add_action('wp_ajax_formbridge_test_connection_draft', [$this, 'ajax_test_connection_draft']);
    }

    /**
     * Registers the FormBridge top-level menu and subpages.
     */
    public function register_menu(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        $menu_icon = formbridge_menu_icon_url();
        if ($menu_icon === null) {
            $menu_icon = 'dashicons-database-export';
        }

        add_menu_page(
            __('CF7 Database Connector', 'formbridge'),
            __('CF7 Database Connector', 'formbridge'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'render_connections_page'],
            $menu_icon,
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Connections', 'formbridge'),
            __('Connections', 'formbridge'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'render_connections_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Mappings', 'formbridge'),
            __('Mappings', 'formbridge'),
            self::CAPABILITY,
            self::MENU_SLUG . '-mappings',
            [$this, 'render_mappings_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Logs', 'formbridge'),
            __('Logs', 'formbridge'),
            self::CAPABILITY,
            self::MENU_SLUG . '-logs',
            [$this, 'render_logs_page']
        );
    }

    /**
     * Enqueues admin assets only on FormBridge admin pages.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     */
    public function enqueue_assets(string $hook_suffix): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        $formbridge_screens = [
            'toplevel_page_formbridge',
            'formbridge_page_formbridge-mappings',
            'formbridge_page_formbridge-logs',
        ];

        if (!in_array($hook_suffix, $formbridge_screens, true)) {
            return;
        }

        wp_enqueue_style(
            'formbridge-admin',
            FORMBRIDGE_PLUGIN_URL . 'admin/assets/admin.css',
            [],
            FORMBRIDGE_VERSION
        );

        wp_enqueue_script(
            'formbridge-admin',
            FORMBRIDGE_PLUGIN_URL . 'admin/assets/admin.js',
            [],
            FORMBRIDGE_VERSION,
            true
        );

        if ($hook_suffix === 'toplevel_page_formbridge') {
            wp_localize_script('formbridge-admin', 'formbridgeAdmin', [
                'ajaxUrl'              => admin_url('admin-ajax.php'),
                'testConnectionNonce' => wp_create_nonce('formbridge_test_connection_draft'),
            ]);
        }
    }

    /**
     * Handles connection create, update, delete, and test actions. Redirects after POST.
     */
    public function handle_connection_actions(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        $action = isset($_REQUEST['formbridge_action']) ? sanitize_text_field(wp_unslash($_REQUEST['formbridge_action'])) : '';
        if ($action === '') {
            return;
        }

        $redirect_url = admin_url('admin.php?page=' . self::MENU_SLUG);

        if ($action === 'delete_connection') {
            $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;
            if ($id && isset($_REQUEST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'formbridge_delete_connection_' . $id)) {
                $this->connection_repository->delete($id);
                $redirect_url = add_query_arg('deleted', '1', $redirect_url);
            }
            wp_safe_redirect($redirect_url);
            exit;
        }

        if ($action === 'test_connection') {
            $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;
            if ($id && isset($_REQUEST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'formbridge_test_connection_' . $id)) {
                $conn = $this->connection_repository->get_by_id($id);
                if ($conn) {
                    $result = $this->connection_manager->test_connection($conn);
                    $redirect_url = add_query_arg('test_success', $result['success'] ? '1' : '0', $redirect_url);
                    $redirect_url = add_query_arg('test_message', rawurlencode($result['message']), $redirect_url);
                }
            }
            wp_safe_redirect($redirect_url);
            exit;
        }

        if ($action === 'save_connection') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::CONNECTION_NONCE_ACTION)) {
                wp_safe_redirect(add_query_arg('error', '1', $redirect_url));
                exit;
            }

            $edit_id = isset($_POST['connection_id']) ? absint($_POST['connection_id']) : 0;
            $name    = isset($_POST['connection_name']) ? sanitize_text_field(wp_unslash($_POST['connection_name'])) : '';
            $host    = isset($_POST['db_host']) ? sanitize_text_field(wp_unslash($_POST['db_host'])) : '';
            $port    = isset($_POST['db_port']) ? absint($_POST['db_port']) : 3306;
            $dbname  = isset($_POST['db_name']) ? sanitize_text_field(wp_unslash($_POST['db_name'])) : '';
            $user    = isset($_POST['db_user']) ? sanitize_text_field(wp_unslash($_POST['db_user'])) : '';
            $pass    = isset($_POST['db_pass']) ? wp_unslash($_POST['db_pass']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- password, not echoed

            if ($port <= 0) {
                $port = 3306;
            }

            $error_message = '';
            if ($name === '') {
                $error_message = __('Connection name is required.', 'formbridge');
            } elseif ($host === '') {
                $error_message = __('Host is required.', 'formbridge');
            } elseif ($dbname === '') {
                $error_message = __('Database name is required.', 'formbridge');
            } elseif ($user === '') {
                $error_message = __('Username is required.', 'formbridge');
            } elseif ($edit_id === 0 && $pass === '') {
                $error_message = __('Password is required for new connections.', 'formbridge');
            }

            if ($error_message !== '') {
                wp_safe_redirect(add_query_arg(['error' => '1', 'message' => rawurlencode($error_message)], $redirect_url));
                exit;
            }

            if ($edit_id > 0) {
                $existing = $this->connection_repository->get_by_id($edit_id);
                if (!$existing) {
                    wp_safe_redirect(add_query_arg('error', '1', $redirect_url));
                    exit;
                }
                $data = [
                    'name'     => $name,
                    'db_host'  => $host,
                    'db_port'  => $port,
                    'db_name'  => $dbname,
                    'db_user'  => $user,
                ];
                if ($pass !== '') {
                    $data['db_pass'] = $pass;
                }
                $ok = $this->connection_repository->update($edit_id, $data);
                $redirect_url = $ok ? add_query_arg('updated', '1', $redirect_url) : add_query_arg('error', '1', $redirect_url);
            } else {
                $data = [
                    'name'     => $name,
                    'db_host'  => $host,
                    'db_port'  => $port,
                    'db_name'  => $dbname,
                    'db_user'  => $user,
                    'db_pass'  => $pass,
                ];
                $id = $this->connection_repository->insert($data);
                $redirect_url = $id ? add_query_arg('created', '1', $redirect_url) : add_query_arg('error', '1', $redirect_url);
            }
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Handles mapping actions: test submission (demo data) and save mapping.
     */
    public function handle_mapping_actions(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        if (isset($_POST['formbridge_action']) && $_POST['formbridge_action'] === 'test_submission') {
            $this->handle_test_submission();
            return;
        }

        if (!isset($_POST['formbridge_action']) || $_POST['formbridge_action'] !== 'save_mapping') {
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'formbridge_save_mapping')) {
            wp_safe_redirect(admin_url('admin.php?page=formbridge-mappings&error=1'));
            exit;
        }

        $form_id        = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $connection_id  = isset($_POST['connection_id']) ? absint($_POST['connection_id']) : 0;
        $destination_table = isset($_POST['destination_table']) ? sanitize_text_field(wp_unslash($_POST['destination_table'])) : '';

        $redirect_url = admin_url('admin.php?page=formbridge-mappings');

        if ($form_id <= 0 || $connection_id <= 0 || $destination_table === '') {
            wp_safe_redirect(add_query_arg(['error' => '1', 'message' => rawurlencode(__('Form, connection, and table are required.', 'formbridge'))], $redirect_url));
            exit;
        }

        $connection = $this->connection_repository->get_by_id($connection_id);
        if (!$connection) {
            wp_safe_redirect(add_query_arg(['error' => '1', 'message' => rawurlencode(__('Connection not found.', 'formbridge'))], $redirect_url));
            exit;
        }

        if (!$this->connection_manager->table_exists($connection, $destination_table)) {
            wp_safe_redirect(add_query_arg(['error' => '1', 'message' => rawurlencode(__('Destination table not found.', 'formbridge'))], $redirect_url));
            exit;
        }

        $valid_columns = $this->connection_manager->get_valid_columns($connection, $destination_table);
        $cf7_fields    = formbridge_get_cf7_fields($form_id);
        $field_map     = [];

        foreach ($cf7_fields as $field_name) {
            $key = 'field_map_' . $field_name;
            if (!isset($_POST[ $key ])) {
                continue;
            }
            $column = sanitize_text_field(wp_unslash($_POST[ $key ]));
            if ($column === '' || !in_array($column, $valid_columns, true)) {
                continue;
            }
            $field_map[ $field_name ] = $column;
        }

        if (empty($field_map)) {
            wp_safe_redirect(add_query_arg([
                'error'   => '1',
                'message' => rawurlencode(__('At least one field must be mapped to a database column.', 'formbridge')),
            ], $redirect_url));
            exit;
        }

        $field_map_json = wp_json_encode($field_map);
        if ($field_map_json === false) {
            $field_map_json = '{}';
        }

        $existing = $this->mapping_repository->get_by_source_and_form('cf7', $form_id);

        if ($existing) {
            $ok = $this->mapping_repository->update((int) $existing['id'], [
                'connection_id'      => $connection_id,
                'destination_table'  => $destination_table,
                'field_map'          => $field_map_json,
                'is_active'          => 1,
            ]);
        } else {
            $id = $this->mapping_repository->insert([
                'source_type'        => 'cf7',
                'form_id'            => $form_id,
                'connection_id'      => $connection_id,
                'destination_type'  => 'mysql',
                'destination_table'  => $destination_table,
                'field_map'          => $field_map_json,
                'is_active'          => 1,
            ]);
            $ok = $id !== false;
        }

        if ($ok) {
            wp_safe_redirect(add_query_arg(['saved' => '1'], $redirect_url));
        } else {
            wp_safe_redirect(add_query_arg(['error' => '1'], $redirect_url));
        }
        exit;
    }

    /**
     * Handles test submission: builds dummy payload from mapping field_map, runs router, redirects with notice.
     */
    private function handle_test_submission(): void {
        $mapping_id = isset($_POST['mapping_id']) ? absint($_POST['mapping_id']) : 0;
        if ($mapping_id <= 0 || !isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'formbridge_test_submission_' . $mapping_id)) {
            wp_safe_redirect(add_query_arg(['page' => 'formbridge-mappings', 'error' => '1', 'message' => rawurlencode(__('Invalid request.', 'formbridge'))], admin_url('admin.php')));
            exit;
        }

        $mapping = $this->mapping_repository->get_by_id($mapping_id);
        if (!$mapping) {
            wp_safe_redirect(add_query_arg(['page' => 'formbridge-mappings', 'error' => '1', 'message' => rawurlencode(__('Mapping not found.', 'formbridge'))], admin_url('admin.php')));
            exit;
        }

        $field_map_json = $mapping['field_map'] ?? '{}';
        $field_map      = is_string($field_map_json) ? json_decode($field_map_json, true) : $field_map_json;
        if (!is_array($field_map)) {
            $field_map = [];
        }

        $dummy_fields = formbridge_dummy_payload_for_field_map($field_map);
        $normalized   = [
            'source'        => 'cf7',
            'form_id'       => (int) $mapping['form_id'],
            'form_title'    => __('Test submission', 'formbridge'),
            'submitted_at'  => formbridge_now(),
            'fields'        => $dummy_fields,
            'meta'          => ['test' => true],
        ];

        $result = $this->router->route($normalized);
        $redirect_url = admin_url('admin.php?page=formbridge-mappings&edit_mapping=' . $mapping_id);

        if (!empty($result['success'])) {
            wp_safe_redirect(add_query_arg(['test_sent' => '1'], $redirect_url));
        } else {
            wp_safe_redirect(add_query_arg(['error' => '1', 'message' => rawurlencode($result['message'] ?? __('Test submission failed.', 'formbridge'))], $redirect_url));
        }
        exit;
    }

    /**
     * AJAX: Test connection using current form values (draft). No save.
     */
    public function ajax_test_connection_draft(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('Permission denied.', 'formbridge')]);
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'formbridge_test_connection_draft')) {
            wp_send_json_error(['message' => __('Security check failed.', 'formbridge')]);
        }

        $id     = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $host   = isset($_POST['db_host']) ? sanitize_text_field(wp_unslash($_POST['db_host'])) : '';
        $port   = isset($_POST['db_port']) ? absint($_POST['db_port']) : 3306;
        $dbname = isset($_POST['db_name']) ? sanitize_text_field(wp_unslash($_POST['db_name'])) : '';
        $user   = isset($_POST['db_user']) ? sanitize_text_field(wp_unslash($_POST['db_user'])) : '';
        $pass   = isset($_POST['db_pass']) ? wp_unslash($_POST['db_pass']) : '';

        if ($host === '' || $dbname === '' || $user === '') {
            wp_send_json_error(['message' => __('Host, database, and user are required.', 'formbridge')]);
        }

        if ($id > 0 && $pass === '') {
            $existing = $this->connection_repository->get_by_id($id);
            if ($existing && isset($existing['db_pass'])) {
                $pass = $existing['db_pass'];
            }
        }

        if ($pass === '') {
            wp_send_json_error(['message' => __('Password is required to test the connection.', 'formbridge')]);
        }

        $connection = [
            'db_host' => $host,
            'db_port' => $port,
            'db_name' => $dbname,
            'db_user' => $user,
            'db_pass' => $pass,
        ];

        $test = $this->connection_manager->test_connection($connection);
        if ($test['success']) {
            wp_send_json_success(['message' => $test['message'] ?? __('Connection successful.', 'formbridge')]);
        }
        wp_send_json_error(['message' => $test['message'] ?? __('Connection failed.', 'formbridge')]);
    }

    /**
     * Returns stats for stat cards (connections, mappings, recent logs count).
     *
     * @return array{connections: int, mappings: int, logs_24h: int}
     */
    public function get_stats(): array {
        $connections = $this->connection_repository->get_all();
        $mappings    = $this->mapping_repository->get_all();
        $logs_24h    = $this->log_repository->get_recent(500, 0);
        $cutoff      = gmdate('Y-m-d H:i:s', strtotime('-24 hours'));
        $count_24h   = 0;
        foreach ($logs_24h as $log) {
            $t = $log['created_at'] ?? '';
            if ($t !== '' && $t >= $cutoff) {
                $count_24h++;
            }
        }

        return [
            'connections' => count($connections),
            'mappings'    => count($mappings),
            'logs_24h'    => $count_24h,
        ];
    }

    /**
     * Renders the Connections page.
     */
    public function render_connections_page(): void {
        $this->check_capability();

        $stats     = $this->get_stats();
        $connections = $this->connection_repository->get_all();
        $edit_id     = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $edit        = $edit_id > 0 ? $this->connection_repository->get_by_id($edit_id) : null;

        $notices = [];
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $notices[] = ['type' => 'success', 'text' => __('Connection created.', 'formbridge')];
        }
        if (isset($_GET['updated']) && $_GET['updated'] === '1') {
            $notices[] = ['type' => 'success', 'text' => __('Connection updated.', 'formbridge')];
        }
        if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
            $notices[] = ['type' => 'success', 'text' => __('Connection deleted.', 'formbridge')];
        }
        if (isset($_GET['error']) && $_GET['error'] === '1') {
            $msg = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : __('An error occurred.', 'formbridge');
            $notices[] = ['type' => 'error', 'text' => $msg];
        }
        if (isset($_GET['test_success'])) {
            $msg = isset($_GET['test_message']) ? sanitize_text_field(wp_unslash($_GET['test_message'])) : '';
            $notices[] = [
                'type' => $_GET['test_success'] === '1' ? 'success' : 'error',
                'text' => $msg ?: ($_GET['test_success'] === '1' ? __('Connection successful.', 'formbridge') : __('Connection failed.', 'formbridge')),
            ];
        }

        $ajax_url  = admin_url('admin-ajax.php');
        $test_nonce = wp_create_nonce('formbridge_test_connection_draft');
        include FORMBRIDGE_PLUGIN_DIR . 'admin/views/connections-page.php';
    }

    /**
     * Renders the Mappings page: list existing mappings, then add/edit form and field mapper.
     */
    public function render_mappings_page(): void {
        $this->check_capability();

        $stats       = $this->get_stats();
        $connections = $this->connection_repository->get_all();
        $cf7_forms   = formbridge_get_cf7_forms();

        $form_id         = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        $connection_id   = isset($_GET['connection_id']) ? absint($_GET['connection_id']) : 0;
        $table           = isset($_GET['table']) ? sanitize_text_field(wp_unslash($_GET['table'])) : '';
        $edit_mapping_id = isset($_GET['edit_mapping']) ? absint($_GET['edit_mapping']) : 0;

        $form_titles   = [];
        $connection_names = [];
        foreach ($cf7_forms as $f) {
            $form_titles[ (int) $f['id'] ] = $f['title'];
        }
        foreach ($connections as $c) {
            $connection_names[ (int) $c['id'] ] = (string) $c['name'];
        }

        $all_mappings = $this->mapping_repository->get_all();
        $mappings_list = [];
        foreach ($all_mappings as $m) {
            $mappings_list[] = [
                'id'                => (int) $m['id'],
                'form_id'           => (int) $m['form_id'],
                'connection_id'    => (int) $m['connection_id'],
                'form_title'        => $form_titles[ (int) $m['form_id'] ] ?? sprintf(__('Form #%d', 'formbridge'), (int) $m['form_id']),
                'connection_name'   => $connection_names[ (int) $m['connection_id'] ] ?? sprintf(__('Connection #%d', 'formbridge'), (int) $m['connection_id']),
                'destination_table' => (string) $m['destination_table'],
            ];
        }

        if ($edit_mapping_id > 0) {
            $edit_mapping_row = $this->mapping_repository->get_by_id($edit_mapping_id);
            if ($edit_mapping_row) {
                $form_id       = (int) $edit_mapping_row['form_id'];
                $connection_id = (int) $edit_mapping_row['connection_id'];
                $table         = (string) $edit_mapping_row['destination_table'];
            } else {
                $edit_mapping_id = 0;
            }
        }

        $tables   = [];
        $columns  = [];
        $cf7_fields = [];
        $connection = null;

        if ($connection_id > 0) {
            $connection = $this->connection_repository->get_by_id($connection_id);
            if ($connection) {
                $tables = $this->connection_manager->get_tables($connection);
                if ($table !== '' && in_array($table, $tables, true)) {
                    $columns = $this->connection_manager->get_columns($connection, $table);
                }
            }
        }

        if ($form_id > 0) {
            $cf7_fields = formbridge_get_cf7_fields($form_id);
        }

        $existing_mapping = null;
        if ($form_id > 0) {
            $existing_mapping = $this->mapping_repository->get_by_source_and_form('cf7', $form_id);
        }

        $notices = [];
        if (isset($_GET['saved']) && $_GET['saved'] === '1') {
            $notices[] = ['type' => 'success', 'text' => __('Mapping saved.', 'formbridge')];
        }
        if (isset($_GET['error']) && $_GET['error'] === '1') {
            $msg = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : __('An error occurred.', 'formbridge');
            $notices[] = ['type' => 'error', 'text' => $msg];
        }
        if (isset($_GET['test_sent']) && $_GET['test_sent'] === '1') {
            $notices[] = ['type' => 'success', 'text' => __('Test data was sent successfully. Check the destination table and Logs.', 'formbridge')];
        }

        $test_submission_nonce = $edit_mapping_id > 0 ? wp_create_nonce('formbridge_test_submission_' . $edit_mapping_id) : '';
        include FORMBRIDGE_PLUGIN_DIR . 'admin/views/mappings-page.php';
    }

    /**
     * Renders the Logs placeholder page.
     */
    /**
     * Renders the Logs page with recent sync attempts and optional payload detail.
     */
    public function render_logs_page(): void {
        $this->check_capability();
        $stats = $this->get_stats();
        $logs  = $this->log_repository->get_recent(50, 0);
        include FORMBRIDGE_PLUGIN_DIR . 'admin/views/logs-page.php';
    }

    /**
     * Ensures the current user can manage options.
     */
    private function check_capability(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'formbridge'));
        }
    }

    /**
     * Loads an admin view file.
     *
     * @param string $view_name View filename without path (e.g. 'connections-page').
     */
    private function load_view(string $view_name): void {
        $path = FORMBRIDGE_PLUGIN_DIR . 'admin/views/' . $view_name . '.php';
        if (is_readable($path)) {
            include $path;
        }
    }
}
