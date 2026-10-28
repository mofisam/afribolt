<?php
namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    protected static string $table = 'audit_logs';
    protected static bool $softDeletes = false;
}
