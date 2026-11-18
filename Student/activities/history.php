<?php 
include '../../_base.php'; 


$_title = 'History Borrow Book';

$_user = $_SESSION['user'] ?? '';

$stm= $_db->prepare('SELECT bor.*, bk.title, bk.author
                    FROM borrowing bor
                    JOIN book bk ON bk.book_id = bor.book_id
                    WHERE bor.user_id=?
                    ORDER BY bor.borrow_date DESC');
$stm->execute([$_user->user_id]);
$borrows = $stm->fetchAll();

if(!$_user){
    redirect('../book/browse.php');
}
include '../../admin_header.php';
?>

<form>
    <div class="borrow-card">
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
                    </tr>

                    <tr>
                        <td>
                            <?= encode($row->title) ?>
                        </td>
                        <td><?= encode($row->author) ?></td>
                        <td><?= encode($row->borrow_date) ?></td>
                        <td><?= encode($row->due_date) ?></td>
                        <td><?= $row->return_date ? encode($row->return_date) : '-'?></td>
                        <td class="status-<?= strtolower(encode($row->status)) ?>">
                            <?= encode($row->status)?>
                        </td>
                        <td>RM <?= number_format($row->fine_amount) ?></td>
                    </tr>
                </table>
            <?php endforeach ?>
        <?php else: ?>
                <h3>You have no history book yet</h3>
        <?php endif ?>

    </div>



</form>

<?php 
include '../../_footer.php'; 
?>