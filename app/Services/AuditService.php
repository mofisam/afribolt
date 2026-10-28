<?php
namespace App\Services;

use App\Core\{Auth, Request};
use App\Models\AuditLog;

/** Record fee / date / link / registration-setting / payment changes (requirements: Auditability). */
class AuditService
{
    public static function log(string $action, string $entityType, ?int $entityId, ?array $old = null, ?array $new = null): void
    {
        $r = Request::capture();
        AuditLog::create([
            'admin_user_id' => Auth::id('admin'),
            'instructor_id' => Auth::id('instructor'),
            'action' => $action, 'entity_type' => $entityType, 'entity_id' => $entityId,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => $r->ip(), 'user_agent' => $r->userAgent(),
        ]);
    }
}
