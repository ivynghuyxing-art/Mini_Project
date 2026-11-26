
<?php
include '../../_base.php';

auth(['admin']);

$_id = (int)req('borrowing_id');
$_err = [];

if ($_id <= 0) {
    temp('info', 'Invalid borrowing ID.');
    redirect('/admin/borrowing/borrowing.php');
}

// Get borrowing details
$stm = $_db->prepare("
    SELECT
        bor.*,
        bk.available_quantity,
        bk.book_id,
        f.payment_status
    FROM borrowing bor
    JOIN book bk ON bk.book_id = bor.book_id
    LEFT JOIN fine f ON f.borrowing_id = bor.borrowing_id
    WHERE bor.borrowing_id = ?
");

$stm->execute([$_id]);
$borrows = $stm->fetch();

if (!$borrows) {
    temp('info', 'Borrow record not found.');
    redirect('/admin/borrowing/borrowing.php');
}

$_title = 'Update Borrow Detail';

// ==========================================
// PROCESS ADMIN ACTION
// ==========================================

if (is_post()) {

    $action = req('action');

    // ======================================
    // 1. ADMIN CONFIRM RETURN
    // ======================================

    if ($action === 'confirm_return') {

        try {
            $_db->beginTransaction();

            // Lock borrowing record
            $stm = $_db->prepare("
                SELECT *
                FROM borrowing
                WHERE borrowing_id = ?
                FOR UPDATE
            ");

            $stm->execute([$_id]);
            $borrowing = $stm->fetch();

            if (!$borrowing) {
                throw new Exception('Borrowing record not found.');
            }

            if (strtoupper(trim($borrowing->status)) !== 'RETURN_REQUESTED') {
                throw new Exception(
                    'This book has not submitted a return request.'
                );
            }

            // Calculate final fine using Admin confirmation date
            $return_date = date('Y-m-d');

            $return_time = strtotime($return_date);
            $due_time = strtotime($borrowing->due_date);

            $day_late = max(
                0,
                (int)floor(($return_time - $due_time) / 86400)
            );

            $fine_amount = $day_late > 0
                ? (int)ceil($day_late / 5) * 5
                : 0;

            // Lock book record
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

            // Update borrowing record
            $stm = $_db->prepare("
                UPDATE borrowing
                SET status = 'RETURN',
                    return_date = ?,
                    fine_amount = ?
                WHERE borrowing_id = ?
            ");

            $stm->execute([
                $return_date,
                $fine_amount,
                $_id
            ]);

            // Create fine record only when overdue
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
                    $_id,
                    'Unpaid',
                    'Member late return book'
                ]);
            }

            // Check earliest approved reservation
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

                // Allocate returned book to reservation
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
                // The book is reserved for the next member.

            } else {

                // No reservation: return book to available stock
                $stm = $_db->prepare("
                    UPDATE book
                    SET available_quantity = available_quantity + 1
                    WHERE book_id = ?
                ");

                $stm->execute([$borrowing->book_id]);
            }

            $_db->commit();

            if ($fine_amount > 0) {
                temp(
                    'info',
                    'Return confirmed successfully. Fine RM' .
                    number_format($fine_amount, 2) .
                    ' recorded as Unpaid.'
                );
            } else {
                temp(
                    'info',
                    'Return confirmed successfully. No fine required.'
                );
            }

            redirect('/admin/borrowing/borrowing.php');

        } catch (Throwable $e) {

            if ($_db->inTransaction()) {
                $_db->rollBack();
            }

            temp('info', 'Confirm return failed: ' . $e->getMessage());

            redirect('/admin/borrowing/update.php?borrowing_id=' . $_id);
        }
    }

    // ======================================
    // 2. ADMIN APPROVE / REJECT
    // ======================================

    elseif ($action === 'update_status') {

        $_status = strtoupper(trim(req('status')));

        $allowed_status = ['PENDING', 'APPROVAL', 'REJECT'];

        if (!in_array($_status, $allowed_status, true)) {
            $_err['status'] = 'Invalid status.';
        }

        if (!$_err) {

            $old_status = strtoupper(trim($borrows->status));

            if (in_array($old_status, ['RETURN', 'RETURN_REQUESTED'], true)) {
                $_err['status'] = 'Cannot change this borrowing status here.';
            } else {

                if ($_status === 'REJECT' && $old_status !== 'REJECT') {

                    $stm = $_db->prepare("
                        UPDATE book
                        SET available_quantity = available_quantity + 1
                        WHERE book_id = ?
                    ");

                    $stm->execute([$borrows->book_id]);
                }

                $stm = $_db->prepare("
                    UPDATE borrowing
                    SET status = ?
                    WHERE borrowing_id = ?
                ");

                $stm->execute([$_status, $_id]);

                temp('info', 'Update record successful.');
                redirect('/admin/borrowing/borrowing.php');
            }
        }
    }
}

// Refresh current status after processing
$stm = $_db->prepare("
    SELECT bor.*, f.payment_status
    FROM borrowing bor
    LEFT JOIN fine f ON f.borrowing_id = bor.borrowing_id
    WHERE bor.borrowing_id = ?
");

$stm->execute([$_id]);
$borrows = $stm->fetch();

include '../../admin_header.php';
?>

<form method="post">

    <div class="card-borrow-management">
<<<<<<< HEAD
      <a href="/admin/borrowing/borrowing.php">Back borrow page</a>
=======
>>>>>>> 06adde94f6cb399c285debcec0eaa5a4d0d8faa9

        <a href="/admin/borrowing/borrowing.php">
            &lt;--- Borrow page
        </a>

        <label>User ID</label>
        <h3><?= encode($borrows->user_id) ?></h3>

        <label>Book ID</label>
        <h3><?= encode($borrows->book_id) ?></h3>

        <label>Borrow Date</label>
        <h3><?= encode($borrows->borrow_date) ?></h3>

        <label>Due Date</label>
        <h3><?= encode($borrows->due_date) ?></h3>

        <label>Current Status</label>
        <h3><?= encode($borrows->status) ?></h3>

        <?php if (strtoupper($borrows->status) === 'RETURN'): ?>

            <label>Return Date</label>
            <h3><?= encode($borrows->return_date) ?></h3>

        <?php endif; ?>

        <label>Fine Amount</label>
        <h3>
            RM<?= number_format((float)$borrows->fine_amount, 2) ?>
        </h3>

        <?php if (!empty($borrows->payment_status)): ?>

            <label>Payment Status</label>
            <h3>
                <?= encode($borrows->payment_status) ?>
            </h3>

        <?php endif; ?>

        <?php if (strtoupper($borrows->status) === 'RETURN_REQUESTED'): ?>

            <hr>

            <h3>Member Return Request</h3>

            <p>
                The member has submitted a return request.
                Please confirm that the physical book has been received
                before confirming the return.
            </p>

            <button
                type="submit"
                name="action"
                value="confirm_return"
                onclick="return confirm('Have you received this book? Confirm return?');"
            >
                Confirm Return
            </button>

        <?php elseif (!in_array(
            strtoupper($borrows->status),
            ['RETURN', 'RETURN_REQUESTED'],
            true
        )): ?>

            <hr>

            <label for="status">Update Status</label>

            <select name="status" id="status">
                <?php foreach (['PENDING', 'APPROVAL', 'REJECT'] as $s): ?>
                    <option
                        value="<?= $s ?>"
                        <?= $s === strtoupper($borrows->status) ? 'selected' : '' ?>
                    >
                        <?= ucfirst(strtolower($s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php err('status') ?>

            <button
                type="submit"
                name="action"
                value="update_status"
            >
                Submit
            </button>

        <?php endif; ?>

    </div>

</form>

<?php
include '../../_footer.php';
?>
