<?php
use App\Controllers\Admin as A;
use App\Controllers\Instructor as I;
use App\Middleware\{AdminAuth, InstructorAuth};

// ---- Admin ----
$router->get('/admin/login', [A\AuthController::class, 'show']);
$router->post('/admin/login', [A\AuthController::class, 'submit']);

$router->group(['prefix' => '/admin', 'middleware' => [AdminAuth::class]], function ($r) {
    $r->get('/', [A\DashboardController::class, 'index']);
    $r->post('/logout', [A\AuthController::class, 'logout']);

    // Categories
    $r->get('/categories', [A\CategoryController::class, 'index']);
    $r->get('/categories/create', [A\CategoryController::class, 'create']);
    $r->post('/categories', [A\CategoryController::class, 'store']);
    $r->get('/categories/{id}/edit', [A\CategoryController::class, 'edit']);
    $r->post('/categories/{id}', [A\CategoryController::class, 'update']);
    $r->post('/categories/{id}/toggle', [A\CategoryController::class, 'toggle']);

    // Courses
    $r->get('/courses', [A\CourseController::class, 'index']);
    $r->get('/courses/create', [A\CourseController::class, 'create']);
    $r->post('/courses', [A\CourseController::class, 'store']);
    $r->get('/courses/{id}/edit', [A\CourseController::class, 'edit']);
    $r->post('/courses/{id}', [A\CourseController::class, 'update']);
    $r->post('/courses/{id}/status', [A\CourseController::class, 'status']);

    // Curriculum builder (self-paced)
    $r->get('/courses/{id}/curriculum', [A\CurriculumController::class, 'index']);
    $r->post('/courses/{id}/sections', [A\CurriculumController::class, 'addSection']);
    $r->get('/courses/{id}/lessons/create', [A\CurriculumController::class, 'createLesson']);
    $r->post('/courses/{id}/lessons', [A\CurriculumController::class, 'storeLesson']);
    $r->post('/sections/{id}', [A\CurriculumController::class, 'renameSection']);
    $r->post('/sections/{id}/move', [A\CurriculumController::class, 'moveSection']);
    $r->post('/sections/{id}/delete', [A\CurriculumController::class, 'deleteSection']);
    $r->get('/lessons/{id}/edit', [A\CurriculumController::class, 'editLesson']);
    $r->post('/lessons/{id}', [A\CurriculumController::class, 'updateLesson']);
    $r->post('/lessons/{id}/move', [A\CurriculumController::class, 'moveLesson']);
    $r->post('/lessons/{id}/delete', [A\CurriculumController::class, 'deleteLesson']);
    $r->post('/lessons/{id}/resources', [A\CurriculumController::class, 'uploadResource']);
    $r->post('/resources/{id}/delete', [A\CurriculumController::class, 'deleteResource']);
    $r->post('/lessons/{id}/questions', [A\CurriculumController::class, 'addQuestion']);
    $r->post('/questions/{id}/delete', [A\CurriculumController::class, 'deleteQuestion']);

    // Jobs board
    $r->get('/jobs', [A\JobController::class, 'index']);
    $r->get('/jobs/create', [A\JobController::class, 'create']);
    $r->get('/jobs/reports', [A\JobController::class, 'reports']);
    $r->post('/jobs/process-ai', [A\JobController::class, 'processAi']);
    $r->post('/jobs', [A\JobController::class, 'store']);
    $r->get('/jobs/{id}/review', [A\JobController::class, 'review']);
    $r->post('/jobs/{id}', [A\JobController::class, 'update']);
    $r->post('/jobs/{id}/status', [A\JobController::class, 'status']);
    $r->post('/job-reports/{id}/resolve', [A\JobController::class, 'resolveReport']);
    $r->get('/job-sources', [A\JobSourceController::class, 'index']);
    $r->get('/job-sources/create', [A\JobSourceController::class, 'create']);
    $r->post('/job-sources', [A\JobSourceController::class, 'store']);
    $r->get('/job-sources/{id}/edit', [A\JobSourceController::class, 'edit']);
    $r->post('/job-sources/{id}', [A\JobSourceController::class, 'update']);
    $r->post('/job-sources/{id}/toggle', [A\JobSourceController::class, 'toggle']);
    $r->post('/job-sources/{id}/run', [A\JobSourceController::class, 'run']);
    $r->post('/job-sources/{id}/test', [A\JobSourceController::class, 'test']);

    // Reviews + self-paced learners
    $r->get('/reviews', [A\ReviewController::class, 'index']);
    $r->post('/reviews/{id}/status', [A\ReviewController::class, 'status']);
    $r->get('/learners', [A\LearnerController::class, 'index']);
    $r->post('/learners/grant', [A\LearnerController::class, 'grant']);
    $r->post('/learners/{id}/revoke', [A\LearnerController::class, 'revoke']);
    $r->post('/learners/{id}/restore', [A\LearnerController::class, 'restore']);

    // Cohorts
    $r->get('/cohorts', [A\CohortController::class, 'index']);
    $r->get('/cohorts/create', [A\CohortController::class, 'create']);
    $r->post('/cohorts', [A\CohortController::class, 'store']);
    $r->get('/cohorts/{id}', [A\CohortController::class, 'show']);
    $r->get('/cohorts/{id}/edit', [A\CohortController::class, 'edit']);
    $r->post('/cohorts/{id}', [A\CohortController::class, 'update']);
    $r->post('/cohorts/{id}/status', [A\CohortController::class, 'status']);
    $r->post('/cohorts/{id}/instructors', [A\CohortController::class, 'assignInstructor']);
    $r->post('/cohorts/{id}/instructors/{instructorId}/remove', [A\CohortController::class, 'removeInstructor']);

    // Class sessions
    $r->get('/sessions', [A\SessionController::class, 'index']);
    $r->get('/sessions/create', [A\SessionController::class, 'create']);
    $r->post('/sessions', [A\SessionController::class, 'store']);
    $r->get('/sessions/{id}/edit', [A\SessionController::class, 'edit']);
    $r->post('/sessions/{id}', [A\SessionController::class, 'update']);
    $r->post('/sessions/{id}/status', [A\SessionController::class, 'status']);

    // Instructors
    $r->get('/instructors', [A\InstructorController::class, 'index']);
    $r->get('/instructors/create', [A\InstructorController::class, 'create']);
    $r->post('/instructors', [A\InstructorController::class, 'store']);
    $r->get('/instructors/{id}/edit', [A\InstructorController::class, 'edit']);
    $r->post('/instructors/{id}', [A\InstructorController::class, 'update']);

    // Students, enrolments, payments
    $r->get('/students', [A\StudentController::class, 'index']);
    $r->get('/students/{id}', [A\StudentController::class, 'show']);
    $r->post('/students/{id}/status', [A\StudentController::class, 'status']);

    $r->get('/enrolments', [A\EnrolmentController::class, 'index']);
    $r->get('/enrolments/{id}', [A\EnrolmentController::class, 'show']);
    $r->post('/enrolments/{id}/action', [A\EnrolmentController::class, 'action']);

    $r->get('/payments', [A\PaymentController::class, 'index']);
    $r->get('/payments/{id}', [A\PaymentController::class, 'show']);
    $r->post('/payments/{id}/verify', [A\PaymentController::class, 'verify']);
    $r->post('/payments/{id}/refund', [A\PaymentController::class, 'refund']);

    // Announcements
    $r->get('/announcements', [A\AnnouncementController::class, 'index']);
    $r->get('/announcements/create', [A\AnnouncementController::class, 'create']);
    $r->post('/announcements', [A\AnnouncementController::class, 'store']);
    $r->get('/announcements/{id}/edit', [A\AnnouncementController::class, 'edit']);
    $r->post('/announcements/{id}', [A\AnnouncementController::class, 'update']);
    $r->post('/announcements/{id}/status', [A\AnnouncementController::class, 'status']);

    // Attendance, reports, logs
    $r->get('/attendance', [A\AttendanceController::class, 'index']);
    $r->post('/attendance', [A\AttendanceController::class, 'save']);
    $r->get('/reports', [A\ReportController::class, 'index']);
    $r->get('/reports/export', [A\ReportController::class, 'export']);
    $r->get('/audit-logs', [A\AuditLogController::class, 'index']);
    $r->get('/email-logs', [A\EmailLogController::class, 'index']);
    $r->post('/email-logs/{id}/retry', [A\EmailLogController::class, 'retry']);

    // Gateways + settings + admin accounts
    $r->get('/gateways', [A\GatewayController::class, 'index']);
    $r->get('/gateways/{id}/edit', [A\GatewayController::class, 'edit']);
    $r->post('/gateways/{id}', [A\GatewayController::class, 'update']);
    $r->get('/settings', [A\SettingsController::class, 'index']);
    $r->post('/settings', [A\SettingsController::class, 'save']);
    $r->post('/settings/add', [A\SettingsController::class, 'add']);
    $r->get('/admin-users', [A\AdminUserController::class, 'index']);
    $r->get('/admin-users/create', [A\AdminUserController::class, 'create']);
    $r->post('/admin-users', [A\AdminUserController::class, 'store']);
    $r->get('/admin-users/{id}/edit', [A\AdminUserController::class, 'edit']);
    $r->post('/admin-users/{id}', [A\AdminUserController::class, 'update']);
});

// ---- Instructor portal ----
$router->get('/instructor/login', [I\AuthController::class, 'show']);
$router->post('/instructor/login', [I\AuthController::class, 'submit']);
$router->group(['prefix' => '/instructor', 'middleware' => [InstructorAuth::class]], function ($r) {
    $r->get('/', [I\DashboardController::class, 'index']);
    $r->post('/logout', [I\AuthController::class, 'logout']);
    $r->get('/cohorts/{id}', [I\CohortController::class, 'show']);
    $r->get('/cohorts/{id}/roster.csv', [I\CohortController::class, 'rosterCsv']);
    $r->get('/sessions/{id}/edit', [I\CohortController::class, 'editSession']);
    $r->post('/sessions/{id}', [I\CohortController::class, 'updateSession']);
    $r->post('/sessions/{id}/status', [I\CohortController::class, 'sessionStatus']);
    $r->get('/sessions/{id}/attendance', [I\CohortController::class, 'attendance']);
    $r->post('/sessions/{id}/attendance', [I\CohortController::class, 'saveAttendance']);
    $r->get('/announcements', [I\AnnouncementController::class, 'index']);
    $r->post('/announcements', [I\AnnouncementController::class, 'store']);
    $r->post('/announcements/{id}/archive', [I\AnnouncementController::class, 'archive']);
    $r->get('/profile', [I\ProfileController::class, 'show']);
    $r->post('/profile', [I\ProfileController::class, 'update']);
    $r->post('/profile/password', [I\ProfileController::class, 'changePassword']);
});
