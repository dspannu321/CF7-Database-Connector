<?php
/**
 * Standardizes and persists sync attempt logs. Never logs credentials.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Logger {

    private FormBridge_Log_Repository $log_repository;

    public function __construct(FormBridge_Log_Repository $log_repository) {
        $this->log_repository = $log_repository;
    }

    /**
     * Logs a sync attempt. Payload must not contain credentials.
     *
     * @param array{source_type: string, form_id: int, mapping_id: int|null, destination_type: string, destination_table: string, payload: array<string, mixed>|string|null, status: string, message: string|null} $data
     * @return int|false Inserted log ID or false.
     */
    public function log(array $data): int|false {
        $payload = $data['payload'] ?? null;
        if (is_array($payload)) {
            $payload = formbridge_json_encode($payload);
        }

        return $this->log_repository->insert([
            'source_type'        => $data['source_type'],
            'form_id'            => (int) $data['form_id'],
            'mapping_id'         => isset($data['mapping_id']) ? (int) $data['mapping_id'] : null,
            'destination_type'   => $data['destination_type'],
            'destination_table'  => $data['destination_table'],
            'payload'            => $payload,
            'status'             => $data['status'],
            'message'            => isset($data['message']) ? $data['message'] : null,
        ]);
    }
}
