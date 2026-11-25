<?php 
include '../../_base.php'; 

$_title = 'History Borrow Book';
auth(['member']);

$_user = $_SESSION['user'] ?? '';

$_array_s = [
    'pending' => 'PENDING',
    'approval' => 'APPROVAL',
    'return' => 'RETURN',
    'reject' => 'REJECT',
    'overdue' => 'OVERDUE'
];

if(!$_user){
    redirect('../book/browse.php');
}
// Automatically detect overdue borrowing records
$stm = $_db->prepare("
    UPDATE borrowing
    SET status = 'OVERDUE'
    WHERE user_id = ?
    AND status = 'APPROVAL'
    AND due_date < CURDATE()
");
$stm->execute([$_user->user_id]);

$_status = $_GET['status'] ??'';

$s='SELECT bor.*, bk.title, bk.author
                    FROM borrowing bor
                    JOIN book bk ON bk.book_id = bor.book_id
                    WHERE bor.user_id=?
                    ';
$result = [$_user->user_id];

if(array_key_exists($_status,$_array_s)){
    $s .= 'AND bor.status =?';
   $result[] =  $_array_s[$_status];
}

$s .= ' ORDER BY bor.borrow_date DESC';

$stm = $_db->prepare($s);
$stm->execute($result);
$borrows = $stm->fetchAll();

include '../../_head.php';
?>

<form method="get">
    <div class="borrow-card">
        <select name="status">
            <option value="">All status</option>
            <?php foreach($_array_s  as $sat =>$_label): ?>
                <option value="<?= $sat ?>" <?= $_status === $sat ? 'selected' : '' ?>>
                    <?= encode($_label)?>
                </option>
            <?php endforeach;?>
        </select>

        <button>Search</button>


        <?php if($borrows):?>
            <?php foreach($borrows as $row): ?>
                <table class="borrow-t">
                    <tr>
                        <th>Book title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Fine Payment</th>
                        <th>Action</th>
                    </tr>

                    <tr>
                        <td>
                            <?= encode($row->title) ?>
                        </td>
                        <td><?= encode($row->author) ?></td>
                        <td><?= encode($row->borrow_date) ?></td>
                        <td><?= encode($row->due_date) ?></td>
                        <td><?= $row->return_date ? encode($row->return_date) : 'Not return yet'?></td>
                        <td class="status-<?= strtolower(encode($row->status)) ?>">
                            <?= encode($row->status)?>
                        </td>
                        <td>RM <?= number_format($row->fine_amount) ?></td>
                        <td>
                        <?php if(in_array(strtoupper($row->status),['APPROVAL','OVERDUE'],true)):?>
                            <button>
                                <a href="/Student/activities/return.php?borrowing_id=<?= encode($row->borrowing_id) ?>">Ready to return book?</a>
                            </button>
                        <?php elseif(strtoupper($row->status) === 'RETURN'):?>
                            <span class="btn_disable"> Book return </span>
                        <?php else:?>
                            <span class="btn_disable"> No return </span>
                        <?php endif;?>
                        </td>
                    </tr>
                </table>
            <?php endforeach ?>
        <?php else: ?>
                <p>You have no borrow yet</p>
        <?php endif ?>

    </div>



</form>

<?php 
include '../../_footer.php'; 
?>