<?php
    include '../_base.php';
    $_title = 'Reset Password';
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

        if ($confirm == '') {
            $_err['confirm'] = 'Required';
        }
        else if (strlen($confirm) < 8 || strlen($confirm) > 20) {
            $_err['confirm'] = 'Between 8-20 characters';
        }
        else if ($confirm != $password) {
            $_err['confirm'] = 'Not matched';
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
</head>

<form method="post" class="tokenForm">
    <label for="password">Password</label>
    <?php html_password('password', 'class="pass-input" maxlength="20"') ?>
    <?php err('password') ?>

    <label for="confirm">Confirm</label>
    <?php html_password('confirm', 'class="pass-input" maxlength="20"') ?>
    <?php err('confirm') ?>

    <div style="text-align: baseline; margin-top: 10px; margin-bottom: 20px;">
        <a href="javascript:void(0)"
        id="allToggleBtn"
        onclick="toggleAllPasswords()">
        Show All Passwords
        </a>
    </div>

    <section>
        <button type="submit">Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

<?php
include '../_foot.php';

