<?php
use App\Controllers\Student\{DashboardController, EnrolmentController, PaymentController, SessionController, NotificationController, ProfileController};
use App\Middleware\StudentAuth;

$router->post('/cohorts/{code}/register', [EnrolmentController::class, 'register'], [StudentAuth::class]);

$router->group(['prefix' => '/student', 'middleware' => [StudentAuth::class]], function ($r) {
    $r->get('/dashboard', [DashboardController::class, 'index']);
    $r->get('/profile', [ProfileController::class, 'show']);
    $r->post('/profile', [ProfileController::class, 'update']);
    $r->post('/profile/password', [ProfileController::class, 'changePassword']);
    $r->get('/notifications', [NotificationController::class, 'index']);
    $r->post('/notifications/read', [NotificationController::class, 'markAllRead']);
    $r->post('/enrolments/{id}/pay', [PaymentController::class, 'start']);
    $r->get('/payments/callback', [PaymentController::class, 'callback']);
    $r->post('/sessions/{id}/join', [SessionController::class, 'join']);
});
