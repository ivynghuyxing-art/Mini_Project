<?php 
include '../../_base.php';

$_cat_id = req('category_id');

$stm = $_db->prepare('SELECT * FROM category WHERE category_id=?');
$stm->execute([$_cat_id]);
$cat = $stm->fetch();


if(!$cat){
    temp('info', 'ID not found');
    redirect('/admin/Category/home-category.php');
}

if(is_post()){
    $stm = $_db->prepare('DELETE FROM category WHERE category_id=?');
    $stm->execute([$_cat_id]);

    temp('info','Category deleted successfully!');
    redirect('/admin/Category/home-category.php');
}

$stm = $_db->prepare('SELECT COUNT(*) FROM category WHERE category_id <=?');
$stm->execute([$_cat_id]);
$id = $stm->fetchColumn();

$_title = ' Delete Category';
include '../../admin_header.php';
?>

<form method="post" enctype="multipart/form-data">
    <div class="cat-delete-card">
        <p>
            ID: <?= $id?>
            Name: <?= encode($cat->name) ?><br>

            Are you sure deleting this category? 
        </p>

        <section>
            <a href="/admin/Category/home-category.php">Cancel</a>
            <button>Confirm</button>
        </section>
    </div>


</form>

<?php  
include '../../_footer.php';
?>
