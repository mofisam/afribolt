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

// 4) Sample SELF-PACED course (needs migration 003). Add a video lesson yourself from Admin > Courses > Curriculum.
use App\Models\{CourseSection, Lesson, QuizOption, QuizQuestion};
use App\Services\CourseCatalogService;
if (!Course::findBy('slug', 'digital-skills-foundations')) {
    $cid = Course::create(['category_id' => (int) $cat['id'], 'title' => 'Digital Skills Foundations (SAMPLE)', 'slug' => 'digital-skills-foundations',
        'short_description' => 'Sample self-paced course. Learn the basics at your own pace.', 'description' => "## About this course\nThis is **sample content**. Replace it with your own.",
        'learning_objectives' => "Understand how the internet works\nUse online tools safely\nSpot common scams", 'prerequisites' => 'No experience needed.',
        'course_level' => 'beginner', 'delivery_mode' => 'self_paced', 'price' => 15000, 'price_currency' => 'NGN', 'promo_price' => 9999,
        'promo_ends_at' => gmdate('Y-m-d H:i:s', time() + 7 * 86400), 'status' => 'published', 'published_at' => utc_now()]);
    $s1 = CourseSection::create(['course_id' => $cid, 'title' => 'Getting started', 'position' => 1]);
    $s2 = CourseSection::create(['course_id' => $cid, 'title' => 'Staying safe online', 'position' => 2]);
    Lesson::create(['course_id' => $cid, 'section_id' => $s1, 'title' => 'Welcome', 'lesson_type' => 'article', 'duration_seconds' => 120, 'is_preview' => 1, 'status' => 'published', 'position' => 1,
        'content' => "# Welcome\nIn this course you will learn the basics, step by step.\n\n- Learn at your own pace\n- Finish each lesson to track your progress\n\nRead more at [our site](https://example.com)."]);
    Lesson::create(['course_id' => $cid, 'section_id' => $s1, 'title' => 'How the internet works', 'lesson_type' => 'article', 'duration_seconds' => 300, 'status' => 'published', 'position' => 2,
        'content' => "## Browsers and servers\nWhen you open a website, your **browser** asks a **server** for the page.\n\n1. You type an address\n2. Your browser finds the server\n3. The server sends the page back"]);
    $q = Lesson::create(['course_id' => $cid, 'section_id' => $s2, 'title' => 'Check your knowledge', 'lesson_type' => 'quiz', 'duration_seconds' => 0, 'status' => 'published', 'position' => 1]);
    foreach ([['What does a browser do?', 'It requests and shows web pages.', ['Requests and shows web pages', 'Stores your emails', 'Makes the internet faster']],
              ['Which is the strongest password habit?', 'Unique passwords stop one leak from exposing every account.', ['Reuse one password everywhere', 'Use a unique password for each account', 'Share it with friends']]] as $i => [$text, $why, $opts]) {
        $qid = QuizQuestion::create(['lesson_id' => $q, 'question' => $text, 'explanation' => $why, 'position' => $i + 1]);
        foreach ($opts as $n => $o) { QuizOption::create(['question_id' => $qid, 'option_text' => $o, 'is_correct' => ($i === 0 ? $n === 0 : $n === 1) ? 1 : 0, 'position' => $n + 1]); }
    }
    CourseCatalogService::refresh($cid);
    echo "Created sample self-paced course (digital-skills-foundations)\n";
}

// 5) Sample jobs + a disabled Jooble source (needs migration 004). Sample jobs link to example.com: delete them before launch.
use App\Models\{Job, JobSource};
use App\Services\Jobs\JobCleaner;
if (!JobSource::findBy('name', 'Jooble Nigeria')) {
    JobSource::create(['name' => 'Jooble Nigeria', 'source_type' => 'jooble', 'config' => json_encode(['keywords' => ['developer', 'accountant', 'customer service', 'marketing', 'graduate trainee'], 'locations' => ['Nigeria', 'Lagos', 'Abuja'], 'pages' => 2]),
        'permission_note' => 'Official Jooble API (needs JOOBLE_API_KEY). Enable after adding the key.', 'fetch_interval_minutes' => 240, 'is_enabled' => 0]);
    echo "Created job source: Jooble Nigeria (disabled; add JOOBLE_API_KEY then enable)\n";
}
if (!Job::findBy('slug', 'junior-php-developer-sample-employer')) {
    $cats = array_column(\App\Core\Database::all('SELECT id, slug FROM job_categories'), 'id', 'slug');
    foreach ([
        ['Junior PHP Developer', 'software-it', 'Lagos', 'Ikeja', 'hybrid', 'full_time', 'entry', ['PHP', 'MySQL', 'JavaScript'], 'Build and maintain internal web tools with a small engineering team. Good fit for a recent graduate who knows PHP and SQL.', 350000, 500000],
        ['Customer Support Representative', 'customer-service', 'FCT', 'Abuja', 'onsite', 'full_time', 'entry', ['Communication', 'CRM', 'Excel'], 'Handle customer enquiries by phone, email and chat, and keep accurate records. Training is provided.', 150000, 220000],
        ['Digital Marketing Executive', 'marketing-sales', 'Lagos', 'Lekki', 'remote', 'contract', 'mid', ['SEO', 'Social media', 'Google Analytics'], 'Plan and run online campaigns for several brands and report on results every month.', null, null],
        ['Data Analyst Intern', 'data-analytics', 'Oyo', 'Ibadan', 'hybrid', 'internship', 'entry', ['Excel', 'SQL', 'Power BI'], 'Support the analytics team by cleaning data and building simple dashboards. A three-month paid internship.', 80000, 100000],
        ['Accountant', 'finance-accounting', 'Rivers', 'Port Harcourt', 'onsite', 'full_time', 'mid', ['Bookkeeping', 'Tax', 'Excel'], 'Prepare monthly accounts, reconcile bank statements and support the annual audit.', 250000, 350000],
        ['Graphic Designer', 'design-creative', 'Lagos', 'Yaba', 'remote', 'part_time', 'mid', ['Figma', 'Photoshop', 'Branding'], 'Design social posts, brochures and presentation slides for a growing training company.', null, null],
    ] as $n => [$title, $cat, $state, $city, $mode, $type, $level, $skills, $summary, $min, $max]) {
        Job::create(['slug' => Job::uniqueSlug($title, 'Sample Employer'), 'canonical_hash' => JobCleaner::canonical($title, 'Sample Employer', $state), 'title' => $title, 'company' => 'Sample Employer',
            'category_id' => $cats[$cat] ?? null, 'location_text' => $city . ', ' . $state, 'state' => $state === 'FCT' ? 'FCT' : $state, 'city' => $city, 'work_mode' => $mode, 'employment_type' => $type, 'experience_level' => $level,
            'salary_min' => $min, 'salary_max' => $max, 'salary_currency' => $min ? 'NGN' : null, 'summary' => $summary, 'skills' => json_encode($skills), 'source_url' => 'https://example.com/jobs/sample-' . ($n + 1),
            'source_name' => 'Sample', 'posted_at' => gmdate('Y-m-d H:i:s', time() - $n * 86400), 'last_seen_at' => utc_now(), 'status' => 'published', 'ai_status' => 'skipped', 'quality_score' => 80, 'scam_risk' => 0,
            'published_at' => utc_now()]);
    }
    echo "Created 6 sample jobs\n";
}
echo "Done.\n";
