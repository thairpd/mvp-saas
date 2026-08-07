-- TaskFlow MVP: task review workflow
-- Run this ONCE after task_attachments.sql in the same database.

USE task_dashboard;

ALTER TABLE tasks
    MODIFY status ENUM('pending','in_progress','submitted','needs_review','rejected','completed')
        NOT NULL DEFAULT 'pending',
    ADD COLUMN completion_note TEXT NULL AFTER description,
    ADD COLUMN submitted_at DATETIME NULL AFTER completion_note,
    ADD COLUMN reviewed_by INT NULL AFTER submitted_at,
    ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD COLUMN review_note TEXT NULL AFTER reviewed_at,
    ADD INDEX idx_tasks_status (status),
    ADD INDEX idx_tasks_submitted_at (submitted_at);

ALTER TABLE tasks
    ADD CONSTRAINT fk_tasks_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS task_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    action ENUM('approved','rejected','needs_review') NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_task_reviews_task_id (task_id),
    CONSTRAINT fk_task_reviews_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_task_reviews_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
