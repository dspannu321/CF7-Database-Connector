<?php
/**
 * Plugin Name: CF7 Database Connector
 * Plugin URI: https://formbridge.dev
 * Description: Send Contact Form 7 submissions to an external MySQL database with field mapping—no code required.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 8.1
 * Author: CF7 Database Connector
 * Author URI: https://formbridge.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: formbridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('FORMBRIDGE_VERSION', '1.0.0');
define('FORMBRIDGE_PLUGIN_FILE', __FILE__);
define('FORMBRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FORMBRIDGE_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('FORMBRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once FORMBRIDGE_PLUGIN_DIR . 'includes/class-activator.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/class-plugin.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/class-admin.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/helpers/helpers.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/class-connection-manager.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/repositories/class-connection-repository.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/repositories/class-mapping-repository.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/repositories/class-log-repository.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/adapters/interface-source-adapter.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/destinations/interface-destination-writer.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/class-mapping-engine.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/class-logger.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/destinations/class-mysql-writer.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/class-router.php';
require_once FORMBRIDGE_PLUGIN_DIR . 'includes/adapters/class-cf7-adapter.php';

register_activation_hook(__FILE__, function (): void {
    FormBridge_Activator::activate();
});

add_action('plugins_loaded', function (): void {
    FormBridge_Plugin::instance()->init();
}, 10);
