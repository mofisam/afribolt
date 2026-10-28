<?php
namespace App\Models;

use App\Core\{Database, Model};

class Payment extends Model
{
    protected static string $table = 'payments';

    public static function findByReference(string $reference): ?array
    {
        return self::findBy('internal_reference', $reference);
    }

    public static function lockByReference(string $reference): ?array
    {
        return Database::one('SELECT * FROM payments WHERE internal_reference = ? FOR UPDATE', [$reference]);
    }

    public static function forEnrolment(int $enrolmentId): array
    {
        return Database::all('SELECT * FROM payments WHERE enrolment_id = ? ORDER BY created_at DESC', [$enrolmentId]);
    }
}
