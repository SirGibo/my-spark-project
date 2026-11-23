<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project SPARK - Student Portal for Accessing Research Knowledge</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Theme Initialization Script (Prevents Dark/Light Mode Flash) -->
    <script>
        (function () {
            const savedTheme = localStorage.getItem('spark_theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme ? savedTheme : (systemPrefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper { flex: 1; }
        .navbar-spark { background-color: #1a2530; }
        .card-stat { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-stat:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

    <!-- Primary Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-spark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/spark/index.php">
                <i class="bi bi-journal-bookmark-fill text-warning fs-4"></i>
                <span>Project <span class="text-warning">SPARK</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sparkNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="sparkNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/spark/index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/spark/papers/browse.php"><i class="bi bi-search"></i> Browse Research</a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/spark/papers/upload.php"><i class="bi bi-upload"></i> Submit Paper</a>
                        </li>
                        <?php if ($_SESSION['user']['role'] === 'Admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-warning fw-semibold" href="/spark/admin/dashboard.php">
                                    <i class="bi bi-shield-lock"></i> Admin Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <!-- Right Menu: Theme Toggle & User Account -->
                <div class="d-flex align-items-center gap-3 ms-auto">
                    <!-- Theme Switcher Button -->
                    <button class="btn btn-outline-light btn-sm rounded-circle p-2 d-flex align-items-center justify-content-center" id="themeToggleBtn" type="button" title="Toggle Theme" style="width: 36px; height: 36px;">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>

                    <ul class="navbar-nav align-items-lg-center">
                        <?php if (isset($_SESSION['user'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle active d-flex align-items-center gap-2" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle fs-5"></i>
                                    <span><?= htmlspecialchars($_SESSION['user']['username']) ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="/spark/profile.php"><i class="bi bi-person-gear me-2"></i> Account Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="/spark/authentication/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item me-2">
                                <a class="btn btn-outline-light btn-sm px-3" href="/spark/authentication/login.php">Log In</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-warning btn-sm px-3 fw-bold" href="/spark/authentication/register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="main-wrapper">