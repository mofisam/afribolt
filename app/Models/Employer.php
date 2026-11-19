<?php
namespace App\Models;

use App\Core\Model;

class Employer extends Model
{
    protected static string $table = 'employers';
    protected static bool $softDeletes = false;
}
