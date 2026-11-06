<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{Course, CourseAccess, User};

/** Who may open a course's lessons, and how someone gets access (free, purchase, admin grant, or via a live cohort). */
class CourseAccessService
{
    /** @return string|null 'purchase'|'free'|'admin_grant'|'cohort' when the user may learn this course now */
    public function source(int $userId, int $courseId): ?string
    {
        $row = Database::one("SELECT source FROM course_access WHERE user_id = ? AND course_id = ? AND status = 'active'", [$userId, $courseId]);
        // A 'cohort' row only keeps working while the live enrolment is still valid
        if ($row && $row['source'] !== 'cohort') { return $row['source']; }
        $viaCohort = Database::value(
            "SELECT COUNT(*) FROM courses c JOIN cohorts h ON h.course_id = c.id JOIN enrolments e ON e.cohort_id = h.id
             WHERE c.id = ? AND c.cohort_includes_access = 1 AND c.lessons_count > 0 AND e.student_id = ? AND e.status IN ('confirmed','completed')", [$courseId, $userId]);
        return $viaCohort ? 'cohort' : null;
    }

    /** Insert or re-activate an access row. */
    public function grant(int $userId, int $courseId, string $source, float $paid = 0, string $currency = 'NGN'): int
    {
        $row = Database::one('SELECT * FROM course_access WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
        if ($row) {
            CourseAccess::update((int) $row['id'], ['status' => 'active', 'source' => $source, 'enrolled_at' => $row['enrolled_at'] ?? utc_now(),
                'amount_paid' => $paid > 0 ? $paid : $row['amount_paid'], 'currency' => $currency]);
            $id = (int) $row['id'];
        } else {
            $id = CourseAccess::create(['user_id' => $userId, 'course_id' => $courseId, 'source' => $source, 'status' => 'active',
                'amount_paid' => $paid, 'currency' => $currency, 'enrolled_at' => utc_now()]);
        }
        CourseCatalogService::refresh($courseId);
        return $id;
    }

    /** Make sure a cohort student has a row so progress/last-lesson can be remembered. */
    public function ensureRow(int $userId, int $courseId, string $source): array
    {
        $row = Database::one('SELECT * FROM course_access WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
        if (!$row) {
            $id = CourseAccess::create(['user_id' => $userId, 'course_id' => $courseId, 'source' => $source, 'status' => 'active', 'enrolled_at' => utc_now()]);
            $row = CourseAccess::find($id);
        }
        return $row;
    }

    /**
     * Start enrolment from the course page.
     * @return array{type:string, url?:string, message?:string}  type: already | free | pay | error
     */
    public function startEnrol(array $student, array $course): array
    {
        if ($this->source((int) $student['id'], (int) $course['id'])) { return ['type' => 'already']; }
        if (!in_array($course['delivery_mode'], ['self_paced', 'both'], true) || (int) $course['lessons_count'] < 1) {
            return ['type' => 'error', 'message' => 'This course is not available for self-paced learning yet.'];
        }
        $price = Course::priceNow($course);
        if ($price <= 0) {
            $this->grant((int) $student['id'], (int) $course['id'], 'free');
            (new NotificationService())->toStudent((int) $student['id'], 'course_access', 'You\'re enrolled', "You can start {$course['title']} now.", 'course', (int) $course['id'], '/learn/' . $course['slug']);
            return ['type' => 'free'];
        }
        $row = Database::one('SELECT * FROM course_access WHERE user_id = ? AND course_id = ?', [(int) $student['id'], (int) $course['id']]);
        if ($row) { CourseAccess::update((int) $row['id'], ['status' => 'pending', 'source' => 'purchase']); }
        else { $row = CourseAccess::find(CourseAccess::create(['user_id' => (int) $student['id'], 'course_id' => (int) $course['id'], 'source' => 'purchase', 'status' => 'pending', 'currency' => $course['price_currency']])); }
        return ['type' => 'pay', 'url' => (new PaymentService())->initiateCourse($row, $course, $student, $price)];
    }

    /** Called inside the payment transaction once the provider has confirmed the money. */
    public function activate(int $accessId, float $amount, string $currency): void
    {
        $a = CourseAccess::find($accessId);
        if (!$a) { return; }
        CourseAccess::update($accessId, ['status' => 'active', 'source' => 'purchase', 'enrolled_at' => $a['enrolled_at'] ?? utc_now(),
            'amount_paid' => round((float) $a['amount_paid'] + $amount, 2), 'currency' => $currency]);
        CourseCatalogService::refresh((int) $a['course_id']);
        $course = Course::find((int) $a['course_id']);
        $student = User::find((int) $a['user_id']);
        (new NotificationService())->toStudent((int) $a['user_id'], 'course_access', 'Payment confirmed', "You now have full access to {$course['title']}.", 'course', (int) $a['course_id'], '/learn/' . $course['slug']);
        if ($student) {
            (new MailService())->queue((int) $student['id'], $student['email'], 'course-access', 'You\'re in: ' . $course['title'], ['student' => $student, 'course' => $course, 'amount' => $amount]);
        }
    }
}
