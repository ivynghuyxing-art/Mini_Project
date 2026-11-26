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
    else if (!preg_match('/[!@#$%^&*()_+]/', $password)) {
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
     else if (strlen($confirm) < 8 || strlen($confirm) > 20) {
        $_err['confirm'] = 'Between 8-20 characters';
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

    if (!$name) {
        $_err['username'] = 'Required';
    }
    else if (strlen($name) > 100) {
        $_err['username'] = 'Maximum 100 characters';
    }

    
if (!$_err) {

    try {
        $_db->beginTransaction();

        // Generate 6-digit verification code
        $verification_code = sprintf('%06d', random_int(0, 999999));

        // Set expiration time (10 minutes)
        $expire = date('Y-m-d H:i:s',time() + 600);

        // Insert user
        $stm = $_db->prepare('
            INSERT INTO user
            (username, email, password, role, valid, email_verified)
            VALUES (?, ?, SHA1(?), ?, 0, 0)
        ');

        $stm->execute([
            $name,
            $email,
            $password,
            'member'
        ]);

        // Get user ID
        $user_id = $_db->lastInsertId();

        // Insert verification code into token table
        $stm = $_db->prepare('
            INSERT INTO token
            (user_id, expire, token_type, verification_code, is_used, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ');

        $stm->execute([
            $user_id,
            $expire,
            'email_verification',
            $verification_code
        ]);

        $_db->commit();

        // Store verification information
        $_SESSION['verify_user_id'] = $user_id;
        $_SESSION['verify_email'] = $email;

        // Send verification email
        $mail = get_mail();

        $mail->addAddress($email, $name);
        $mail->isHTML(true);

        $mail->Subject = 'Verify Your Email - Library Hub';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">

        <style>
            body {
                margin: 0;
                padding: 30px 10px;
                background-color: #f3f4f8;
                font-family: Arial, sans-serif;
            }

            .email-container {
                max-width: 550px;
                margin: auto;
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
            }

            .email-header {
                background: #5148c8;
                padding: 28px;
                text-align: center;
                color: #ffffff;
            }

            .email-header h1 {
                margin: 0;
                font-size: 25px;
            }

            .email-content {
                padding: 35px 30px;
                text-align: center;
                    color: #555555;
            }

            .verification-box {
                background: #f0efff;
                padding: 22px;
                margin: 25px 0;
                border-radius: 8px;
            }

            .verification-code {
                color: #5148c8;
                font-size: 32px;
                letter-spacing: 8px;
                margin: 0;
            }

            .email-footer {
                background: #f8f8fb;
                padding: 20px;
                text-align: center;
                color: #999999;
                font-size: 12px;
            }
        </style>

        </head>

        <body>

            <div class="email-container">

                <div class="email-header">
                    <h1>LIBRARY HUB</h1>
                    <p>YOUR DIGITAL LIBRARY</p>
                </div>

                <div class="email-content">

                    <h2>Verify Your Email</h2>

                    <p>Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>

                    <p>
                        Thank you for registering with Library Hub!
                        Please use the verification code below
                        to verify your email address.
                    </p>

                    <div class="verification-box">

                        <p><strong>YOUR VERIFICATION CODE</strong></p>

                        <h1 class="verification-code">
                            ' . htmlspecialchars($verification_code, ENT_QUOTES, 'UTF-8') . '
                        </h1>

                    </div>

                    <p>
                        This code will expire in
                        <strong>10 minutes</strong>.
                    </p>

                    <p>
                        If you did not create an account,
                        please ignore this email.
                    </p>

                </div>

                <div class="email-footer">
                    © 2026 Library Hub. All rights reserved.
                </div>

            </div>

        </body>
        </html>
        ';

        $mail->AltBody =
            "Your verification code is: $verification_code. " .
            "This code expires in 10 minutes.";

        $mail->send();

        temp('info', 'Registration successful! Please check your email.');

        redirect('/login/verify_email.php');

    }
    catch (Throwable $e) {

    if ($_db->inTransaction()) {
        $_db->rollBack();
    }

    echo '<pre>';
    echo 'Registration Error: ';
    echo $e->getMessage();
    echo '</pre>';

    exit();
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
        <div class ="logo">
            <img src="/images/logo.png" alt="Logo">
        </div>
</header>

<div class="auth-card">
        <form method="post" class="auth-form-register">
            <h4 class="auth-card__title">Create an account</h4>
            <label for="username" class="sr-only">Name</label>
            <div class="field">
                <?php html_text('username', 'placeholder="Full name..." maxlength="100"', $name) ?>
                <?php err('username') ?>
            </div>

            <label for="email" class="sr-only">Email</label>
            <div class="field">
                <?php html_text('email', 'placeholder="Email address..." maxlength="254"', $email) ?>
                <?php err('email') ?>
            </div>

            <label for="password" class="sr-only">Password</label>
            <div class="field">
                <?php html_password('password','class="password_input" id="password" placeholder="Password"', $password) ?>
                <?php err('password') ?>
            </div>
    
            <label for="confirm" class="sr-only">Confirm password</label>
            <div class="field">
                <?php html_password('confirm', 'class="password_input" id="confirm" placeholder="Confirm password"', $confirm) ?>
                <?php err('confirm') ?>
            </div>

            <div class="auth-form__row">
                <a href="javascript:void(0)"
                id="allToggleBtn"
                onclick="toggleAllPassword()"
                class="auth-link-muted">
                    Show All Password
                </a>
            </div>

        <div class="password_progress">
            <div id="percentage_password" class="pass-percentage"></div>
        </div>
        <p id="message_password"></p>
        
        <p class="pass_contain">Password must contain:</p>
        <ul>
            <li>Must contain at least 8 or 20 characters</li>
            <li>Must contain at least one uppercase letter</li>
            <li>Must contain at least one lowercase letter</li>
            <li>Must contain at least one number</li>
            <li>Must contain at least one special character (!@#$%^&*()_+)</li>
        </ul> 

            <button class="auth-btn-primary">Register</button>

            <p class="auth-footer-text">
                Already have an account? 
                <a href="login.php" class="auth-link">Login here</a>
            </p>
        </form>
</div>

<script>
    document.getElementById('password').addEventListener('input', evalPasswordStrength);
    evalPasswordStrength();
</script>

<?php
include '../_footer.php';
?>