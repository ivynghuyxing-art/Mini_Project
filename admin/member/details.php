<?php 
include '../../_base.php';

$_id = req('user_id');
$_err = [];
$stm = $_db->prepare('SELECT username , email, profile_photo FROM user 
                    WHERE user_id = ?');
$stm->execute([$_id]);
$u = $stm->fetch();

if(!$u){
    temp('info', 'Member not exits');
    redirect('/admin/member/member.php');
}
$stm = $_db->prepare('SELECT * FROM borrowing WHERE user_id = ? ORDER BY borrow_date DESC');
$stm->execute([$_id]);
$borrow_record = $stm->fetchAll();

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

    <table>
        <tr>
            <th>Borrow Id</th>
            <th>Book Id</th>
            <th>Borrow date</th>
            <th>Due date</th>
            <th>Return date</th>
            <th>Status</th>
            <th>Fine </th>
        </tr>
    </table>

    <?php if($borrow_record):?>
        <?php foreach($borrow_record as $b):?>
            <td> <?= encode($b->borrowing_id) ?></td>
            <td> <?= encode($b->book_id) ?></td>
            <td> <?= encode($b->borrow_date) ?></td>
            <td> <?= encode($b->due_date) ?></td>
            <td> <?= encode($b->return_date) ?></td>
            <td> <?= encode($b->status) ?></td>
            <td> <?= encode($b->fine_amount) ?></td>
        <?php endforeach;?>
    <?php else: ?>
        <p>This member has no borrow yet</p>
    <?php endif?>

    </div>
</form>

<?php 
include '../../_footer.php';
?>