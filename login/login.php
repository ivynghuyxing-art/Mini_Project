
<?php
    include '../_base.php';
    $_title = 'Login';

    if (is_post()) {
        $email    = req('email');
        $password = req('password');

        $_err = [];

        // Validate: email
        if ($email == '') {
            $_err['email'] = 'Required';
        }
        else if (!is_email($email)) {
            $_err['email'] = 'Invalid email';
        }

        // Validate: password
        if ($password == '') {
            $_err['password'] = 'Required';
        }

        // Login
        if (!$_err) {
            $stm = $_db->prepare('
                SELECT * FROM user
                WHERE email = ? AND password = SHA1(?)
            ');
            $stm->execute([$email, $password]);
            $u = $stm->fetch();

            if ($u) {
                temp('info', 'Login successfully, Welcome ' . $u->username);

                // Redirect based on user role
                $url = ($u->role == 'admin')
                    ? '/admin/home.php'
                    : '/';

                login($u, $url);
            }
            else {
                $_err['password'] = 'Not matched';
            }
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
    <div class="logo">
        <img src="/images/logo.png" alt="Logo">
    </div>
</header>

<div class="auth-card">
    <h2 class="auth-card__title">Welcome back</h2>

    <form method="post" class="auth-form">

        <label for="email" class="sr-only">Email</label>
        <?php html_text('email', 'placeholder="Email address" maxlength="100"') ?>
        <?php err('email') ?>

        <label for="password" class="sr-only">Password</label>
        <?php html_password('password', 'placeholder="Password" maxlength="100"') ?>
        <?php err('password') ?>

        <div class="auth-form__row">
            <a href="javascript:void(0)"
               id="toggleBtn"
               onclick="toggleViews()"
               class="auth-link-muted">
                Show Password
            </a>

            <a href="forget_pass.php" class="auth-link">
                Forgot password?
            </a>
        </div>

        <button class="auth-btn-primary">Login</button>

        <p class="auth-footer-text">
            Don't have an account?
            <a href="register.php" class="auth-link">Register here</a>
        </p>

        <p class="auth-footer-text">
            <a href="/_head.php" class="auth-link">
                ← Back to Home Page
            </a>
        </p>

    </form>
</div>

<?php
    include '../_footer.php';
?>
