<?php 

include '../../_base.php';

$_book_id = req('book_id');

if(is_post()){

    $stm = $_db->prepare('SELECT * FROM book WHERE book_id = ?');
    $stm->execute([$_book_id]);
    $book = $stm->fetch();
    
    if(!$_book_id){
        temp('info', 'Bood not found');
        redirect('read_book.php');
    }

    $stm = $_db->prepare('SELECT COUNT(*) FROM borrow WHERE book_id =? ');
    $stm->execute([$_book_id]);
    $borrow_book = $stm->fetchColumn();

    if($borrow_book > 0 ){
        temp('info', "Cannot deleted,book". ($book->title). "have member borrowed");
        redirect('read_book.php');
    }else{
        $stm = $_db->prepare('DELETE FROM book WHERE book_id =? ');
        $stm->execute([$_book_id]);

        $f = $_SERVER['DOCUMENT_ROOT'] . "/images/Book_pic/$book->image";
        if ($book->image && file_exists($f)) {
            unlink($f);
        }
            
        temp('info', "Book ".($book->title)." has deleted");
        redirect('read_book.php');
    }

    
   
}


?>


<?php 

include '../../_footer.php';
?>