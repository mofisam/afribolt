<?php
namespace App\Models;

use App\Core\Model;

class AdminUser extends Model
{
    protected static string $table = 'admin_users';
    protected static bool $softDeletes = false;
}
