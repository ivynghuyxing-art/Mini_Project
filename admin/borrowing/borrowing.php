<?php 
include '../../_base.php'; 

$_title = 'Borrow Book';


$stm= $_db->prepare('SELECT * FROM borrowing');
$stm->execute();
$borrows = $stm->fetchAll();

auth(['admin']);
include '../../admin_header.php';
?>

<form>
    <div class="borrow-card">
        <?php if($borrows):?>
            <?php foreach($borrows as $row): ?>
                <table class="borrow-t">
                    <tr>
                        <th>User ID</th>
                        <th>Book ID</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Fine Payment</th>
                        <th>Action</th>
                    </tr>

                    <tr>
                        <td><?= encode($row->user_id) ?></td>
                        <td><?= encode($row->book_id) ?></td>
                        <td><?= encode($row->borrow_date) ?></td>
                        <td><?= encode($row->due_date) ?></td>
                        <td class="status-<?= strtolower(encode($row->status)) ?>">
                            <?= encode($row->status)?>
                        </td>
                        <td><?= encode($row->fine_amount) ?></td>
                        <td>
                            <a href="/admin/borrowing/update.php?borrowing_id=<?= $row->borrowing_id ?>">Update</a>
                        </td>

                    </tr>
                </table>
            <?php endforeach ?>
        <?php else: ?>
                <h3>No borrow book found</h3>
        <?php endif ?>

    </div>



</form>

<?php 
include '../../_footer.php'; 
?>