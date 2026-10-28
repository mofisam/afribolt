<?php
namespace App\Models;

use App\Core\Model;

class CourseCategory extends Model
{
    protected static string $table = 'course_categories';
    protected static bool $softDeletes = false;
}
