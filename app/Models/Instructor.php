<?php
namespace App\Models;

use App\Core\Model;

class Instructor extends Model
{
    protected static string $table = 'instructors';
    protected static bool $softDeletes = false;
}
