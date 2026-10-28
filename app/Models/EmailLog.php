<?php
namespace App\Models;

use App\Core\Model;

class EmailLog extends Model
{
    protected static string $table = 'email_logs';
    protected static bool $softDeletes = false;
}
