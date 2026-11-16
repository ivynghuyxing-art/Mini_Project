<?php

include '../../_base.php';

$_title = 'My Borrowing';
auth(['member']);

$_id =req('book_id');
$_err =[];

include '../../_head.php';

$stm = $_db->prepare('SELECT bk.title, bk.image, bk.author, bk.category_id, bk.description,
                        c.name AS category_name
                        FROM book bk 
                        JOIN category c ON bk.category_id = c.category_id
                        WHERE bk.book_id =?');
$stm->execute([$_id]);
$_b = $stm->fetch();

if(!$_b){
    redirect('/student/activities/borrowing.php');
}

$_user = $_user->user_id;
$_due_date = date('d-m-Y', strtotime('+8 days'));
$_current = date('d-m-Y');
$max_days = date('d-m-Y', strtotime('+5 weeks'));

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
        $stm->execute([$_id]);

        if($stm->rowCount() === 0){
         throw new ErrorException("Sorry, the book out of stock");
        }
        if(!$_err){
        $stm = $_db->prepare('INSERT INTO borrowing (user_id, borrow_date, return_date, book_id
        , due_date, status) VALUES (?, ?, ?, ?, ?, ?');

        $stm->execute([$_user,$_current, $_return_date, $_id,$_due_date, 'borrow']);
        temp('info', 'Borrow successful !');
    }
    }catch(ErrorException $e){
        echo $e->getMessage();
    }
}


?>

<form method="post" enctype="multipart/form-data">
    <div class="borrow-card">
    <?php foreach($_b as $books):?>
        <label>Book details</label>
        <img src="/images/Book_pic/<?=$books->image ?>" alt="<?php $books->title ?>">
        <p>
            Title: <?php encode($books->title)?> <br>
            Author: <?php encode($books->author)?> <br>
            Descirption: <?php encode($books->description)?> <br>
            Categories: <?php encode($books->category_name)?>
        </p>
    <?php endforeach?>

        <div class="Date-card">
            <labal>Borrow date</labal>
            <input type="date" name="borrow_date" value="<?= $_current ?>" readonly >


            <labal>Return date</labal>
            <input type="date" name="return_date" min="<?= date('d-m-Y'), strtotime('+1 weeks')?> max=<?= $max_days ?>">
            <?php $_err['return_date']?>

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

