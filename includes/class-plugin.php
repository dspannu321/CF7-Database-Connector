<?php
/**
 * Main plugin class. Wires dependencies and registers hooks.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Plugin {

    private static ?FormBridge_Plugin $instance = null;

    private ?FormBridge_Admin $admin = null;

    private ?FormBridge_Connection_Repository $connection_repository = null;

    private ?FormBridge_Connection_Manager $connection_manager = null;

    private ?FormBridge_Mapping_Repository $mapping_repository = null;

    private ?FormBridge_Log_Repository $log_repository = null;

    private ?FormBridge_Router $router = null;

    private ?FormBridge_CF7_Adapter $cf7_adapter = null;

    /**
     * Returns the plugin instance.
     */
    public static function instance(): FormBridge_Plugin {
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
        $this->connection_repository = new FormBridge_Connection_Repository();
        $this->connection_manager    = new FormBridge_Connection_Manager();
        $this->mapping_repository   = new FormBridge_Mapping_Repository();
        $this->log_repository        = new FormBridge_Log_Repository();

        $mapping_engine = new FormBridge_Mapping_Engine();
        $logger         = new FormBridge_Logger($this->log_repository);
        $mysql_writer   = new FormBridge_MySQL_Writer($this->connection_manager);

        $this->router = new FormBridge_Router(
            $this->mapping_repository,
            $this->connection_repository,
            $mapping_engine,
            $mysql_writer,
            $logger
        );

        if (class_exists('WPCF7_ContactForm')) {
            $this->cf7_adapter = new FormBridge_CF7_Adapter($this->router);
            $this->cf7_adapter->register_hooks();
        }

        if (is_admin()) {
            $this->admin = new FormBridge_Admin(
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
    public function get_admin(): ?FormBridge_Admin {
        return $this->admin;
    }

    /**
     * Returns the connection repository (for use by router etc. later).
     */
    public function get_connection_repository(): ?FormBridge_Connection_Repository {
        return $this->connection_repository;
    }

    /**
     * Returns the connection manager (for use by router etc. later).
     */
    public function get_connection_manager(): ?FormBridge_Connection_Manager {
        return $this->connection_manager;
    }

    /**
     * Returns the mapping repository (for use by router etc. later).
     */
    public function get_mapping_repository(): ?FormBridge_Mapping_Repository {
        return $this->mapping_repository;
    }

    /**
     * Returns the router (for use by adapters).
     */
    public function get_router(): ?FormBridge_Router {
        return $this->router;
    }
}
