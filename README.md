# Task Dashboard — MVP

A minimal, working task-assignment system: managers assign tasks, employees update their status.
Built small on purpose so it's easy to extend.

## Setup

1. Create the database: import `schema.sql` (phpMyAdmin, Adminer, or `mysql -u root -p < schema.sql`).
2. Edit `config.php` with your real DB host/user/password.
3. Upload the whole folder to your PHP host (or run locally: `php -S localhost:8000` from the project root).
4. Log in at `login.php` with one of the seeded accounts (all use password `password123`):
   - manager@example.com (manager)
   - employee1@example.com (employee)
   - employee2@example.com (employee)
5. **Change these passwords / delete the test accounts before going live.**

## How it works

- `users` table holds everyone, with a `role` column (`manager` / `employee`).
- `tasks` table holds title, description, who it's assigned to/by, status, due date.
- Managers see `manager/dashboard.php`: stat cards (total/pending/in-progress/completed/overdue tasks), a per-employee workload breakdown with completion bars, a form to assign tasks, and a filterable/searchable table of every task. "Overdue" means `due_date` has passed and the task isn't `completed` — computed on the fly, no schema change needed.
- Employees see `employee/dashboard.php`: only their own tasks, with a dropdown to update status.
- `includes/auth.php` handles login sessions and keeps employees out of manager pages and vice versa.

## Where to extend next (once this MVP feels solid)

- Add an `admin` role that can create/manage users through the UI (right now, add users directly in the `users` table with a bcrypt-hashed password).
- Task comments/discussion thread — a `task_comments` table (task_id, user_id, comment, created_at).
- File attachments — a `task_attachments` table + upload handling.
- Priority field on tasks (`low` / `medium` / `high`).
- Email notifications when a task is assigned or completed (PHPMailer).
- A reports page — task counts by status/employee, overdue tasks, etc.

Each of these can be added as its own small module without touching what already works.
