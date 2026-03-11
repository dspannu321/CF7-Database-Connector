<?php
/**
 * CRUD for saved form-to-destination mappings.
 *
 * @package CF7_Database_Connector
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin tables; $wpdb is the correct API for custom table CRUD.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class CF7DB_Mapping_Repository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'cf7db_mappings';
    }

    /**
     * Inserts a new mapping. field_map must be JSON string.
     *
     * @param array{source_type: string, form_id: int, connection_id: int, destination_type: string, destination_table: string, field_map: string, is_active: int} $data
     * @return int|false Inserted row ID or false on failure.
     */
    public function insert(array $data): int|false {
        global $wpdb;

        $now = cf7db_now();
        $active = isset($data['is_active']) ? (int) $data['is_active'] : 1;

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->table} (source_type, form_id, connection_id, destination_type, destination_table, field_map, is_active, created_at, updated_at) VALUES (%s, %d, %d, %s, %s, %s, %d, %s, %s)",
            $data['source_type'],
            (int) $data['form_id'],
            (int) $data['connection_id'],
            $data['destination_type'] ?? 'mysql',
            $data['destination_table'],
            $data['field_map'],
            $active,
            $now,
            $now
        ));

        if ($result === false) {
            return false;
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * Gets a mapping by ID.
     *
     * @return array<string, mixed>|null Row as associative array or null if not found.
     */
    public function get_by_id(int $id): ?array {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ), ARRAY_A);

        return is_array($row) ? $row : null;
    }

    /**
     * Gets all mappings ordered by updated_at descending.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_all(): array {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY updated_at DESC", ARRAY_A);
        return is_array($results) ? $results : [];
    }

    /**
     * Gets the first mapping for a given source type and form ID (any is_active). Used for upsert on save.
     *
     * @return array<string, mixed>|null Single row or null if none found.
     */
    public function get_by_source_and_form(string $source_type, int $form_id): ?array {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE source_type = %s AND form_id = %d LIMIT 1",
            $source_type,
            $form_id
        ), ARRAY_A);

        return is_array($row) ? $row : null;
    }

    /**
     * Gets the active mapping for a given source type and form ID (for runtime submission handling).
     *
     * @return array<string, mixed>|null Single row or null if none active.
     */
    public function get_active_by_source_and_form(string $source_type, int $form_id): ?array {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE source_type = %s AND form_id = %d AND is_active = 1 LIMIT 1",
            $source_type,
            $form_id
        ), ARRAY_A);

        return is_array($row) ? $row : null;
    }

    /**
     * Updates a mapping by ID.
     *
     * @param array<string, mixed> $data Keys: source_type, form_id, connection_id, destination_type, destination_table, field_map, is_active.
     * @return bool True on success.
     */
    public function update(int $id, array $data): bool {
        global $wpdb;

        $existing = $this->get_by_id($id);
        if ($existing === null) {
            return false;
        }

        $now = cf7db_now();
        $update = [
            'source_type'        => $data['source_type'] ?? $existing['source_type'],
            'form_id'            => (int) ($data['form_id'] ?? $existing['form_id']),
            'connection_id'      => (int) ($data['connection_id'] ?? $existing['connection_id']),
            'destination_type'   => $data['destination_type'] ?? $existing['destination_type'],
            'destination_table'  => $data['destination_table'] ?? $existing['destination_table'],
            'field_map'          => $data['field_map'] ?? $existing['field_map'],
            'is_active'          => isset($data['is_active']) ? (int) $data['is_active'] : (int) $existing['is_active'],
            'updated_at'         => $now,
        ];

        $updated = $wpdb->update(
            $this->table,
            $update,
            ['id' => $id],
            ['%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s'],
            ['%d']
        );

        return $updated !== false;
    }

    /**
     * Deletes a mapping by ID.
     *
     * @return bool True if a row was deleted.
     */
    public function delete(int $id): bool {
        global $wpdb;
        $deleted = $wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $deleted > 0;
    }
}
