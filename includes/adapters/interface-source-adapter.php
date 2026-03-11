<?php
/**
 * Interface for form source adapters (e.g. CF7, WPForms).
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

interface FormBridge_Source_Adapter {

    /**
     * Registers hooks to capture form submissions and pass normalized payload to the router.
     */
    public function register_hooks(): void;

    /**
     * Returns the source identifier (e.g. 'cf7').
     */
    public function get_source_key(): string;
}
