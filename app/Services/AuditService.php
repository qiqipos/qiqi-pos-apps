<?php

namespace App\Services;

/** Mencatat jejak perubahan data ke tabel audit_logs. */
class AuditService
{
    public static function log(string $action, string $table, ?int $recordId, ?array $old = null, ?array $new = null): void
    {
        try {
            unset($old['password_hash'], $new['password_hash'], $new['password']);
            db_connect()->table('audit_logs')->insert([
                'user_id'    => current_user('id'),
                'action'     => $action,
                'table_name' => $table,
                'record_id'  => $recordId,
                'old_data'   => $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
                'new_data'   => $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
                'ip_address' => service('request')->getIPAddress(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Audit log gagal: ' . $e->getMessage());
        }
    }
}
