<?php

include '../../_base.php';

$_title = 'My WishList';
auth(['member']);

$message = temp('message');

$_title ="My WishList";

$user = $_user->user_id;

//pagination
$record_per_page = 6;

$page = (int)get('page',1);

if($page<1){
    $page=1;
}

//count total wishlist records
$stm = $_db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id =?");
$stm->execute([$user]);

$total_records = $stm->fetchColumn();

$total_pages = max(1,ceil($total_records/$record_per_page));

if($page > $total_pages){
    $page = $total_pages;
}

$offset = ($page -1) * $record_per_page;

$stm= $_db->prepare("SELECT * FROM wishlist JOIN book ON wishlist.book_id = book.book_id LEFT JOIN category ON book.category_id = category.category_id WHERE wishlist.user_id = ? ORDER BY wishlist.added_date DESC LIMIT $record_per_page OFFSET $offset");
$stm->execute([$user]);
$wishlist = $stm->fetchAll();

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
            <?= $total_records; ?> Saved Books
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
                            <img src="/images/Book_pic/<?= encode($book->image)?>" alt="<?=encode($book->title) ?>" >

                        <?php else:?>
                            <div class = "no-image">
                                No image available
                            </div>
                        <?php endif;?>
                    </div>

                    <div class="book-info">
                        <h3> <?= encode($book->title) ?></h3>
                        <p>Author: <?= encode($book->author) ?></p>

                        <span class = "category-tag"><?= encode($book->name) ?? 'Uncategorized'; ?></span>

                        <?php if($book->available_quantity > 0): ?>
                            <span class ="available-status">Available</span>
                        <?php else:?>
                            <span class="unavailable-status">Unavailable</span>
                        <?php endif; ?>

                        
                        <?php if($book->available_quantity>0):?>
                            <a href="/student/activities/my_borrowing.php?book_id=<?= $book->book_id ?>" class="borrowing-btn"> Borrow </a>
                        <?php else: ?>
                            <a href ="/student/activities/reservation.php?book_id=<?= $book->book_id ?>" class="reservation-btn"> Reservation </a>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="remove_id" value="<?= $book->wishlist_id ?>">
                            <button type="submit" class="remove-btn">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach;?>
        </div>
        <?php if($total_records>0):?>
            <div class ="pagination">
                <?php if ($page > 1):?>
                    <a href = "?page=<?= $page -1 ?>" class ="page-btn">
                        &larr;
                    </a>
                <?php endif;?>

                <!-- Calculate start and end-->
                <?php
                    $start = $page -2 ;
                    $end = $page +2;

                    if($start<1){
                        $start = 1;
                    }

                    if($end > $total_pages){
                        $end = $total_pages;
                    }
                ?>

                <!-- First page-->
                 <?php if($start > 1):?>
                    <a href="?page=1" class ="page-btn">1</a>
                    <?php if ($start > 2): ?>
                        <span class ="page-dots">...</span>
                    <?php endif ;?>
                <?php endif;?>

                <!-- page numbers -->
                 <?php for($i = $start;$i <= $end;$i++):?>
                    <a href = "?page=<?= $i ?>" class = "page-btn <?= ($page == $i)?'active': '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor;?>

                <!--Last page-->
                <?php if($end < $total_pages):?>
                    <?php if ($end<$total_pages - 1):?>
                        <span class ="page-dots">...</span>
                    <?php endif; ?>

                    <a href = "?page=<?= $total_pages ?>" class="page-btn">
                        <?= $total_pages ?>
                    </a>
                <?php endif;?>

                <!-- next -->
                 <?php if ($page < $total_pages):?>
                    <a href ="?page=<?= $page +1?>" class ="page-btn">
                        &rarr;
                    </a>
                <?php endif;?>
            </div>
        <?php endif;?>
    <?php endif;?>
</main>

