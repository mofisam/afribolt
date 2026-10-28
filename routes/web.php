<?php
/** Public + auth routes. $router is provided by App::run(). */
use App\Controllers\Public\{HomeController, AboutController, CourseController, CohortController, OpportunitiesController, ContactController, PageController};
use App\Controllers\Auth\{LoginController, RegisterController, VerifyEmailController, PasswordResetController};
use App\Middleware\GuestOnly;

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [AboutController::class, 'index']);
$router->get('/courses', [CourseController::class, 'index']);
$router->get('/courses/{slug}', [CourseController::class, 'show']);
$router->get('/cohorts/{code}', [CohortController::class, 'show']);
$router->get('/career-accelerator', [OpportunitiesController::class, 'index']);
$router->get('/contact', [ContactController::class, 'show']);
$router->post('/contact', [ContactController::class, 'submit']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/privacy', [PageController::class, 'privacy']);
$router->get('/terms', [PageController::class, 'terms']);
$router->get('/refund-policy', [PageController::class, 'refund']);

$router->get('/login', [LoginController::class, 'show'], [GuestOnly::class]);
$router->post('/login', [LoginController::class, 'submit'], [GuestOnly::class]);
$router->get('/register', [RegisterController::class, 'show'], [GuestOnly::class]);
$router->post('/register', [RegisterController::class, 'submit'], [GuestOnly::class]);
$router->post('/logout', [LoginController::class, 'logout']);
$router->get('/verify-email/{token}', [VerifyEmailController::class, 'verify']);
$router->get('/forgot-password', [PasswordResetController::class, 'forgot']);
$router->post('/forgot-password', [PasswordResetController::class, 'sendLink']);
$router->get('/reset-password/{token}', [PasswordResetController::class, 'form']);
$router->post('/reset-password/{token}', [PasswordResetController::class, 'reset']);
