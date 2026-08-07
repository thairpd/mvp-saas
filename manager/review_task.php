<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_role('manager');

$taskId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT
        t.*,
        employee.name AS employee_name,
        manager.name AS manager_name,
        reviewer.name AS reviewer_name
    FROM tasks t
    JOIN users employee ON employee.id = t.assigned_to
    JOIN users manager ON manager.id = t.assigned_by
    LEFT JOIN users reviewer ON reviewer.id = t.reviewed_by
    WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: dashboard.php');
    exit;
}

$attachmentsStmt = $pdo->prepare("
    SELECT id, original_name, mime_type, file_size, uploaded_at
    FROM task_attachments
    WHERE task_id = ?
    ORDER BY uploaded_at ASC
");
$attachmentsStmt->execute([$taskId]);
$attachments = $attachmentsStmt->fetchAll();

$historyStmt = $pdo->prepare("
    SELECT r.*, u.name AS reviewer_name
    FROM task_reviews r
    JOIN users u ON u.id = r.reviewer_id
    WHERE r.task_id = ?
    ORDER BY r.created_at DESC
");
$historyStmt->execute([$taskId]);
$history = $historyStmt->fetchAll();

$me = current_user();
$pageTitle = 'Review Task';

function format_file_size($bytes): string {
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
<title>Review Task - TaskFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard-v2.css">
</head>
<body>
<div class="wrapper">
<?php include "../includes/sidebar.php"; ?>
<div class="main-content">
<?php include "../includes/topbar.php"; ?>
<div class="content">

<div class="page-header">
    <div>
        <h1>Review Task</h1>
        <p>Check the submitted work before accepting it as completed.</p>
    </div>
    <div class="header-actions">
        <a href="dashboard.php" class="btn btn-outline-light">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="task-form-alert"><i class="fa-solid fa-circle-exclamation"></i><span>The review could not be saved. Please try again.</span></div>
<?php endif; ?>

<div class="review-layout">

    <div class="review-main">

        <div class="widget">
            <div class="review-task-heading">
                <div>
                    <span class="review-eyebrow">Submitted for review</span>
                    <h2><?= h($task['title']) ?></h2>
                    <p>Submitted by <strong><?= h($task['employee_name']) ?></strong>
                    <?php if ($task['submitted_at']): ?> · <?= date('d M Y, H:i', strtotime($task['submitted_at'])) ?><?php endif; ?></p>
                </div>
                <span class="review-status status-submitted">Awaiting Review</span>
            </div>

            <div class="review-divider"></div>

            <div class="review-meta-grid">
                <div><span>Assigned Employee</span><strong><?= h($task['employee_name']) ?></strong></div>
                <div><span>Due Date</span><strong><?= $task['due_date'] ? h($task['due_date']) : 'No deadline' ?></strong></div>
                <div><span>Created</span><strong><?= date('d M Y', strtotime($task['created_at'])) ?></strong></div>
                <div><span>Current Status</span><strong>Submitted</strong></div>
            </div>

            <div class="review-section">
                <h3><i class="fa-solid fa-align-left"></i> Task Description</h3>
                <div class="review-text"><?= nl2br(h($task['description'] ?: 'No description was provided.')) ?></div>
            </div>

            <div class="review-section">
                <h3><i class="fa-solid fa-comment-dots"></i> Employee Completion Note</h3>
                <div class="review-text <?= $task['completion_note'] ? '' : 'review-empty' ?>">
                    <?= $task['completion_note'] ? nl2br(h($task['completion_note'])) : 'The employee did not add a completion note.' ?>
                </div>
            </div>

            <div class="review-section">
                <h3><i class="fa-solid fa-paperclip"></i> Task Attachments</h3>
                <?php if (!$attachments): ?>
                    <div class="review-empty"><i class="fa-regular fa-folder-open"></i> No attachments are currently stored for this task.</div>
                <?php else: ?>
                    <div class="attachment-list">
                    <?php foreach ($attachments as $file): ?>
                        <a class="attachment-item" href="download_attachment.php?id=<?= (int)$file['id'] ?>">
                            <span class="attachment-icon"><i class="fa-solid fa-file"></i></span>
                            <span class="attachment-details">
                                <strong><?= h($file['original_name']) ?></strong>
                                <small><?= format_file_size($file['file_size']) ?></small>
                            </span>
                            <i class="fa-solid fa-download attachment-download"></i>
                        </a>
                    <?php endforeach; ?>
                    </div>
                    <p class="attachment-retention"><i class="fa-solid fa-shield-halved"></i> Attachments are automatically removed from the server when you accept this task.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($history): ?>
        <div class="widget review-history">
            <div class="widget-header"><h3><i class="fa-solid fa-clock-rotate-left"></i> Review History</h3></div>
            <?php foreach ($history as $item): ?>
            <div class="review-history-item">
                <div class="review-history-icon action-<?= h($item['action']) ?>">
                    <i class="fa-solid <?= $item['action']==='approved' ? 'fa-check' : ($item['action']==='rejected' ? 'fa-xmark' : 'fa-rotate') ?>"></i>
                </div>
                <div>
                    <strong><?= h(ucwords(str_replace('_',' ',$item['action']))) ?></strong>
                    <span><?= h($item['reviewer_name']) ?> · <?= date('d M Y, H:i', strtotime($item['created_at'])) ?></span>
                    <?php if ($item['note']): ?><p><?= nl2br(h($item['note'])) ?></p><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <aside class="review-sidebar">
        <div class="widget review-action-card">
            <div class="widget-header">
                <h3><i class="fa-solid fa-clipboard-check"></i> Manager Review</h3>
            </div>

            <?php if ($task['status'] !== 'submitted'): ?>
                <div class="review-locked">
                    <i class="fa-solid fa-lock"></i>
                    <strong>This task is no longer awaiting review.</strong>
                    <span>Current status: <?= h(ucwords(str_replace('_',' ', $task['status']))) ?></span>
                </div>
            <?php else: ?>
                <p class="review-action-helper">Choose the outcome after checking the task details and submitted work.</p>

                <form method="post" action="task_action.php" class="manager-review-form">
                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                    <label for="review_note">Review note <span>Optional</span></label>
                    <textarea id="review_note" name="review_note" class="form-control" rows="5" placeholder="Add feedback, corrections, or an approval note..."></textarea>

                    <div class="review-actions">
                        <button type="submit" name="action" value="approve" class="review-btn approve" onclick="return confirm('Accept this task as completed? Its attachments will be permanently deleted from the server.');">
                            <i class="fa-solid fa-circle-check"></i>
                            Accept & Complete
                        </button>
                        <button type="submit" name="action" value="needs_review" class="review-btn revise">
                            <i class="fa-solid fa-rotate"></i>
                            Request Review
                        </button>
                        <button type="submit" name="action" value="reject" class="review-btn reject">
                            <i class="fa-solid fa-circle-xmark"></i>
                            Reject Task
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="widget review-rule-card">
            <div class="review-rule-icon"><i class="fa-solid fa-hard-drive"></i></div>
            <strong>Automatic file cleanup</strong>
            <p>When you accept a task, its stored attachments are removed from both the server folder and database record.</p>
        </div>
    </aside>
</div>

</div>
</div>
</div>
</body>
</html>
