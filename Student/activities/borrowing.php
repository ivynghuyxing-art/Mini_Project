
<?php

include '../../_base.php';

$_title = 'My Borrowing';
auth(['member']);

$book_id = (int)req('book_id');

$_err = [];

$_user = $_SESSION['user']->user_id;

$stm = $_db->prepare('SELECT bk.title, bk.image, bk.author, bk.category_id, bk.description,
                        c.name AS category_name
                        FROM book bk 
                        JOIN category c ON bk.category_id = c.category_id
                        WHERE bk.book_id =?');
$stm->execute([$book_id]);
$_b = $stm->fetch();


if (!$_b) {
    redirect('/student/activities/my_borrowing.php');
}

$_return_date = date('Y-m-d', strtotime('+8 days'));
$_current = date('Y-m-d');
$max_days = date('Y-m-d', strtotime('+2 weeks'));

if (is_post()) {
    $_return_date = req('return_date');

    if ($_return_date == '') {
        $_err['return_date'] = 'Required, please fill in return date ';
    }
    else if ($_return_date <= $_current) {
        $_err['return_date'] = 'Invalid, return cannot be same as borrow date';
    }
    else if ($_return_date > $max_days) {
        $_err['return_date'] = 'Return date cannot more then 2 weeks ';
    }

    if (!$_err) {
        try {

            $stm = $_db->prepare('UPDATE book SET available_quantity = available_quantity - 1 
                            WHERE book_id =? AND available_quantity > 0');

            $stm->execute([$book_id]);

            if ($stm->rowCount() === 0) {
                throw new ErrorException("Sorry, the book out of stock");
            }

            $stm = $_db->prepare('INSERT INTO borrowing (user_id, borrow_date, return_date, book_id
            , due_date, status) VALUES (?, ?, ?, ?, ?, ?)');

            $stm->execute([$_user, $_current, $_return_date, $book_id, $_due_date, 'pending']);

            temp('info', 'Borrow successful, now waiting for approval from admin!');

        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }
}

include '../../_head.php';

?>

<form method="post" enctype="multipart/form-data">
    <div class="borrow-card">
        <label>Book details</label>

        <div class="books">
            <img src="/images/Book_pic/<?= encode($_b->image) ?>"
                 alt="<?= encode($_b->title) ?>">

            <div class="book_detail">
                <p>
                    <strong>Title:</strong> <?= encode($_b->title) ?> <br>
                    <strong>Author:</strong> <?= encode($_b->author) ?> <br>
                    <strong>Description:</strong> <?= encode($_b->description) ?> <br>
                    <strong>Category:</strong> <?= encode($_b->category_name) ?>
                </p>
            </div>
        </div>

        <div class="Date-card">
            <label>Borrow date</label>
            <input type="date" name="borrow_date" value="<?= $_current ?>" readonly>

            <label>Return date</label>
            <?php html_date('return_date', "min='" . date('Y-m-d', strtotime('+1 day')) . "' max='$max_days'") ?>
            <?php err('return_date') ?>

            <label>Due date</label>
            <?php html_date('due_date',"min='" . date('Y-m-d', strtotime('+1 day')) . "' max='$max_days'")?>
            <?php err('due_date') ?>

        
            <span class="Warning-overdue">
                <p>
                    If member did not return in time before the due time <?= encode($_due_date) ?>.
                    The libraries have the rule to charge the member.

                    <br>

                    Late 1-3 days: Fine <strong>RM5</strong> <br>
                    Late 4-6 days: Fine <strong>RM8</strong> <br>
                    Late 7-8 days: Fine <strong>RM12</strong>
                </p>
            </span>

            <section>
                <button type="button" onclick="location.href='/student/book/browse.php'">
                    Cancel
                </button>

                <button type="submit">
                    Submit
                </button>
            </section>

        </div>
    </div>
</form>

<?php
include '../../_footer.php';
?>
