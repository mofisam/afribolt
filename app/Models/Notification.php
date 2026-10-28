<?php
namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static bool $softDeletes = false;
}
