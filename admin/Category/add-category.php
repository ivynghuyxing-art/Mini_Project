<?php

include '../../_base.php';

$_title = ' Add new books';

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
        redirect('/admin/Category/home-category.php');
    }
}


$_title = 'Add category';
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
    <div class="add-book-card">

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