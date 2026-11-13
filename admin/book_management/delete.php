<?php 

include '../../_base.php';

$book_id = req('book_id');

$stm = $_db->prepare('SELECT * FROM book WHERE book_id = ?');
$stm->execute([$book_id]);
    
$book = $stm->fetch();

if(!$book_id){
    temp('info', 'Boodk not found');
    redirect('/admin/book_management/read_book.php');
}

$stm = $_db->prepare('SELECT COUNT(*) FROM borrowing WHERE book_id =? ');
$stm->execute([$book_id]);
$borrow_book = $stm->fetchColumn();

if($borrow_book > 0 ){
    temp('info', "Cannot deleted,book". ($book->title). "have member borrowed");
    redirect('/admin/book_management/read_book.php');
}


if(is_post()){
        $stm = $_db->prepare('DELETE FROM book WHERE book_id =? ');
        $stm->execute([$book_id]);

        $f = $_SERVER['DOCUMENT_ROOT'] . "/images/Book_pic/$book->image";
        if ($book->image && file_exists($f)) {
            unlink($f);
        }
            
        temp('info', "Book ".($book->title)." has deleted");
        redirect('/admin/book_management/read_book.php');
    

    
   
}



?>
<form method="post" >
    <div class="delete-card">
        <label>Delete</label>
         <p>
            Title:<?= encode($book->title)?> <br>
            Author<?= encode($book->author)?> <br>
            Description: <?= encode($book->description) ?> <br>
            Category: <?= encode($book->category_id)?> <br>
            Quantity: <?= $book->quantity ?> <br>
            Available: <?= $book->quantity - $borrow_book ?>

        <section>
            <a href="/admin/book_management/read_book.php">Cancel</a>
            <button>Confirm</button>
        </section>

    </div>
</form>

<?php 

include '../../_footer.php';
?>