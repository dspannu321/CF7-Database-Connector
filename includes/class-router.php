<?php
/**
 * Receives normalized submissions, finds mapping, runs engine and writer, logs result.
 *
 * @package FormBridge
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class FormBridge_Router {

    private FormBridge_Mapping_Repository $mapping_repository;
    private FormBridge_Connection_Repository $connection_repository;
    private FormBridge_Mapping_Engine $mapping_engine;
    private FormBridge_Destination_Writer $writer;
    private FormBridge_Logger $logger;

    public function __construct(
        FormBridge_Mapping_Repository $mapping_repository,
        FormBridge_Connection_Repository $connection_repository,
        FormBridge_Mapping_Engine $mapping_engine,
        FormBridge_Destination_Writer $writer,
        FormBridge_Logger $logger
    ) {
        $this->mapping_repository   = $mapping_repository;
        $this->connection_repository = $connection_repository;
        $this->mapping_engine       = $mapping_engine;
        $this->writer               = $writer;
        $this->logger               = $logger;
    }

    /**
     * Routes a normalized submission: find mapping, transform, write, log.
     *
     * @param array{source: string, form_id: int, form_title: string, submitted_at: string, fields: array<string, mixed>, meta: array} $normalized
     * @return array{success: bool, message: string}
     */
    public function route(array $normalized): array {
        $source  = $normalized['source'] ?? '';
        $form_id = (int) ($normalized['form_id'] ?? 0);

        if ($source === '' || $form_id <= 0) {
            $this->log_skipped($normalized, null, __('Invalid submission: missing source or form_id.', 'formbridge'));
            return ['success' => false, 'message' => __('Invalid submission.', 'formbridge')];
        }

        $mapping = $this->mapping_repository->get_active_by_source_and_form($source, $form_id);
        if (!$mapping) {
            $this->log_skipped($normalized, null, __('No active mapping found for this form.', 'formbridge'));
            return ['success' => true, 'message' => __('No mapping configured.', 'formbridge')];
        }

        $connection = $this->connection_repository->get_by_id((int) $mapping['connection_id']);
        if (!$connection) {
            $this->log_failed($normalized, $mapping, __('Connection not found.', 'formbridge'));
            return ['success' => false, 'message' => __('Connection not found.', 'formbridge')];
        }

        $field_map_json = $mapping['field_map'] ?? '{}';
        $field_map      = json_decode($field_map_json, true);
        if (!is_array($field_map)) {
            $field_map = [];
        }

        $payload = $this->mapping_engine->map($normalized, $field_map);
        if (empty($payload)) {
            $this->log_skipped($normalized, $mapping, __('No fields mapped; payload empty.', 'formbridge'));
            return ['success' => false, 'message' => __('No fields mapped.', 'formbridge')];
        }

        $config = [
            'connection' => $connection,
            'table'      => (string) $mapping['destination_table'],
        ];

        $result = $this->writer->write($payload, $config);

        $this->logger->log([
            'source_type'        => $source,
            'form_id'            => $form_id,
            'mapping_id'         => (int) $mapping['id'],
            'destination_type'   => (string) ($mapping['destination_type'] ?? 'mysql'),
            'destination_table'  => (string) $mapping['destination_table'],
            'payload'            => $payload,
            'status'             => $result['success'] ? 'success' : 'failed',
            'message'            => $result['message'],
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
        ];
    }

    private function log_skipped(array $normalized, ?array $mapping, string $message): void {
        $this->logger->log([
            'source_type'        => (string) ($normalized['source'] ?? ''),
            'form_id'            => (int) ($normalized['form_id'] ?? 0),
            'mapping_id'         => $mapping ? (int) $mapping['id'] : null,
            'destination_type'   => $mapping ? (string) ($mapping['destination_type'] ?? 'mysql') : 'mysql',
            'destination_table'  => $mapping ? (string) ($mapping['destination_table'] ?? '') : '',
            'payload'            => $normalized['fields'] ?? null,
            'status'             => 'skipped',
            'message'            => $message,
        ]);
    }

    private function log_failed(array $normalized, array $mapping, string $message): void {
        $this->logger->log([
            'source_type'        => (string) ($normalized['source'] ?? ''),
            'form_id'            => (int) ($normalized['form_id'] ?? 0),
            'mapping_id'         => (int) $mapping['id'],
            'destination_type'   => (string) ($mapping['destination_type'] ?? 'mysql'),
            'destination_table'  => (string) $mapping['destination_table'],
            'payload'            => $normalized['fields'] ?? null,
            'status'             => 'failed',
            'message'            => $message,
        ]);
    }
}
