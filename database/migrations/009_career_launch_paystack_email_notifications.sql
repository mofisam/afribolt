-- 1) Career Launch can be paid through Paystack (payments get a career_launch_id).
-- 2) Students can opt out of notification emails (default: on).
-- Run after 008. Needs MySQL 8.0.19+ or MariaDB 10.2+ (DROP CONSTRAINT).
USE live_learning_platform;

ALTER TABLE payments DROP CONSTRAINT chk_payment_target;

ALTER TABLE payments
    MODIFY payment_type ENUM('course_fee','deposit','balance','refund','adjustment','career_launch') NOT NULL DEFAULT 'course_fee',
    ADD COLUMN career_launch_id BIGINT UNSIGNED NULL AFTER course_access_id,
    ADD KEY idx_payments_career_launch (career_launch_id),
    ADD CONSTRAINT fk_payments_career_launch FOREIGN KEY (career_launch_id) REFERENCES career_launch_registrations (id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE payments
    ADD CONSTRAINT chk_payment_target CHECK (enrolment_id IS NOT NULL OR course_access_id IS NOT NULL OR career_launch_id IS NOT NULL);

ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) NOT NULL DEFAULT 1;

-- Paystack needs a gateway row (the webhook ignores events when it is missing). Keys stay in .env.
INSERT IGNORE INTO payment_gateways (provider_code, display_name, is_enabled, is_test_mode, supported_currencies)
VALUES ('paystack', 'Paystack', 1, 1, '["NGN"]');
