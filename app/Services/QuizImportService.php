<?php
namespace App\Services;

/**
 * Turns a plain-text question bank into structured quiz questions.
 *
 * Format (blank lines are ignored, lines starting with # are comments):
 *
 *   Q: What does CRM stand for?
 *   A) Customer Relationship Management *
 *   B) Customer Revenue Model
 *   C) Central Record Manager
 *   Explanation: CRM tools track every customer interaction.
 *
 * - A question starts with "Q:", "Q1.", "Question 1:" or "1." / "1)".
 * - Options are A-H followed by ")" or "." and are 2 to 8 per question.
 * - Mark the correct option with a "*" at the end (or start) of its line,
 *   OR add a line "Answer: B" after the options.
 * - "Explanation:" (or "Why:") is optional and may span several lines.
 */
class QuizImportService
{
    public const MAX_QUESTIONS = 200;
    public const MAX_OPTIONS = 8;

    public const SAMPLE = <<<'TXT'
# AfriBolt quiz import: sample file
# Lines that start with # are ignored. Delete these notes if you like.
#
# Each question: "Q:" line, 2 to 8 options (A) B) C) ...), and ONE correct answer.
# Mark the correct answer with a * at the end of the option, OR add an "Answer: B" line.
# "Explanation:" is optional and is shown to learners after they answer.

Q: What does CRM stand for?
A) Customer Relationship Management *
B) Customer Revenue Model
C) Central Record Manager
D) Client Retention Method
Explanation: CRM software tracks every interaction with a customer in one place.

Q: Which of these is a good first step when a customer complains?
A) Argue your side
B) Listen and acknowledge the problem
C) Transfer them straight away
Answer: B
Explanation: People calm down when they feel heard.

Q: Naira is the currency of Nigeria.
A) True *
B) False

Q: A longer question can
continue on the next line like this. Which option is correct?
A) The first one
B) The second one *
C) The third one
D) None of the above
TXT;

    /**
     * @return array{questions: array<int,array{question:string,options:string[],correct:int,explanation:?string}>, errors: string[]}
     */
    public static function parse(string $text): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;       // strip BOM
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');   // Notepad "ANSI" files
        }
        $lines = preg_split('/\R/u', $text) ?: [];

        $raw = [];      // raw question blocks
        $cur = null;
        $inExplanation = false;

        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '' || $t[0] === '#') { continue; }

            if (preg_match('/^(?:Q(?:uestion)?\s*\d*\s*[:.)]|\d+\s*[.)])\s*(.*)$/iu', $t, $m)) {
                if ($cur !== null) { $raw[] = $cur; }
                $cur = ['question' => trim($m[1]), 'options' => [], 'marked' => [], 'answer' => null, 'explanation' => ''];
                $inExplanation = false;
                continue;
            }
            if ($cur === null) { continue; } // text before the first question

            if (preg_match('/^(?:explanation|why|rationale)\s*[:\-]\s*(.*)$/iu', $t, $m)) {
                $cur['explanation'] = trim($m[1]);
                $inExplanation = true;
                continue;
            }
            if (preg_match('/^(?:answer|ans|correct(?:\s+answer)?)\s*[:=\-]\s*\(?([A-Ha-h])\)?\.?\s*$/iu', $t, $m)) {
                $cur['answer'] = strtoupper($m[1]);
                continue;
            }
            if (!$inExplanation && preg_match('/^(\*?)\s*\(?([A-Ha-h])(?:\)\s*|\.\s+)(.+?)\s*(\*?)$/u', $t, $m)) {
                $cur['options'][] = ['letter' => strtoupper($m[2]), 'text' => trim($m[3])];
                if ($m[1] === '*' || $m[4] === '*') { $cur['marked'][] = count($cur['options']) - 1; }
                continue;
            }
            // continuation line
            if ($inExplanation) {
                $cur['explanation'] .= ' ' . $t;
            } elseif (!$cur['options']) {
                $cur['question'] .= ' ' . $t;
            } else {
                $last = count($cur['options']) - 1;
                $cur['options'][$last]['text'] .= ' ' . $t;
            }
        }
        if ($cur !== null) { $raw[] = $cur; }

        $questions = []; $errors = [];
        if (!$raw) {
            return ['questions' => [], 'errors' => ['No questions found. Each question must start with "Q:" (see the sample file).']];
        }
        if (count($raw) > self::MAX_QUESTIONS) {
            $errors[] = 'Too many questions in one file (max ' . self::MAX_QUESTIONS . '). Split the file.';
            $raw = array_slice($raw, 0, self::MAX_QUESTIONS);
        }

        foreach ($raw as $i => $q) {
            $n = $i + 1;
            $label = 'Question ' . $n . ($q['question'] !== '' ? ' ("' . mb_substr($q['question'], 0, 40) . (mb_strlen($q['question']) > 40 ? '...' : '') . '")' : '');
            $bad = [];
            if ($q['question'] === '') { $bad[] = 'the question text is empty'; }
            if (mb_strlen($q['question']) > 2000) { $bad[] = 'the question is longer than 2000 characters'; }
            $count = count($q['options']);
            if ($count < 2) { $bad[] = 'it needs at least 2 options'; }
            if ($count > self::MAX_OPTIONS) { $bad[] = 'it has more than ' . self::MAX_OPTIONS . ' options'; }
            foreach ($q['options'] as $o) { if (mb_strlen($o['text']) > 500) { $bad[] = 'option ' . $o['letter'] . ' is longer than 500 characters'; } }

            $correct = null;
            $marked = array_values(array_unique($q['marked']));
            if (count($marked) > 1) { $bad[] = 'more than one option is marked with *'; }
            elseif (count($marked) === 1) { $correct = $marked[0]; }
            if ($q['answer'] !== null) {
                $idx = null;
                foreach ($q['options'] as $k => $o) { if ($o['letter'] === $q['answer']) { $idx = $k; break; } }
                if ($idx === null) { $bad[] = 'the answer letter ' . $q['answer'] . ' does not match any option'; }
                elseif ($correct !== null && $correct !== $idx) { $bad[] = 'the * mark and the "Answer:" line disagree'; }
                else { $correct = $idx; }
            }
            if ($correct === null && !$bad) { $bad[] = 'no correct answer given (put a * after it or add an "Answer: B" line)'; }

            if ($bad) { $errors[] = $label . ': ' . implode('; ', $bad) . '.'; continue; }

            $questions[] = [
                'question' => $q['question'],
                'options' => array_map(fn($o) => $o['text'], $q['options']),
                'correct' => (int) $correct,
                'explanation' => trim($q['explanation']) !== '' ? trim($q['explanation']) : null,
            ];
        }
        return ['questions' => $questions, 'errors' => $errors];
    }

    /** Read pasted text, or an uploaded .txt file (text wins if both are given). Returns [text|null, error|null]. */
    public static function readInput(?array $file, string $pasted): array
    {
        if (trim($pasted) !== '') { return [$pasted, null]; }
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return [null, 'Choose a .txt file or paste your text.']; }
        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) { return [null, 'The file could not be uploaded. Try again.']; }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['txt', 'text', 'md'], true)) { return [null, 'Please upload a plain-text .txt file.']; }
        if ((int) $file['size'] > 1024 * 1024) { return [null, 'The file is too large (max 1 MB).']; }
        $text = @file_get_contents($file['tmp_name']);
        if ($text === false || trim($text) === '') { return [null, 'The file is empty.']; }
        return [$text, null];
    }
}
