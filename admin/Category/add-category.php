<?php

include '../../_base.php';

$_title = ' Add new books';
auth(['admin']);
if(is_post()){
    $_err=[];
    $_name = req('name');

    if($_name == ''){
        $_err['name'] = 'Required';
    }else if(strlen($_name) > 100){
        $_err['name'] = 'No more then 100 character';
    }

    if(!$_err){
        $stm = $_db->prepare('INSERT INTO category (name) VALUES (?)');    

        $stm->execute([$_name]);
        temp('info','Category added successfully!');
        redirect('/admin/Category/home-category.php');
    }
}


$_title = 'Add category';
include '../../admin_header.php';
?>

<form method="post" enctype="multipart/form-data">
    <div class="add-book-card-cat">
        <a href="/admin/Category/home-category.php">Back to home</a>

        <label for="name">Name</label>
        <?php html_text('name', 'maxlength="100"') ?>
        <?php err('name') ?>

    <section>
        <button>Done</button>
        <button type="reset">Reset</button>
    </section>

    </div>

</form>

<?php
 include '../../_footer.php';
?>