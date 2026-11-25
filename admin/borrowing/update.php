<?php
include '../../_base.php';

$_id = req('borrowing_id');

$_err=[];
auth(['admin']);
$stm= $_db->prepare('SELECT bor.*, 
                    bk.available_quantity, bk.book_id,
                    f.payment_status
                    FROM borrowing bor 
                    JOIN book bk ON bk.book_id = bor.book_id
                    LEFT JOIN fine f ON f.borrowing_id = bor.borrowing_id
                    WHERE bor.borrowing_id = ?');
$stm->execute([$_id]);
$borrows = $stm->fetch();

if(!$borrows){
    temp('info', 'Borrow record not founded');
    redirect('/admin/borrowing/borrowing.php');
}

const Fine_Of_Day = 5.00;
$_status = $borrows->status;
$_due_date = $borrows->due_date;
$_due_td = strtotime($_due_date);
$_return_date = date('Y-m-d');
$_return_td = strtotime($_return_date);
$_fine_amount = $borrows->fine_amount;


$_oldstatus = strtoupper(trim($borrows->status));
$_array = ['APPROVAL','RETURN','PENDING','REJECT','OVERDUE'];

if(is_post()){
    $_status =strtoupper(trim(req('status')));

    if($_status== ''){
        $_err['status'] = 'Required';
    }else if (strlen($_status) > 10){
        $_err['status'] = 'No more then 10 character';
    }else if(!in_array($_status, $_array) ){
        $_err['status'] = 'Must be PENDING, APPROVAL, RETURN, OVERDUE OR REJECT only';
    }


    if(!$_err){
        if($_status == 'APPROVAL'){
            $stm = $_db->prepare('UPDATE borrowing SET status=? WHERE borrowing_id=?');
            $stm->execute([$_status,$_id]);
        }
        else if ($_status == 'REJECT'){
            $stm = $_db->prepare('UPDATE book SET available_quantity 
                                  = available_quantity + 1 WHERE book_id=? ');
            $stm->execute([$borrows->book_id,]);

            $stm = $_db->prepare('UPDATE borrowing SET status=? WHERE borrowing_id=?');
            $stm->execute([$_status,$_id]);

        }
        else if($_status == 'RETURN' && $_oldstatus == 'APPROVAL'){

            if($_return_td > $_due_date){
                $day_late = max(0, (int)floor(($_return_td - $_due_td) / 86400 ));
            }

            if($day_late >= 2){
             $_fine_amount = ceil($_fine_amount / 5) * Fine_Of_Day;
            }   

            $stm = $_db->prepare('UPDATE book SET available_quantity 
                                = available_quantity + 1 WHERE book_id = ?');

            $stm->execute([$borrows->book_id]);

            $_check_status = ($_fine_amount > 0) ? 'OVERDUE' : 'RETURN';

            $stm = $_db->prepare('UPDATE borrowing
                            SET status = ?, return_date = ?, fine_amount = ?
                            WHERE borrowing_id = ?');
            $stm->execute([$_check_status, $_return_date, $_fine_amount, $_id]);

            if($_check_status == 'OVERDUE'){
                $stm = $_db->prepare('INSERT INTO fine (amount,payment_status,reason,borrowing_id) 
                VALUES (?,?,?,?)');

            $stm->execute([$_fine_amount,'Unpaid','Member late return book',$borrows->borrowing_id]);
            }
        }
        
        temp('info', 'Update record successfull');
        redirect('/admin/borrowing/borrowing.php');
    }
}

$_title = 'Update borrow detail';

include '../../admin_header.php';
?>

<form method="post">
    <label>Borrow details</label>

    <div class="card-borrow-management">
      <a href="/admin/borrowing/borrowing.php"><--- Borrow page </a>

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

        <label>Due date</label>
        <h3>
            <?= encode($borrows->due_date) ?>
        </h3>
        <label>Status</label>
        <?php html_text('status', 'maxlength=10') ?>
        <?php err('status')?>

        <select>
            <option></option>
        </select>

        <label>Fine amount</label>
        <h3>
            <?= encode($borrows->fine_amount) ?>
        </h3>

        <?php if(!empty($borrows->payment_status)):?>

            <label>Payment status</label>
            <h3 class="pay-<?= strtolower($borrows->payment_status ?? '') ?>">
                <?= encode($borrows->payment_status ?? 'No fine') ?>
            </h3>

        <?php endif;?>



        <button type="reset">Reset</button>
        <button type="submit">Submit</button>

        
    </div>

    

</form>

<?php
include '../../_footer.php';

?>
