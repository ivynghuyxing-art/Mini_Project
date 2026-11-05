<?php
include '../_base.php';
$_title = 'Register';

$name     = req('username');
$email    = req('email');
$password = req('password');
$confirm  = req('confirm');


if (is_post()) {
    $_err = [];

    if (!$email) {
        $_err['email'] = 'Required';
    }
    else if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    }
    else if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    }
    else if (!is_unique($email, 'user', 'email')) {
        $_err['email'] = 'Duplicated';
    }

    if (!$password) {
        $_err['password'] = 'Required';
    }
    else if (strlen($password) < 8 || strlen($password) > 20) {
        $_err['password'] = 'Between 8-20 characters';
    }
    else if (!preg_match('/[A-Z]/', $password)) {
        $_err['password'] = 'Must contain at least one uppercase letter';
    }
    else if (!preg_match('/[a-z]/', $password)) {
        $_err['password'] = 'Must contain at least one lowercase letter';
    }
    else if (!preg_match('/[0-9]/', $password)) {
        $_err['password'] = 'Must contain at least one number';
    }
    else if (!preg_match('/[\W_]/', $password)) {
        $_err['password'] = 'Must contain at least one special character';
    }

    if (!$confirm) {
        $_err['confirm'] = 'Required';
    }
    else if (strlen($confirm) < 8 || strlen($confirm) > 20) {
        $_err['confirm'] = 'Between 8-20 characters';
    }
    else if ($confirm != $password) {
        $_err['confirm'] = 'Not matched';
    }

    if (!$name) {
        $_err['username'] = 'Required';
    }
    else if (strlen($name) > 100) {
        $_err['username'] = 'Maximum 100 characters';
    }

    

    if (!$_err) {
        $stm = $_db->prepare('
            INSERT INTO user (name, email, password, gender)
            VALUES (?, ?, SHA1(?), ?)
        ');
        $stm->execute([$name, $email, $password]);

        temp('info', 'Registered! Please login.');
        redirect('/login/login.php');
        exit();
    }
}

?>

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

    <div class="auth-card">
        <p class="auth-card__eyebrow">Just a few details</p>
        <h2 class="auth-card__title">Create an account</h2>

        <form method="post" class="auth-form">
            <label for="username" class="sr-only">Name</label>
            <?php html_text('username', 'placeholder="Full name" maxlength="100"', $name) ?>
            <?php err('username') ?>

            <label for="email" class="sr-only">Email</label>
            <?php html_text('email', 'placeholder="Email address" maxlength="254"', $email) ?>
            <?php err('email') ?>

            <label for="password" class="sr-only">Password</label>
            <?php html_password('password', 'class="pass-input" placeholder="Password" maxlength="20"', $password) ?>
            <?php err('password') ?>

            <div class="auth-form__row">
                <div id="percent_pass" style="width:0%" class="pass-percentage"></div>
            </div>
            <p>Password must contain:</p>
            <lu>
                <li>Must contain at least 8 or 20 characters</li>
                <li>Must contain at least one uppercase letter</li>
                <li>Must contain at least one lowercase letter</li>
                <li>Must contain at least one number</li>
                <li>Must contain at least one special character</li>
            </lu>
    c
            <label for="confirm" class="sr-only">Confirm password</label>
            <?php html_password('confirm', 'class="pass-input" placeholder="Confirm password" maxlength="20"', $confirm) ?>
            <?php err('confirm') ?>

        

            <div class="auth-form__row">
                <a href="javascript:void(0)"
                id="allToggleBtn"
                onclick="toggleAllPasswords()"
                class="auth-link-muted">
                    Show All Passwords
                </a>
            </div>

            <button class="auth-btn-primary">Register</button>

            <p class="auth-footer-text">
                Already have an account? <a href="login.php" class="auth-link">Login here</a>
            </p>
        </form>
    </div>

    <?php
    include '../_foot.php';
    ?>