-- Self-paced learning (Udemy-style): curriculum, lessons, quizzes, progress, reviews, certificates.
-- Requires MySQL 8.0.16+ (CHECK constraints). Run after schema.sql and migrations 001-002.
USE live_learning_platform;

-- 1) Courses gain delivery mode, pricing and cached stats
ALTER TABLE courses
    ADD COLUMN delivery_mode ENUM('live','self_paced','both') NOT NULL DEFAULT 'live' AFTER course_level,
    ADD COLUMN instructor_id BIGINT UNSIGNED NULL AFTER delivery_mode,
    ADD COLUMN price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER instructor_id,
    ADD COLUMN price_currency CHAR(3) NOT NULL DEFAULT 'NGN' AFTER price,
    ADD COLUMN promo_price DECIMAL(12,2) NULL AFTER price_currency,
    ADD COLUMN promo_ends_at DATETIME NULL AFTER promo_price,
    ADD COLUMN preview_video_url VARCHAR(500) NULL AFTER promo_ends_at,
    ADD COLUMN language VARCHAR(40) NOT NULL DEFAULT 'English' AFTER preview_video_url,
    ADD COLUMN has_certificate TINYINT(1) NOT NULL DEFAULT 1 AFTER language,
    ADD COLUMN cohort_includes_access TINYINT(1) NOT NULL DEFAULT 1 AFTER has_certificate,
    ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN learners_count INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN lessons_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN total_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    ADD KEY idx_courses_delivery (delivery_mode, status),
    ADD CONSTRAINT fk_courses_instructor FOREIGN KEY (instructor_id) REFERENCES instructors (id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT chk_course_prices CHECK (price >= 0 AND (promo_price IS NULL OR (promo_price >= 0 AND promo_price <= price)));

-- 2) Curriculum
CREATE TABLE course_sections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sections_course_pos (course_id, position),
    CONSTRAINT fk_sections_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lessons (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    lesson_type ENUM('video','article','quiz') NOT NULL DEFAULT 'video',
    video_provider ENUM('youtube','vimeo','file') NULL,
    video_url VARCHAR(500) NULL,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    content MEDIUMTEXT NULL,
    is_preview TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lessons_section_pos (section_id, position),
    KEY idx_lessons_course_status (course_id, status),
    CONSTRAINT fk_lessons_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT fk_lessons_section FOREIGN KEY (section_id) REFERENCES course_sections (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lesson_resources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lesson_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_resources_lesson (lesson_id),
    CONSTRAINT fk_resources_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lesson_id BIGINT UNSIGNED NOT NULL,
    question TEXT NOT NULL,
    explanation TEXT NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_questions_lesson_pos (lesson_id, position),
    CONSTRAINT fk_questions_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id BIGINT UNSIGNED NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_options_question (question_id),
    CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES quiz_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3) Learner access, progress, attempts, reviews
CREATE TABLE course_access (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    source ENUM('purchase','free','admin_grant','cohort') NOT NULL DEFAULT 'purchase',
    status ENUM('pending','active','revoked','refunded') NOT NULL DEFAULT 'pending',
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    enrolled_at DATETIME NULL,
    last_lesson_id BIGINT UNSIGNED NULL,
    last_accessed_at DATETIME NULL,
    completed_at DATETIME NULL,
    certificate_code VARCHAR(40) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_access_user_course (user_id, course_id),
    UNIQUE KEY uq_access_certificate (certificate_code),
    KEY idx_access_course_status (course_id, status),
    CONSTRAINT fk_access_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_access_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT chk_access_amount CHECK (amount_paid >= 0)
) ENGINE=InnoDB;

CREATE TABLE lesson_progress (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    quiz_best_score TINYINT UNSIGNED NULL,
    position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progress_user_lesson (user_id, lesson_id),
    KEY idx_progress_user_course (user_id, course_id),
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL,
    score_percent TINYINT UNSIGNED NOT NULL,
    correct_count SMALLINT UNSIGNED NOT NULL,
    total_count SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_user_lesson (user_id, lesson_id),
    CONSTRAINT fk_attempts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_attempts_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE course_reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    status ENUM('published','hidden') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_review_course_user (course_id, user_id),
    KEY idx_reviews_course_status (course_id, status, created_at),
    CONSTRAINT fk_reviews_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- 4) Payments can now be for a self-paced course (no cohort enrolment)
ALTER TABLE payments
    MODIFY enrolment_id BIGINT UNSIGNED NULL,
    ADD COLUMN course_access_id BIGINT UNSIGNED NULL AFTER enrolment_id,
    ADD KEY idx_payments_course_access (course_access_id),
    ADD CONSTRAINT fk_payments_course_access FOREIGN KEY (course_access_id) REFERENCES course_access (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT chk_payment_target CHECK (enrolment_id IS NOT NULL OR course_access_id IS NOT NULL);
