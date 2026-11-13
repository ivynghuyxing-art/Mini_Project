<?php

include '../../_base.php';

$_title = 'My WishList';
auth(['member']);

$_title ="My WishList";

$user = $_user->user_id;

if(is_post()){
    $book_id=(int)post('add_id');

    if($book_id>0){
        $stm=$_db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=? AND book_id=?");

        $stm->execute([$user,$book_id]);
        if($stm->fetchColumn()==0){
            $stm=$_db->prepare("INSERT INTO wishlist(user_id,book_id,added_date) VALUES (?,?,NOW())");
            $stm->execute([$user,$book_id]);
            temp('message','Book added successful!');
        }else{
            temp('message','This book already in your wishlist.');
        }
        redirect('/student/book/browse.php');
    }
   $wishlist_id = (int)post('remove_id');

   if($wishlist_id > 0){
    $stm = $_db->prepare("
        DELETE FROM wishlist WHERE wishlist_id =? AND user_id = ?
    ");
    $stm->execute([$wishlist_id,$user]);

    if($stm->rowCount()>0){
        temp('message','Book removed successfully.');
    }
   }
   redirect('/student/engagement/wishlist.php');
}

$stm = $_db->prepare("SELECT wishlist.wishlist_id, wishlist.added_date,book.book_id,book.title,book.author,book.description,book.image,book.quantity,book.available_quantity FROM wishlist JOIN book ON wishlist.book_id = book.book_id WHERE wishlist.user_id = ? ORDER BY wishlist.added_date DESC");
$stm->execute([$user]);

$wishlist = $stm->fetchAll();
$message = temp('message');
include '../../_head.php';

?>
<main class="main-content">
    <?php if($message): ?>
        <div class="success-message">
            <?= encode($message) ?>
        </div>
    <?php endif; ?>

    <div class = "wishlist-header">
        <div>
            <h1>My Wishlist</h1>
            <p>View and manage your favourite books</p>
        </div>

        <div class = "wishlist-count">
            <?= count($wishlist); ?> Saved Books
        </div>
    </div>

    <?php if(empty($wishlist)): ?>
        <div class ="empty-wishlist">
            <div class="empty-icon">
                ♡
            </div>
            <h2>Your wishlist is Empty</h2>
            <p>You haven't added any books to your wishlist yet.</p>
            <a href="/student/book/browse.php" class="browse-btn">
                Browse Books
            </a>
        </div>
    <?php else:?>
        <div class ="wishlist-grid">
            <?php foreach ($wishlist as $book):?>
                <div class="wishlist-card">
                    <div class="book-image">
                        <?php if(!empty($book->image)):?>
                            <img src="/uploads/books/<?= encode($book->image)?>" alt="<?=encode($book->title) ?>" >

                        <?php else:?>
                            <div class = "no-image">
                                No image available
                            </div>
                        <?php endif;?>
                    </div>

                    <div class="book-info">
                        <h3> <?= encode($book->title) ?></h3>
                        <p>Author: <?= encode($book->author) ?></p>

                        <?php if($book->available_quantity > 0): ?>
                            <span class ="available-status">Available</span>
                        <?php else:?>
                            <span class="unavailable-status">Unavailable</span>
                        <?php endif; ?>

                        
                        <?php if($book->available_quantity>0):?>
                            <a href="/student/activities/borrowing.php?id=<?= $book->book_id ?>" class="borrowing-btn"> Borrow </a>
                        <?php else: ?>
                            <a href ="/student/activities/reservation.php?id=<?= $book->book_id ?>" class="reservation-btn"> Reservation </a>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="remove_id" value="<?= $book->wishlist_id ?>">
                            <button type="submit" class="remove-btn">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach;?>
        </div>
    <?php endif;?>
</main>

