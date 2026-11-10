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
    $_REQUEST['username'] = $u->name;
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
    $password     = req('password');
    $new_password = req('new_password');
    $confirm      = req('confirm');

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
    
    // Validate: password
    if ($password != '') {
        if (strlen($password) < 8 || strlen($password) > 20) {
        $_err['password'] = 'Between 8-20 characters';
    }else {
        // check if new password same width
        // current password
        $stm = $_db->prepare('SELECT COUNT(*) FROM user WHERE password = SHA1(?) AND user_id = ?');
        $stm->execute([$password,$_user->user_id]); //
        
        if ($stm->fetchColumn() == 0) {
            $_err['password'] = 'Not matched';
        }
    }

    // Validate: new_password
    if ($new_password == '') {
        $_err['new_password'] = 'Required';
    }
    else if (strlen($new_password) < 8 || strlen($new_password) > 20) {
        $_err['new_password'] = 'Between 8-20 characters';
    }

    // Validate: confirm
    if (!$confirm) {
        $_err['confirm'] = 'Required';
    }
    else if (strlen($confirm) < 8 || strlen($confirm) > 20) {
        $_err['confirm'] = 'Between 8-20 characters';
    }
    else if ($confirm != $new_password) {
        $_err['confirm'] = 'Not matched';
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

        // (3) Update global member object
        $_user->email = $email;
        $_user->name = $name;  
        $_user->profile_photo = $photo;
        $_SESSION['user'] = $_user;

        // redirect();
        // Update user (password)
        if($password != ''){
            $stm = $_db->prepare('UPDATE user SET password = SHA1(?) WHERE user_id =?');
            $stm->execute([$new_password,$_user->user_id]);
        }

        $stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
        $stm->execute([$_user->user_id]);
        $updated = $stm->fetch();
        $_SESSION['user'] = $updated;

        temp('info', 'Record updated');
        redirect('/');
    }
}

// ----------------------------------------------------------------------------
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


<form method="post" class="form_m" enctype="multipart/form-data">
    <a href="/Student/dashboard.php"> <=== Back to Home page</a>
    <label for="photo">Photo</label>
    <label class="upload" tabindex="0">
        <img id="profilePreview" src="/images/profile_pic/<?= $photo ?? ''?>" alt="Profile photo">
        <?php html_file('profile_photo', 'image/*', 'hidden') ?>
    </label>
    <?php err('profile_photo') ?>

    <label for="name">Name</label>
    <?php html_text('username', 'maxlength="100"') ?>
    <?php err('name') ?>

    <label for="email">Email</label>
    <?php html_text('email', 'maxlength="100"') ?>
    <?php err('email') ?>


    <p>Want to reset password? Here ➡️<a href="/user/password.php" id="reset" >Reset Password ? </a></p>

    <section>
        <button>Done</button>
        <button type="reset">Reset</button>
    </section>
</form>


<?php
include '../_footer.php';

?>
