-- Jobs board: aggregated Nigerian job postings, cleaned/categorised/scored by AI, with recommendations.
-- Requires MySQL 8.0.16+. Run after 001-003.
USE live_learning_platform;

CREATE TABLE job_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_job_categories_slug (slug)
) ENGINE=InnoDB;

INSERT INTO job_categories (name, slug, display_order) VALUES
 ('Software & IT', 'software-it', 1), ('Data & Analytics', 'data-analytics', 2), ('Design & Creative', 'design-creative', 3),
 ('Marketing & Sales', 'marketing-sales', 4), ('Customer Service', 'customer-service', 5), ('Finance & Accounting', 'finance-accounting', 6),
 ('Banking & Insurance', 'banking-insurance', 7), ('Engineering & Technical', 'engineering-technical', 8), ('Healthcare', 'healthcare', 9),
 ('Education & Training', 'education-training', 10), ('Admin & Office', 'admin-office', 11), ('Human Resources', 'human-resources', 12),
 ('Legal', 'legal', 13), ('Logistics & Supply Chain', 'logistics-supply-chain', 14), ('Hospitality & Tourism', 'hospitality-tourism', 15),
 ('Oil, Gas & Energy', 'oil-gas-energy', 16), ('Agriculture', 'agriculture', 17), ('NGO & Development', 'ngo-development', 18),
 ('Media & Communications', 'media-communications', 19), ('Retail & FMCG', 'retail-fmcg', 20), ('Other', 'other', 99);

CREATE TABLE job_sources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    source_type ENUM('rss','jooble','json_api','jsonld') NOT NULL,
    endpoint_url VARCHAR(500) NULL,
    config JSON NULL,
    permission_note TEXT NULL,
    fetch_interval_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 180,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    last_run_at DATETIME NULL,
    last_status ENUM('ok','partial','failed') NULL,
    last_message VARCHAR(500) NULL,
    jobs_total INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_job_sources_enabled (is_enabled, last_run_at)
) ENGINE=InnoDB;

CREATE TABLE jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_id BIGINT UNSIGNED NULL,
    external_id VARCHAR(191) NULL,
    canonical_hash CHAR(40) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    title VARCHAR(250) NOT NULL,
    company VARCHAR(200) NULL,
    category_id BIGINT UNSIGNED NULL,
    location_text VARCHAR(250) NULL,
    state VARCHAR(60) NULL,
    city VARCHAR(100) NULL,
    work_mode ENUM('onsite','remote','hybrid','unspecified') NOT NULL DEFAULT 'unspecified',
    employment_type ENUM('full_time','part_time','contract','internship','temporary','volunteer','unspecified') NOT NULL DEFAULT 'unspecified',
    experience_level ENUM('entry','mid','senior','executive','unspecified') NOT NULL DEFAULT 'unspecified',
    salary_min DECIMAL(14,2) NULL,
    salary_max DECIMAL(14,2) NULL,
    salary_currency CHAR(3) NULL,
    salary_text VARCHAR(150) NULL,
    summary TEXT NULL,
    description MEDIUMTEXT NULL,
    skills JSON NULL,
    source_url VARCHAR(1000) NOT NULL,
    source_name VARCHAR(150) NULL,
    posted_at DATETIME NULL,
    deadline_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    status ENUM('pending','published','hidden','rejected','expired') NOT NULL DEFAULT 'pending',
    ai_status ENUM('none','done','failed','skipped') NOT NULL DEFAULT 'none',
    ai_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ai_model VARCHAR(60) NULL,
    quality_score TINYINT UNSIGNED NULL,
    scam_risk TINYINT UNSIGNED NOT NULL DEFAULT 0,
    scam_flags JSON NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    apply_clicks INT UNSIGNED NOT NULL DEFAULT 0,
    raw_hash CHAR(40) NULL,
    published_at DATETIME NULL,
    created_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jobs_slug (slug),
    UNIQUE KEY uq_jobs_source_external (source_id, external_id),
    KEY idx_jobs_status_published (status, published_at),
    KEY idx_jobs_canonical (canonical_hash),
    KEY idx_jobs_ai (ai_status, status),
    KEY idx_jobs_category (category_id, status),
    KEY idx_jobs_state (state, status),
    CONSTRAINT fk_jobs_source FOREIGN KEY (source_id) REFERENCES job_sources (id) ON DELETE SET NULL,
    CONSTRAINT fk_jobs_category FOREIGN KEY (category_id) REFERENCES job_categories (id) ON DELETE SET NULL,
    CONSTRAINT chk_jobs_scam CHECK (scam_risk <= 100)
) ENGINE=InnoDB;

CREATE TABLE job_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_id BIGINT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    status ENUM('ok','partial','failed') NOT NULL DEFAULT 'ok',
    fetched SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    inserted SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    duplicates SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    skipped SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    message VARCHAR(500) NULL,
    PRIMARY KEY (id),
    KEY idx_job_runs_source (source_id, started_at),
    CONSTRAINT fk_job_runs_source FOREIGN KEY (source_id) REFERENCES job_sources (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE saved_jobs (
    user_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, job_id),
    CONSTRAINT fk_saved_jobs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_jobs_job FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_job_preferences (
    user_id BIGINT UNSIGNED NOT NULL,
    categories JSON NULL,
    skills TEXT NULL,
    states JSON NULL,
    work_modes JSON NULL,
    levels JSON NULL,
    alerts_enabled TINYINT(1) NOT NULL DEFAULT 0,
    last_alert_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_job_prefs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE job_recommendations (
    user_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    reason VARCHAR(400) NULL,
    tip VARCHAR(300) NULL,
    generated_at DATETIME NOT NULL,
    PRIMARY KEY (user_id, job_id),
    CONSTRAINT fk_job_recs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_job_recs_job FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE job_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    reason ENUM('scam','expired','duplicate','inappropriate','other') NOT NULL DEFAULT 'scam',
    details VARCHAR(500) NULL,
    status ENUM('open','resolved') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_job_reports_status (status, created_at),
    CONSTRAINT fk_job_reports_job FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE CASCADE,
    CONSTRAINT fk_job_reports_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Job-board settings (editable in Admin > Settings)
INSERT IGNORE INTO platform_settings (setting_key, setting_value, value_type, description, is_public) VALUES
 ('jobs_auto_publish', '1', 'boolean', 'Publish jobs automatically when AI checks pass (otherwise every job waits for manual review).', 0),
 ('jobs_scam_hold_threshold', '60', 'number', 'Jobs with a scam-risk score at or above this are held for review (0-100).', 0),
 ('jobs_min_quality', '35', 'number', 'Jobs with a quality score below this are held for review (0-100).', 0),
 ('jobs_stale_days', '45', 'number', 'Jobs not seen at their source for this many days are marked expired.', 0);
