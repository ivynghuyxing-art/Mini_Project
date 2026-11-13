<?php 

include '../../_base.php';

$category_id = req('category_id');

if(is_get()){
    $stm = $_db->prepare('SELECT * FROM category WHERE category_id=?');
    $stm->execute([$category_id]);
    $cat = $stm->fetch();

    if(!$category_id){
        temp('info', 'No category Id is found');
        redirect('/admin/Category/home-category.php');
    }

     extract((array)$cat);
    $_REQUEST['name'] = $name;
}

if(is_post()){
    $name = req('name');


    if($name == ''){
        $_err['name'] = 'Required';
    }else if (strlen($name) > 50){
        $_err['name'] = 'No more then 50 character';
    }else if(strlen($name) < 0 ){
        $_err['name'] = 'No less then 0 character';
    }

    if(!$_err){

        $stm = $_db->prepare('UPDATE category SET name=? WHERE category_id=?');
        $stm->execute([$name, $category_id]);
        
        temp('info', 'Record category updated');
        redirect('/admin/Category/home-category.php');
    }
}


$_title = ' Update Category';
include '../../admin_header.php';
?>

<form method="post" enctype="multipart/form-data">
    <div class="cat-update-card">
        <label for="name">Name</label>
        <?php html_text('name', 'maxlength=50') ?>

        <section>
            <button>Done</button>
            <button type="reset">Reset</button>
        </section>
    </div>


</form>

<?php  
include '../../_footer.php';
?>
