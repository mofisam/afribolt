-- Lets the notification bell tell a student when a general announcement (not tied to a cohort) is new to them.
-- Run after 001-005.
USE live_learning_platform;

ALTER TABLE users ADD COLUMN announcements_seen_at DATETIME NULL;
