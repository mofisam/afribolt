<?php
namespace App\Controllers\Public;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\{Cohort, Course};
use App\Services\{CourseAccessService, LearningService, VideoService};

class CourseController extends Controller
{
    private const PER_PAGE = 12;

    public function index(): void
    {
        $f = [];
        foreach (['q', 'category', 'level', 'price', 'mode', 'rating', 'sort'] as $k) {
            $v = trim((string) $this->request->query($k, ''));
            if ($v !== '') { $f[$k] = mb_substr($v, 0, 100); }
        }
        $page = max(1, (int) $this->request->query('page', 1));
        $total = Course::searchCount($f);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $this->view('public/courses/index', [
            'title' => isset($f['q']) ? 'Results for "' . $f['q'] . '"' : 'Courses',
            'courses' => Course::search($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'categories' => Course::categoryCounts(), 'f' => $f, 'total' => $total,
            'pager' => ['page' => $page, 'pages' => $pages, 'total' => $total],
        ]);
    }

    public function show(string $slug): void
    {
        $course = Course::findPublishedBySlug($slug) ?? $this->abort(404);
        $learning = new LearningService();
        $selfPaced = in_array($course['delivery_mode'], ['self_paced', 'both'], true);
        $curriculum = $selfPaced ? $learning->curriculum((int) $course['id']) : [];

        $uid = Auth::id('student');
        $source = $uid ? (new CourseAccessService())->source($uid, (int) $course['id']) : null;
        $progress = ($source && $uid) ? $learning->summary($uid, (int) $course['id']) : null;
        if (!$uid) { Session::put('intended', '/courses/' . $slug); }

        $dist = array_fill(1, 5, 0);
        foreach (Database::all("SELECT rating, COUNT(*) n FROM course_reviews WHERE course_id = ? AND status = 'published' GROUP BY rating", [(int) $course['id']]) as $r) { $dist[(int) $r['rating']] = (int) $r['n']; }
        $reviews = Database::all("SELECT r.*, u.first_name, u.last_name FROM course_reviews r JOIN users u ON u.id = r.user_id
                                  WHERE r.course_id = ? AND r.status = 'published' AND r.comment IS NOT NULL AND r.comment <> '' ORDER BY r.created_at DESC LIMIT 10", [(int) $course['id']]);

        $preview = $course['preview_video_url'] ? VideoService::parse($course['preview_video_url']) : null;
        $this->view('public/courses/show', [
            'title' => $course['title'], 'course' => $course, 'curriculum' => $curriculum, 'source' => $source, 'progress' => $progress,
            'cohorts' => in_array($course['delivery_mode'], ['live', 'both'], true) ? Cohort::publicList((int) $course['id']) : [],
            'dist' => $dist, 'reviews' => $reviews, 'selfPaced' => $selfPaced,
            'previewEmbed' => $preview ? VideoService::embedUrl($preview) : null, 'previewFile' => $preview && $preview['provider'] === 'file',
            'resourceCount' => (int) Database::value('SELECT COUNT(*) FROM lesson_resources r JOIN lessons l ON l.id = r.lesson_id WHERE l.course_id = ? AND l.status = \'published\'', [(int) $course['id']]),
        ]);
    }

    /** Free-preview lesson, viewable without enrolling. */
    public function preview(string $slug, string $lessonId): void
    {
        $course = Course::findPublishedBySlug($slug) ?? $this->abort(404);
        $lesson = Database::one("SELECT * FROM lessons WHERE id = ? AND course_id = ? AND status = 'published' AND is_preview = 1", [(int) $lessonId, (int) $course['id']]) ?? $this->abort(404);
        $video = ($lesson['lesson_type'] === 'video' && $lesson['video_url']) ? VideoService::parse($lesson['video_url']) : null;
        $this->view('public/courses/preview', [
            'title' => $lesson['title'] . ' (preview)', 'course' => $course, 'lesson' => $lesson,
            'embed' => $video ? VideoService::embedUrl($video) : null, 'isFile' => $video && $video['provider'] === 'file',
        ]);
    }
}
