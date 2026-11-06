<?php
use App\Controllers\Student\{DashboardController, EnrolmentController, PaymentController, SessionController, NotificationController, ProfileController, LearningController, JobController};
use App\Middleware\StudentAuth;

$router->post('/cohorts/{code}/register', [EnrolmentController::class, 'register'], [StudentAuth::class]);

// Self-paced learning (Udemy-style)
$router->post('/courses/{slug}/enroll', [LearningController::class, 'enroll'], [StudentAuth::class]);
$router->get('/learn/resource/{id}', [LearningController::class, 'resource'], [StudentAuth::class]);
$router->get('/learn/{slug}', [LearningController::class, 'resume'], [StudentAuth::class]);
$router->post('/learn/{slug}/review', [LearningController::class, 'review'], [StudentAuth::class]);
$router->get('/learn/{slug}/{lessonId}', [LearningController::class, 'player'], [StudentAuth::class]);
$router->post('/learn/{slug}/{lessonId}/complete', [LearningController::class, 'complete'], [StudentAuth::class]);
$router->post('/learn/{slug}/{lessonId}/progress', [LearningController::class, 'progress'], [StudentAuth::class]);
$router->post('/learn/{slug}/{lessonId}/quiz', [LearningController::class, 'quiz'], [StudentAuth::class]);

$router->group(['prefix' => '/student', 'middleware' => [StudentAuth::class]], function ($r) {
    $r->get('/dashboard', [DashboardController::class, 'index']);
    $r->get('/learning', [LearningController::class, 'index']);
    $r->get('/jobs', [JobController::class, 'index']);
    $r->get('/jobs/preferences', [JobController::class, 'preferences']);
    $r->post('/jobs/preferences', [JobController::class, 'savePreferences']);
    $r->post('/jobs/{id}/save', [JobController::class, 'toggleSave']);
    $r->post('/jobs/{id}/report', [JobController::class, 'report']);
    $r->get('/profile', [ProfileController::class, 'show']);
    $r->post('/profile', [ProfileController::class, 'update']);
    $r->post('/profile/password', [ProfileController::class, 'changePassword']);
    $r->get('/notifications', [NotificationController::class, 'index']);
    $r->post('/notifications/read', [NotificationController::class, 'markAllRead']);
    $r->post('/enrolments/{id}/pay', [PaymentController::class, 'start']);
    $r->get('/payments/callback', [PaymentController::class, 'callback']);
    $r->post('/sessions/{id}/join', [SessionController::class, 'join']);
});
