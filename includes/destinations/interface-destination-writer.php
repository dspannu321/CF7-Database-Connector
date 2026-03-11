<?php
/**
 * Interface for destination writers (e.g. MySQL, webhook).
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

interface FormBridge_Destination_Writer {

    /**
     * Returns the destination identifier (e.g. 'mysql').
     */
    public function get_key(): string;

    /**
     * Writes the mapped payload to the destination.
     *
     * @param array<string, mixed> $payload Associative array of column => value.
     * @param array<string, mixed> $config Destination config including connection and table.
     * @return array{success: bool, message: string, insert_id: int|null}
     */
    public function write(array $payload, array $config): array;
}
