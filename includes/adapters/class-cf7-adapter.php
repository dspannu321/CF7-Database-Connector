<?php
/**
 * Captures Contact Form 7 submissions, normalizes them, and sends to the router.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_CF7_Adapter implements FormBridge_Source_Adapter {

    private const INTERNAL_KEYS = [
        '_wpcf7',
        '_wpcf7_version',
        '_wpcf7_locale',
        '_wpcf7_unit_tag',
        '_wpcf7_container_post',
        '_wpnonce',
    ];

    private FormBridge_Router $router;

    public function __construct(FormBridge_Router $router) {
        $this->router = $router;
    }

    /**
     * @return string
     */
    public function get_source_key(): string {
        return 'cf7';
    }

    /**
     * Registers the CF7 mail_sent hook.
     */
    public function register_hooks(): void {
        add_action('wpcf7_mail_sent', [$this, 'handle_submission'], 10, 1);
    }

    /**
     * Called after CF7 successfully sends mail. Normalizes submission and routes it.
     *
     * @param WPCF7_ContactForm $contact_form The CF7 form object.
     */
    public function handle_submission($contact_form): void {
        if (!class_exists('WPCF7_ContactForm') || !$contact_form instanceof WPCF7_ContactForm) {
            return;
        }

        $submission = \WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $posted_data = $submission->get_posted_data();
        if (!is_array($posted_data)) {
            $posted_data = [];
        }

        $fields = [];
        foreach ($posted_data as $key => $value) {
            if (in_array($key, self::INTERNAL_KEYS, true)) {
                continue;
            }
            $fields[ $key ] = $value;
        }

        $normalized = [
            'source'       => $this->get_source_key(),
            'form_id'      => (int) $contact_form->id(),
            'form_title'   => $contact_form->title() ?: '',
            'submitted_at' => formbridge_now(),
            'fields'       => $fields,
            'meta'         => [
                'ip'         => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            ],
        ];

        $this->router->route($normalized);
    }
}
