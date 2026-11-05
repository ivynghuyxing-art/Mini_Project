<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
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
            <a href="/student/dashboard.php" class="sidebar-link">Home</a>

            <p class="sidebar-title">Books</p>
            <a href="/student/book/browse.php" class="sidebar-link">Browse Books</a>

            <p class="sidebar-title">My Activities</p>
            <a href="/student/activities/borrowing.php" class="sidebar-link">My Borrowing</a>
            <a href="/student/activities/reservation.php" class="sidebar-link">My Reservations</a>
            <a href="/student/activities/fines.php" class="sidebar-link">My Fines</a>
            <a href="/student/activities/history.php" class="sidebar-link">My Borrowing History</a>
            <a href="/student/activities/rating.php" class="sidebar-link">My Ratings</a>

            <p class="sidebar-title">Engagement</p>
            <a href="/student/engagement/wishlist.php" class="sidebar-link">Wish List</a>

            <p class="sidebar-title">Account</p>
            <a href="/student/profile.php" class="sidebar-link">My Profile</a> 
        

        </nav>
    </aside>