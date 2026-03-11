<?php
/**
 * Helper functions for FormBridge.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns current time in WordPress MySQL datetime format.
 *
 * @return string
 */
function formbridge_now(): string {
    return current_time('mysql');
}

/**
 * Safe JSON encode for logs and field maps. Returns '{}' on failure.
 *
 * @param array<string, mixed> $data
 * @return string
 */
function formbridge_json_encode(array $data): string {
    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    return $json !== false ? $json : '{}';
}

/**
 * Returns list of Contact Form 7 forms for dropdowns.
 *
 * @return array<int, array{id: int, title: string}>
 */
function formbridge_get_cf7_forms(): array {
    if (!class_exists('WPCF7_ContactForm')) {
        return [];
    }

    $forms = WPCF7_ContactForm::find();
    $list  = [];

    foreach ($forms as $form) {
        $list[] = [
            'id'    => (int) $form->id(),
            'title' => $form->title() ?: sprintf(/* translators: form id */ __('Form #%d', 'formbridge'), $form->id()),
        ];
    }

    return $list;
}

/**
 * Extracts field names from a CF7 form template (e.g. your-name, your-email).
 *
 * @param int $form_id CF7 form (post) ID.
 * @return array<int, string>
 */
function formbridge_get_cf7_fields(int $form_id): array {
    if (!class_exists('WPCF7_ContactForm')) {
        return [];
    }

    $form = WPCF7_ContactForm::get_instance($form_id);
    if (!$form) {
        return [];
    }

    // Prefer CF7's tag scanner if available.
    if (method_exists($form, 'scan_form_tags')) {
        $tags = $form->scan_form_tags();
        if (is_array($tags)) {
            $names = [];
            foreach ($tags as $tag) {
                $name = is_object($tag) && isset($tag->name) ? $tag->name : (is_array($tag) && isset($tag['name']) ? $tag['name'] : null);
                if ($name !== null && $name !== '') {
                    $names[] = $name;
                }
            }
            return array_values(array_unique($names));
        }
    }

    // Fallback: parse form template with regex. Match [type name] or [type* name].
    $template = $form->prop('form');
    if (!is_string($template) || $template === '') {
        return [];
    }

    if (preg_match_all('/\[\s*(?:\w+\*?\s+)?([a-zA-Z0-9_-]+)/', $template, $matches) && !empty($matches[1])) {
        return array_values(array_unique($matches[1]));
    }

    return [];
}

/**
 * Builds a dummy payload for testing: CF7 field name => test value.
 * Uses destination column name for hints (e.g. email -> test@example.com).
 *
 * @param array<string, string> $field_map CF7 field name => DB column name.
 * @return array<string, string>
 */
function formbridge_dummy_payload_for_field_map(array $field_map): array {
    $payload = [];
    $column_hints = [
        'email'       => 'test@example.com',
        'e-mail'      => 'test@example.com',
        'mail'        => 'test@example.com',
        'name'        => 'Test User',
        'full_name'   => 'Test User',
        'first_name'  => 'Test',
        'last_name'   => 'User',
        'phone'       => '555-123-4567',
        'tel'         => '555-123-4567',
        'message'     => 'Test message from CF7 Database Connector.',
        'notes'       => 'Test message from CF7 Database Connector.',
        'subject'     => 'Test submission',
        'company'     => 'Test Company',
        'date'        => gmdate('Y-m-d'),
        'created_at'  => formbridge_now(),
        'updated_at'  => formbridge_now(),
    ];

    foreach ($field_map as $cf7_field => $db_column) {
        $col_lower = strtolower($db_column);
        if (isset($column_hints[$col_lower])) {
            $payload[$cf7_field] = $column_hints[$col_lower];
        } else {
            $payload[$cf7_field] = 'Test ' . $db_column;
        }
    }

    return $payload;
}

/**
 * Outputs the admin logo img if admin/assets/images/logo.png or logo.svg exists.
 * Call from admin page headers. Does nothing if no logo file is present.
 */
function formbridge_admin_logo(): void {
    $dir = defined('FORMBRIDGE_PLUGIN_DIR') ? FORMBRIDGE_PLUGIN_DIR : '';
    $url = defined('FORMBRIDGE_PLUGIN_URL') ? FORMBRIDGE_PLUGIN_URL : '';
    if ($dir === '' || $url === '') {
        return;
    }
    $base = $dir . 'admin/assets/images/';
    if (is_readable($base . 'logo.png')) {
        echo '<img src="' . esc_url($url . 'admin/assets/images/logo.png') . '" alt="" class="formbridge-admin-logo" />';
    } elseif (is_readable($base . 'logo.svg')) {
        echo '<img src="' . esc_url($url . 'admin/assets/images/logo.svg') . '" alt="" class="formbridge-admin-logo" />';
    }
}

/**
 * Returns URL to custom menu icon if admin/assets/images/icon.png or icon.svg exists; otherwise null (caller uses dashicon).
 *
 * @return string|null
 */
function formbridge_menu_icon_url(): ?string {
    $dir = defined('FORMBRIDGE_PLUGIN_DIR') ? FORMBRIDGE_PLUGIN_DIR : '';
    $url = defined('FORMBRIDGE_PLUGIN_URL') ? FORMBRIDGE_PLUGIN_URL : '';
    if ($dir === '' || $url === '') {
        return null;
    }
    $base = $dir . 'admin/assets/images/';
    if (is_readable($base . 'icon.png')) {
        return $url . 'admin/assets/images/icon.png';
    }
    if (is_readable($base . 'icon.svg')) {
        return $url . 'admin/assets/images/icon.svg';
    }
    return null;
}
