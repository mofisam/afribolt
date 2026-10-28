<?php
namespace App\Core;

/**
 * Rules (pipe separated): required, email, min:n, max:n, numeric, integer,
 * same:otherField, in:a,b,c, phone, url, date, nullable
 */
final class Validator
{
    private array $errors = [];
    private array $clean = [];

    private function __construct(private array $data, private array $rules) {}

    public static function make(array $data, array $rules): self
    {
        $v = new self($data, $rules);
        $v->run();
        return $v;
    }

    public function fails(): bool { return $this->errors !== []; }
    public function errors(): array { return $this->errors; }
    public function validated(): array { return $this->clean; }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $raw = $this->data[$field] ?? null;
            // never trim passwords: spaces may be intentional
            $value = is_string($raw) && !str_contains($field, 'password') ? trim($raw) : $raw;
            $label = ucfirst(str_replace('_', ' ', $field));
            $empty = $value === null || $value === '';

            if ($empty) {
                if (in_array('required', $rules, true)) {
                    $this->errors[$field] = "{$label} is required.";
                }
                $this->clean[$field] = null;
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $len = is_string($value) ? mb_strlen($value) : 0;
                $error = match ($name) {
                    'email'   => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "{$label} must be a valid email address.",
                    'min'     => $len < (int) $arg ? "{$label} must be at least {$arg} characters." : null,
                    'max'     => $len > (int) $arg ? "{$label} may not be longer than {$arg} characters." : null,
                    'numeric' => is_numeric($value) ? null : "{$label} must be a number.",
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "{$label} must be a whole number.",
                    'same'    => ($this->data[$arg] ?? null) === $raw ? null : "{$label} does not match.",
                    'in'      => in_array((string) $value, explode(',', (string) $arg), true) ? null : "{$label} is not a valid choice.",
                    'url'     => (filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', (string) $value)) ? null : "{$label} must be a valid http(s) link.",
                    'date'    => strtotime((string) $value) !== false ? null : "{$label} must be a valid date.",
                    'phone'   => preg_match('/^\+?[0-9\s\-]{7,20}$/', (string) $value) ? null : "{$label} must be a valid phone number.",
                    default   => null,
                };
                if ($error) {
                    $this->errors[$field] = $error;
                    break;
                }
            }
            $this->clean[$field] = $value;
        }
    }
}
