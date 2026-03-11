<?php
/**
 * Writes mapped payload to external MySQL via PDO. Validates table/columns against schema.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_MySQL_Writer implements FormBridge_Destination_Writer {

    private FormBridge_Connection_Manager $connection_manager;

    public function __construct(FormBridge_Connection_Manager $connection_manager) {
        $this->connection_manager = $connection_manager;
    }

    /**
     * @return string
     */
    public function get_key(): string {
        return 'mysql';
    }

    /**
     * Validates payload and schema, then executes prepared INSERT.
     *
     * @param array<string, mixed> $payload Column => value.
     * @param array<string, mixed> $config Must contain 'connection' (array) and 'table' (string).
     * @return array{success: bool, message: string, insert_id: int|null}
     */
    public function write(array $payload, array $config): array {
        $connection = $config['connection'] ?? null;
        $table     = isset($config['table']) && is_string($config['table']) ? $config['table'] : '';

        if (!is_array($connection) || $table === '') {
            return [
                'success'   => false,
                'message'   => __('Invalid destination config.', 'formbridge'),
                'insert_id' => null,
            ];
        }

        if (empty($payload)) {
            return [
                'success'   => false,
                'message'   => __('Payload is empty.', 'formbridge'),
                'insert_id' => null,
            ];
        }

        if (!$this->connection_manager->table_exists($connection, $table)) {
            return [
                'success'   => false,
                'message'   => __('Destination table does not exist.', 'formbridge'),
                'insert_id' => null,
            ];
        }

        $valid_columns = $this->connection_manager->get_valid_columns($connection, $table);
        foreach (array_keys($payload) as $column) {
            if (!in_array($column, $valid_columns, true)) {
                return [
                    'success'   => false,
                    'message'   => sprintf(
                        /* translators: %s: column name */
                        __('Column %s does not exist in destination table.', 'formbridge'),
                        $column
                    ),
                    'insert_id' => null,
                ];
            }
        }

        try {
            $pdo = $this->connection_manager->connect($connection);
        } catch (PDOException $e) {
            return [
                'success'   => false,
                'message'   => $e->getMessage(),
                'insert_id' => null,
            ];
        }

        $columns_escaped = array_map(function (string $col): string {
            return '`' . str_replace('`', '``', $col) . '`';
        }, array_keys($payload));
        $columns_list = implode(', ', $columns_escaped);
        $placeholders = implode(', ', array_fill(0, count($payload), '?'));

        $sql = "INSERT INTO `" . str_replace('`', '``', $table) . "` ({$columns_list}) VALUES ({$placeholders})";

        try {
            $stmt = $pdo->prepare($sql);
            if ($stmt === false) {
                return [
                    'success'   => false,
                    'message'   => __('Failed to prepare statement.', 'formbridge'),
                    'insert_id' => null,
                ];
            }
            $stmt->execute(array_values($payload));
            $insert_id = (int) $pdo->lastInsertId();
            return [
                'success'   => true,
                'message'   => __('Insert successful.', 'formbridge'),
                'insert_id' => $insert_id > 0 ? $insert_id : null,
            ];
        } catch (PDOException $e) {
            return [
                'success'   => false,
                'message'   => $e->getMessage(),
                'insert_id' => null,
            ];
        }
    }
}
