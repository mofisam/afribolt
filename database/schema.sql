-- ============================================================
-- Live Online Educational Platform
-- MySQL 8.0.16+ | InnoDB | utf8mb4 | PHP MySQLi compatible
-- Version: 1.0
--
-- Architecture:
--   * users: student accounts
--   * admin_users: separate platform administrator accounts
--   * instructors: optional instructor accounts/profiles
--   * courses: reusable course catalogue entries
--   * cohorts: scheduled programme batches with independent rules
--   * class_sessions: individual live classes and meeting links
--
-- IMPORTANT:
--   1. Review database name and currency/timezone defaults before use.
--   2. Never store raw card details or plaintext gateway secrets here.
--   3. Verify payment transactions server-side with the payment provider.
--   4. Enforce permissions, capacity and registration rules in PHP too.
-- ============================================================

CREATE DATABASE IF NOT EXISTS afribolt
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE afribolt;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ------------------------------------------------------------
-- 1. Student accounts
-- ------------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_image VARCHAR(500) NULL,
    email_verified_at DATETIME NULL,
    phone_verified_at DATETIME NULL,
    account_status ENUM('pending_verification','active','suspended','closed')
        NOT NULL DEFAULT 'pending_verification',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status (account_status),
    KEY idx_users_name (last_name, first_name)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Separate platform administrator accounts
-- ------------------------------------------------------------
CREATE TABLE admin_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    admin_role ENUM('super_admin','admin','content_manager','finance_manager')
        NOT NULL DEFAULT 'admin',
    account_status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_users_email (email),
    KEY idx_admin_users_status (account_status),
    CONSTRAINT fk_admin_created_by
        FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Optional instructors
-- ------------------------------------------------------------
CREATE TABLE instructors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NULL,
    biography TEXT NULL,
    profile_image VARCHAR(500) NULL,
    account_status ENUM('invited','active','suspended') NOT NULL DEFAULT 'invited',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_instructors_email (email),
    KEY idx_instructors_status (account_status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Course categories
-- ------------------------------------------------------------
CREATE TABLE course_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_course_categories_name (name),
    UNIQUE KEY uq_course_categories_slug (slug),
    KEY idx_course_categories_status_order (status, display_order)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. Courses: reusable catalogue information, not a dated cohort
-- ------------------------------------------------------------
CREATE TABLE courses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    short_description VARCHAR(500) NULL,
    description LONGTEXT NULL,
    learning_objectives TEXT NULL,
    prerequisites TEXT NULL,
    thumbnail_url VARCHAR(500) NULL,
    default_duration_weeks SMALLINT UNSIGNED NULL,
    course_level ENUM('beginner','intermediate','advanced','all_levels')
        NOT NULL DEFAULT 'all_levels',
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    created_by_admin_id BIGINT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_courses_slug (slug),
    KEY idx_courses_category_status (category_id, status),
    KEY idx_courses_status_created (status, created_at),
    CONSTRAINT fk_courses_category
        FOREIGN KEY (category_id) REFERENCES course_categories(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_courses_created_by
        FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. Cohorts: a specific scheduled run of a course
-- Registration policies are configured separately for every cohort.
-- Store event timestamps in UTC; display in the configured timezone.
-- ------------------------------------------------------------
CREATE TABLE cohorts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    cohort_name VARCHAR(180) NOT NULL,
    cohort_code VARCHAR(80) NOT NULL,
    summary TEXT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Africa/Lagos',

    registration_opens_at DATETIME NULL,
    registration_closes_at DATETIME NULL,
    allow_registration_after_start TINYINT(1) NOT NULL DEFAULT 0,
    registration_enabled TINYINT(1) NOT NULL DEFAULT 1,
    capacity INT UNSIGNED NULL,
    waitlist_enabled TINYINT(1) NOT NULL DEFAULT 0,

    fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    require_payment TINYINT(1) NOT NULL DEFAULT 1,
    payment_plan ENUM('full','deposit') NOT NULL DEFAULT 'full',
    deposit_amount DECIMAL(12,2) NULL,

    status ENUM('draft','published','open','closed','in_progress',
                'postponed','completed','cancelled')
        NOT NULL DEFAULT 'draft',
    meeting_provider_default ENUM('google_meet','zoom','microsoft_teams','other','none')
        NOT NULL DEFAULT 'none',
    registration_instructions TEXT NULL,
    created_by_admin_id BIGINT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cohorts_code (cohort_code),
    KEY idx_cohorts_course_status (course_id, status),
    KEY idx_cohorts_start (start_at),
    KEY idx_cohorts_registration_window (registration_opens_at, registration_closes_at),
    KEY idx_cohorts_status_start (status, start_at),
    CONSTRAINT fk_cohorts_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cohorts_created_by
        FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_cohort_dates CHECK (end_at IS NULL OR end_at >= start_at),
    CONSTRAINT chk_cohort_registration_dates CHECK (
        registration_opens_at IS NULL OR registration_closes_at IS NULL
        OR registration_closes_at >= registration_opens_at
    ),
    CONSTRAINT chk_cohort_fee CHECK (fee_amount >= 0),
    CONSTRAINT chk_cohort_deposit CHECK (
        deposit_amount IS NULL OR (deposit_amount >= 0 AND deposit_amount <= fee_amount)
    )
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. Instructor assignments to cohorts
-- ------------------------------------------------------------
CREATE TABLE cohort_instructors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cohort_id BIGINT UNSIGNED NOT NULL,
    instructor_id BIGINT UNSIGNED NOT NULL,
    assignment_role ENUM('lead','assistant') NOT NULL DEFAULT 'lead',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cohort_instructor (cohort_id, instructor_id),
    KEY idx_cohort_instructors_instructor (instructor_id),
    CONSTRAINT fk_cohort_instructors_cohort
        FOREIGN KEY (cohort_id) REFERENCES cohorts(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cohort_instructors_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructors(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. Student enrolments
-- A student can enrol once per cohort.
-- Payment attempts and payment history are stored separately.
-- ------------------------------------------------------------
CREATE TABLE enrolments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    enrolment_number VARCHAR(40) NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    cohort_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending_payment','pending_review','confirmed','waitlisted',
                'cancelled','completed','refunded')
        NOT NULL DEFAULT 'pending_payment',
    fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    completed_at DATETIME NULL,
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_enrolments_number (enrolment_number),
    UNIQUE KEY uq_enrolments_student_cohort (student_id, cohort_id),
    KEY idx_enrolments_cohort_status (cohort_id, status),
    KEY idx_enrolments_student_status (student_id, status),
    KEY idx_enrolments_registered (registered_at),
    CONSTRAINT fk_enrolments_student
        FOREIGN KEY (student_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_enrolments_cohort
        FOREIGN KEY (cohort_id) REFERENCES cohorts(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_enrolment_amounts CHECK (
        fee_amount >= 0 AND amount_paid >= 0
    )
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. Payment gateway configuration
-- Store secrets encrypted by the application, or preferably in
-- environment/secret management. Never store plaintext secrets.
-- ------------------------------------------------------------
CREATE TABLE payment_gateways (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider_code VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    public_key VARCHAR(255) NULL,
    secret_key_encrypted TEXT NULL,
    webhook_secret_encrypted TEXT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    is_test_mode TINYINT(1) NOT NULL DEFAULT 1,
    supported_currencies JSON NULL,
    configuration_json JSON NULL,
    created_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_gateways_provider (provider_code),
    KEY idx_payment_gateways_enabled (is_enabled),
    CONSTRAINT fk_payment_gateways_created_by
        FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. Payments
-- Supports multiple attempts, deposits, balance payments and refunds.
-- ------------------------------------------------------------
CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    enrolment_id BIGINT UNSIGNED NOT NULL,
    gateway_id BIGINT UNSIGNED NULL,
    payment_type ENUM('course_fee','deposit','balance','refund','adjustment')
        NOT NULL DEFAULT 'course_fee',
    provider_reference VARCHAR(191) NULL,
    internal_reference VARCHAR(80) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    status ENUM('initiated','pending','successful','failed','cancelled',
                'refunded','partially_refunded')
        NOT NULL DEFAULT 'initiated',
    payment_method VARCHAR(80) NULL,
    provider_transaction_id VARCHAR(191) NULL,
    provider_response JSON NULL,
    failure_reason VARCHAR(500) NULL,
    paid_at DATETIME NULL,
    refunded_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_internal_reference (internal_reference),
    UNIQUE KEY uq_payments_provider_reference (gateway_id, provider_reference),
    KEY idx_payments_enrolment_status (enrolment_id, status),
    KEY idx_payments_status_created (status, created_at),
    KEY idx_payments_provider_transaction (provider_transaction_id),
    CONSTRAINT fk_payments_enrolment
        FOREIGN KEY (enrolment_id) REFERENCES enrolments(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_gateway
        FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_payment_amount CHECK (amount >= 0 AND refunded_amount >= 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 11. Payment webhook/event idempotency
-- Prevents duplicate provider notifications from processing twice.
-- ------------------------------------------------------------
CREATE TABLE payment_webhook_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    gateway_id BIGINT UNSIGNED NOT NULL,
    provider_event_id VARCHAR(191) NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    payload JSON NULL,
    processing_status ENUM('received','processed','failed','ignored')
        NOT NULL DEFAULT 'received',
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    processing_error VARCHAR(1000) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_webhook_gateway_event (gateway_id, provider_event_id),
    KEY idx_webhook_processing (processing_status, received_at),
    CONSTRAINT fk_webhook_gateway
        FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 12. Live class sessions
-- Meeting links should be available only to authorised users.
-- ------------------------------------------------------------
CREATE TABLE class_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cohort_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    session_number SMALLINT UNSIGNED NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Africa/Lagos',
    meeting_provider ENUM('google_meet','zoom','microsoft_teams','other','none')
        NOT NULL DEFAULT 'none',
    meeting_url TEXT NULL,
    meeting_id VARCHAR(191) NULL,
    meeting_passcode_encrypted TEXT NULL,
    join_early_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    status ENUM('scheduled','live','completed','postponed','cancelled')
        NOT NULL DEFAULT 'scheduled',
    recording_url TEXT NULL,
    created_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sessions_cohort_start (cohort_id, starts_at),
    KEY idx_sessions_status_start (status, starts_at),
    CONSTRAINT fk_sessions_cohort
        FOREIGN KEY (cohort_id) REFERENCES cohorts(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sessions_created_by
        FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_session_times CHECK (ends_at > starts_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 13. Cohort announcements
-- ------------------------------------------------------------
CREATE TABLE announcements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cohort_id BIGINT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    body LONGTEXT NOT NULL,
    visibility ENUM('public','enrolled_students','staff_only')
        NOT NULL DEFAULT 'enrolled_students',
    publish_at DATETIME NULL,
    expires_at DATETIME NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    created_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_announcements_cohort_status (cohort_id, status, publish_at),
    CONSTRAINT fk_announcements_cohort
        FOREIGN KEY (cohort_id) REFERENCES cohorts(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_announcements_created_by
        FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 14. Notifications (in-platform)
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_entity_type VARCHAR(60) NULL,
    related_entity_id BIGINT UNSIGNED NULL,
    action_url VARCHAR(500) NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user_read_created (user_id, read_at, created_at),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 15. Email notification delivery queue/log
-- The actual sending worker is application code or a scheduled job.
-- ------------------------------------------------------------
CREATE TABLE email_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    recipient_email VARCHAR(191) NOT NULL,
    template_code VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    related_entity_type VARCHAR(60) NULL,
    related_entity_id BIGINT UNSIGNED NULL,
    status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_email_logs_status_queued (status, queued_at),
    KEY idx_email_logs_user (user_id),
    CONSTRAINT fk_email_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 16. Password reset tokens
-- Store only a hash of the random token, not the raw token.
-- ------------------------------------------------------------
CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_token_hash (token_hash),
    KEY idx_password_reset_user_expiry (user_id, expires_at),
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 17. Email verification tokens
-- ------------------------------------------------------------
CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_verification_token_hash (token_hash),
    KEY idx_email_verification_user_expiry (user_id, expires_at),
    CONSTRAINT fk_email_verification_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 18. Audit logs
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id BIGINT UNSIGNED NULL,
    instructor_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_admin_created (admin_user_id, created_at),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    KEY idx_audit_action_created (action, created_at),
    CONSTRAINT fk_audit_admin
        FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_audit_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructors(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 19. Platform settings
-- Keep non-secret settings here. Keep secrets encrypted or in env vars.
-- ------------------------------------------------------------
CREATE TABLE platform_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(150) NOT NULL,
    setting_value TEXT NULL,
    value_type ENUM('string','number','boolean','json') NOT NULL DEFAULT 'string',
    description VARCHAR(500) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_platform_settings_key (setting_key),
    CONSTRAINT fk_platform_settings_updated_by
        FOREIGN KEY (updated_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 20. Optional attendance tracking
-- Instructor/admin records attendance per enrolled student per session.
-- ------------------------------------------------------------
CREATE TABLE session_attendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    enrolment_id BIGINT UNSIGNED NOT NULL,
    attendance_status ENUM('present','late','absent','excused')
        NOT NULL DEFAULT 'present',
    joined_at DATETIME NULL,
    left_at DATETIME NULL,
    notes VARCHAR(500) NULL,
    marked_by_admin_id BIGINT UNSIGNED NULL,
    marked_by_instructor_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance_session_enrolment (session_id, enrolment_id),
    KEY idx_attendance_enrolment (enrolment_id),
    CONSTRAINT fk_attendance_session
        FOREIGN KEY (session_id) REFERENCES class_sessions(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attendance_enrolment
        FOREIGN KEY (enrolment_id) REFERENCES enrolments(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attendance_admin
        FOREIGN KEY (marked_by_admin_id) REFERENCES admin_users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_attendance_instructor
        FOREIGN KEY (marked_by_instructor_id) REFERENCES instructors(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Suggested public platform settings (editable in admin panel)
-- ------------------------------------------------------------
INSERT INTO platform_settings (setting_key, setting_value, value_type, description, is_public)
VALUES
('platform_name', 'Online Learning Platform', 'string', 'Public platform name', 1),
('default_timezone', 'Africa/Lagos', 'string', 'Default display timezone for schedules', 1),
('default_currency', 'NGN', 'string', 'Default currency for programme fees', 1),
('support_email', '', 'string', 'Public support email address', 1)
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

-- End of schema.
-- Recommended application-level rules:
-- * Use prepared statements for all user-provided SQL values.
-- * Hash passwords with PHP password_hash(); verify with password_verify().
-- * Store and compare timestamps consistently; render them in cohort timezone.
-- * Confirm payment with the provider API/webhook before confirming enrolment.
-- * Process webhook events idempotently using payment_webhook_events.
-- * Enforce registration windows and capacity in a database transaction.
-- * Only confirmed/eligible enrolments can retrieve session meeting URLs.
-- * Calculate countdowns from cohort/session timestamps; do not store seconds
--   remaining in the database.
