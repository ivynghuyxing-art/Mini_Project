

<?php

require_once '../../_base.php';

auth(['member']);

$_title = 'Return Book';

const FINE_OF_DAY = 5;

// ==========================================
// GET BORROWING ID
// ==========================================

$borrow_id = (int)req('borrowing_id');

if ($borrow_id <= 0) {
    temp('info', 'Invalid borrowing record.');
    redirect('/student/activities/history.php');
}

// ==========================================
// GET BORROWING AND BOOK DETAILS
// ==========================================

$stm = $_db->prepare("
    SELECT
        bor.*,
        bk.title,
        bk.image,
        bk.author,
        bk.category_id,
        bk.description,
        c.name AS category_name
    FROM borrowing bor
    INNER JOIN book bk
        ON bk.book_id = bor.book_id
    LEFT JOIN category c
        ON c.category_id = bk.category_id
    WHERE bor.borrowing_id = ?
    AND bor.user_id = ?
");

$stm->execute([
    $borrow_id,
    $_user->user_id
]);

$return = $stm->fetch();

if (!$return) {
    temp('info', 'Borrowing record not found.');
    redirect('/student/activities/history.php');
}

// ==========================================
// CHECK BORROWING STATUS
// ==========================================

$status = strtoupper(trim($return->status));

if (!in_array($status, ['APPROVAL', 'APPROVED'])) {
    temp('info', 'This book is not eligible for return.');
    redirect('/student/activities/history.php');
}

// ==========================================
// CALCULATE RETURN INFORMATION
// ==========================================

$borrow_date = $return->borrow_date;
$due_date = $return->due_date;
$return_date = date('Y-m-d');

$return_time = strtotime($return_date);
$due_time = strtotime($due_date);

// Calculate overdue days
$day_late = max(
    0,
    (int)floor(($return_time - $due_time) / 86400)
);

// Fine formula:
// ceil(overdue days / 5) x RM5

$fine_amount = 0;

if ($day_late > 0) {
    $fine_amount = (int)ceil($day_late / 5) * FINE_OF_DAY;
}

// ==========================================
// PROCESS RETURN
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $_db->beginTransaction();

        // 1. Lock borrowing record

        $stm = $_db->prepare("
            SELECT *
            FROM borrowing
            WHERE borrowing_id = ?
            AND user_id = ?
            FOR UPDATE
        ");

        $stm->execute([
            $borrow_id,
            $_user->user_id
        ]);

        $borrowing = $stm->fetch();

        if (!$borrowing) {
            throw new Exception('Borrowing record not found.');
        }

        $borrowing_status = strtoupper(trim($borrowing->status));

        if (!in_array($borrowing_status, ['APPROVAL', 'APPROVED'])) {
            throw new Exception(
                'This borrowing record has already been processed.'
            );
        }

        // 2. Recalculate fine using current return date

        $return_date = date('Y-m-d');

        $return_time = strtotime($return_date);
        $due_time = strtotime($borrowing->due_date);

        $day_late = max(
            0,
            (int)floor(($return_time - $due_time) / 86400)
        );

        $fine_amount = 0;

        if ($day_late > 0) {
            $fine_amount = (int)ceil($day_late / 5) * FINE_OF_DAY;
        }

        // 3. Lock book record

        $stm = $_db->prepare("
            SELECT book_id
            FROM book
            WHERE book_id = ?
            FOR UPDATE
        ");

        $stm->execute([$borrowing->book_id]);

        if (!$stm->fetch()) {
            throw new Exception('Book record not found.');
        }

        // 4. Update borrowing record

        $stm = $_db->prepare("
            UPDATE borrowing
            SET return_date = ?,
                fine_amount = ?,
                status = 'RETURN'
            WHERE borrowing_id = ?
        ");

        $stm->execute([
            $return_date,
            $fine_amount,
            $borrow_id
        ]);

        // 5. Create fine record if overdue

        if ($fine_amount > 0) {

            $stm = $_db->prepare("
                INSERT INTO fine
                (
                    amount,
                    borrowing_id,
                    payment_status,
                    reason
                )
                VALUES (?, ?, ?, ?)
            ");

            $stm->execute([
                $fine_amount,
                $borrow_id,
                'Unpaid',
                'Member late return book'
            ]);
        }

        // 6. Find earliest approved reservation

        $stm = $_db->prepare("
            SELECT *
            FROM reservation
            WHERE book_id = ?
            AND UPPER(status) = 'APPROVED'
            ORDER BY reservation_date ASC,
                     reservation_id ASC
            LIMIT 1
            FOR UPDATE
        ");

        $stm->execute([$borrowing->book_id]);

        $reservation = $stm->fetch();

        if ($reservation) {

            // 7. Allocate returned book to earliest reservation

            $stm = $_db->prepare("
                UPDATE reservation
                SET status = 'READY',
                    expiry_date = DATE_ADD(
                        CURDATE(),
                        INTERVAL 3 DAY
                    )
                WHERE reservation_id = ?
                AND UPPER(status) = 'APPROVED'
            ");

            $stm->execute([
                $reservation->reservation_id
            ]);

            if ($stm->rowCount() !== 1) {
                throw new Exception(
                    'Unable to allocate the returned book.'
                );
            }

            // Do not increase available_quantity.
            // The returned book is held for this member.

        } else {

            // 8. No reservation: increase available stock

            $stm = $_db->prepare("
                UPDATE book
                SET available_quantity = available_quantity + 1
                WHERE book_id = ?
            ");

            $stm->execute([
                $borrowing->book_id
            ]);
        }

        // 9. Commit transaction

        $_db->commit();

        if ($fine_amount > 0) {

            temp(
                'info',
                'Book returned successfully. RM ' .
                number_format($fine_amount, 2) .
                ' fine has been recorded as Unpaid.'
            );

        } else {

            temp(
                'info',
                'Book returned successfully. No fine is required.'
            );
        }

        redirect('/student/activities/history.php');

    } catch (Throwable $e) {

        if ($_db->inTransaction()) {
            $_db->rollBack();
        }

        temp(
            'info',
            'Return failed: ' . $e->getMessage()
        );

        redirect('/student/activities/history.php');
    }
}

// ==========================================
// DISPLAY RETURN PAGE
// ==========================================

include '../../_head.php';

?>

<link rel="stylesheet" href="/css/app.css">
<link rel="stylesheet" href="/css/return.css">

<main class="main-content">

    <div class="return-page">

        <!-- PAGE HEADER -->

        <div class="return-header">

            <div class="return-header-icon">
                <span>📚</span>
            </div>

            <div>
                <h1>Return Book</h1>
                <p>
                    Review your borrowing details before confirming your return.
                </p>
            </div>


        </div>

        <!-- MAIN CARD -->

        <div class="return-card">

            <!-- BOOK DETAILS -->

            <div class="return-book-section">

                <div class="return-book-image">

                    <?php if (!empty($return->image)): ?>

                        <img
                            src="/images/Book_pic/<?= encode($return->image) ?>"
                            alt="<?= encode($return->title) ?>"
                        >

                    <?php else: ?>

                        <div class="book-placeholder">
                            📚
                        </div>

                    <?php endif; ?>

                </div>

                <div class="return-book-details">

                    <span class="section-label">
                        BOOK DETAILS
                    </span>

                    <h2>
                        <?= encode($return->title) ?>
                    </h2>

                    <p class="book-author">
                        By <?= encode($return->author) ?>
                    </p>

                    <div class="book-meta">

                        <span>Category</span>

                        <strong>
                            <?= encode($return->category_name ?? 'N/A') ?>
                        </strong>

                    </div>

                    <div class="book-description">

                        <?= encode($return->description) ?>

                    </div>

                </div>

            </div>

            <div class="return-divider"></div>

            <!-- BORROWING INFORMATION -->

            <div class="borrowing-section">

                <div class="section-heading">

                    <span class="section-label">
                        BORROWING INFORMATION
                    </span>

                    <span class="return-status">
                        Awaiting Return
                    </span>

                </div>

                <div class="return-info-grid">

                    <div class="info-box">

                        <div class="info-icon">📅</div>

                        <div>
                            <span>Borrow Date</span>

                            <strong>
                                <?= date('d M Y', strtotime($borrow_date)) ?>
                            </strong>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-icon">📆</div>

                        <div>
                            <span>Due Date</span>

                            <strong>
                                <?= date('d M Y', strtotime($due_date)) ?>
                            </strong>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-icon">↩</div>

                        <div>
                            <span>Return Date</span>

                            <strong>
                                <?= date('d M Y', strtotime($return_date)) ?>
                            </strong>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-icon">⏱</div>

                        <div>
                            <span>Overdue Days</span>

                            <strong class="<?= $day_late > 0 ? 'late-text' : '' ?>">
                                <?= $day_late ?> day(s)
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

            <div class="return-divider"></div>

            <!-- FINE SUMMARY -->

            <div class="fine-summary">

                <div class="fine-summary-details">

                    <span class="section-label">
                        FINE SUMMARY
                    </span>

                    <h3>Total Fine Amount</h3>

                    <?php if ($fine_amount > 0): ?>

                        <p>
                            Your fine will be recorded as Unpaid.
                            You can pay it later under My Fines.
                        </p>

                    <?php else: ?>

                        <p>
                            No overdue fine. Thank you for returning
                            the book on time.
                        </p>

                    <?php endif; ?>

                </div>

                <div class="fine-amount <?= $fine_amount > 0 ? 'has-fine' : '' ?>">

                    RM <?= number_format($fine_amount, 2) ?>

                </div>

            </div>

            <!-- FINE RATE INFORMATION -->

            <div class="fine-rate-note">

                <span>ⓘ</span>

                Fine rate: RM5 for every 1–5 overdue days.

            </div>

            <!-- ACTION BUTTONS -->

            <form method="post" class="return-actions">

                <a
                    href="/student/activities/history.php"
                    class="return-cancel-btn"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="return-confirm-btn"
                    onclick="return confirm('Are you sure you want to return this book?');"
                >
                    Confirm Return
                    <span>→</span>
                </button>

            </form>

        </div>

        <!-- FOOTER NOTE -->

        <p class="return-footer-note">
            Please ensure that the book is returned in good condition.
        </p>

    </div>

</main>

<?php include '../../_footer.php'; ?>
