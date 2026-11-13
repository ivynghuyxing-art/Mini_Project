<?php

include '../../_base.php';

if(is_get()){

$stm = $_db->prepare('SELECT * FROM category');
$stm->execute();
$category = $stm->fetchAll();
    
}

$_title = ' Categories';
include '../../admin_header.php';
?>

<form method="post" enctype="multipart/form-data">
    <div class="home-cat-card">
        <a href="/admin/Category/add-category.php">+Add new Category</a>
        <?php if($category):?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                </tr>

                <?php foreach($category as $cat):?>
                    <tr>
                    
                        <td><?= $cat->category_id?></td>
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