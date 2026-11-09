
<?php

include '../../_base.php';

auth(['student']);

$_title = 'My Fines';

//generate fines
$stm = $_db->prepare("SELECT borrowing_id FROM borrowing WHERE return_date IS NULL AND DATEDIFF(COALESCE(return_date,CURDATE()),due_date)>3");
$stm -> execute();
$borrowings = $stm->fetchAll();

foreach ($borrowings as $borrowing){
    $stm=$_db->prepare("SELECT fine_id FROM fine WHERE borrowing_id =?");
    $stm->execute([$borrowing->borrowing_id]);
    $existing_fine = $stm->fetchAll();

    if(!$existing_fine){
        $stm = $_db->prepare("INSERT INTO fine(borrowing_id,amount,reason,payment_status) VALUES (?,?,?,?)");
        $stm->execute([$borrowing -> borrowing_id,5.00,'Overdue more than 3 days','Unpaid']);

    }
}
$user_id = $_user->user_id;

$records_per_page = 5;
$page = (int)get('page',1);

if($page<1){
    $page = 1;
}

$stm = $_db -> prepare("SELECT COUNT(*) FROM fine JOIN borrowing ON fine.borrowing_id = borrowing.borrowing_id WHERE borrowing.user_id =?");
$stm->execute([$user_id]);
$total_records = $stm->fetchColumn();

$total_pages = max(1,ceil($total_records/$records_per_page));

if($page > $total_pages){
    $page = $total_pages;
}
$offset = ($page - 1) * $records_per_page;

$stm = $_db -> prepare("SELECT fine.fine_id,fine.amount,fine.reason,borrowing.due_date,book.title FROM fine JOIN borrowing ON fine.borrowing_id = borrowing.borrowing_id JOIN book ON borrowing.book_id = book.book_id WHERE borrowing.user_id = ? ORDER BY fine.fine_id DESC");
$stm -> execute([$user_id]);
$fines = $stm -> fetchAll();

$total_fines=0;

foreach($fines as $fine){
    $total_fines += $fine->amount;
}

include '../../_head.php';
?>

<main class="main-content" id = "main-content">
    <div class = "fine-header">
        <h1> My Fines </h1>
        <p> View your fines and payment status</p>
    </div>

    <div class ="fine-summary">
        <div class ="fine-summary-card">
            <h2>Total Fines</h2>
            <p class="amount">RM <?=number_format($total_fines, 2); ?></p>
        </div>
    </div>

    <div class="fine-records">
        <h2> Fine Records</h2>
        <?php if (empty($fines)) :?>
            <p>No fines found.</p>
        
        <?php else:?>
            <table>
                <thead>
                    <tr>Num</tr>
                    <tr>Book Title</tr>
                    <tr>Due Date</tr>
                    <tr>Return Date</tr>
                    <tr>Amount</tr>
                    <tr>Reason</tr>
                    <tr>Payment Status</tr>
                </thead>

                <tbody>
                    <?php foreach ($fines as $fine): ?>
                        <tr>
                            <td>
                                <?= $fine + 1 ?>
                            </td>

                            <td>
                                <?= encode($fine->$title); ?>
                            </td>

                            <td>
                                <?=encode($fine -> due_date); ?>
                            </td>

                            <td>
                                <?= $fine->return_date ? encode($fine->return_date): 'Not Returned'; ?>
                            </td>

                            <td>
                                RM <?= number_format($fine->amount,2); ?>
                            </td>

                            <td>
                                <?= encode($fine->reason); ?>
                            </td>

                            <td>
                                <?php if ($fine->payment_status == 'Paid'):?>
                                    <span class ="status status_paid">
                                        Paid
                                    </span>
                                <?php else :?>
                                    <span class = "status status-unpaid">
                                        Unpaid
                                    </span>

                                <?php endif;?>

                            </td>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        <?php endif;?>
    </div>
</main>
</body>
</html>
-