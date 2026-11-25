
<?php

include '../../_base.php';

auth(['member']);

$_title = 'Return Book';

$user_id = $_user->user_id;

// Get borrowing ID
$borrowing_id = req('borrowing_id');

if (!$borrowing_id || !ctype_digit((string)$borrowing_id)) {
    temp('info', 'Invalid borrowing record');
    redirect('/student/activities/history.php');
}

// Get borrowing and book details
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
    JOIN book bk ON bk.book_id = bor.book_id
    JOIN category c ON bk.category_id = c.category_id
    WHERE bor.borrowing_id = ?
    AND bor.user_id = ?
");

$stm->execute([$borrowing_id, $user_id]);
$return = $stm->fetch();

if (!$return) {
    temp('info', 'Borrowing record not found');
    redirect('/student/activities/history.php');
}

// Check borrowing status
$_status = strtoupper(trim($return->status));

if (!in_array($_status, ['APPROVAL', 'OVERDUE'], true)) {
    temp('info', 'This book cannot be returned');
    redirect('/student/activities/history.php');
}

// Date calculation
$_return_date = date('Y-m-d');
$_return_td = strtotime($_return_date);

$_due_date = $return->due_date;
$_due_td = strtotime($_due_date);

$_borrow_date = $return->borrow_date;

// Calculate overdue days
$day_late = max(
    0,
    (int)floor(($_return_td - $_due_td) / 86400)
);

// Fine calculation
// 1-5 days = RM5
// 6-10 days = RM10
// 11-15 days = RM15

$_fine_amount = ($day_late > 0)
    ? ceil($day_late / 5) * 5
    : 0;

// Process return only after clicking Return button
if (is_post()) {

    try {

        $_db->beginTransaction();

        // Lock borrowing record to prevent duplicate return
        $stm = $_db->prepare("
            SELECT *
            FROM borrowing
            WHERE borrowing_id = ?
            AND user_id = ?
            FOR UPDATE
        ");

        $stm->execute([$borrowing_id, $user_id]);
        $borrowing = $stm->fetch();

        if (!$borrowing ||
            !in_array($borrowing->status, ['APPROVAL', 'OVERDUE'], true)) {

            throw new Exception('This book has already been returned or cannot be returned');
        }

        // Calculate final overdue days
        $return_date = date('Y-m-d');

        $return_td = strtotime($return_date);
        $due_td = strtotime($borrowing->due_date);

        $day_late = max(
            0,
            (int)floor(($return_td - $due_td) / 86400)
        );

        $fine_amount = ($day_late > 0)
            ? ceil($day_late / 5) * 5
            : 0;

        // Update borrowing record
        $stm = $_db->prepare("
            UPDATE borrowing
            SET return_date = ?,
                fine_amount = ?,
                status = 'RETURN'
            WHERE borrowing_id = ?
            AND user_id = ?
        ");

        $stm->execute([
            $return_date,
            $fine_amount,
            $borrowing_id,
            $user_id
        ]);

        // Restore available book quantity
        $stm = $_db->prepare("
            UPDATE book
            SET available_quantity = available_quantity + 1
            WHERE book_id = ?
        ");

        $stm->execute([$borrowing->book_id]);

        // Create fine record if overdue
        if ($fine_amount > 0) {

            $period = (int)ceil($day_late / 5);

            $range_start = ($period - 1) * 5 + 1;
            $range_end = $period * 5;

            $reason = "Overdue $range_start - $range_end days";

            // Check duplicate fine record
            $stm = $_db->prepare("
                SELECT fine_id
                FROM fine
                WHERE borrowing_id = ?
            ");

            $stm->execute([$borrowing_id]);
            $existing_fine = $stm->fetch();

            if (!$existing_fine) {

                $stm = $_db->prepare("
                    INSERT INTO fine
                    (borrowing_id, amount, reason, payment_status)
                    VALUES (?, ?, ?, 'Unpaid')
                ");

                $stm->execute([
                    $borrowing_id,
                    $fine_amount,
                    $reason
                ]);
            }
        }

        $_db->commit();

        if ($fine_amount > 0) {

            temp(
                'info',
                'Book returned successfully. Fine amount: RM' .
                number_format($fine_amount, 2)
            );

        } else {

            temp('info', 'Book returned successfully. No fine required');

        }

        redirect('/student/activities/history.php');

    } catch (Throwable $e) {

        if ($_db->inTransaction()) {
            $_db->rollBack();
        }

        temp('info', $e->getMessage());
        redirect('/student/activities/history.php');
    }
}

include '../../_head.php';

?>

<form method="post">

    <div class="return-borrow-card">

        <label>Book Details</label>

        <div class="books">

            <img
                src="/images/Book_pic/<?= encode($return->image) ?>"
                alt="<?= encode($return->title) ?>"
            >

            <div class="book_detail">

                <p>
                    <strong>Title:</strong>
                    <?= encode($return->title) ?>
                </p>

                <p>
                    <strong>Author:</strong>
                    <?= encode($return->author) ?>
                </p>

                <p>
                    <strong>Description:</strong>
                    <?= encode($return->description) ?>
                </p>

                <p>
                    <strong>Category:</strong>
                    <?= encode($return->category_name) ?>
                </p>

                <strong>Fine Amount</strong>

                <h3 class="<?= $_fine_amount > 0 ? 'paid_overdue' : 'Not_overdue' ?>">
                    RM <?= number_format($_fine_amount, 2) ?>
                </h3>

            </div>

        </div>

        <div class="Date-card">

            <label>Borrow Date</label>

            <input
                type="date"
                value="<?= encode($_borrow_date) ?>"
                readonly
            >

            <label>Due Date</label>

            <input
                type="date"
                value="<?= encode($_due_date) ?>"
                readonly
            >

            <label>Return Date</label>

            <input
                type="date"
                value="<?= encode($_return_date) ?>"
                readonly
            >

            <label>Late Days</label>

            <p><?= $day_late ?> days</p>

        </div>

        <section>

            <button
                type="button"
                onclick="location.href='/student/activities/history.php'">
                Cancel
            </button>

            <button type="submit">
                Confirm Return
            </button>

        </section>

    </div>

</form>

<?php
include '../../_footer.php';
?>
