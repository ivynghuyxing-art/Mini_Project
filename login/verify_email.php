
<?php

require '../_base.php';

$_title = 'Verify Email';
$_err=[];
if (!isset($_SESSION['verify_user_id']) ||
    !isset($_SESSION['verify_email'])) {

    temp('info', 'Please register your account first.');
    redirect('register.php');
}

$user_id = $_SESSION['verify_user_id'];
$email   = $_SESSION['verify_email'];

if (is_post()) {

    $code = req('code');

    // Validate verification code
    if (!$code) {
        $_err['code'] = 'Required';
    }
    else if (strlen($code) != 6 || !ctype_digit($code)) {
        $_err['code'] = 'Invalid code format. Please try again.';
    }

    if (!$_err) {

        try {

            // Find valid verification token
            $stm = $_db->prepare('
                SELECT id, verification_code, expire
                FROM token
                WHERE user_id = ?
                AND verification_code = ?
                AND token_type = ?
                AND is_used = 0
                AND expire >= NOW()
                ORDER BY created_at DESC
                LIMIT 1
            ');

            $stm->execute([
                $user_id,
                $code,
                'email_verification'
            ]);

            $token = $stm->fetch();

            if ($token) {

                $_db->beginTransaction();

                // Activate user account
                $stm = $_db->prepare('
                    UPDATE user
                    SET valid = 1,
                    email_verified = 1
                    WHERE user_id = ?
                    AND email = ?
                ');

                $stm->execute([$user_id, $email]);

                if ($stm->rowCount() === 0) {
                    throw new Exception('User account could not be updated.');
                }

                // Mark verification token as used
                $stm = $_db->prepare('
                    UPDATE token
                    SET is_used = 1
                    WHERE id = ?
                    AND is_used = 0
                ');

                $stm->execute([$token->id]);

                if ($stm->rowCount() === 0) {
                    throw new Exception('Verification token has already been used.');
                }

                $_db->commit();

                // Clear verification session
                unset($_SESSION['verify_user_id']);
                unset($_SESSION['verify_email']);

                temp('info', 'Email verified successfully! You can now login.');

                redirect('/login/login.php');

            }
            else {

                $_err['code'] = 'Invalid or expired verification code. Please try again or request a new code.';

            }

        }
        catch (Throwable $e) {

            if ($_db->inTransaction()) {
                $_db->rollBack();
            }

            $_err['code'] = 'Verification failed. Please try again.';

        }
    }
}

$title = 'Verify Email | Library Hub';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?></title>

    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>

</head>

<body>

    <div id="info"><?= encode(temp('info') ?? '') ?></div>

    <div class="auth-wrapper">

        <div class="auth-card">

            <h1 class="auth-title">Verify Your Email</h1>

            <p class="auth-subtitle">
                Enter the 6-digit code sent to <?= encode($email) ?>
            </p>

            <form method="post" class="auth-form">

                <label for="code">Verification Code</label>

                <?php html_text('code', 'maxlength="6" pattern="[0-9]{6}" inputmode="numeric"') ?>

                <?php err('code') ?>

                <button type="submit" class="register-btn">
                    Verify
                </button>

                <p class="switch">
                    Didn't receive the code?
                    <a href="resend_verification.php">Resend</a>
                </p>

            </form>

        </div>

    </div>

</body>

</html>

<?php include '../_footer.php'; ?>
