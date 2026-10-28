<?php
namespace App\Models;

use App\Core\Model;

class PasswordResetToken extends Model
{
    protected static string $table = 'password_reset_tokens';
    protected static bool $softDeletes = false;
}
