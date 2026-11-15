<?php
namespace App\Services;

use App\Core\Database;

/**
 * Build a whole curriculum from one text file.
 *
 *   [SECTION] Getting started
 *   [VIDEO] Welcome to the course | https://youtu.be/xxxx | 4 | preview
 *   [ARTICLE] Reading: how the course works | 5
 *   Any lines here (until the next [TAG]) are the article text. Markdown is supported.
 *   [QUIZ] Check your understanding
 *   Q: Question text?
 *   A) Option *
 *   B) Option
 *
 * Tags are case-insensitive and must start the line. After the title, the optional
 * "| a | b | c" parts are: VIDEO = link | minutes | preview,  ARTICLE = minutes | preview.
 * Everything imports as DRAFT unless you tick "publish complete lessons" on the import page.
 */
class CourseOutlineImportService
{
    public const MAX_LESSONS = 150;

    public const SAMPLE = <<<'TXT'
# AfriBolt course outline: sample file
# Lines that start with # are ignored (except inside article text, where # is a heading: use ## there).
# Tags: [SECTION]  [VIDEO]  [ARTICLE]  [QUIZ]. Put each tag at the start of a line.
#   [VIDEO] Title | video link | minutes | preview      (minutes and "preview" are optional)
#   [ARTICLE] Title | minutes | preview                  (the text follows on the next lines)
#   [QUIZ] Title                                         (questions follow, same format as the quiz import)

[SECTION] Getting started

[VIDEO] Welcome to the course | https://www.youtube.com/watch?v=dQw4w9WgXcQ | 4 | preview

[ARTICLE] How this course works | 5
## What you will learn
You will learn the basics of customer service in Nigerian businesses.

- Short lessons you can finish on your phone
- A quiz at the end of each section

**Tip:** study a little every day.

[SECTION] Handling customers

[VIDEO] Listening to the customer | https://vimeo.com/76979871 | 9

[QUIZ] Section quiz
Q: What should you do first when a customer complains?
A) Argue your side
B) Listen and acknowledge the problem *
C) Transfer them straight away
Explanation: People calm down when they feel heard.

Q: Naira is the currency of Nigeria.
A) True *
B) False
TXT;

    /**
     * @return array{sections: array<int,array{title:string,lessons:array}>, errors: string[], lesson_count:int, question_count:int}
     */
    public static function parse(string $text): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
        if (!mb_check_encoding($text, 'UTF-8')) { $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252'); }
        $lines = preg_split('/\R/u', $text) ?: [];

        $sections = []; $errors = [];
        $sec = null;       // index into $sections
        $lesson = null;    // lesson being collected
        $body = [];

        $flush = function () use (&$lesson, &$body, &$sections, &$sec, &$errors) {
            if ($lesson === null) { return; }
            $raw = implode("\n", $body);
            $name = '"' . $lesson['title'] . '"';
            if ($lesson['type'] === 'article') {
                $content = trim($raw);
                if ($content === '') { $errors[] = 'Article ' . $name . ' has no text under it.'; }
                $lesson['content'] = $content;
            } elseif ($lesson['type'] === 'quiz') {
                $r = QuizImportService::parse($raw);
                foreach ($r['errors'] as $er) { $errors[] = 'Quiz ' . $name . ' - ' . $er; }
                $lesson['questions'] = $r['questions'];
            }
            $sections[$sec]['lessons'][] = $lesson;
            $lesson = null; $body = [];
        };

        foreach ($lines as $n => $line) {
            $t = trim($line);
            $inArticle = $lesson !== null && $lesson['type'] === 'article';

            if (preg_match('/^\[(section|video|article|quiz)\]\s*(.*)$/iu', $t, $m)) {
                $flush();
                $tag = strtolower($m[1]);
                $parts = array_map('trim', explode('|', $m[2]));
                $title = array_shift($parts);
                if ($title === '') { $errors[] = 'Line ' . ($n + 1) . ': [' . strtoupper($tag) . '] needs a title.'; $title = 'Untitled'; }
                $title = mb_substr($title, 0, 200);

                if ($tag === 'section') {
                    $sections[] = ['title' => $title, 'lessons' => []];
                    $sec = count($sections) - 1;
                    continue;
                }
                if ($sec === null) { $sections[] = ['title' => 'Lessons', 'lessons' => []]; $sec = 0; }

                $lesson = ['type' => $tag, 'title' => $title, 'video_url' => null, 'video_provider' => null, 'minutes' => null, 'preview' => false, 'content' => null, 'questions' => []];
                foreach ($parts as $p) {
                    if (strcasecmp($p, 'preview') === 0) { $lesson['preview'] = true; }
                    elseif ($tag === 'video' && $lesson['video_url'] === null && preg_match('#^https?://#i', $p)) { $lesson['video_url'] = $p; }
                    elseif (is_numeric($p)) { $lesson['minutes'] = max(0, (float) $p); }
                    elseif ($p !== '') { $errors[] = 'Line ' . ($n + 1) . ': could not understand "' . $p . '" (expected a link, minutes, or "preview").'; }
                }
                if ($tag === 'video') {
                    $v = $lesson['video_url'] ? VideoService::parse($lesson['video_url']) : null;
                    if (!$v) { $errors[] = 'Video "' . $title . '": add a YouTube, Vimeo or direct https .mp4/.webm link after the title.'; }
                    else { $lesson['video_provider'] = $v['provider']; }
                }
                if ($tag === 'quiz') { $lesson['preview'] = false; }
                continue;
            }

            if ($lesson !== null) {
                // Inside an article keep every line (including blank lines and # headings) exactly as written.
                if ($inArticle) { $body[] = rtrim($line); }
                elseif ($t !== '') { $body[] = $line; }
            }
            // text before any tag is ignored (instructions / comments)
        }
        $flush();

        $lessonCount = 0; $questionCount = 0;
        foreach ($sections as $i => $s) {
            if (!$s['lessons']) { $errors[] = 'Section "' . $s['title'] . '" has no lessons.'; }
            foreach ($s['lessons'] as $l) { $lessonCount++; $questionCount += count($l['questions']); }
        }
        if (!$sections) { $errors[] = 'Nothing to import. Start with [SECTION] Title, then add [VIDEO], [ARTICLE] or [QUIZ] lines (see the sample file).'; }
        if ($lessonCount > self::MAX_LESSONS) { $errors[] = 'Too many lessons in one file (max ' . self::MAX_LESSONS . '). Split the file.'; }

        return ['sections' => $sections, 'errors' => $errors, 'lesson_count' => $lessonCount, 'question_count' => $questionCount];
    }

    /**
     * Save a parsed outline to a course (appended after existing sections). Everything runs in one transaction.
     * @return array{sections:int,lessons:int,questions:int,published:int}
     */
    public static function save(int $courseId, array $parsed, bool $publishComplete): array
    {
        return Database::transaction(function () use ($courseId, $parsed, $publishComplete) {
            $secPos = (int) Database::value('SELECT COALESCE(MAX(position), 0) FROM course_sections WHERE course_id = ?', [$courseId]);
            $out = ['sections' => 0, 'lessons' => 0, 'questions' => 0, 'published' => 0];
            foreach ($parsed['sections'] as $s) {
                Database::execute('INSERT INTO course_sections (course_id, title, position) VALUES (?, ?, ?)', [$courseId, $s['title'], ++$secPos]);
                $sid = Database::insertId(); $out['sections']++;
                $lp = 0;
                foreach ($s['lessons'] as $l) {
                    $seconds = $l['minutes'] !== null ? (int) round($l['minutes'] * 60) : 0;
                    if ($l['type'] === 'article' && $seconds === 0) { $seconds = max(60, (int) ceil(str_word_count(strip_tags((string) $l['content'])) / 200) * 60); }
                    if ($l['type'] === 'quiz') { $seconds = 0; }
                    $complete = match ($l['type']) { 'video' => !empty($l['video_url']), 'article' => trim((string) $l['content']) !== '', 'quiz' => count($l['questions']) > 0, default => false };
                    $status = ($publishComplete && $complete) ? 'published' : 'draft';
                    Database::execute(
                        'INSERT INTO lessons (course_id, section_id, title, lesson_type, video_provider, video_url, duration_seconds, content, is_preview, status, position) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                        [$courseId, $sid, $l['title'], $l['type'], $l['video_provider'], $l['video_url'], $seconds, $l['type'] === 'article' ? $l['content'] : null, ($l['preview'] && $l['type'] !== 'quiz') ? 1 : 0, $status, ++$lp]
                    );
                    $lid = Database::insertId(); $out['lessons']++;
                    if ($status === 'published') { $out['published']++; }
                    if ($l['type'] === 'quiz') { $out['questions'] += self::saveQuestions($lid, $l['questions'], 0); }
                }
            }
            return $out;
        });
    }

    /** Insert questions + options for a lesson, appending after $startPos. Returns how many were added. */
    public static function saveQuestions(int $lessonId, array $questions, int $startPos): int
    {
        $pos = $startPos;
        foreach ($questions as $q) {
            Database::execute('INSERT INTO quiz_questions (lesson_id, question, explanation, position) VALUES (?,?,?,?)', [$lessonId, $q['question'], $q['explanation'], ++$pos]);
            $qid = Database::insertId();
            foreach ($q['options'] as $i => $text) {
                Database::execute('INSERT INTO quiz_options (question_id, option_text, is_correct, position) VALUES (?,?,?,?)', [$qid, $text, $i === $q['correct'] ? 1 : 0, $i + 1]);
            }
        }
        return count($questions);
    }
}
