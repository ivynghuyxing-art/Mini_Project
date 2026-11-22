<?php

include '../../_base.php';

$_title = 'Return book';
auth(['member']);

const Fine_of_Day = 2.50;

$_borrow_id = req('borrowing_id');
$book_id = (int)req('book_id');
$_user = $_SESSION['user'] ?? '';

if(!$_user){
    redirect('/Student/book/browse.php');
}
$stm = $_db->prepare('SELECT bor.*, bk.title, bk.image, bk.author, bk.category_id, bk.description,
                        c.name AS category_name
                        FROM borrowing bor
                        JOIN book bk ON bk.book_id = bor.book_id
                        JOIN category c ON bk.category_id = c.category_id
                        WHERE bor.borrowing_id =? AND bor.user_id=?');
$stm->execute([$_borrow_id,$_user->user_id]);
$return = $stm->fetch();

if(!$return){
    temp('info', 'No book you has borrow yet');
    redirect('/Student/book/browse.php');
}

$_return_date = date('Y-m-d');
$_return_td = strtotime($_return_date);

$_due_date = $return->due_date;
$_due_td = strtotime($_due_date);

$_borrow_date = $return->borrow_date;

$_status = strtoupper(trim($return->status));

if($_status === 'RETURN'){
    $_return_date = $return->return_date;
    $_fine_amount = $return->fine_amount;
    $day_late = (int)($_fine_amount / Fine_of_Day);

    temp('info', 'Invaild, if book status is return');
    redirect('/Student/activities/history.php');   

}else if($_status === 'PENDING'){

    temp('info', 'The book is still pending cannot, must be approval first');
    redirect('/Student/activities/history.php');

}else if($_status === 'REJECT'){

    temp('info', 'The book is been rejected');
    redirect('/Student/activities/history.php');

}else if($_status !== 'APPROVAL'){

    temp('info', 'The book must be approval first');
    redirect('/Student/activities/history.php');

}else{

    $day_late = max(0, (int)floor(($_return_td - $_due_td) / 86400 ));
    $_fine_amount = $day_late * Fine_of_Day;
}


if($_return_td > $_due_td){
    $stm = $_db->prepare('UPDATE borrowing SET fine_amount=?, status=? WHERE borrowing_id=?');
    $stm->execute([$_fine_amount,'PENDING',$_borrow_id]);

}else{

    $stm = $_db->prepare('UPDATE book SET available_quantity = available_quantity + 1 WHERE book_id =?');
    $stm->execute([$return->book_id]);

    $stm = $_db->prepare('UPDATE borrowing SET return_date=?,status=?, fine_amount=? WHERE borrowing_id=?');
    $stm->execute([$_return_date,'RETURN',$_fine_amount,$_borrow_id]);

}

temp('info', 'Return book successful');
redirect('/Student/activities/history.php');

include '../../_head.php';

?>

<form method="post" enctype="multipart/form-data">
    <div class="return-borrow-card">
        <label>Book details</label>

        <div class="books">
            <img src="/images/Book_pic/<?= encode($return->image) ?>"
                 alt="<?= encode($return->title) ?>">

            <div class="book_detail">
                <p>
                    <strong>Title:</strong> <?= encode($return->title) ?> <br>
                    <strong>Author:</strong> <?= encode($return->author) ?> <br>
                    <strong>Description:</strong> <?= encode($return->description) ?> <br>
                    <strong>Category:</strong> <?= encode($return->category_name) ?>
                    <strong>Fine amount</strong>
                    <h3 class="fine-<?= $_fine_amount > 0 ? 'paid_overdue' : 'Not_overdue'  ?>">
                        <?= number_format($_fine_amount, 2) ?> ?>
                    </h3>
                </p>
            </div>
        </div>

        <div class="Date-card">
            <label>Borrow date</label>
            <input type="date" name="borrow_date" value="<?= $_borrow_date ?>" readonly>

            <label>Due date</label>
            <input type="date" name="due_date" value="<?= $_due_date ?>" readonly>

            <label>Return date</label>
            <input type="date" name="return_date" value="<?= $_return_td ?>" readonly>

            <label>Late days</label>
            <p>
                <?= $day_late ?>
            </p>

    </div>
   
            <section>
                <button type="button" onclick="location.href='/student/book/browse.php'">
                    Cancel
                </button>

                <button type="submit">
                    Return
                </button>
            </section>

        </div>
    </div>
</form>
<?php
include '../../_footer.php';
?>
