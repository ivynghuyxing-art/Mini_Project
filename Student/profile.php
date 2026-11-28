<?php

include '../_base.php';

$_title = 'My Profile';

auth(['member']);
$info = temp('info');

if (is_get()) {
    $stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
    $stm->execute([$_user->user_id]); //
    $u = $stm->fetch();

    if (!$u) {
        redirect('/');
    }

    extract((array)$u);
    $_REQUEST['username'] = $u->username;
    $_REQUEST['email'] = $u->email;
    $_SESSION['profile_photo'] = $u->profile_photo;
    $photo = $u->profile_photo;

}

if (is_post()) {
    $_err = [];
    $email = req('email');
    $name  = req('username');
    $photo = $_SESSION['profile_photo'];
    $f = get_file('profile_photo');


    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    }
    else if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    }
    else if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    }
    else {
        $stm = $_db->prepare('SELECT COUNT(*) FROM user WHERE email = ? AND user_id != ?');
        $stm->execute([$email, $_user->user_id]); 

        if ($stm->fetchColumn() > 0) {
            $_err['email'] = 'Duplicated';
        }
    }

    // Validate: name
    if ($name == '') {
        $_err['username'] = 'Required';
    }
    else if (strlen($name) > 100) {
        $_err['username'] = 'Maximum 100 characters';
    }

    // Validate: photo (file) --> optional
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['profile_photo'] = 'Must be image';
        }
        else if ($f->size > 1 * 1024 * 1024) {
            $_err['profile_photo'] = 'Maximum 1MB';
        }
    }
   

    // DB operation
    if (!$_err) {
        if ($f) {
            $old = $_SERVER['DOCUMENT_ROOT'] . "/images/profile_pic/$photo";
            if(file_exists($old)){
                unlink($old);
            }
            $photo = save_photo($f, $_SERVER['DOCUMENT_ROOT'] .'/images/profile_pic');
        }
        
        $stm = $_db->prepare('UPDATE user SET email = ?, username = ?, profile_photo = ? WHERE user_id = ?');
        $stm->execute([$email, $name, $photo, $_user->user_id]); 

        $stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
        $stm->execute([$_user->user_id]);
        $updated = $stm->fetch();
        $_SESSION['user'] = $updated;

        temp('info', 'Profile picture updated successful!');
        redirect('/Student/profile.php');
    }

}
include '../_head.php';
// ----------------------------------------------------------------------------
?>  

<main class="main-content" id="main-content">

    <div class="profile-heading">

        <h1>My Profile</h1>
        <p>Manage your personal information and account settings.</p>
    </div>

    <form method="post" class="form_m profile-form" enctype="multipart/form-data">

        <?php if ($info): ?>
            <p class="success-message">
                <?= encode($info) ?>
            </p>
        <?php endif; ?>

        <div class="profile-card">

            <!-- Profile Photo -->
            <div class="profile-photo-section">
                <h3>Profile Photo</h3>

                <label class="upload profile-upload" tabindex="0">
                    <img id="profilePreview"
                         src="/images/profile_pic/<?= $photo ?? '' ?>"
                         alt="Profile photo">

                    <span class="photo-edit">✎</span>

                    <?php html_file('profile_photo', 'image/*', 'hidden') ?>
                </label>

                <p class="photo-hint">Click to upload your photo</p>

                <?php err('profile_photo') ?>
            </div>

            <div class="profile-divider"></div>

            <!-- Personal Information -->
            <div class="profile-details">

                <h3>Personal Information</h3>

                <p class="section-description">
                    Update your personal details below.
                </p>

                <div class="profile-field">
                    <label for="username">Full Name</label>
                    <?php html_text('username', 'maxlength="100"') ?>
                    <?php err('username') ?>
                </div>

                <div class="profile-field">
                    <label for="email">Email Address</label>
                    <?php html_text('email', 'maxlength="100"') ?>
                    <?php err('email') ?>
                </div>

                <div class="password-section">
                    <div>
                        <h4>Password & Security</h4>
                        <p>Want to change your password?</p>
                    </div>

                    <a href="/Student/update_password.php" class ="reset-password-btn">
                        Reset Password
                    </a>
                </div>

            </div>

        </div>

        <div class="profile-actions">
            <button type="reset" class="reset-btn">Reset</button>
            <button type="submit" class="done-btn">Done</button>
        </div>

    </form>

</main>

<?php
include '../_footer.php';
?>
