<?php
/**
 * Plugin Name: CF7 Database Connector
 * Plugin URI: https://wordpress.org/plugins/cf7-database-connector
 * Description: Send Contact Form 7 submissions to an external MySQL database with field mapping—no code required.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 8.1
 * Author: Dilawar
 * Author URI: https://profiles.wordpress.org/dilawar321/profile
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf7-database-connector
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CF7DB_VERSION', '1.0.0');
define('CF7DB_PLUGIN_FILE', __FILE__);
define('CF7DB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF7DB_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CF7DB_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CF7DB_PLUGIN_DIR . 'includes/class-activator.php';
require_once CF7DB_PLUGIN_DIR . 'includes/class-plugin.php';
require_once CF7DB_PLUGIN_DIR . 'includes/class-admin.php';
require_once CF7DB_PLUGIN_DIR . 'includes/helpers/helpers.php';
require_once CF7DB_PLUGIN_DIR . 'includes/class-connection-manager.php';
require_once CF7DB_PLUGIN_DIR . 'includes/repositories/class-connection-repository.php';
require_once CF7DB_PLUGIN_DIR . 'includes/repositories/class-mapping-repository.php';
require_once CF7DB_PLUGIN_DIR . 'includes/repositories/class-log-repository.php';
require_once CF7DB_PLUGIN_DIR . 'includes/adapters/interface-source-adapter.php';
require_once CF7DB_PLUGIN_DIR . 'includes/destinations/interface-destination-writer.php';
require_once CF7DB_PLUGIN_DIR . 'includes/class-mapping-engine.php';
require_once CF7DB_PLUGIN_DIR . 'includes/class-logger.php';
require_once CF7DB_PLUGIN_DIR . 'includes/destinations/class-mysql-writer.php';
require_once CF7DB_PLUGIN_DIR . 'includes/class-router.php';
require_once CF7DB_PLUGIN_DIR . 'includes/adapters/class-cf7-adapter.php';

register_activation_hook(__FILE__, function (): void {
    CF7DB_Activator::activate();
});

add_action('plugins_loaded', function (): void {
    CF7DB_Plugin::instance()->init();
}, 10);

add_action('admin_notices', function (): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (class_exists('WPCF7_ContactForm')) {
        return;
    }
    $install_url = admin_url('plugin-install.php?s=contact+form+7&tab=search&type=term');
    ?>
    <div class="notice notice-warning is-dismissible">
        <p><strong><?php esc_html_e('CF7 Database Connector', 'cf7-database-connector'); ?></strong> <?php esc_html_e('requires the Contact Form 7 plugin.', 'cf7-database-connector'); ?> <?php esc_html_e('We recommend installing it now.', 'cf7-database-connector'); ?>
            <a href="<?php echo esc_url($install_url); ?>" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e('Install Contact Form 7', 'cf7-database-connector'); ?></a>
        </p>
    </div>
    <?php
});
