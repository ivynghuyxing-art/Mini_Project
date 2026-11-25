<?php 
include '../../_base.php';

$_id = req('user_id');
$_err = [];
$stm = $_db->prepare('SELECT username , email, profile_photo FROM user WHERE user_id =?');
$stm->execute([$_id]);
$u = $stm->fetch();

if(!$u){
    temp('info', 'Member not exits');
    redirect('/admin/member/member.php');
}

$name = $u->username;
$email= $u->email;
$profile_photo= $u->profile_photo;
$photo = $u->profile_photo;


include '../../admin_header.php';
?>
<form method="post">
    <div class="u-card">
    <a href="/admin/member/member.php">Back member page</a>
    <label for="photo">Photo</label>
    <?php if(!empty($u->profile_photo)): ?>
        <img src="/images/profile_pic/<?= encode($u->profile_photo)?>" alt="Profile photo" class="profile_picture">
    <?php else:?>
        <p>No photo yet</p>
    <?php endif; ?>

    <label>Member ID</label>
    <?= encode($_id)?>

    <label for="username">Name</label>
    <?= encode($name) ?>
 

    <label for="email">Email</label>
    <h3 class="email-u">
        <?= encode($u->email)?>
    </h3>

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
</form>

<?php 
include '../../_footer.php';
?>