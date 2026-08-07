<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_role('employee');

$me = current_user();
$taskId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT t.*, u.name AS manager_name
    FROM tasks t
    JOIN users u ON u.id = t.assigned_by
    WHERE t.id = ? AND t.assigned_to = ?
");
$stmt->execute([$taskId, $me['id']]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: dashboard.php');
    exit;
}

$filesStmt = $pdo->prepare("
    SELECT id, original_name, mime_type, file_size
    FROM task_attachments
    WHERE task_id = ?
    ORDER BY uploaded_at ASC
");
$filesStmt->execute([$taskId]);
$attachments = $filesStmt->fetchAll();

$historyStmt = $pdo->prepare("
    SELECT r.*, u.name AS reviewer_name
    FROM task_reviews r
    JOIN users u ON u.id = r.reviewer_id
    WHERE r.task_id = ?
    ORDER BY r.created_at DESC
");
$historyStmt->execute([$taskId]);
$history = $historyStmt->fetchAll();

function file_size_label($bytes): string {
    $bytes = (int)$bytes;
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Task Details - TaskFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard-v2.css?v=3">
<link rel="stylesheet" href="../assets/css/employee.css?v=1">
</head>
<body>
<div class="employee-task-page">
<div class="employee-shell">
<main class="employee-main">
<div class="employee-task-top">
    <a href="dashboard.php" class="employee-back"><i class="fa-solid fa-arrow-left"></i> My Tasks</a>
    <span class="employee-task-status status-<?= h($task['status']) ?>"><?= h(ucwords(str_replace('_',' ',$task['status']))) ?></span>
</div>

<div class="employee-task-grid">
    <section class="widget">
        <div class="employee-task-title">
            <span class="review-eyebrow">Task assigned by <?= h($task['manager_name']) ?></span>
            <h1><?= h($task['title']) ?></h1>
            <p>Created <?= date('d M Y', strtotime($task['created_at'])) ?><?php if ($task['due_date']): ?> · Due <?= h($task['due_date']) ?><?php endif; ?></p>
        </div>

        <div class="review-section">
            <h3><i class="fa-solid fa-align-left"></i> Instructions</h3>
            <div class="review-text"><?= nl2br(h($task['description'] ?: 'No additional instructions were provided.')) ?></div>
        </div>

        <div class="review-section">
            <h3><i class="fa-solid fa-paperclip"></i> Files</h3>
            <?php if (!$attachments): ?>
                <div class="review-empty">No files attached to this task.</div>
            <?php else: ?>
                <div class="attachment-list">
                <?php foreach ($attachments as $file): ?>
                    <a class="attachment-item" href="download_attachment.php?id=<?= (int)$file['id'] ?>">
                        <span class="attachment-icon"><i class="fa-solid fa-file"></i></span>
                        <span class="attachment-details">
                            <strong><?= h($file['original_name']) ?></strong>
                            <small><?= file_size_label($file['file_size']) ?></small>
                        </span>
                        <i class="fa-solid fa-download attachment-download"></i>
                    </a>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($task['review_note']): ?>
        <div class="employee-review-note">
            <div class="employee-review-note-icon"><i class="fa-solid fa-comment-dots"></i></div>
            <div><strong>Manager feedback</strong><p><?= nl2br(h($task['review_note'])) ?></p></div>
        </div>
        <?php endif; ?>
    </section>

    <aside class="widget employee-submit-card">
        <div class="widget-header">
            <h3><i class="fa-solid fa-paper-plane"></i> Update Task</h3>
        </div>

        <?php if (in_array($task['status'], ['pending','in_progress','needs_review','rejected'], true)): ?>
        <form method="post" action="update_task.php">
            <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
            <label for="employee_status">Current progress</label>
            <select id="employee_status" name="status" class="form-control">
                <option value="pending" <?= $task['status']==='pending'?'selected':'' ?>>Pending</option>
                <option value="in_progress" <?= $task['status']==='in_progress'?'selected':'' ?>>In Progress</option>
            </select>
            <button class="employee-save-progress" type="submit" name="action" value="save">
                <i class="fa-solid fa-floppy-disk"></i> Save Progress
            </button>
        </form>

        <div class="employee-submit-divider"><span>Ready to submit?</span></div>

        <form method="post" action="update_task.php" onsubmit="return confirm('Submit this task to the manager for review?');">
            <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
            <label for="completion_note">Completion note</label>
            <textarea id="completion_note" name="completion_note" class="form-control" rows="6" placeholder="Tell the manager what you completed, what was delivered, or anything they should check..."><?= h($task['completion_note'] ?? '') ?></textarea>
            <button class="employee-submit-btn" type="submit" name="action" value="submit">
                <i class="fa-solid fa-paper-plane"></i> Submit for Manager Review
            </button>
        </form>
        <?php elseif ($task['status'] === 'submitted'): ?>
            <div class="employee-awaiting">
                <i class="fa-solid fa-hourglass-half"></i>
                <strong>Waiting for manager review</strong>
                <span>Your work has been submitted. The manager will accept it, reject it, or request changes.</span>
            </div>
        <?php elseif ($task['status'] === 'completed'): ?>
            <div class="employee-complete">
                <i class="fa-solid fa-circle-check"></i>
                <strong>Task completed</strong>
                <span>The manager accepted this task. Stored attachments have been cleaned up.</span>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php if ($history): ?>
<section class="widget employee-history">
    <div class="widget-header"><h3><i class="fa-solid fa-clock-rotate-left"></i> Manager Review History</h3></div>
    <?php foreach ($history as $item): ?>
    <div class="review-history-item">
        <div class="review-history-icon action-<?= h($item['action']) ?>"><i class="fa-solid <?= $item['action']==='approved'?'fa-check':($item['action']==='rejected'?'fa-xmark':'fa-rotate') ?>"></i></div>
        <div><strong><?= h(ucwords(str_replace('_',' ',$item['action']))) ?></strong><span><?= h($item['reviewer_name']) ?> · <?= date('d M Y, H:i', strtotime($item['created_at'])) ?></span><?php if($item['note']): ?><p><?= nl2br(h($item['note'])) ?></p><?php endif; ?></div>
    </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
</main>
</div>
</div>
</body>
</html>
