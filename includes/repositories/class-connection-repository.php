<?php
/**
 * CRUD for saved external database connections.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Connection_Repository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'formbridge_connections';
    }

    /**
     * Inserts a new connection. All fields required including db_pass.
     *
     * @param array{name: string, db_host: string, db_port: int, db_name: string, db_user: string, db_pass: string} $data
     * @return int|false Inserted row ID or false on failure.
     */
    public function insert(array $data): int|false {
        global $wpdb;

        $now = formbridge_now();
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->table} (name, db_host, db_port, db_name, db_user, db_pass, created_at, updated_at) VALUES (%s, %s, %d, %s, %s, %s, %s, %s)",
            $data['name'],
            $data['db_host'],
            (int) $data['db_port'],
            $data['db_name'],
            $data['db_user'],
            $data['db_pass'],
            $now,
            $now
        ));

        if ($result === false) {
            return false;
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * Gets a connection by ID.
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
     * Gets all connections ordered by name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_all(): array {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY name ASC", ARRAY_A);
        return is_array($results) ? $results : [];
    }

    /**
     * Updates a connection. Pass only fields to change; blank db_pass means keep existing.
     *
     * @param array<string, mixed> $data Keys: name, db_host, db_port, db_name, db_user, db_pass (optional).
     * @return bool True on success.
     */
    public function update(int $id, array $data): bool {
        global $wpdb;

        $existing = $this->get_by_id($id);
        if ($existing === null) {
            return false;
        }

        $name   = isset($data['name']) ? $data['name'] : $existing['name'];
        $host   = isset($data['db_host']) ? $data['db_host'] : $existing['db_host'];
        $port   = isset($data['db_port']) ? (int) $data['db_port'] : (int) $existing['db_port'];
        $dbname = isset($data['db_name']) ? $data['db_name'] : $existing['db_name'];
        $user   = isset($data['db_user']) ? $data['db_user'] : $existing['db_user'];
        $pass   = isset($data['db_pass']) && $data['db_pass'] !== '' ? $data['db_pass'] : $existing['db_pass'];
        $now    = formbridge_now();

        $updated = $wpdb->update(
            $this->table,
            [
                'name'       => $name,
                'db_host'    => $host,
                'db_port'    => $port,
                'db_name'    => $dbname,
                'db_user'    => $user,
                'db_pass'    => $pass,
                'updated_at' => $now,
            ],
            ['id' => $id],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        return $updated !== false;
    }

    /**
     * Deletes a connection by ID.
     *
     * @return bool True if a row was deleted.
     */
    public function delete(int $id): bool {
        global $wpdb;
        $deleted = $wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $deleted > 0;
    }
}
