<?php
include_once __DIR__ . '/_base.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Library Hub' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>

<body>
    <header class="header">
        <div class ="logo">
            <img src="/images/logo.png" alt="Logo">
        </div>

        <div class="actions">
            <?php if($_user): ?>
                <a href="/logout.php" class="header-btn">Logout</a>
            <?php else: ?>
                <a href="login/login.php" class="header-btn">Login</a>
               
            <?php endif; ?>

        </div>

    </header>

    <aside class="sidebar-menu">
        <nav class="sidebar">
            <p class= "sidebar-title">Overview</p>
            <a href="/student/dashboard.php" class="sidebar-link <?= $current_page =='dashboard.php'?'active':'' ?>"> Home</a>

            <p class="sidebar-title">Books</p>
            <a href="/student/book/browse.php" class="sidebar-link <?= $current_page == 'browse.php'?'active':'' ?>"> Browse Books</a>

            <p class="sidebar-title">My Activities</p>
            <a href="/Student/activities/my_borrowing.php" class="sidebar-link <?= $current_page == 'my_borrowing.php'?'active':'' ?>"> My Borrowing</a>
            <a href="/student/activities/reservation.php" class="sidebar-link <?= $current_page == 'reservation.php'?'active':'' ?>"> My Reservations</a>
            <a href="/student/activities/fines.php" class="sidebar-link <?= $current_page == 'fines.php'?'active':'' ?>"> My Fines</a>
            <a href="/student/activities/history.php" class="sidebar-link <?= $current_page == 'history.php'?'active':'' ?>"> My Borrowing History</a>
            <a href="/student/activities/rating.php" class="sidebar-link <?= $current_page == 'rating.php'?'active':'' ?>"> My Ratings</a>

            <p class="sidebar-title">Engagement</p>
            <a href="/student/engagement/wishlist.php" class="sidebar-link <?=$current_page == 'wishlist.php'?'active':'' ?>"> My WishList</a>

            <p class="sidebar-title">Account</p>
            <a href="/student/profile.php" class="sidebar-link <?= $current_page == 'profile.php'?'active':'' ?>"> My Profile</a> 

            <p class ="sidebar-title">Conversations</p>
            <a href ="/student/conversation/conversation.php" class="sidebar-link <?= $current_page == 'chat.php'?'active':'' ?>"> Chat</a>
        

        </nav>
    </aside>
    <div id="info"><?= temp('info') ?></div>