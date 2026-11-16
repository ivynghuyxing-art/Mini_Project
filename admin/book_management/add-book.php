<?php

include '../../_base.php';

$_title = ' Add new books';

if(is_post()){
    $_err=[];
    $_title = req('title');
    $_author = req('author');
    $_descrip = req('description');
    $_photo = $_SESSION['image'] ?? '';
    $f = get_file('image');
    $_quantity = req('quantity');
    $available_quantity = req('available_quantity');
    $_categoryid = req('category_id');

    if($_title == ''){
        $_err['title'] = 'Required';
    }else if(strlen($_title) > 100){
        $_err['title'] = 'Not more then 100 character';
    }

    if($_author == ''){
        $_err['author'] = 'Required';
    }else if(strlen($_author) > 30){
        $_err['author'] = 'No more then 30 character';
    }

    if($_descrip ==''){
        $_err['description'] = 'Required';
    }else if(strlen($_descrip) > 999){
        $_err['description'] = 'Not more than 100 character';
    }

    if($_categoryid == ''){
        $_err['category_id'] = 'Please choose a category';
    }

    if($_quantity == ''){
        $_err['quantity'] = 'Required, enter numberic';
    }else if(is_numeric($_quantity) < 0){
        $_err['quantity'] = 'Not less than 0';
    }else if(is_numeric($_quantity) > 999){
        $_err['quantity'] = 'Not more than 999';
    }

    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['image'] = 'Must be image';
        }
        else if ($f->size > 1 * 1024 * 1024) {
            $_err['image'] = 'Maximum 1MB';
        }
    }

    


    if(!$_err){
        $_quantity = (int)$_quantity;
        $availble_quantity = $_quantity;
        if($f){
            $old = $_SERVER['DOCUMENT_ROOT'] . "/images/Book_pic/$_photo";
            if(file_exists($old)){
                unlink($old);
            }
            $_photo = save_photo($f, $_SERVER['DOCUMENT_ROOT'] .'/images/Book_pic');
        }

        $stm = $_db->prepare('INSERT INTO book (title, author, description, category_id, quantity, available_quantity, image) VALUES 
                                (?,?,?,?,?,?,?)');    

        $stm->execute([$_title,$_author,$_descrip,$_categoryid,$_quantity,$available_quantity,$_photo]);
        redirect('/admin/book_management/read_book.php');
    }
}

$categories = $_db->query('SELECT category_id, name FROM category')->fetchAll();

$_title = 'Add book';

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
    <a href="/admin/book_managemant/read_book.php">Back to home</a>
    <div class="add-book-card">
        <label for="image">Photo</label>
        <label class="upload" tabindex="0">
        <img id="profile_Preview" src="/images/Book_pic/<?= $_photo ?? ''?>" alt="Book photo">
        <?php html_file('image', 'image/*', 'hidden') ?>
        </label>
        <?php err('image') ?>

        <label for="title">Title</label>
        <?php html_text('title', 'maxlength="100"') ?>
        <?php err('title') ?>

        <label for="author">Author</label>
        <?php html_text('author', 'maxlength="100"') ?>
        <?php err('author') ?>

        <label for="description">Description</label>
        <?php html_textarea('description', ' "maxlength="999" rows="5" ') ?>
        <?php err('description') ?>

        <label>Category</label>
        <select name="category_id">
            <option value="">--Select Category</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= $cat->category_id ?>"><?= $cat->name ?></option>
            <?php endforeach ?>
        </select>

        <label for="quantity">Quantity</label>
        <?php html_text('quantity', 'maxlength="999"') ?>
        <?php err('quantity') ?>

        <label for="available_quantity">Quantity</label>
        <input type="text" id="available_quantity" value="<?= (int)($_quantity ?? 0) ?>" disabled>

        <section>
        <button>Done</button>
        <button type="reset">Reset</button>
    </section>

    </div>

</form>
<script>
    document.addEventListener('DOMContentLoaded', ()=>{
        const quantity = document.querySelector('input[name="quantity"]');
        const availble = document.getElementById('available_quantity');

        if(quantity && availble){
            quantity.addEventListener('input', ()=> availble.value = quantity.value);
        }
    });
</script>

<?php
 include '../../_footer.php';
?>