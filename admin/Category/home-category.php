<?php

include '../../_base.php';

if(is_get()){

$stm = $_db->prepare('SELECT * FROM category');
$stm->execute();
$category = $stm->fetchAll();
    
}

$_title = ' Categories';
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

<form method="post" enctype="multipart/form-data">
    <a href="/admin/home.php">Back to home</a>
    
    <a href="/admin/Category/add-category.php">+Add new Category</a>
    <div class="add-book-card">
        <?php if($category):?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                </tr>
        
                <tr>
                    <?php foreach($category as $cat):?>
                        <td><h3><?= encode($cat->category_id) ?></h3></td>
                        <td>
                            <h3><?= encode($cat->name)?>  </h3>
                        </td>
                    <?php endforeach?>
                
                </tr>
            </table>
            <?php else:?>
                <p>No category added</p>
        <?php endif ?>

    </div>

</form>

<?php
 include '../../_footer.php';
?>