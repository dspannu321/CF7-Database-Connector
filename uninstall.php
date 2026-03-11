<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package FormBridge
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Optional: delete options and tables on uninstall.
// For MVP Batch 1, no cleanup is performed so user data is preserved.
