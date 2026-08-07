<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_role('employee');

$taskId = (int)($_POST['task_id'] ?? 0);
$action = $_POST['action'] ?? '';
$status = $_POST['status'] ?? '';
$completionNote = trim($_POST['completion_note'] ?? '');

$stmt = $pdo->prepare("SELECT id, status FROM tasks WHERE id = ? AND assigned_to = ?");
$stmt->execute([$taskId, $_SESSION['user_id']]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: dashboard.php');
    exit;
}

if ($action === 'submit') {
    // An employee can submit from normal work states or after manager requested changes.
    if (in_array($task['status'], ['pending','in_progress','needs_review','rejected'], true)) {
        $update = $pdo->prepare("
            UPDATE tasks
            SET status='submitted',
                completion_note=?,
                submitted_at=NOW(),
                reviewed_by=NULL,
                reviewed_at=NULL
            WHERE id=? AND assigned_to=?
        ");
        $update->execute([$completionNote !== '' ? $completionNote : null, $taskId, $_SESSION['user_id']]);
    }
} elseif ($action === 'save' && in_array($status, ['pending','in_progress'], true)) {
    if (in_array($task['status'], ['pending','in_progress','needs_review','rejected'], true)) {
        $update = $pdo->prepare("
            UPDATE tasks
            SET status=?
            WHERE id=? AND assigned_to=?
        ");
        $update->execute([$status, $taskId, $_SESSION['user_id']]);
    }
}

header('Location: task.php?id=' . $taskId);
exit;
