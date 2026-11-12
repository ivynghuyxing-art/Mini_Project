<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Library Hub Management' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>

<body>
    <header class="header">
        <div class ="logo">
            <img src="/images/logo.png" alt="Logo">
        </div>

        <div class="actions">
            <a href="logout.php" class="header-btn">Logout</a>
            <a href="settings.php" class="header-btn">Settings</a>
        </div>

    </header>

    <aside class="sidebar-menu">
        <nav class="sidebar">
            <p class= "sidebar-title">Overview</p>
            <a href="/student/dashboard.php" class="sidebar-link">Dashboard</a>

            <p class="sidebar-title">Management</p>
            <a href="/Book/read_book.php" class="sidebar-link">Book Management</a>
            <a href="/student/activities/borrowing.php" class="sidebar-link">Member Management</a>
            <a href="/student/activities/reservation.php" class="sidebar-link">Borrowing Management</a>
            <a href="/student/activities/fines.php" class="sidebar-link">Reservation Management</a>
            <a href="/student/activities/history.php" class="sidebar-link">Fine Management</a>

            

        </nav>
    </aside>