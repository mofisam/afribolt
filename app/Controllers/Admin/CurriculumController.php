<?php
namespace App\Controllers\Admin;

use App\Core\{Database, Session};
use App\Models\{Course, CourseSection, Lesson, LessonResource, QuizOption, QuizQuestion};
use App\Services\{CourseCatalogService, LearningService, VideoService};

/** Build a self-paced course: sections -> lessons (video / article / quiz), downloadable resources, quiz questions. */
class CurriculumController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private const EXT = ['pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'png', 'jpg', 'jpeg'];
    private const MAX_BYTES = 25 * 1024 * 1024;

    private function course(string $id): array { return Course::find((int) $id) ?? $this->abort(404); }
    private function after(int $courseId): void { CourseCatalogService::refresh($courseId); }

    /** Move a row up/down inside its scope by renumbering the whole scope (robust to duplicate positions). */
    private function move(string $table, string $scopeCol, int $scopeVal, int $id, string $dir): void
    {
        $ids = array_map('intval', array_column(Database::all("SELECT id FROM `{$table}` WHERE `{$scopeCol}` = ? ORDER BY position, id", [$scopeVal]), 'id'));
        $i = array_search($id, $ids, true);
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($i === false || !isset($ids[$j])) { return; }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $n => $rid) { Database::execute("UPDATE `{$table}` SET position = ? WHERE id = ?", [$n + 1, $rid]); }
    }

    private function nextPos(string $table, string $col, int $val): int
    {
        return 1 + (int) Database::value("SELECT COALESCE(MAX(position), 0) FROM `{$table}` WHERE `{$col}` = ?", [$val]);
    }

    private function removeFiles(array $lessonIds): void
    {
        if (!$lessonIds) { return; }
        $in = implode(',', array_map('intval', $lessonIds));
        foreach (Database::all("SELECT file_path FROM lesson_resources WHERE lesson_id IN ({$in})") as $r) { @unlink(base_path('storage/private_uploads/' . $r['file_path'])); }
    }

    // ---------- overview ----------
    public function index(string $id): void
    {
        $course = $this->course($id);
        $cur = (new LearningService())->curriculum((int) $course['id'], false);
        $q = [];
        foreach (Database::all('SELECT lesson_id, COUNT(*) n FROM quiz_questions WHERE lesson_id IN (SELECT id FROM lessons WHERE course_id = ?) GROUP BY lesson_id', [(int) $course['id']]) as $r) { $q[(int) $r['lesson_id']] = (int) $r['n']; }
        $this->page('admin/curriculum/index', ['title' => 'Curriculum: ' . $course['title'], 'course' => $course, 'curriculum' => $cur, 'qcounts' => $q]);
    }

    // ---------- sections ----------
    public function addSection(string $id): void
    {
        $course = $this->course($id);
        $d = $this->validate(['title' => 'required|max:200'], '/admin/courses/' . $course['id'] . '/curriculum');
        $sid = CourseSection::create(['course_id' => (int) $course['id'], 'title' => $d['title'], 'position' => $this->nextPos('course_sections', 'course_id', (int) $course['id'])]);
        $this->audit('section.created', 'course_section', $sid, null, ['course_id' => (int) $course['id'], 'title' => $d['title']]);
        $this->done('Section added. Now add lessons to it.', '/admin/courses/' . $course['id'] . '/curriculum');
    }

    public function renameSection(string $id): void
    {
        $s = CourseSection::find((int) $id) ?? $this->abort(404);
        $d = $this->validate(['title' => 'required|max:200'], '/admin/courses/' . $s['course_id'] . '/curriculum');
        CourseSection::update((int) $s['id'], ['title' => $d['title']]);
        $this->done('Section renamed.', '/admin/courses/' . $s['course_id'] . '/curriculum');
    }

    public function moveSection(string $id): void
    {
        $s = CourseSection::find((int) $id) ?? $this->abort(404);
        $this->move('course_sections', 'course_id', (int) $s['course_id'], (int) $s['id'], $this->request->post('dir') === 'up' ? 'up' : 'down');
        $this->redirect('/admin/courses/' . $s['course_id'] . '/curriculum');
    }

    public function deleteSection(string $id): void
    {
        $s = CourseSection::find((int) $id) ?? $this->abort(404);
        $this->removeFiles(array_column(Database::all('SELECT id FROM lessons WHERE section_id = ?', [(int) $s['id']]), 'id'));
        CourseSection::delete((int) $s['id']);
        $this->audit('section.deleted', 'course_section', (int) $s['id'], ['title' => $s['title']], []);
        $this->after((int) $s['course_id']);
        $this->done('Section and its lessons deleted.', '/admin/courses/' . $s['course_id'] . '/curriculum');
    }

    // ---------- lessons ----------
    private function sectionOptions(int $courseId): array
    {
        $o = [];
        foreach (Database::all('SELECT id, title FROM course_sections WHERE course_id = ? ORDER BY position, id', [$courseId]) as $s) { $o[$s['id']] = $s['title']; }
        return $o;
    }

    public function createLesson(string $id): void
    {
        $course = $this->course($id);
        $sections = $this->sectionOptions((int) $course['id']);
        if (!$sections) { $this->done('Add a section first.', '/admin/courses/' . $course['id'] . '/curriculum', 'error'); }
        $this->page('admin/curriculum/lesson', [
            'title' => 'New lesson', 'course' => $course, 'lesson' => ['section_id' => (int) $this->request->query('section', 0), 'lesson_type' => 'video', 'status' => 'draft', 'is_preview' => 0],
            'sections' => $sections, 'action' => url('/admin/courses/' . $course['id'] . '/lessons'), 'resources' => [], 'questions' => [], 'isNew' => true,
        ]);
    }

    public function storeLesson(string $id): void
    {
        $course = $this->course($id);
        $row = $this->lessonRow($course, null, '/admin/courses/' . $course['id'] . '/lessons/create');
        $row += ['course_id' => (int) $course['id'], 'position' => $this->nextPos('lessons', 'section_id', (int) $row['section_id'])];
        $lid = Lesson::create($row);
        $this->audit('lesson.created', 'lesson', $lid, null, ['course_id' => (int) $course['id'], 'title' => $row['title'], 'type' => $row['lesson_type']]);
        $this->after((int) $course['id']);
        $msg = $row['lesson_type'] === 'quiz' ? 'Lesson created. Now add the quiz questions, then publish it.' : 'Lesson created.';
        $this->done($msg, $row['lesson_type'] === 'quiz' ? '/admin/lessons/' . $lid . '/edit' : '/admin/courses/' . $course['id'] . '/curriculum');
    }

    public function editLesson(string $id): void
    {
        $lesson = Lesson::find((int) $id) ?? $this->abort(404);
        $course = $this->course((string) $lesson['course_id']);
        $lesson['duration_minutes'] = $lesson['duration_seconds'] ? round($lesson['duration_seconds'] / 60, 1) : '';
        $questions = Database::all('SELECT * FROM quiz_questions WHERE lesson_id = ? ORDER BY position, id', [(int) $lesson['id']]);
        foreach ($questions as &$q) { $q['options'] = Database::all('SELECT * FROM quiz_options WHERE question_id = ? ORDER BY position, id', [(int) $q['id']]); }
        unset($q);
        $this->page('admin/curriculum/lesson', [
            'title' => 'Edit lesson', 'course' => $course, 'lesson' => $lesson, 'sections' => $this->sectionOptions((int) $course['id']),
            'action' => url('/admin/lessons/' . $lesson['id']), 'resources' => Database::all('SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY id', [(int) $lesson['id']]),
            'questions' => $questions, 'isNew' => false,
        ]);
    }

    public function updateLesson(string $id): void
    {
        $lesson = Lesson::find((int) $id) ?? $this->abort(404);
        $course = $this->course((string) $lesson['course_id']);
        $row = $this->lessonRow($course, $lesson, '/admin/lessons/' . $lesson['id'] . '/edit');
        if ((int) $row['section_id'] !== (int) $lesson['section_id']) { $row['position'] = $this->nextPos('lessons', 'section_id', (int) $row['section_id']); }
        Lesson::update((int) $lesson['id'], $row);
        $this->audit('lesson.updated', 'lesson', (int) $lesson['id'], $lesson, array_diff_key($row, ['content' => 1]));
        $this->after((int) $course['id']);
        $this->done('Lesson saved.', '/admin/lessons/' . $lesson['id'] . '/edit');
    }

    /** Validate the lesson form and return the DB row (redirects back with errors if invalid). */
    private function lessonRow(array $course, ?array $existing, string $back): array
    {
        $d = $this->validate([
            'title' => 'required|max:200', 'section_id' => 'required|integer', 'lesson_type' => 'required|in:video,article,quiz', 'video_url' => 'url',
            'duration_minutes' => 'numeric', 'content' => 'max:200000', 'status' => 'required|in:draft,published',
        ], $back);
        $errors = [];
        if (!isset($this->sectionOptions((int) $course['id'])[(int) $d['section_id']])) { $errors['section_id'] = 'Choose a section of this course.'; }
        $type = $d['lesson_type'];
        $provider = null; $url = null; $content = null;
        if ($type === 'video') {
            $v = $d['video_url'] ? VideoService::parse($d['video_url']) : null;
            if (!$v) { $errors['video_url'] = 'Paste a YouTube or Vimeo link, or a direct https .mp4/.webm link.'; }
            else { $provider = $v['provider']; $url = $d['video_url']; }
        }
        if ($type === 'article') {
            if (!trim((string) $d['content'])) { $errors['content'] = 'Write the lesson text.'; } else { $content = $d['content']; }
        }
        if ($type === 'quiz' && $d['status'] === 'published') {
            $ok = $existing && (int) Database::value('SELECT COUNT(*) FROM quiz_questions WHERE lesson_id = ?', [(int) $existing['id']]) > 0;
            if (!$ok) { $errors['status'] = 'Add at least one question before publishing a quiz. Save it as a draft first.'; }
        }
        if ($errors) {
            Session::flashInput($this->request->all(), $errors);
            Session::flash('error', 'Please fix the highlighted fields.');
            $this->redirect($back);
        }
        $seconds = $d['duration_minutes'] !== null ? (int) round(((float) $d['duration_minutes']) * 60) : 0;
        if ($type === 'article' && $seconds === 0) { $seconds = max(60, (int) ceil(str_word_count(strip_tags((string) $content)) / 200) * 60); }
        if ($type === 'quiz') { $seconds = 0; }
        return [
            'section_id' => (int) $d['section_id'], 'title' => $d['title'], 'lesson_type' => $type, 'video_provider' => $provider, 'video_url' => $url,
            'duration_seconds' => $seconds, 'content' => $content, 'is_preview' => $type === 'quiz' ? 0 : $this->checkbox('is_preview'), 'status' => $d['status'],
        ];
    }

    public function moveLesson(string $id): void
    {
        $l = Lesson::find((int) $id) ?? $this->abort(404);
        $this->move('lessons', 'section_id', (int) $l['section_id'], (int) $l['id'], $this->request->post('dir') === 'up' ? 'up' : 'down');
        $this->redirect('/admin/courses/' . $l['course_id'] . '/curriculum');
    }

    public function deleteLesson(string $id): void
    {
        $l = Lesson::find((int) $id) ?? $this->abort(404);
        $this->removeFiles([(int) $l['id']]);
        Lesson::delete((int) $l['id']);
        $this->audit('lesson.deleted', 'lesson', (int) $l['id'], ['title' => $l['title']], []);
        $this->after((int) $l['course_id']);
        $this->done('Lesson deleted.', '/admin/courses/' . $l['course_id'] . '/curriculum');
    }

    // ---------- resources ----------
    public function uploadResource(string $id): void
    {
        $l = Lesson::find((int) $id) ?? $this->abort(404);
        $back = '/admin/lessons/' . $l['id'] . '/edit#resources';
        $f = $this->request->file('resource');
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { $this->done('Choose a file to upload (max 25 MB).', $back, 'error'); }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::EXT, true) || $f['size'] > self::MAX_BYTES) { $this->done('Allowed: ' . implode(', ', self::EXT) . ', up to 25 MB.', $back, 'error'); }
        $dir = base_path('storage/private_uploads/resources');
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { $this->done('Could not save the file. Check folder permissions.', $back, 'error'); }
        $title = trim((string) $this->request->post('title', '')) ?: pathinfo($f['name'], PATHINFO_FILENAME);
        $rid = LessonResource::create(['lesson_id' => (int) $l['id'], 'title' => mb_substr($title, 0, 200), 'file_path' => 'resources/' . $name,
            'file_name' => mb_substr(preg_replace('/[^\w.\- ]+/u', '_', $f['name']), 0, 250), 'mime_type' => null, 'file_size' => (int) $f['size']]);
        $this->audit('resource.uploaded', 'lesson_resource', $rid, null, ['lesson_id' => (int) $l['id'], 'file' => $f['name']]);
        $this->done('File uploaded.', $back);
    }

    public function deleteResource(string $id): void
    {
        $r = LessonResource::find((int) $id) ?? $this->abort(404);
        @unlink(base_path('storage/private_uploads/' . $r['file_path']));
        LessonResource::delete((int) $r['id']);
        $this->done('File removed.', '/admin/lessons/' . $r['lesson_id'] . '/edit#resources');
    }

    // ---------- quiz questions ----------
    public function addQuestion(string $id): void
    {
        $l = Lesson::find((int) $id) ?? $this->abort(404);
        $back = '/admin/lessons/' . $l['id'] . '/edit#quiz';
        $question = trim((string) $this->request->post('question', ''));
        $opts = array_map(fn($o) => trim((string) $o), (array) ($_POST['option'] ?? []));
        $correct = (int) $this->request->post('correct', -1);
        $valid = array_filter($opts, fn($o) => $o !== '');
        if ($question === '' || count($valid) < 2 || !isset($valid[$correct])) {
            $this->done('Write the question, fill in at least two options, and mark the correct one.', $back, 'error');
        }
        $qid = QuizQuestion::create(['lesson_id' => (int) $l['id'], 'question' => mb_substr($question, 0, 2000), 'explanation' => trim((string) $this->request->post('explanation', '')) ?: null,
            'position' => $this->nextPos('quiz_questions', 'lesson_id', (int) $l['id'])]);
        $pos = 1;
        foreach ($valid as $i => $text) { QuizOption::create(['question_id' => $qid, 'option_text' => mb_substr($text, 0, 500), 'is_correct' => $i === $correct ? 1 : 0, 'position' => $pos++]); }
        $this->audit('quiz.question_added', 'lesson', (int) $l['id'], null, ['question_id' => $qid]);
        $this->done('Question added.', $back);
    }

    public function deleteQuestion(string $id): void
    {
        $q = QuizQuestion::find((int) $id) ?? $this->abort(404);
        QuizQuestion::delete((int) $q['id']);
        $left = (int) Database::value('SELECT COUNT(*) FROM quiz_questions WHERE lesson_id = ?', [(int) $q['lesson_id']]);
        if ($left === 0) { Lesson::update((int) $q['lesson_id'], ['status' => 'draft']); $this->after((int) Lesson::find((int) $q['lesson_id'])['course_id']); }
        $this->done('Question deleted.' . ($left === 0 ? ' The quiz was set back to draft because it has no questions.' : ''), '/admin/lessons/' . $q['lesson_id'] . '/edit#quiz');
    }
}
