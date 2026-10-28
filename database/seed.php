<?php
/**
 * php database/seed.php            -> super admin + payment gateway row (required)
 * php database/seed.php --sample   -> also adds a sample category/course/cohort/sessions to click through
 */
require dirname(__DIR__) . '/cron/bootstrap.php';

use App\Models\{AdminUser, Cohort, ClassSession, Course, CourseCategory, PaymentGateway};

// 1) Super admin (credentials from .env)
$email = strtolower((string) env('SEED_ADMIN_EMAIL', 'admin@example.com'));
$password = (string) env('SEED_ADMIN_PASSWORD', '');
if ($password === '' || strlen($password) < 8) { exit("Set SEED_ADMIN_PASSWORD (8+ chars) in .env first.\n"); }
if (!AdminUser::findBy('email', $email)) {
    AdminUser::create(['first_name' => 'Super', 'last_name' => 'Admin', 'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'admin_role' => 'super_admin']);
    echo "Created super admin {$email}\n";
} else { echo "Admin {$email} already exists\n"; }

// 2) Gateway row (webhook events + payments reference it). Keys stay in .env, not the DB.
if (!PaymentGateway::findBy('provider_code', 'paystack')) {
    PaymentGateway::create(['provider_code' => 'paystack', 'display_name' => 'Paystack', 'is_enabled' => 1, 'is_test_mode' => 1,
        'supported_currencies' => json_encode(['NGN'])]);
    echo "Created payment gateway row (paystack)\n";
}

if (!in_array('--sample', $argv ?? [], true)) { exit("Done.\n"); }

// 3) Sample catalogue (clearly fake, delete before launch)
$cat = CourseCategory::findBy('slug', 'technology') ?? CourseCategory::find(CourseCategory::create(['name' => 'Technology', 'slug' => 'technology', 'display_order' => 1]));
$course = Course::findBy('slug', 'introduction-to-web-development');
if (!$course) {
    $cid = Course::create(['category_id' => (int) $cat['id'], 'title' => 'Introduction to Web Development (SAMPLE)', 'slug' => 'introduction-to-web-development',
        'short_description' => 'Sample course. Replace with the approved programme description.', 'description' => "Sample description.\nHTML, CSS and JavaScript fundamentals.",
        'learning_objectives' => "Build a responsive page\nUnderstand how the web works", 'prerequisites' => 'A laptop and a reliable internet connection.',
        'default_duration_weeks' => 4, 'course_level' => 'beginner', 'status' => 'published', 'published_at' => utc_now()]);
    $course = Course::find($cid);
}
if (!Cohort::findBy('cohort_code', 'WEB-SAMPLE-1')) {
    $start = (new DateTimeImmutable('next monday 10:00', new DateTimeZone('Africa/Lagos')))->modify('+3 weeks');
    $chId = Cohort::create([
        'course_id' => (int) $course['id'], 'cohort_name' => 'Sample Cohort', 'cohort_code' => 'WEB-SAMPLE-1',
        'summary' => 'Sample cohort for testing the registration and countdown flow.',
        'start_at' => local_to_utc($start->format('Y-m-d H:i'), 'Africa/Lagos'),
        'end_at' => local_to_utc($start->modify('+4 weeks')->format('Y-m-d H:i'), 'Africa/Lagos'),
        'registration_opens_at' => utc_now(), 'registration_closes_at' => local_to_utc($start->modify('-2 days')->format('Y-m-d H:i'), 'Africa/Lagos'),
        'capacity' => 30, 'waitlist_enabled' => 1, 'fee_amount' => 25000, 'currency' => 'NGN', 'require_payment' => 1,
        'status' => 'open', 'meeting_provider_default' => 'google_meet', 'published_at' => utc_now(),
    ]);
    for ($i = 0; $i < 4; $i++) {
        $s = $start->modify("+{$i} weeks");
        ClassSession::create(['cohort_id' => $chId, 'title' => 'Week ' . ($i + 1), 'session_number' => $i + 1,
            'starts_at' => local_to_utc($s->format('Y-m-d H:i'), 'Africa/Lagos'),
            'ends_at' => local_to_utc($s->modify('+2 hours')->format('Y-m-d H:i'), 'Africa/Lagos'),
            'meeting_provider' => 'google_meet', 'meeting_url' => 'https://meet.google.com/sample-link', 'join_early_minutes' => 10]);
    }
    echo "Created sample course + cohort WEB-SAMPLE-1 with 4 sessions\n";
}
echo "Done.\n";
