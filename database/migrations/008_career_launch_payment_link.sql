-- The team pastes each student's payment link here; the student sees a Pay button on their dashboard.
-- Run after 007.
USE live_learning_platform;

ALTER TABLE career_launch_registrations ADD COLUMN payment_link VARCHAR(500) NULL AFTER admin_note;
