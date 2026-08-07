<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_role('manager');

$me = current_user();

$employees = $pdo->query("
    SELECT id,name
    FROM users
    WHERE role='employee'
    ORDER BY name
")->fetchAll();

$totalTasks = (int)$pdo->query("
    SELECT COUNT(*)
    FROM tasks
")->fetchColumn();

$counts = [
    'pending'=>0,
    'in_progress'=>0,
    'submitted'=>0,
    'needs_review'=>0,
    'rejected'=>0,
    'completed'=>0
];

foreach(
    $pdo->query("
        SELECT status,COUNT(*) c
        FROM tasks
        GROUP BY status
    ")
    as $row
){
    $counts[$row['status']] = (int)$row['c'];
}

$reviewCount=(int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status='submitted'")->fetchColumn();

$overdueCount=(int)$pdo->query("
SELECT COUNT(*)
FROM tasks
WHERE due_date IS NOT NULL
AND due_date<CURDATE()
AND status!='completed'
")->fetchColumn();


// ---------------- Per-employee workload ----------------
$workload = $pdo->query("
    SELECT
        u.id, u.name,
        COUNT(t.id) AS total,
        COALESCE(SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END),0) AS completed,
        COALESCE(SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END),0) AS in_progress,
        COALESCE(SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END),0) AS pending,
        COALESCE(SUM(CASE WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE()
            AND t.status != 'completed' THEN 1 ELSE 0 END),0) AS overdue
    FROM users u
    LEFT JOIN tasks t ON t.assigned_to = u.id
    WHERE u.role='employee'
    GROUP BY u.id, u.name
    ORDER BY u.name
")->fetchAll();

// ---------------- Recent tasks ----------------
$tasks = $pdo->query("
    SELECT t.*, u.name AS assignee_name
    FROM tasks t
    JOIN users u ON u.id = t.assigned_to
    ORDER BY t.created_at DESC
    LIMIT 10
")->fetchAll();

$employeeCount=count($employees);

$completionRate=
$totalTasks>0
?
round(($counts['completed']/$totalTasks)*100)
:
0;

$pageTitle="Dashboard";

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1">

<title>TaskFlow Manager Dashboard</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
rel="stylesheet">

<link
rel="preconnect"
href="https://fonts.googleapis.com">

<link
rel="preconnect"
href="https://fonts.gstatic.com"
crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="../assets/css/dashboard-v2.css">

</head>

<body>

<div class="wrapper">

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

<?php include "../includes/topbar.php"; ?>

<div class="content">

<div class="page-header">

<div>

<h1>
Welcome,
<?= htmlspecialchars($me['name']) ?>
👋
</h1>

<p>

Monitor your team performance,
assign new work,
and track progress in real time.

</p>

</div>

<div class="header-actions">

<a href="assign_task.php" class="btn btn-primary">

<i class="fa-solid fa-plus"></i>

New Task

</a>

<button class="btn btn-outline-light">

<i class="fa-solid fa-chart-line"></i>

Reports

</button>

</div>

</div>

<?php if (($_GET['task'] ?? '') === 'created'): ?>
<div class="task-success-alert"><i class="fa-solid fa-circle-check"></i> Task assigned successfully.</div>
<?php elseif (($_GET['review'] ?? '') === 'approved'): ?>
<div class="task-success-alert"><i class="fa-solid fa-circle-check"></i> Task accepted and marked as completed. Its attachments were removed from the server.</div>
<?php elseif (($_GET['review'] ?? '') === 'needs_review'): ?>
<div class="task-form-alert review-warning-alert"><i class="fa-solid fa-rotate"></i> Review request sent back to the employee.</div>
<?php elseif (($_GET['review'] ?? '') === 'rejected'): ?>
<div class="task-form-alert"><i class="fa-solid fa-circle-xmark"></i> Task was rejected and returned to the employee.</div>
<?php endif; ?>

<!-- Statistics Row Starts -->
<div class="stats-grid">

    <div class="stat-card total">
        <div class="stat-icon">
            <i class="fa-solid fa-list-check"></i>
        </div>

        <div class="stat-info">
            <span>Total Tasks</span>
            <h2><?= number_format($totalTasks) ?></h2>
            <small><?= $employeeCount ?> Employees</small>
        </div>
    </div>

    <div class="stat-card pending">
        <div class="stat-icon">
            <i class="fa-solid fa-clock"></i>
        </div>

        <div class="stat-info">
            <span>Pending</span>
            <h2><?= number_format($counts['pending']) ?></h2>
            <small>Awaiting Start</small>
        </div>
    </div>

    <div class="stat-card in-progress">
        <div class="stat-icon">
            <i class="fa-solid fa-spinner"></i>
        </div>

        <div class="stat-info">
            <span>In Progress</span>
            <h2><?= number_format($counts['in_progress']) ?></h2>
            <small>Currently Active</small>
        </div>
    </div>

    <div class="stat-card completed">
        <div class="stat-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <div class="stat-info">
            <span>Completed</span>
            <h2><?= number_format($counts['completed']) ?></h2>
            <small><?= $completionRate ?>% Completed</small>
        </div>
    </div>

    <div class="stat-card overdue">
        <div class="stat-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="stat-info">
            <span>Overdue</span>
            <h2><?= number_format($overdueCount) ?></h2>
            <small>Need Attention</small>
        </div>
    </div>

</div>

<div class="dashboard-grid">

    <div class="dashboard-left">

        <div class="widget">

            <div class="widget-header">

                <h3>Task Overview</h3>

                <button class="btn btn-sm btn-light">
                    This Month
                </button>

            </div>

            <div class="chart-box">

                <canvas id="taskChart"></canvas>

            </div>

        </div>

    </div>

    <div class="dashboard-right">

        <div class="widget quick-stats">

            <h3>Quick Summary</h3>

            <div class="summary-item">

                <div>

                    <strong><?= $totalTasks ?></strong>

                    <span>Total Tasks</span>

                </div>

                <i class="fa-solid fa-list-check"></i>

            </div>

            <div class="summary-item">

                <div>

                    <strong><?= $employeeCount ?></strong>

                    <span>Employees</span>

                </div>

                <i class="fa-solid fa-users"></i>

            </div>

            <div class="summary-item">

                <div>

                    <strong><?= $completionRate ?>%</strong>

                    <span>Completion Rate</span>

                </div>

                <i class="fa-solid fa-chart-line"></i>

            </div>

            <div class="summary-item">

                <div>

                    <strong><?= $reviewCount ?></strong>

                    <span>Awaiting Review</span>

                </div>

                <i class="fa-solid fa-clipboard-check"></i>

            </div>

            <div class="summary-item">

                <div>

                    <strong><?= $overdueCount ?></strong>

                    <span>Overdue Tasks</span>

                </div>

                <i class="fa-solid fa-fire"></i>

            </div>

        </div>

    </div>

</div>

<!-- Team Workload -->

<div class="dashboard-grid dashboard-secondary-grid">

    <div class="widget team-workload-widget">



        <div class="widget-header">

            <h3>

                <i class="fa-solid fa-users"></i>

                Team Workload

            </h3>

        </div>

        <?php if(empty($workload)): ?>

            <div class="empty-state">

                <i class="fa-solid fa-user-slash"></i>

                <p>No Employees Found</p>

            </div>

        <?php else: ?>

            <?php foreach($workload as $member):

                $percent = $member['total'] > 0
                    ? round(($member['completed'] / $member['total']) * 100)
                    : 0;

            ?>

            <div class="team-card">

                <div class="team-header">

                    <div class="avatar">

                        <?= strtoupper(substr($member['name'],0,1)); ?>

                    </div>

                    <div>

                        <strong>

                            <?= htmlspecialchars($member['name']); ?>

                        </strong>

                        <small>

                            <?= $member['total']; ?> Tasks

                        </small>

                    </div>

                    <span class="percent">

                        <?= $percent; ?>%

                    </span>

                </div>

                <div class="team-progress">

                    <div
                        class="progress-bar"
                        style="width:<?= $percent; ?>%">
                    </div>

                </div>

                <div class="team-footer">

                    <span title="Completed">
                        <i class="fa-solid fa-circle-check"></i>
                        <?= $member['completed']; ?>
                    </span>

                    <span title="In Progress">
                        <i class="fa-solid fa-rotate"></i>
                        <?= $member['in_progress']; ?>
                    </span>

                    <span title="Pending">
                        <i class="fa-solid fa-clock"></i>
                        <?= $member['pending']; ?>
                    </span>

                    <?php if($member['overdue']>0): ?>
                        <span class="text-danger" title="Overdue">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <?= $member['overdue']; ?>
                        </span>
                    <?php endif; ?>

                </div>

            </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>


    </div>

</div>

<!-- Recent Tasks -->
<div class="widget">

    <div class="widget-header">

        <h3>

            <i class="fa-solid fa-list-check"></i>

            Recent Tasks

        </h3>

    </div>

    <?php if(empty($tasks)): ?>

        <div class="empty-state">

            <i class="fa-solid fa-folder-open"></i>

            <p>No tasks available.</p>

        </div>

    <?php else: ?>

    <div class="table-responsive">

        <table class="table dashboard-table align-middle">

            <thead>

                <tr>

                    <th>Task</th>

                    <th>Employee</th>

                    <th>Due Date</th>

                    <th>Status</th>

                    <th>Created</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach($tasks as $task):

                $isOverdue =
                    $task['due_date']
                    &&
                    $task['due_date'] < date('Y-m-d')
                    &&
                    $task['status'] != 'completed';

            ?>

            <tr>

                <td>

                    <strong>

                        <?= htmlspecialchars($task['title']); ?>

                    </strong>

                </td>

                <td>

                    <?= htmlspecialchars($task['assignee_name']); ?>

                </td>

                <td>

                    <?= $task['due_date'] ?: '-'; ?>

                </td>

                <td>

                    <?php

                    $badge='secondary';

                    if($task['status']=='pending') $badge='warning';

                    if($task['status']=='in_progress') $badge='primary';

                    if($task['status']=='submitted') $badge='info';

                    if($task['status']=='needs_review') $badge='warning';

                    if($task['status']=='rejected') $badge='danger';

                    if($task['status']=='completed') $badge='success';

                    ?>

                    <span class="badge bg-<?= $badge; ?>">

                        <?= ucwords(str_replace('_',' ',$task['status'])); ?>

                    </span>

                    <?php if($isOverdue): ?>

                        <span class="badge bg-danger">

                            Overdue

                        </span>

                    <?php endif; ?>

                    <?php if($task['status']==='submitted'): ?>

                        <a href="review_task.php?id=<?= (int)$task['id'] ?>" class="table-review-link">

                            Review

                        </a>

                    <?php endif; ?>

                </td>

                <td>

                    <?= date('d M Y',strtotime($task['created_at'])); ?>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php endif; ?>

</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../assets/js/dashboard.js"></script>

<script>

const ctx=document.getElementById('taskChart');

if(ctx){

new Chart(ctx,{

type:'doughnut',

data:{

labels:[

'Pending',

'In Progress',

'Completed'

],

datasets:[{

data:[

<?= $counts['pending']; ?>,

<?= $counts['in_progress']; ?>,

<?= $counts['completed']; ?>

],

backgroundColor:[

'#f59e0b',

'#3b82f6',

'#10b981'

],

borderWidth:0

}]

},

options:{

responsive:true,

plugins:{

legend:{

position:'bottom'

}

},

cutout:'70%'

}

});

}

</script>

</body>

</html>