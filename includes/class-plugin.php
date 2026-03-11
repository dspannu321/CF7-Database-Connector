<?php
/**
 * Main plugin class. Wires dependencies and registers hooks.
 *
 * @package CF7_Database_Connector
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class CF7DB_Plugin {

    private static ?CF7DB_Plugin $instance = null;

    private ?CF7DB_Admin $admin = null;

    private ?CF7DB_Connection_Repository $connection_repository = null;

    private ?CF7DB_Connection_Manager $connection_manager = null;

    private ?CF7DB_Mapping_Repository $mapping_repository = null;

    private ?CF7DB_Log_Repository $log_repository = null;

    private ?CF7DB_Router $router = null;

    private ?CF7DB_CF7_Adapter $cf7_adapter = null;

    /**
     * Returns the plugin instance.
     */
    public static function instance(): CF7DB_Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Dependencies and hooks are registered in init() after plugins_loaded.
    }

    /**
     * Initializes the plugin. Called on plugins_loaded.
     * Runtime pipeline (adapter, router, etc.) runs on every request so CF7 submissions are captured on the frontend.
     */
    public function init(): void {
        $this->connection_repository = new CF7DB_Connection_Repository();
        $this->connection_manager    = new CF7DB_Connection_Manager();
        $this->mapping_repository    = new CF7DB_Mapping_Repository();
        $this->log_repository        = new CF7DB_Log_Repository();

        $mapping_engine = new CF7DB_Mapping_Engine();
        $logger         = new CF7DB_Logger($this->log_repository);
        $mysql_writer   = new CF7DB_MySQL_Writer($this->connection_manager);

        $this->router = new CF7DB_Router(
            $this->mapping_repository,
            $this->connection_repository,
            $mapping_engine,
            $mysql_writer,
            $logger
        );

        if (class_exists('WPCF7_ContactForm')) {
            $this->cf7_adapter = new CF7DB_CF7_Adapter($this->router);
            $this->cf7_adapter->register_hooks();
        }

        if (is_admin()) {
            $this->admin = new CF7DB_Admin(
                $this->connection_repository,
                $this->connection_manager,
                $this->mapping_repository,
                $this->log_repository,
                $this->router
            );
            $this->admin->register_hooks();
        }
    }

    /**
     * Returns the admin instance (for use by other components later).
     */
    public function get_admin(): ?CF7DB_Admin {
        return $this->admin;
    }

    /**
     * Returns the connection repository (for use by router etc. later).
     */
    public function get_connection_repository(): ?CF7DB_Connection_Repository {
        return $this->connection_repository;
    }

    /**
     * Returns the connection manager (for use by router etc. later).
     */
    public function get_connection_manager(): ?CF7DB_Connection_Manager {
        return $this->connection_manager;
    }

    /**
     * Returns the mapping repository (for use by router etc. later).
     */
    public function get_mapping_repository(): ?CF7DB_Mapping_Repository {
        return $this->mapping_repository;
    }

    /**
     * Returns the router (for use by adapters).
     */
    public function get_router(): ?CF7DB_Router {
        return $this->router;
    }
}
