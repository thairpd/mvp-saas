<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_role('employee');

$me = current_user();

$stmt = $pdo->prepare("
    SELECT id, title, description, due_date, status, created_at, submitted_at, review_note
    FROM tasks
    WHERE assigned_to = ?
    ORDER BY
        CASE status
            WHEN 'needs_review' THEN 1
            WHEN 'rejected' THEN 2
            WHEN 'submitted' THEN 3
            WHEN 'in_progress' THEN 4
            WHEN 'pending' THEN 5
            WHEN 'completed' THEN 6
            ELSE 7
        END,
        created_at DESC
");
$stmt->execute([$me['id']]);
$tasks = $stmt->fetchAll();

function badge_class($status): string {
    return [
        'pending'=>'pending',
        'in_progress'=>'in-progress',
        'submitted'=>'submitted',
        'needs_review'=>'needs-review',
        'rejected'=>'rejected',
        'completed'=>'completed'
    ][$status] ?? 'pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Tasks - TaskFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard-v2.css?v=3">
<link rel="stylesheet" href="../assets/css/employee.css?v=1">
</head>
<body class="employee-dashboard-body">
<div class="employee-shell">
<main class="employee-main">
<header class="employee-header">
    <div>
        <span class="review-eyebrow">Employee Workspace</span>
        <h1>My Tasks</h1>
        <p>Welcome back, <?= h($me['name']) ?>. Review your assignments and submit completed work.</p>
    </div>
    <a href="../logout.php" class="btn btn-outline-light"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</header>

<div class="employee-task-list">
<?php if (!$tasks): ?>
    <div class="widget empty-state"><i class="fa-solid fa-inbox"></i><p>No tasks assigned to you yet.</p></div>
<?php else: ?>
<?php foreach ($tasks as $task): ?>
    <a href="task.php?id=<?= (int)$task['id'] ?>" class="employee-task-row">
        <div class="employee-task-row-icon"><i class="fa-solid fa-list-check"></i></div>
        <div class="employee-task-row-main">
            <strong><?= h($task['title']) ?></strong>
            <span><?= h(mb_strimwidth($task['description'] ?? '', 0, 130, '...')) ?></span>
            <small>
                <?php if ($task['due_date']): ?><i class="fa-regular fa-calendar"></i> Due <?= h($task['due_date']) ?> · <?php endif; ?>
                Created <?= date('d M Y', strtotime($task['created_at'])) ?>
            </small>
        </div>
        <div class="employee-task-row-right">
            <span class="employee-task-status status-<?= h(badge_class($task['status'])) ?>"><?= h(ucwords(str_replace('_',' ',$task['status']))) ?></span>
            <i class="fa-solid fa-chevron-right"></i>
        </div>
    </a>
<?php endforeach; ?>
<?php endif; ?>
</div>
</main>
</div>
</body>
</html>
