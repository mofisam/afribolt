<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{CourseAccess, LessonProgress, QuizAttempt};

/** Curriculum, progress, quizzes and certificates for self-paced learning. */
class LearningService
{
    public const PASS_MARK = 70;

    /** @return array<int, array> sections, each with 'lessons' */
    public function curriculum(int $courseId, bool $publishedOnly = true): array
    {
        $sections = Database::all('SELECT * FROM course_sections WHERE course_id = ? ORDER BY position, id', [$courseId]);
        $lessons = Database::all('SELECT l.*, (SELECT COUNT(*) FROM lesson_resources r WHERE r.lesson_id = l.id) AS resource_count FROM lessons l WHERE l.course_id = ?'
            . ($publishedOnly ? " AND l.status = 'published'" : '') . ' ORDER BY l.position, l.id', [$courseId]);
        $by = [];
        foreach ($lessons as $l) { $by[(int) $l['section_id']][] = $l; }
        foreach ($sections as &$s) { $s['lessons'] = $by[(int) $s['id']] ?? []; $s['seconds'] = array_sum(array_column($s['lessons'], 'duration_seconds')); }
        unset($s);
        return $publishedOnly ? array_values(array_filter($sections, fn($s) => $s['lessons'])) : $sections;
    }

    public function flat(array $curriculum): array
    {
        $out = [];
        foreach ($curriculum as $s) { foreach ($s['lessons'] as $l) { $out[] = $l; } }
        return $out;
    }

    /** @return array<int, array{completed:bool, pos:int, quiz:?int}> keyed by lesson id */
    public function progressMap(int $userId, int $courseId): array
    {
        $map = [];
        foreach (Database::all('SELECT lesson_id, completed_at, position_seconds, quiz_best_score FROM lesson_progress WHERE user_id = ? AND course_id = ?', [$userId, $courseId]) as $r) {
            $map[(int) $r['lesson_id']] = ['completed' => $r['completed_at'] !== null, 'pos' => (int) $r['position_seconds'], 'quiz' => $r['quiz_best_score'] !== null ? (int) $r['quiz_best_score'] : null];
        }
        return $map;
    }

    /** @return array{total:int, done:int, percent:int} */
    public function summary(int $userId, int $courseId): array
    {
        $total = (int) Database::value("SELECT COUNT(*) FROM lessons WHERE course_id = ? AND status = 'published'", [$courseId]);
        $done = (int) Database::value("SELECT COUNT(*) FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id AND l.status = 'published'
                                       WHERE p.user_id = ? AND p.course_id = ? AND p.completed_at IS NOT NULL", [$userId, $courseId]);
        return ['total' => $total, 'done' => $done, 'percent' => $total ? (int) floor(100 * $done / $total) : 0];
    }

    public function touch(int $userId, int $courseId, int $lessonId): void
    {
        Database::execute('UPDATE course_access SET last_lesson_id = ?, last_accessed_at = UTC_TIMESTAMP() WHERE user_id = ? AND course_id = ?', [$lessonId, $userId, $courseId]);
    }

    /** Where "Continue" should go: last opened lesson, else first unfinished, else first. */
    public function resumeLessonId(int $userId, array $course, array $flat, array $progress): ?int
    {
        if (!$flat) { return null; }
        $last = (int) Database::value('SELECT last_lesson_id FROM course_access WHERE user_id = ? AND course_id = ?', [$userId, (int) $course['id']]);
        $ids = array_column($flat, 'id');
        if ($last && in_array($last, array_map('intval', $ids), true) && empty($progress[$last]['completed'])) { return $last; }
        foreach ($flat as $l) { if (empty($progress[(int) $l['id']]['completed'])) { return (int) $l['id']; } }
        return (int) $flat[0]['id'];
    }

    /** Mark a lesson done/undone. Issues the certificate when the last lesson is completed. */
    public function setComplete(int $userId, array $course, array $lesson, bool $done): array
    {
        $existing = Database::one('SELECT * FROM lesson_progress WHERE user_id = ? AND lesson_id = ?', [$userId, (int) $lesson['id']]);
        if ($done && $lesson['lesson_type'] === 'quiz' && (int) ($existing['quiz_best_score'] ?? 0) < self::PASS_MARK) {
            return ['ok' => false, 'message' => 'Pass the quiz (' . self::PASS_MARK . '% or more) to complete this lesson.'];
        }
        if ($existing) { LessonProgress::update((int) $existing['id'], ['completed_at' => $done ? ($existing['completed_at'] ?? utc_now()) : null]); }
        else { LessonProgress::create(['user_id' => $userId, 'course_id' => (int) $course['id'], 'lesson_id' => (int) $lesson['id'], 'completed_at' => $done ? utc_now() : null]); }

        $sum = $this->summary($userId, (int) $course['id']);
        $cert = null;
        if ($done && $sum['percent'] >= 100) { $cert = $this->completeCourse($userId, $course); }
        return ['ok' => true, 'summary' => $sum, 'certificate' => $cert];
    }

    private function completeCourse(int $userId, array $course): ?string
    {
        $row = Database::one('SELECT * FROM course_access WHERE user_id = ? AND course_id = ?', [$userId, (int) $course['id']]);
        if (!$row) { return null; }
        $data = [];
        if (!$row['completed_at']) { $data['completed_at'] = utc_now(); }
        if ((int) $course['has_certificate'] && !$row['certificate_code']) { $data['certificate_code'] = 'AFB-C-' . strtoupper(bin2hex(random_bytes(5))); }
        if ($data) {
            CourseAccess::update((int) $row['id'], $data);
            if (isset($data['completed_at'])) {
                (new NotificationService())->toStudent($userId, 'course_completed', 'Course completed', "Congratulations! You completed {$course['title']}.", 'course', (int) $course['id'], '/student/learning');
            }
        }
        return $data['certificate_code'] ?? $row['certificate_code'];
    }

    public function savePosition(int $userId, int $courseId, int $lessonId, int $seconds): void
    {
        $seconds = max(0, min($seconds, 86400));
        Database::execute('INSERT INTO lesson_progress (user_id, course_id, lesson_id, position_seconds) VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE position_seconds = VALUES(position_seconds)', [$userId, $courseId, $lessonId, $seconds]);
    }

    /** Questions + options for display. The correct answers are NOT included. */
    public function quizForDisplay(int $lessonId): array
    {
        $qs = Database::all('SELECT id, question FROM quiz_questions WHERE lesson_id = ? ORDER BY position, id', [$lessonId]);
        foreach ($qs as &$q) { $q['options'] = Database::all('SELECT id, option_text FROM quiz_options WHERE question_id = ? ORDER BY position, id', [(int) $q['id']]); }
        return $qs;
    }

    /**
     * @param array<int,int> $answers question id => chosen option id
     * @return array{score:int, correct:int, total:int, passed:bool, results:array}
     */
    public function gradeQuiz(int $userId, array $course, array $lesson, array $answers): array
    {
        $qs = Database::all('SELECT id, explanation FROM quiz_questions WHERE lesson_id = ?', [(int) $lesson['id']]);
        $correct = 0; $results = [];
        foreach ($qs as $q) {
            $rightIds = array_map('intval', array_column(Database::all('SELECT id FROM quiz_options WHERE question_id = ? AND is_correct = 1', [(int) $q['id']]), 'id'));
            $chosen = (int) ($answers[(int) $q['id']] ?? 0);
            $ok = $chosen > 0 && in_array($chosen, $rightIds, true);
            if ($ok) { $correct++; }
            $results[(int) $q['id']] = ['correct' => $ok, 'right' => $rightIds, 'explanation' => $q['explanation']];
        }
        $total = count($qs);
        $score = $total ? (int) round(100 * $correct / $total) : 0;
        QuizAttempt::create(['user_id' => $userId, 'lesson_id' => (int) $lesson['id'], 'score_percent' => $score, 'correct_count' => $correct, 'total_count' => $total]);

        $existing = Database::one('SELECT * FROM lesson_progress WHERE user_id = ? AND lesson_id = ?', [$userId, (int) $lesson['id']]);
        $best = max($score, (int) ($existing['quiz_best_score'] ?? 0));
        if ($existing) { LessonProgress::update((int) $existing['id'], ['quiz_best_score' => $best]); }
        else { LessonProgress::create(['user_id' => $userId, 'course_id' => (int) $course['id'], 'lesson_id' => (int) $lesson['id'], 'quiz_best_score' => $best]); }

        $passed = $score >= self::PASS_MARK;
        $sum = $passed ? $this->setComplete($userId, $course, $lesson, true) : null;
        return ['score' => $score, 'correct' => $correct, 'total' => $total, 'passed' => $passed, 'results' => $results, 'summary' => $sum['summary'] ?? $this->summary($userId, (int) $course['id']), 'certificate' => $sum['certificate'] ?? null];
    }
}
