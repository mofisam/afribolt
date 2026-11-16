-- Career Launch now requires a student account. Links each registration to its user and tracks the stage they are on.
-- Run after 005. Existing (guest) registrations keep user_id NULL and are linked automatically when that email logs in and joins.
USE live_learning_platform;

ALTER TABLE career_launch_registrations
    ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN stage TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER status, -- 1-6 = current stage, 7 = all stages complete
    ADD UNIQUE KEY uq_career_launch_user (user_id),
    ADD CONSTRAINT fk_career_launch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
