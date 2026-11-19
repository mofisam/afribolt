-- Employer accounts: companies register, upload verification documents, get approved by an admin, then post jobs.
-- Students apply on the platform. Run after 009.
USE live_learning_platform;

CREATE TABLE employers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_name VARCHAR(200) NOT NULL,
    contact_first_name VARCHAR(100) NOT NULL,
    contact_last_name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    website VARCHAR(300) NULL,
    industry VARCHAR(120) NULL,
    company_size ENUM('1-10','11-50','51-200','201-1000','1000+') NULL,
    rc_number VARCHAR(40) NULL,
    address VARCHAR(300) NULL,
    state VARCHAR(60) NULL,
    description TEXT NULL,
    account_status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    verification_status ENUM('unverified','pending_review','verified','rejected') NOT NULL DEFAULT 'unverified',
    submitted_at DATETIME NULL,
    verified_at DATETIME NULL,
    reviewed_by_admin_id BIGINT UNSIGNED NULL,
    review_note VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_employers_email (email),
    KEY idx_employers_verification (verification_status, submitted_at)
) ENGINE=InnoDB;

-- Verification documents are stored OUTSIDE the web root (storage/private_uploads/employer_docs) and only served to admins.
CREATE TABLE employer_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    employer_id BIGINT UNSIGNED NOT NULL,
    doc_type ENUM('cac_certificate','director_id','tax_id','proof_of_address','other') NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(80) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_employer_docs_employer (employer_id),
    CONSTRAINT fk_employer_docs_employer FOREIGN KEY (employer_id) REFERENCES employers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE jobs
    ADD COLUMN employer_id BIGINT UNSIGNED NULL AFTER source_id,
    ADD KEY idx_jobs_employer (employer_id, status),
    ADD CONSTRAINT fk_jobs_employer FOREIGN KEY (employer_id) REFERENCES employers (id) ON DELETE SET NULL;

CREATE TABLE job_applications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    cover_note TEXT NULL,
    cv_original_name VARCHAR(255) NULL,
    cv_stored_name VARCHAR(80) NULL,
    status ENUM('submitted','viewed','shortlisted','interview','rejected','hired') NOT NULL DEFAULT 'submitted',
    employer_note VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_job_applications (job_id, user_id),
    KEY idx_job_applications_user (user_id, created_at),
    KEY idx_job_applications_job (job_id, status),
    CONSTRAINT fk_job_applications_job FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE CASCADE,
    CONSTRAINT fk_job_applications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;
