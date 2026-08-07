<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_role('manager');

function removeTaskAttachments(PDO $pdo, int $taskId): void
{
    $stmt = $pdo->prepare("SELECT file_path FROM task_attachments WHERE task_id = ?");
    $stmt->execute([$taskId]);
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($files as $relativePath) {
        $absolute = __DIR__ . '/../' . ltrim($relativePath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    $delete = $pdo->prepare("DELETE FROM task_attachments WHERE task_id = ?");
    $delete->execute([$taskId]);

    $dir = __DIR__ . '/../uploads/tasks/' . $taskId;
    if (is_dir($dir)) {
        $remaining = glob($dir . '/*');
        if (!$remaining) {
            @rmdir($dir);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
$action = $_POST['action'] ?? '';
$note = trim($_POST['review_note'] ?? '');
$managerId = (int)$_SESSION['user_id'];

if (!$taskId || !in_array($action, ['approve', 'reject', 'needs_review'], true)) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, status
    FROM tasks
    WHERE id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task || $task['status'] !== 'submitted') {
    header('Location: dashboard.php?review=invalid');
    exit;
}

$pdo->beginTransaction();

try {
    if ($action === 'approve') {
        // Accepted tasks are permanently completed.
        $update = $pdo->prepare("
            UPDATE tasks
            SET status='completed',
                reviewed_by=?,
                reviewed_at=NOW(),
                review_note=?
            WHERE id=?
        ");
        $update->execute([$managerId, $note !== '' ? $note : null, $taskId]);

        $history = $pdo->prepare("
            INSERT INTO task_reviews (task_id, reviewer_id, action, note)
            VALUES (?, ?, 'approved', ?)
        ");
        $history->execute([$taskId, $managerId, $note !== '' ? $note : null]);

        // Remove all task attachments after the manager accepts the work.
        removeTaskAttachments($pdo, $taskId);

        $redirect = 'dashboard.php?review=approved';
    } elseif ($action === 'reject') {
        $update = $pdo->prepare("
            UPDATE tasks
            SET status='rejected',
                reviewed_by=?,
                reviewed_at=NOW(),
                review_note=?
            WHERE id=?
        ");
        $update->execute([$managerId, $note !== '' ? $note : null, $taskId]);

        $history = $pdo->prepare("
            INSERT INTO task_reviews (task_id, reviewer_id, action, note)
            VALUES (?, ?, 'rejected', ?)
        ");
        $history->execute([$taskId, $managerId, $note !== '' ? $note : null]);

        $redirect = 'dashboard.php?review=rejected';
    } else {
        $update = $pdo->prepare("
            UPDATE tasks
            SET status='needs_review',
                reviewed_by=?,
                reviewed_at=NOW(),
                review_note=?
            WHERE id=?
        ");
        $update->execute([$managerId, $note !== '' ? $note : null, $taskId]);

        $history = $pdo->prepare("
            INSERT INTO task_reviews (task_id, reviewer_id, action, note)
            VALUES (?, ?, 'needs_review', ?)
        ");
        $history->execute([$taskId, $managerId, $note !== '' ? $note : null]);

        $redirect = 'dashboard.php?review=needs_review';
    }

    $pdo->commit();
    header('Location: ' . $redirect);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: review_task.php?id=' . $taskId . '&error=1');
    exit;
}
