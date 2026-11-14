<?php 
include '../../_base.php'; 

$_title = 'Member';
include '../../admin_header.php';

    $stm= $_db->prepare('SELECT * FROM user');
    $stm->execute();
    $u = $stm->fetchAll();

?>

<form>
    <div class="member-card">
        <?php if($u):?>
            <table class="member-table">
                  <tr>
                        <th>Profile photo</th>
                        <th>Name</th>
                        <th>Email</th>
                        
                    </tr>
               <?php foreach( $u as $member): ?>
                    <tr>
                        <td>
                          <?php if($member->profile_photo): ?>
                            <img src="/images/profile_pic/<?= encode($member->profile_photo)?>" alt="Profile photo" class="profile_picture">
                           <?php else: ?>
                            <img src="/images/profile_pic/sample.jpg" alt="No profile photo" class="profile_picture">
                            <?php endif ?>
                        </td>
                        <td><?= encode($member->username) ?></td>
                        <td><?= encode($member->email) ?></td>
                        <td>
                            <a href="/admin/member/update.php?user_id=<?=$member->user_id?>">Update</a>
                            <a href="/admin/member/update.php?user_id=<?=$member->user_id?>">Update</a>
                        </td>
                        
                        
                    </tr>
                <?php endforeach ?>
             </table>
        <?php else: ?>
                <h3>No member found</h3>
        <?php endif ?>

    </div>



</form>

<?php 
include '../../_footer.php'; 
?>