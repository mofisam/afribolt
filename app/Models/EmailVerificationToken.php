<?php
namespace App\Models;

use App\Core\Model;

class EmailVerificationToken extends Model
{
    protected static string $table = 'email_verification_tokens';
    protected static bool $softDeletes = false;
}
