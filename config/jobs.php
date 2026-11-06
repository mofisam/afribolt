<?php
return [
    // Anthropic (Claude) powers cleaning, categorising, scam checks and recommendation explanations.
    'api_key'        => env('ANTHROPIC_API_KEY', ''),
    'model'          => env('JOBS_AI_MODEL', 'claude-haiku-5-5'),      // fast + cheap, used for bulk cleaning
    'rec_model'      => env('JOBS_AI_REC_MODEL', 'claude-haiku-5-5'),  // used for "why this job fits you"
    'ai_batch'       => 8,      // jobs per AI request
    'ai_max_per_run' => 40,     // cap per cron run (controls cost)
    'ai_max_attempts' => 3,

    'jooble_key'     => env('JOOBLE_API_KEY', ''),
    'user_agent'     => 'AfriBoltJobsBot/1.0 (+' . env('APP_URL', 'https://example.com') . '; ' . env('MAIL_FROM_ADDRESS', 'support@example.com') . ')',
    'timeout'        => 20,
    'max_bytes'      => 3 * 1024 * 1024,
    'max_items_per_source' => 100,
    'request_delay_ms' => 600,  // politeness delay between requests to the same host
];
