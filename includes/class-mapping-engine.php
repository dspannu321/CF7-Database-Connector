<?php
/**
 * Transforms normalized submission fields into destination payload using field map.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Mapping_Engine {

    /**
     * Maps normalized submission fields to destination columns. Skips missing or unmapped fields.
     *
     * @param array{fields: array<string, mixed>} $normalized_submission Normalized submission (must have 'fields' key).
     * @param array<string, string>               $field_map              Source field name => destination column name.
     * @return array<string, mixed> Destination payload (column => value), ready for insert.
     */
    public function map(array $normalized_submission, array $field_map): array {
        $fields = $normalized_submission['fields'] ?? [];
        if (!is_array($fields)) {
            return [];
        }

        $payload = [];

        foreach ($field_map as $source_field => $destination_column) {
            if ($destination_column === '' || !is_string($destination_column)) {
                continue;
            }
            if (!array_key_exists($source_field, $fields)) {
                continue;
            }

            $value = $fields[ $source_field ];
            if (is_array($value)) {
                $payload[ $destination_column ] = wp_json_encode($value);
            } else {
                $payload[ $destination_column ] = $value;
            }
        }

        return $payload;
    }
}
