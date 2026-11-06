<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Database, Response, Session};
use App\Models\{Course, CourseReview, Lesson};
use App\Services\{AuditService, CourseAccessService, CourseCatalogService, LearningService, VideoService};

class LearningController extends Controller
{
    private LearningService $learning;
    private CourseAccessService $access;

    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);
        $this->learning = new LearningService();
        $this->access = new CourseAccessService();
    }

    /** @return array{0:array,1:string} course + access source, or 404/redirect */
    private function load(string $slug, bool $json = false): array
    {
        $course = Course::findForLearning($slug) ?? $this->abort(404);
        $src = $this->access->source((int) Auth::id('student'), (int) $course['id']);
        if (!$src) {
            if ($json) { $this->json(['ok' => false, 'message' => 'You do not have access to this course.'], 403); }
            Session::flash('error', 'Enrol in this course to start learning.');
            $this->redirect('/courses/' . $course['slug']);
        }
        if ($src === 'cohort') { $this->access->ensureRow((int) Auth::id('student'), (int) $course['id'], 'cohort'); }
        return [$course, $src];
    }

    // ---------- My learning ----------
    public function index(): void
    {
        $uid = (int) Auth::id('student');
        $courses = Database::all(
            "SELECT c.*, CONCAT(i.first_name, ' ', i.last_name) AS instructor_name, ca.last_accessed_at, ca.completed_at, ca.certificate_code
             FROM courses c LEFT JOIN instructors i ON i.id = c.instructor_id
             LEFT JOIN course_access ca ON ca.course_id = c.id AND ca.user_id = ? AND ca.status = 'active'
             WHERE c.deleted_at IS NULL AND c.status IN ('published','archived') AND (ca.id IS NOT NULL OR (c.lessons_count > 0 AND c.cohort_includes_access = 1 AND EXISTS
                (SELECT 1 FROM enrolments e JOIN cohorts h ON h.id = e.cohort_id WHERE e.student_id = ? AND h.course_id = c.id AND e.status IN ('confirmed','completed'))))
             ORDER BY COALESCE(ca.last_accessed_at, c.updated_at) DESC", [$uid, $uid]);
        foreach ($courses as &$c) { $c['progress'] = $this->learning->summary($uid, (int) $c['id']); }
        unset($c);
        $this->view('student/learning', ['title' => 'My learning', 'courses' => $courses, 'tab' => (string) $this->request->query('tab', 'all')], 'student');
    }

    // ---------- enrol / buy ----------
    public function enroll(string $slug): void
    {
        $course = Course::findPublishedBySlug($slug) ?? $this->abort(404);
        $student = Auth::user('student');
        if (config('security.require_email_verification') && empty($student['email_verified_at'])) {
            Session::flash('error', 'Please verify your email address first.');
            $this->redirect('/courses/' . $slug);
        }
        try { $r = $this->access->startEnrol($student, $course); }
        catch (\RuntimeException $e) { error_log('Course checkout failed: ' . $e->getMessage()); $r = ['type' => 'error', 'message' => 'We could not start the payment. Please try again shortly.']; }

        if ($r['type'] === 'pay') { $this->redirect($r['url']); }
        if ($r['type'] === 'error') { Session::flash('error', $r['message']); $this->redirect('/courses/' . $slug); }
        if ($r['type'] === 'free') { Session::flash('success', "You're enrolled. Happy learning!"); }
        $this->redirect('/learn/' . $slug);
    }

    // ---------- player ----------
    public function resume(string $slug): void
    {
        [$course] = $this->load($slug);
        $uid = (int) Auth::id('student');
        $flat = $this->learning->flat($this->learning->curriculum((int) $course['id']));
        $id = $this->learning->resumeLessonId($uid, $course, $flat, $this->learning->progressMap($uid, (int) $course['id']));
        if (!$id) { Session::flash('error', 'This course has no lessons yet. Check back soon.'); $this->redirect('/student/learning'); }
        $this->redirect('/learn/' . $course['slug'] . '/' . $id);
    }

    public function player(string $slug, string $lessonId): void
    {
        [$course, $source] = $this->load($slug);
        $uid = (int) Auth::id('student');
        $curriculum = $this->learning->curriculum((int) $course['id']);
        $flat = $this->learning->flat($curriculum);
        $lesson = null; $idx = -1;
        foreach ($flat as $i => $l) { if ((int) $l['id'] === (int) $lessonId) { $lesson = $l; $idx = $i; break; } }
        if (!$lesson) { $this->abort(404); }

        $this->learning->touch($uid, (int) $course['id'], (int) $lesson['id']);
        $progress = $this->learning->progressMap($uid, (int) $course['id']);
        $video = ($lesson['lesson_type'] === 'video' && $lesson['video_url']) ? VideoService::parse($lesson['video_url']) : null;
        $access = Database::one('SELECT certificate_code, completed_at FROM course_access WHERE user_id = ? AND course_id = ?', [$uid, (int) $course['id']]);
        $review = Database::one('SELECT * FROM course_reviews WHERE course_id = ? AND user_id = ?', [(int) $course['id'], $uid]);

        $this->view('student/player', [
            'title' => $lesson['title'] . ' | ' . $course['title'], 'course' => $course, 'curriculum' => $curriculum, 'lesson' => $lesson,
            'prev' => $flat[$idx - 1] ?? null, 'next' => $flat[$idx + 1] ?? null, 'progress' => $progress, 'summary' => $this->learning->summary($uid, (int) $course['id']),
            'embed' => $video ? VideoService::embedUrl($video) : null, 'video' => $video,
            'resources' => Database::all('SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY id', [(int) $lesson['id']]),
            'quiz' => $lesson['lesson_type'] === 'quiz' ? $this->learning->quizForDisplay((int) $lesson['id']) : [],
            'certificate' => $access['certificate_code'] ?? null, 'review' => $review,
            'passMark' => LearningService::PASS_MARK,
        ], null);
    }

    // ---------- JSON actions ----------
    private function lessonFor(array $course, string $lessonId): array
    {
        return Database::one("SELECT * FROM lessons WHERE id = ? AND course_id = ? AND status = 'published'", [(int) $lessonId, (int) $course['id']]) ?? $this->json(['ok' => false, 'message' => 'Lesson not found.'], 404);
    }

    public function complete(string $slug, string $lessonId): void
    {
        [$course] = $this->load($slug, true);
        $lesson = $this->lessonFor($course, $lessonId);
        $done = $this->request->post('done') === '1';
        $r = $this->learning->setComplete((int) Auth::id('student'), $course, $lesson, $done);
        $this->json($r, $r['ok'] ? 200 : 422);
    }

    public function progress(string $slug, string $lessonId): void
    {
        [$course] = $this->load($slug, true);
        $lesson = $this->lessonFor($course, $lessonId);
        $this->learning->savePosition((int) Auth::id('student'), (int) $course['id'], (int) $lesson['id'], (int) $this->request->post('seconds', 0));
        $this->json(['ok' => true]);
    }

    public function quiz(string $slug, string $lessonId): void
    {
        [$course] = $this->load($slug, true);
        $lesson = $this->lessonFor($course, $lessonId);
        if ($lesson['lesson_type'] !== 'quiz') { $this->json(['ok' => false, 'message' => 'Not a quiz.'], 422); }
        $answers = [];
        foreach ((array) ($_POST['q'] ?? []) as $qid => $oid) { $answers[(int) $qid] = (int) $oid; }
        $this->json(['ok' => true] + $this->learning->gradeQuiz((int) Auth::id('student'), $course, $lesson, $answers));
    }

    public function review(string $slug): void
    {
        [$course] = $this->load($slug);
        $uid = (int) Auth::id('student');
        $d = $this->validate(['rating' => 'required|integer|in:1,2,3,4,5', 'comment' => 'max:2000'], '/learn/' . $slug);
        $existing = Database::one('SELECT id FROM course_reviews WHERE course_id = ? AND user_id = ?', [(int) $course['id'], $uid]);
        $row = ['rating' => (int) $d['rating'], 'comment' => $d['comment'], 'status' => 'published'];
        if ($existing) { CourseReview::update((int) $existing['id'], $row); }
        else { CourseReview::create($row + ['course_id' => (int) $course['id'], 'user_id' => $uid]); }
        CourseCatalogService::refresh((int) $course['id']);
        Session::flash('success', 'Thanks for your review!');
        $last = (int) Database::value('SELECT last_lesson_id FROM course_access WHERE user_id = ? AND course_id = ?', [$uid, (int) $course['id']]);
        $this->redirect('/learn/' . $slug . ($last ? '/' . $last : '') . '#reviews');
    }

    /** Download a lesson resource (private file, access-checked). */
    public function resource(string $id): void
    {
        $r = Database::one('SELECT r.*, l.course_id FROM lesson_resources r JOIN lessons l ON l.id = r.lesson_id WHERE r.id = ?', [(int) $id]) ?? $this->abort(404);
        $course = Course::find((int) $r['course_id']) ?? $this->abort(404);
        if (!$this->access->source((int) Auth::id('student'), (int) $course['id'])) { $this->abort(403); }
        $file = base_path('storage/private_uploads/' . $r['file_path']);
        if (!is_file($file)) { $this->abort(404); }
        header('Content-Type: application/octet-stream');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $r['file_name']) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
