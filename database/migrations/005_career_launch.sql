-- Career Launch programme: founding-price registrations (payment link is sent manually by the team).
-- Run after 001-004.
USE live_learning_platform;

CREATE TABLE IF NOT EXISTS career_launch_registrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    target_role VARCHAR(150) NOT NULL,
    linkedin_url VARCHAR(300) NULL,
    referral_code VARCHAR(40) NULL,
    status ENUM('new','payment_sent','paid','cancelled') NOT NULL DEFAULT 'new',
    admin_note VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_career_launch_email (email),
    KEY idx_career_launch_status (status, created_at)
) ENGINE=InnoDB;
