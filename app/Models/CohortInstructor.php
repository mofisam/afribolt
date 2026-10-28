<?php
namespace App\Models;

use App\Core\Model;

class CohortInstructor extends Model
{
    protected static string $table = 'cohort_instructors';
    protected static bool $softDeletes = false;
}
