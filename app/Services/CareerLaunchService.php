<?php
namespace App\Services;

use App\Core\Database;

/** Career Launch programme: stage copy (shared by the public page, dashboard and admin) and small helpers. */
class CareerLaunchService
{
    /** [title, what it is, done when] */
    public const STAGES = [
        ['Register', 'Create your account and complete a short intake form: target role, experience, current CV and LinkedIn link.', 'your profile is complete'],
        ['Enrol in the course', 'Learn at your own pace on the Afribolt platform, with a certificate at the end.', 'you finish the course'],
        ['Live projects', 'Work in a team on supervised projects built from real or realistic Nigerian business briefs, and get your work assessed.', 'you complete and pass the assigned projects'],
        ['Full Accelerator', 'Own a project yourself over 3 to 6 months, with mentor check-ins along the way. You leave with a verified project record.', 'your assigned projects are complete. This unlocks reference eligibility'],
        ['Interview Ready', 'Three mock interviews, behavioural and role-specific, with written feedback, plus a job application strategy session.', 'you complete all three mock interviews'],
        ['Job Ready', 'A revamped CV built from your verified project record, a LinkedIn rewrite, a cover letter and your Afribolt reference letter.', 'your CV and reference letter are issued'],
    ];

    public const STATUS_LABELS = ['new' => 'Registered', 'payment_sent' => 'Payment link sent', 'paid' => 'Active', 'cancelled' => 'Cancelled'];

    /** The signed-in student's registration, or null (also null if the migration has not been run yet). */
    public static function forUser(int $userId): ?array
    {
        try { return Database::one('SELECT * FROM career_launch_registrations WHERE user_id = ?', [$userId]); }
        catch (\Throwable $e) { return null; }
    }

    /** Tell the student (bell + notifications page) when something changes on their Career Launch. */
    public static function notify(array $reg, string $title, string $message): void
    {
        if (!empty($reg['user_id'])) {
            (new NotificationService())->toStudent((int) $reg['user_id'], 'career_launch', $title, $message, 'career_launch', (int) $reg['id'], '/student/dashboard');
        }
    }
}
