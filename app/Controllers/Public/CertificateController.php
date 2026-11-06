<?php
namespace App\Controllers\Public;

use App\Core\{Controller, Database};

class CertificateController extends Controller
{
    /** Anyone with the code can verify a certificate (e.g. an employer). */
    public function show(string $code): void
    {
        $row = Database::one("SELECT ca.*, u.first_name, u.last_name, c.title AS course_title, c.total_seconds, c.language,
                CONCAT(i.first_name, ' ', i.last_name) AS instructor_name
            FROM course_access ca JOIN users u ON u.id = ca.user_id JOIN courses c ON c.id = ca.course_id LEFT JOIN instructors i ON i.id = c.instructor_id
            WHERE ca.certificate_code = ? AND ca.completed_at IS NOT NULL AND ca.status = 'active'", [$code]) ?? $this->abort(404);
        $this->view('public/certificate', ['title' => 'Certificate of completion', 'c' => $row], null);
    }
}
