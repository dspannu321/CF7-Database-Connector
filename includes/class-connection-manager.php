<?php
/**
 * Builds PDO connections to external MySQL and discovers tables/columns.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Connection_Manager {

    /**
     * Builds a PDO instance from a connection array (keys: db_host, db_port, db_name, db_user, db_pass).
     *
     * @param array{db_host: string, db_port: int|string, db_name: string, db_user: string, db_pass: string} $connection
     * @return PDO
     * @throws PDOException On connection failure.
     */
    public function connect(array $connection): PDO {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $connection['db_host'],
            (int) $connection['db_port'],
            $connection['db_name']
        );

        $pdo = new PDO(
            $dsn,
            $connection['db_user'],
            $connection['db_pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES    => false,
            ]
        );

        return $pdo;
    }

    /**
     * Tests a connection. Returns a result array suitable for admin notices (no password in message).
     *
     * @param array{db_host: string, db_port: int|string, db_name: string, db_user: string, db_pass: string} $connection
     * @return array{success: bool, message: string}
     */
    public function test_connection(array $connection): array {
        try {
            $this->connect($connection);
            return ['success' => true, 'message' => __('Connection successful.', 'formbridge')];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $this->get_safe_error_message($e),
            ];
        }
    }

    /**
     * Returns list of table names for the given connection.
     *
     * @param array{db_host: string, db_port: int|string, db_name: string, db_user: string, db_pass: string} $connection
     * @return array<int, string>
     */
    public function get_tables(array $connection): array {
        try {
            $pdo = $this->connect($connection);
            $stmt = $pdo->query('SHOW TABLES');
            if ($stmt === false) {
                return [];
            }
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return is_array($rows) ? array_values($rows) : [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Returns column names for a table. Table name must exist in discovered schema (call get_tables first).
     *
     * @param array{db_host: string, db_port: int|string, db_name: string, db_user: string, db_pass: string} $connection
     * @return array<int, string> Column names.
     */
    public function get_columns(array $connection, string $table): array {
        $tables = $this->get_tables($connection);
        if (!in_array($table, $tables, true)) {
            return [];
        }

        try {
            $pdo = $this->connect($connection);
            $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
            if ($stmt === false) {
                return [];
            }
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return is_array($rows) ? array_values($rows) : [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Checks if a table exists in the connection's database.
     *
     * @param array{db_host: string, db_port: int|string, db_name: string, db_user: string, db_pass: string} $connection
     */
    public function table_exists(array $connection, string $table): bool {
        $tables = $this->get_tables($connection);
        return in_array($table, $tables, true);
    }

    /**
     * Returns column names for a table (alias for get_columns for clarity in validation flows).
     *
     * @param array{db_host: string, db_port: int|string, db_name: string, db_user: string, db_pass: string} $connection
     * @return array<int, string>
     */
    public function get_valid_columns(array $connection, string $table): array {
        return $this->get_columns($connection, $table);
    }

    /**
     * Returns a safe error message for display (no credentials).
     */
    private function get_safe_error_message(PDOException $e): string {
        $msg = $e->getMessage();

        // Remove common credential leaks.
        $msg = preg_replace('/\bpassword[=\s][^\s]+/i', 'password=***', $msg);
        $msg = preg_replace('/\buser[=\s][^\s]+/i', 'user=***', $msg);
        $msg = preg_replace('/\bhost[=\s][^\s]+/i', 'host=***', $msg);

        if (strlen($msg) > 200) {
            $msg = substr($msg, 0, 197) . '...';
        }

        return $msg ?: __('Connection failed.', 'formbridge');
    }
}
