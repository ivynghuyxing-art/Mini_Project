<?php 
include '../../_base.php';

$_id = req('user_id');

$stm = $_db->prepare('SELECT u.*, bor.book_id, bor.borrowing_id
                    , bor.return_date, bor.due_date, bor.borrow_date, bor.status AS borrow_status
                    FROM user u
                    LEFT JOIN borrowing bor ON bor.user_id = u.user_id
                    WHERE u.user_id =?');
$stm->execute([$_id]);
$u = $stm->fetch();

extract((array)$u);
$_REQUEST['username'] = $u->username;
$_REQUEST['email'] = $u->email;
$_REQUEST['book_id'] = $u->book_id;
$_REQUEST['return_date'] = $u->return_date;
$_REQUEST['due_date'] = $u->due_date;
$_REQUEST['borrow_date'] = $u->borrow_date;
$_REQUEST['status'] = $u->borrow_status;
$_SESSION['profile_photo'] = $u->profile_photo;
$photo = $u->profile_photo;

if(!$u){
    temp('info', 'Member not exits');
}

if(is_post()){
    $name = req('username');
    $email = req('email');

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


    if (!$_err) {        
        $stm = $_db->prepare('UPDATE user SET email = ?, username = ? WHERE user_id = ?');
        $stm->execute([$email, $name, $_user->user_id]); 

        $stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
        $stm->execute([$_user->user_id]);
        $updated = $stm->fetch();
        $_SESSION['user'] = $updated;

        temp('info', 'Update member record successful!');
        redirect('/admin/member/member.php');
    }
}

include '../../admin_header.php' ;
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Library Hub Management' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>
<form>
    <div class="u-card">
    <label for="photo">Photo</label>
    <?php if(!empty($u->profile_photo)): ?>
        <img src="/images/profile_pic/<?= encode($u->profile_photo)?>" alt="Profile photo" class="profile_picture">
    <?php endif; ?>
    <label>Member ID</label>
    <?php encode($u->user_id)?>

    <label for="username">Name</label>
    <?php html_text('username', 'maxlength="100"') ?>
    <?php err('username') ?>

    <label for="email">Email</label>
    <?php html_text('email', 'maxlength="100"') ?>
    <?php err('email') ?>


    <?php if(!empty($u->borrowing_id)):?>
        <h3>Borrow detail</h3>
        <label>Book ID</label>
        <?= encode($u->book_id)?>

        <label>Borrow date</label>
        <?=  encode($u->borrow_date)?>

        <label>Due date</label>
        <?= encode($u->due_date)?>

        <label>Return_date</label>
        <?= encode($u->return_date) ?? 'Not return yet' ?>

        <label>Borrow Status</label>
        <?= encode($u->borrow_status)?>
    <?php else: ?>
        <p>This member has no borrow yet</p>
    <?php endif?>



    </div>

    <section>
        <button>Done</button>
        <button type="reset">Reset</button>
    </section>
</form>

<?php 
include '../../_footer.php';
?>