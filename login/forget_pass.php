<?php
    include '../_base.php';
    $_title = 'Reset Password';
    $_err=[];
    // ----------------------------------------------------------------------------

    if (is_post()) {
        $email = req('email');

        if ($email == '') {
            $_err['email'] = 'Required';
        }
        else if (!is_email($email)) {
            $_err['email'] = 'Invalid email';
        }
        else if (!is_exists($email, 'user', 'email')) {
            $_err['email'] = 'Not exists';
        }

        if (!$_err) {
            $stm = $_db->prepare('SELECT * FROM user WHERE email = ?');
            $stm->execute([$email]);
            $u = $stm->fetch();

            $id = SHA1(uniqid() . rand());

            $stm = $_db->prepare('DELETE FROM token WHERE user_id = ?');
            $stm->execute([$u->user_id]);

            $stm = $_db->prepare('
                INSERT INTO token (id, expire, user_id)
                VALUES (?, ADDTIME(NOW(), "00:05"), ?)
            ');
            $stm->execute([$id, $u->user_id]);

            $url = base("login/token.php?id=$id");

            $m = get_mail();
            $m->addAddress($u->email, $u->name);
            $m->isHTML(true);
            $m->Subject = 'Reset Password';
            $m->Body = "
                <p>Dear $u->name,
                You are requesting a password reset for your account.< p>
                <h1 style='color: red'>Reset Password</h1>
                <p>
                    Please click this url <a href='$url'>here</a>
                    to reset your password.
                </p>
                <p>From, Management</p>
            ";
            $m->send();
            temp('info', 'Email sent');
            redirect('/login/login.php');
        }
    }
?>

<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>

<div class="auth-card">
    <p class="auth-card__eyebrow">Forgot your password?</p>
    <h2 class="auth-card__title">Reset Password</h2>

    <form method="post" class="auth-form">
        <label for="email" class="sr-only">Email</label>
        <?php html_text('email', 'placeholder="Email address" maxlength="100"') ?>
        <?php err('email') ?>

        <button class="auth-btn-primary">Submit</button>

        <p class="auth-footer-text">
            Remember your password? <a href="login.php" class="auth-link">Back to Login</a>
        </p>
    </form>
</div>

<?php
    include '../_foot.php';
?>