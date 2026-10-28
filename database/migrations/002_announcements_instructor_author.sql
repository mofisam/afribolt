-- Lets instructors post announcements to their own cohorts and see/archive only their own.
USE live_learning_platform;

ALTER TABLE announcements
    ADD COLUMN created_by_instructor_id BIGINT UNSIGNED NULL AFTER created_by_admin_id,
    ADD KEY idx_announcements_instructor (created_by_instructor_id),
    ADD CONSTRAINT fk_announcements_instructor FOREIGN KEY (created_by_instructor_id) REFERENCES instructors (id) ON DELETE SET NULL;
