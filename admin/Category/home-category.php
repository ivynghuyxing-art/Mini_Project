<?php

include '../../_base.php';

auth(['admin']);
$stm = $_db->prepare('SELECT * FROM category');
$stm->execute();
$category = $stm->fetchAll();
    


$_title = ' Categories';
include '../../admin_header.php';
$message = temp('info');
?>

<form method="post" enctype="multipart/form-data">
    <div class="home-cat-card">
        <?php if($message):?>
            <div class="success-message">
                <?= encode($message) ?>
            </div>
        <?php endif;?>
        <a href="/admin/Category/add-category.php" class="cat-btn-admin">+Add Category</a>
        <div class="count-category">    
            <?= count($category)?> records
        </div>
        
        <?php if($category):?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Action</th>
                </tr>

                <?php foreach($category as $c =>$cat):?>
                    <tr>
                    
                        <td><?= $c + 1?></td>
                        <td>
                            <h3><?= encode($cat->name)?></h3>
                        </td>
                        <td>
                            <a href="cat-update.php?category_id=<?= $cat->category_id  ?>">Update</a>
                            <a href="cat-delete.php?category_id=<?= $cat->category_id  ?>">Delete</a>
                        </td> 
                    </tr>
                <?php endforeach?>
            </table>
            <?php else:?>
                <p>No category added</p>
        <?php endif ?>

    </div>

</form>

<?php
 include '../../_footer.php';
?>