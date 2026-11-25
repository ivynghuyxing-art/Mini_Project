
<?php

require_once '../../_base.php';

auth(['member']);

$_title = 'My Borrowing';

// ==========================================
// STATUS FILTER
// ==========================================

$status_options = [
    'pending'  => 'PENDING',
    'approval' => 'APPROVAL',
    'approved' => 'APPROVED',
    'return'   => 'RETURN',
    'reject'   => 'REJECT',
    'overdue'  => 'OVERDUE'
];

$status_filter = $_GET['status'] ?? '';

// ==========================================
// GET MEMBER BORROWING RECORDS
// ==========================================

$sql = "
    SELECT
        bor.borrowing_id,
        bor.borrow_date,
        bor.due_date,
        bor.return_date,
        bor.status,
        bor.fine_amount,
        bk.book_id,
        bk.title,
        bk.author,
        bk.image
    FROM borrowing bor
    INNER JOIN book bk
        ON bk.book_id = bor.book_id
    WHERE bor.user_id = ?
";

$params = [$_user->user_id];

// Apply status filter
if (array_key_exists($status_filter, $status_options)) {

    $sql .= " AND UPPER(bor.status) = ? ";

    $params[] = $status_options[$status_filter];
}

$sql .= "
    ORDER BY bor.borrow_date DESC,
             bor.borrowing_id DESC
";

$stm = $_db->prepare($sql);
$stm->execute($params);

$borrows = $stm->fetchAll();

include '../../_head.php';

?>

<link rel="stylesheet" href="/css/app.css">
<link rel="stylesheet" href="/css/history.css">

<main class="main-content">

    <div class="history-page">

        <!-- PAGE HEADER -->

        <div class="history-header">

            <div>
                <h1>My Borrowing History</h1>
                <p>View and manage your book borrowing records.</p>
            </div>

            <div class="history-total">
                <?= count($borrows) ?> Records
            </div>

        </div>

        <!-- BORROWING HISTORY CARD -->

        <div class="history-card">

            <!-- FILTER -->

            <form method="get" class="history-filter">

                <div class="filter-group">

                    <label for="status">Filter by Status</label>

                    <select name="status" id="status">

                        <option value="">All Status</option>

                        <?php foreach ($status_options as $key => $label): ?>

                            <option
                                value="<?= encode($key) ?>"
                                <?= $status_filter === $key ? 'selected' : '' ?>
                            >
                                <?= encode($label) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <button type="submit" class="history-search-btn">
                    Search
                </button>

                <a
                    href="/Student/activities/history.php"
                    class="history-reset-btn"
                >
                    Reset
                </a>

            </form>

            <!-- TABLE -->

            <?php if ($borrows): ?>

                <div class="history-table-wrapper">

                    <table class="history-table">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Book Details</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Fine Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php $no = 1; ?>

                            <?php foreach ($borrows as $row): ?>

                                <?php
                                    $status = strtoupper(trim($row->status));
                                    $status_class = strtolower($status);
                                ?>

                                <tr>

                                    <!-- NUMBER -->

                                    <td>
                                        <?= $no++ ?>
                                    </td>

                                    <!-- BOOK DETAILS -->

                                    <td>

                                        <div class="history-book">

                                            <?php if (!empty($row->image)): ?>

                                                <img
                                                    src="/images/Book_pic/<?= encode($row->image) ?>"
                                                    alt="<?= encode($row->title) ?>"
                                                >

                                            <?php else: ?>

                                                <div class="history-book-placeholder">
                                                    📚
                                                </div>

                                            <?php endif; ?>

                                            <div class="history-book-info">

                                                <strong>
                                                    <?= encode($row->title) ?>
                                                </strong>

                                                <small>
                                                    By <?= encode($row->author) ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>

                                    <!-- BORROW DATE -->

                                    <td>
                                        <?= date('d M Y', strtotime($row->borrow_date)) ?>
                                    </td>

                                    <!-- DUE DATE -->

                                    <td>
                                        <?= date('d M Y', strtotime($row->due_date)) ?>
                                    </td>

                                    <!-- RETURN DATE -->

                                    <td>

                                        <?php if (!empty($row->return_date) && $row->return_date !== '0000-00-00'): ?>

                                            <?= date('d M Y', strtotime($row->return_date)) ?>

                                        <?php else: ?>

                                            <span class="not-returned">
                                                Not Returned
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- STATUS -->

                                    <td>

                                        <span class="history-status status-<?= encode($status_class) ?>">
                                            <?= encode($status) ?>
                                        </span>

                                    </td>

                                    <!-- FINE AMOUNT -->

                                    <td>

                                        <span class="<?= $row->fine_amount > 0 ? 'fine-value' : 'no-fine' ?>">
                                            RM <?= number_format((float)$row->fine_amount, 2) ?>
                                        </span>

                                    </td>

                                    <!-- ACTION -->

                                    <td>

                                        <?php if (in_array($status, ['APPROVAL', 'APPROVED'])): ?>

                                            <a
                                                href="/Student/activities/return.php?borrowing_id=<?= (int)$row->borrowing_id ?>"
                                                class="history-return-btn"
                                            >
                                                Book Return
                                            </a>

                                        <?php elseif ($status === 'RETURN'): ?>

                                            <span class="history-action-disabled">
                                                Returned
                                            </span>

                                        <?php elseif ($status === 'PENDING'): ?>

                                            <span class="history-action-pending">
                                                Waiting Approval
                                            </span>

                                        <?php else: ?>

                                            <span class="history-action-disabled">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <!-- EMPTY STATE -->

                <div class="history-empty">

                    <div class="history-empty-icon">
                        📚
                    </div>

                    <h3>No Borrowing Records Found</h3>

                    <p>
                        You have no borrowing records under this status.
                    </p>

                    <a href="/Student/book/browse.php">
                        Browse Books
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

<?php include '../../_footer.php'; ?>
