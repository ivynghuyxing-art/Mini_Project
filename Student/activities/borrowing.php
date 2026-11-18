<?php

include '../../_base.php';

$_title = 'My Borrowing';
auth(['member']);

$book_id =req('book_id');
$_err =[];

include '../../_head.php';

$stm = $_db->prepare('SELECT bk.title, bk.image, bk.author, bk.category_id, bk.description,
                        c.name AS category_name
                        FROM book bk 
                        JOIN category c ON bk.category_id = c.category_id
                        WHERE bk.book_id =?');
$stm->execute([$book_id]);
$_b = $stm->fetch();

if(!$_b){
    redirect('/student/activities/my_borrowing.php');
}

$_user = $_user->user_id;
$_due_date = date('Y-m-d', strtotime('+8 days'));
$_current = date('Y-m-d');
$max_days = date('Y-m-d', strtotime('+5 weeks'));

if(is_post()){

    $_return_date = req('return_date');

    if($_return_date == ''){
        $_err['return_date'] = 'Required, please fill in return date ';
    }
    else if($_return_date == $_current){
        $_err['return_date'] = 'Invalid, return cannot be same as borrow date';
    }
    else if ($_return_date > $max_days){
        $_err['return_date'] = 'Return date cannot more then 5 weeks ';
    }

   
    try{
        $stm = $_db->prepare('UPDATE book SET available_quantity = available_quantity - 1 
                            WHERE book_id =? AND available_quantity > 0');
        $stm->execute([$book_id]);

        if($stm->rowCount() === 0){
         throw new ErrorException("Sorry, the book out of stock");
        }
        if(!$_err){
        $stm = $_db->prepare('INSERT INTO borrowing (user_id, borrow_date, return_date, book_id
        , due_date, status) VALUES (?, ?, ?, ?, ?, ?)');

        $stm->execute([$_user,$_current, $_return_date, $book_id,$_due_date, 'pending']);
        temp('info', 'Borrow successful !');
    }
    }catch(ErrorException $e){
        echo $e->getMessage();
    }
}


?>

<form method="post" enctype="multipart/form-data">
    <div class="borrow-card">
        <label>Book details</label>
        <img src="/images/Book_pic/<?=$_b->image ?>" alt="<?php $_b->title ?>">
        <p>
            Title: <?= encode($_b->title) ?> <br>
            Author: <?= encode($_b->author)?> <br>
            Descirption: <?= encode($_b->description)?> <br>
            Categories: <?= encode($_b->category_name)?>
        </p>

        <div class="Date-card">
            <labal>Borrow date</labal>
            <input type="date" name="borrow_date" value="<?= $_current ?>" readonly >


            <labal>Return date</labal>
            <input type="date" name="return_date" min="<?= date('d-m-Y'), strtotime('+1 weeks')?> max=<?= $max_days ?>">
            <?= $_err['return_date'] ?? '' ?>

            <labal>Due date</labal>
            <input type="date" name="due_date" value="<?= $_due_date ?>" readonly >

            <span class="Warning-overdue">
                <p>
                    If member did not return in time before the due time <?= encode($_due_date) ?>. 
                    The libraries have the rule to change the member. 

                    Late 1-3 days: Fine Rm5 <br>
                    Late 4-6 days: Fine Rm8 <br>
                    Late 7-8 days: Fine Rm12
                </p>
            </span>

        </div>

        <section>
            <button type="submit">
                Submit
            </button>
            <button>
                <a href="/Student/book/browse.php">Cancel</a>
            </button>
        </section>


    </div>

</form>

<?php 
include '../../_footer.php';
?>

