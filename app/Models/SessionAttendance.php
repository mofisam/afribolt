<?php
namespace App\Models;

use App\Core\Model;

class SessionAttendance extends Model
{
    protected static string $table = 'session_attendance';
    protected static bool $softDeletes = false;
}
