<?php

if (!isset($me)) {
    $me = current_user();
}

$pageTitle = $pageTitle ?? 'Dashboard';

?>

<header class="topbar">

    <div class="topbar-left">

        <button class="menu-toggle" id="menuToggle">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="page-title">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            <small><?= date('l, d F Y') ?></small>
        </div>

    </div>

    <div class="topbar-center">

        <div class="search-box">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                placeholder="Search tasks, employees..."
                id="dashboardSearch">

        </div>

    </div>

    <div class="topbar-right">

        <button class="icon-btn">
            <i class="fa-regular fa-bell"></i>
            <span class="badge">3</span>
        </button>

        <button class="icon-btn">
            <i class="fa-regular fa-envelope"></i>
            <span class="badge">5</span>
        </button>

        <div class="profile-dropdown">

            <div class="profile-info">

                <div class="avatar">
                    <?= strtoupper(substr($me['name'],0,1)); ?>
                </div>

                <div class="user-info">
                    <strong><?= htmlspecialchars($me['name']); ?></strong>
                    <small>Manager</small>
                </div>

            </div>

            <div class="dropdown-menu">

                <a href="#">
                    <i class="fa-regular fa-user"></i>
                    My Profile
                </a>

                <a href="#">
                    <i class="fa-solid fa-gear"></i>
                    Settings
                </a>

                <hr>

                <a href="../logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>

            </div>

        </div>

    </div>

</header>