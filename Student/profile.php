<?php

include '../_base.php';

$_title = 'My Profile';

auth(['student']);

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
    $f = get_file('photo');


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
        if (!str_starts_with($f['type'], 'image/')) {
            $_err['profile_photo'] = 'Must be image';
        }
        else if ($f['size'] > 1 * 1024 * 1024) {
            $_err['profile_photo'] = 'Maximum 1MB';
        }
    }
    var_dump($_err);

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

        // (3) Update global username object
        $_user->email = $email;
        $_user->username = $name;  
        $_user->profile_photo = $photo;
        $_SESSION['user'] = $_user;

        $stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
        $stm->execute([$_user->user_id]);
        $updated = $stm->fetch();
        $_SESSION['user'] = $updated;

        temp('info', 'Record updated');
        redirect('/Student/profile.php');
    }

}

// ----------------------------------------------------------------------------
?>


<form method="post" class="form_m" enctype="multipart/form-data">
    <a href="/Student/dashboard.php"> <=== Back to Home page</a>
    <label for="photo">Photo</label>
    <label class="upload" tabindex="0">
        <img src="/images/profile_pic/<?= $profile_photo ?: 'sample.jpe'?>" alt="Profile photo">
        <?php html_file('profile_photo', 'image/*', 'hidden') ?>
    </label>
    <?php err('profile_photo') ?>

    <label for="username">Name</label>
    <?php html_text('username', 'maxlength="100"') ?>
    <?php err('username') ?>

    <label for="email">Email</label>
    <?php html_text('email', 'maxlength="100"') ?>
    <?php err('email') ?>


    <p>Want to reset password? Here ➡️<a href="/Student/update_password.php" id="reset" >Reset Password ? </a></p>

    <section>
        <button>Done</button>
        <button type="reset">Reset</button>
    </section>
</form>


<?php
include '../_footer.php';
?>