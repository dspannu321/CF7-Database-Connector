<?php
/**
 * Read/write for form sync attempt logs.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Log_Repository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'formbridge_logs';
    }

    /**
     * Inserts a log entry. Logs are append-only.
     *
     * @param array{source_type: string, form_id: int, mapping_id: int|null, destination_type: string, destination_table: string, payload: string|null, status: string, message: string|null} $data
     * @return int|false Inserted row ID or false on failure.
     */
    public function insert(array $data): int|false {
        global $wpdb;

        $mapping_id = isset($data['mapping_id']) && $data['mapping_id'] !== null && $data['mapping_id'] !== ''
            ? (int) $data['mapping_id']
            : null;
        $payload = $data['payload'] ?? null;
        $message = $data['message'] ?? null;
        $now = formbridge_now();

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->table} (source_type, form_id, mapping_id, destination_type, destination_table, payload, status, message, created_at) VALUES (%s, %d, %s, %s, %s, %s, %s, %s, %s)",
            $data['source_type'],
            (int) $data['form_id'],
            $mapping_id,
            $data['destination_type'],
            $data['destination_table'],
            $payload,
            $data['status'],
            $message,
            $now
        ));

        if ($result === false) {
            return false;
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * Gets recent log entries, newest first.
     *
     * @param int $limit Max number of rows to return.
     * @param int $offset Offset for pagination.
     * @return array<int, array<string, mixed>>
     */
    public function get_recent(int $limit = 50, int $offset = 0): array {
        global $wpdb;

        $limit  = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);

        return is_array($results) ? $results : [];
    }

    /**
     * Gets a single log entry by ID.
     *
     * @return array<string, mixed>|null Row or null if not found.
     */
    public function get_by_id(int $id): ?array {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ), ARRAY_A);

        return is_array($row) ? $row : null;
    }
}
