<?php
include '../../_base.php';

$_id = req('borrowing_id');

$_err=[];

$stm= $_db->prepare('SELECT * FROM borrowing WHERE borrowing_id=?');
$stm->execute([$_id]);
$borrows = $stm->fetch();

if(is_post()){
    $_status = req('status');

    if($_status== ''){
        $_err['status'] = 'Required';
    }else if(strlen($_status) > 20){
        $_err['status'] = 'Cannot more then 20 character';
    }

    if(!$_err){
        $stm = $_db->prepare('UPDATE borrowing SET status WHERE borrowing_id=?');
        $stm->execute([$_status,$_id]);

        temp('info', 'Update record successfull');
        redirect('/admin/borrowing/borrowing.php');
    }
}

$_title = 'Update borrow detail';

include '../../admin_header.php';
?>

<form method="post" enctype="multipart/form-data">

    <a href="/admin/borrowing/borrowing.php"><==Borrow page</a>
    <label>Borrow details</label>

    <div class="card-borrow-management">
    <a href="/admin/borrowing/borrowing.php"><== Back to borrow page</a>

        <label>User Id </label>
        <h3>
            <?= encode($borrows->user_id) ?>
        </h3>

        <label>Book Id </label>
        <h3>
            <?= encode($borrows->book_id) ?>
        </h3>
        <label>Borrow date</label>
        <h3>
            <?= encode($borrows->borrow_date) ?>
        </h3>
        <label>Return date</label>
        <h3>
            <?= encode($borrows->return_date) ?>
        </h3>

        <label>Due date</label>
        <h3>
            <?= encode($borrows->due_date) ?>
        </h3>
        <label>Status</label>
        <?php html_text('status','maxlenght=20')?>
        <?php err('stauts')?>

        <label>Fine amount</label>
        <h3>
            <?= encode($borrows->fine_amount) ?>
        </h3>


        <button type="reset">Reset</button>
        <button type="submit">Submit</button>

        
    </div>

    

</form>

<?php
include '../../_footer.php';

?>
