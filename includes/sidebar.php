<?php

$current = basename($_SERVER['PHP_SELF']);

$menu = [

    ['title'=>'Dashboard','icon'=>'fa-solid fa-house','url'=>'dashboard.php'],

    ['title'=>'Assign Task','icon'=>'fa-solid fa-plus','url'=>'assign_task.php'],

    ['title'=>'Employees','icon'=>'fa-solid fa-users','url'=>'#'],

    ['title'=>'Projects','icon'=>'fa-solid fa-folder-open','url'=>'#'],

    ['title'=>'Calendar','icon'=>'fa-solid fa-calendar-days','url'=>'#'],

    ['title'=>'Analytics','icon'=>'fa-solid fa-chart-line','url'=>'#'],

    ['title'=>'Reports','icon'=>'fa-solid fa-chart-pie','url'=>'#'],

    ['title'=>'Settings','icon'=>'fa-solid fa-gear','url'=>'#']

];

?>

<aside class="sidebar">

<div class="sidebar-top">

    <div class="logo">

        <div class="logo-icon">
            <i class="fa-solid fa-layer-group"></i>
        </div>

        <div>
            <h2>TaskFlow</h2>
            <small>Management System</small>
        </div>

    </div>

</div>

<nav class="sidebar-menu">

<?php foreach($menu as $item): ?>

<a href="<?= $item['url']; ?>" class="<?= $current==basename($item['url'])?'active':''; ?>">

<i class="<?= $item['icon']; ?>"></i>

<span><?= $item['title']; ?></span>

</a>

<?php endforeach; ?>

</nav>

<div class="sidebar-bottom">

<div class="user-card">

<div class="avatar">

<?= strtoupper(substr($me['name'],0,1)); ?>

</div>

<div>

<strong><?= htmlspecialchars($me['name']); ?></strong>

<small>Manager</small>

</div>

</div>

<a href="../logout.php" class="logout-btn">

<i class="fa-solid fa-right-from-bracket"></i>

Logout

</a>

</div>

</aside>