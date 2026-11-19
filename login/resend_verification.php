<?php
require '../_base.php';

$title = 'Resend Verification | StoryVault';

if(
    !isset($_SESSION['verify_user_id']) ||
    !isset($_SESSION['verify_email'])
){
    temp('info', 'No verification in progress.');
    redirect('/login/login.php');
}

$user_id = $_SESSION['verify_user_id'];
$email = $_SESSION['verify_email'];

// Get user details
$stm = $_db->prepare('SELECT username ,email,valid,email_verified FROM user WHERE user_id = ? AND email=?');
$stm->execute([$user_id,$email]);
$user = $stm->fetch();

if(!$user){
    temp('info', 'User not found.');
    redirect('/login/register.php');
}

if ($user->email_verified == 1) {
    unset($_SESSION['verify_user_id']);
    unset($_SESSION['verify_email']);

    temp('info', 'Your email has already been verified.');
    redirect('/login/login.php');
}

if (is_post()) {

    try {

        // Generate a new 6-digit verification code
        $verification_code = sprintf(
            '%06d',
            random_int(0, 999999)
        );

        // Set expiration time (10 minutes)
        $expire = date(
            'Y-m-d H:i:s',
            time() + 600
        );


        // Start database transaction
        $_db->beginTransaction();


        // Invalidate previous verification tokens
        $stm = $_db->prepare('
            UPDATE token
            SET is_used = 1
            WHERE user_id = ?
            AND token_type = ?
            AND is_used = 0
        ');

        $stm->execute([
            $user_id,
            'email_verification'
        ]);


        // Insert new verification token
        $stm = $_db->prepare('
            INSERT INTO token
            (
                user_id,
                expire,
                token_type,
                verification_code,
                is_used,
                created_at
            )
            VALUES (?, ?, ?, ?, 0, NOW())
        ');

        $stm->execute([
            $user_id,
            $expire,
            'email_verification',
            $verification_code
        ]);


        // Save database changes
        $_db->commit();


        // Send new verification code to email
        
        // Send designed verification email
        $mail = get_mail();

        $mail->addAddress($email, $user->username);
        $mail->isHTML(true);

        $mail->Subject = 'New Verification Code - Library Hub';

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

                    <h2>New Verification Code</h2>

                    <p>Hello ' . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') . ',</p>

                    <p>
                        You requested a new verification code.
                        Please use the code below to verify your email address.
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
                        If you did not request this code,
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
            "Your new verification code is: $verification_code. " .
            "This code expires in 10 minutes.";

        $mail->send();

        // Redirect to verification page
        temp('info', 'A new verification code has been sent to your email.');

        redirect('/verify_email.php');

    }
    catch (Throwable $e) {

        // Rollback if database transaction is still active
        if ($_db->inTransaction()) {
            $_db->rollBack();
        }

        temp('info', 'Unable to resend verification code. Please try again.');

        redirect('/resend_verification.php');
    }
}


?>

<!DOCTYPE html>
<html lang ="en">
<head>
    <meta charset ="UTF-8">
    <meta name="viewport" content= "width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Untitled' ?></title>
    <link rel = "shortcut icon" href="/images/favicon.png">
    <link rel = "stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>

<body>
    <div id="info"><?= temp('info') ?></div>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-title">Resend Verification Code</h1>
            <p class="auth-subtitle">Click the button to resend the verification code to <?= $email ?></p>

            <form method="post" class="auth-form">
                <button type="submit" class="register-btn">Resend Code</button>

                <p class="switch">
                    <a href="verify_email.php">Back to Verification</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
<?php include '../_footer.php'; ?>