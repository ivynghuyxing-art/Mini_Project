<?php
    include '../_base.php';
    $_title = 'reset Password';
    $_err = [];
    // ----------------------------------------------------------------------------

    $_db->query('DELETE FROM token WHERE expire < NOW()');

    $id = req('id');

    if (!is_exists($id, 'token', 'id')) {
        temp('info', 'Invalid token. Try again');
        redirect('/');
    }

    if (is_post()) {
        $password = req('password');
        $confirm  = req('confirm');

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
                SET password = ?
                WHERE user_id = (SELECT user_id FROM token WHERE id = ?)
            ');
            $stm->execute([$password, $id]);

            $stm = $_db->prepare('DELETE FROM token WHERE id = ?');
            $stm->execute([$id]);

            temp('info', 'Record updated');
            redirect('/login/login.php');
        }
    }
?>

<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
    <link rel="stylesheet" href="/css/app.css">
</head>

<form method="post" class="tokenForm">
    <label for="password">Password</label>
    <?php html_password('password', 'class="password_input"') ?>
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

<?php
include '../_footer.php';

