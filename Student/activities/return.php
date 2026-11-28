

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

if (!in_array($status, ['APPROVAL', 'APPROVED','OVERDUE'],true)) {
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


// ==========================================
// PROCESS RETURN REQUEST
// ==========================================

if (is_post()){

    try {
        $_db->beginTransaction();

        // Lock borrowing record
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

        if (!in_array($borrowing_status, ['APPROVAL', 'APPROVED', 'OVERDUE'], true)) {
            throw new Exception('This borrowing record cannot request a return.');
        }

        // Only submit a return request.
        // Admin will confirm the actual return later.
        $stm = $_db->prepare("
            UPDATE borrowing
            SET status = 'RETURN_REQUESTED'
            WHERE borrowing_id = ?
            AND user_id = ?
        ");

        $stm->execute([
            $borrow_id,
            $_user->user_id
        ]);

        $_db->commit();

        temp(
            'info',
            'Return request submitted successfully. Please wait for Admin to confirm the returned book.'
        );

        redirect('/student/activities/history.php');

    } catch (Throwable $e) {

        if ($_db->inTransaction()) {
            $_db->rollBack();
        }

        temp('info', 'Return request failed: ' . $e->getMessage());

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

                Fine rate: RM5 for every 1-5 overdue days.

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
                    Submit Return Request
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
