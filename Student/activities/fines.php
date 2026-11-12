
<?php

include '../../_base.php';

auth(['member']);

$_title = 'My Fines';

$user_id = $_user->user_id;

// Generate fines
// Only calculate fines when the book has been returned late

$stm = $_db->prepare("
    SELECT borrowing_id,
           DATEDIFF(return_date, due_date) AS overdue_days
    FROM borrowing
    WHERE return_date IS NOT NULL
    AND DATEDIFF(return_date, due_date) >= 2
");

$stm->execute();
$borrowings = $stm->fetchAll();

foreach ($borrowings as $borrowing) {

    $overdue_days = (int)$borrowing->overdue_days;

    // Calculate fine amount
    $amount = ceil($overdue_days / 5) * 5;

    // Generate fine reason
    $period = (int)ceil($overdue_days / 5);

    $range_start = ($period - 1) * 5 + 1;
    $range_end = $period * 5;

    if ($period == 1) {
        $range_start = 2;
    }

    $reason = "Overdue $range_start - $range_end days";

    // Check whether fine already exists
    $stm = $_db->prepare("
        SELECT fine_id
        FROM fine
        WHERE borrowing_id = ?
    ");

    $stm->execute([$borrowing->borrowing_id]);
    $existing_fine = $stm->fetch();

    if (!$existing_fine) {

        // Insert new fine record
        $stm = $_db->prepare("
            INSERT INTO fine
            (borrowing_id, amount, reason, payment_status)
            VALUES (?, ?, ?, ?)
        ");

        $stm->execute([
            $borrowing->borrowing_id,
            $amount,
            $reason,
            'Unpaid'
        ]);
    }
}

// Pagination
$records_per_page = 5;

$page = (int)get('page', 1);

if ($page < 1) {
    $page = 1;
}

// Count total fine records
$stm = $_db->prepare("
    SELECT COUNT(*)
    FROM fine
    JOIN borrowing
    ON fine.borrowing_id = borrowing.borrowing_id
    WHERE borrowing.user_id = ?
");

$stm->execute([$user_id]);

$total_records = $stm->fetchColumn();

$total_pages = max(1, ceil($total_records / $records_per_page));

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $records_per_page;

// Calculate total fines
$stm = $_db->prepare("
    SELECT COALESCE(SUM(fine.amount), 0)
    FROM fine
    JOIN borrowing
    ON fine.borrowing_id = borrowing.borrowing_id
    WHERE borrowing.user_id = ?
");

$stm->execute([$user_id]);

$total_fines = $stm->fetchColumn();

// Retrieve fine records
$stm = $_db->prepare("
    SELECT
        fine.fine_id,
        fine.amount,
        fine.reason,
        fine.payment_status,
        borrowing.due_date,
        borrowing.return_date,
        book.title
    FROM fine
    JOIN borrowing
    ON fine.borrowing_id = borrowing.borrowing_id
    JOIN book
    ON borrowing.book_id = book.book_id
    WHERE borrowing.user_id = ?
    ORDER BY fine.fine_id DESC
    LIMIT $records_per_page OFFSET $offset
");

$stm->execute([$user_id]);

$fines = $stm->fetchAll();

include '../../_head.php';

?>

<main class="main-content" id="main-content">

    <div class="fine-header">
        <h1>My Fines</h1>
        <p>View your fines and payment status</p>
    </div>

    <div class="fine-summary">

        <div class="fine-summary-card">
            <h2>Total Fines</h2>

            <p class="amount">
                RM <?= number_format($total_fines, 2); ?>
            </p>
        </div>

    </div>

    <div class="fine-records">

        <h2>Fine Records</h2>

        <?php if (empty($fines)): ?>

            <p>No fines found.</p>

        <?php else: ?>

            <table>

                <thead>
                    <tr>
                        <th>Num</th>
                        <th>Book Title</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($fines as $index => $fine): ?>

                        <tr>

                            <td>
                                <?= $offset + $index + 1; ?>
                            </td>

                            <td>
                                <?= encode($fine->title); ?>
                            </td>

                            <td>
                                <?= encode($fine->due_date); ?>
                            </td>

                            <td>
                                <?= encode($fine->return_date); ?>
                            </td>

                            <td>
                                RM <?= number_format($fine->amount, 2); ?>
                            </td>

                            <td>
                                <?= encode($fine->reason); ?>
                            </td>

                            <td>

                                <?php if ($fine->payment_status == 'Paid'): ?>

                                    <span class="status status_paid">
                                        Paid
                                    </span>

                                <?php else: ?>

                                    <span class="status status-unpaid">
                                        Unpaid
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <!-- Pagination -->

            <?php if ($total_pages > 1): ?>

                <div class="pagination">

                    <?php if ($page > 1): ?>

                        <a href="?page=<?= $page - 1; ?>">
                            Previous
                        </a>

                    <?php endif; ?>

                    <span>
                        Page <?= $page; ?> of <?= $total_pages; ?>
                    </span>

                    <?php if ($page < $total_pages): ?>

                        <a href="?page=<?= $page + 1; ?>">
                            Next
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</main>

</body>
</html>
