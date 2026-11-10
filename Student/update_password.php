<?php 
include '../_base.php';
$_user = $_SESSION['user'] ?? null;

$_err =[];
auth(['student']);

if (is_post()) {
        $current = req('current');
        $password = req('password');
        $confirm  = req('confirm');

        if($current == ''){
            $_err['current'] = 'Required';
        }
        else if($current == $password){
            $_err['current'] = 'Current and new password cannot be same!';
        }
        else{
            $stm = $_db->prepare('SELECT COUNT(*) FROM user WHERE user_id =? AND password = SHA(?)');
            $stm->execute([$_user->user_id, $current]);
            if($stm->fetchColumn() == 0){
                $_err['current'] = 'Not matched';
            }
        }

        if ($password == '') {
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
        else if (!preg_match('/[!@#$%^&*()_+]/', $password)) {
            $_err['password'] = 'Must contain at least one special character';
        }

        if ($confirm == '') {
            $_err['confirm'] = 'Required';
        }
        else if (strlen($confirm) < 8 || strlen($confirm) > 20) {
            $_err['confirm'] = 'Between 8-20 characters';
        }
        else if ($confirm != $password) {
            $_err['confirm'] = 'Not matched';
        }
        else if (!preg_match('/[A-Z]/', $confirm)) {
            $_err['confirm'] = 'Must contain at least one uppercase letter';
        }
        else if (!preg_match('/[a-z]/', $confirm)) {
            $_err['confirm'] = 'Must contain at least one lowercase letter';
        }
        else if (!preg_match('/[0-9]/', $confirm)) {
            $_err['confirm'] = 'Must contain at least one number';
        }
        else if (!preg_match('/[!@#$%^&*()_+]/', $confirm)) {
            $_err['confirm'] = 'Must contain at least one special character';
        }
        
        if (!$_err) {
            $stm = $_db->prepare('
                UPDATE user
                SET password = SHA(?)
                WHERE user_id = ?
            ');
            $stm->execute([$password, $_user->user_id]);

            temp('info', 'Record updated');
            redirect('/Student/profile.php');
        }
    }
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Library Hub' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>

<header class="header">
        <div class ="logo">
            <img src="/images/logo.png" alt="Logo">
        </div>
</header>

<form method="post" class="update_pass_form">
    <label for="password">Current Password</label>
    <?php html_password('current', 'class="password_input" id="current"') ?>
    <?php err('current') ?>

    <label for="password">Password</label>
    <?php html_password('password', 'class="password_input" id="password"') ?>
    <?php err('password') ?>

    <label for="confirm">Confirm</label>
    <?php html_password('confirm', 'class="password_input"') ?>
    <?php err('confirm') ?>

    <div class="password_progress">
        <div id="percentage_password" class="pass-percentage"></div>
    </div>
    <p id="message_password"></p>
        
    <div class="auth-form__row">
            <a href="javascript:void(0)"
            id="allToggleBtn"
            onclick="toggleAllPassword()"
            class="auth-link-muted">
                Show All Passwords
            </a>
    </div>

    <p class="pass_contain">Password must contain:</p>
        <ul>
            <li>Must contain at least 8 or 20 characters</li>
            <li>Must contain at least one uppercase letter</li>
            <li>Must contain at least one lowercase letter</li>
            <li>Must contain at least one number</li>
            <li>Must contain at least one special character (!@#$%^&*()_+)</li>
        </ul> 

    <section>
        <button type="submit">Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

<script>
    document.getElementById('password').addEventListener('input', evalPasswordStrength);
    evalPasswordStrength();
</script>

<?php
include '../_footer.php';



?>
