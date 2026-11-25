
<?php

require_once '../../_base.php';

auth(['member']);

$_title = 'My Reservations';

$user_id = $_user->user_id;

// ==========================================
// ADD NEW RESERVATION
// ==========================================

if (isset($_GET['id'])) {

    $book_id = (string)$_GET['id'];

    // Validate Book ID
    if (!ctype_digit($book_id) || (int)$book_id <= 0) {
        temp('message', 'Invalid book selection.');
        redirect('/Student/activities/reservation.php');
    }

    // Check book information
    $stm = $_db->prepare("
        SELECT book_id, title, available_quantity
        FROM book
        WHERE book_id = ?
    ");

    $stm->execute([$book_id]);
    $book = $stm->fetch();

    if (!$book) {
        temp('message', 'Book not found.');
        redirect('/Student/activities/reservation.php');
    }

    // Reservation is only allowed when no copy is available
    if ($book->available_quantity > 0) {
        temp('message', 'This book is currently available. You can borrow it instead.');
        redirect('/Student/activities/reservation.php');
    }

    // Check existing active reservation
    // READY is also an active reservation
    $stm = $_db->prepare("
        SELECT reservation_id
        FROM reservation
        WHERE user_id = ?
        AND book_id = ?
        AND UPPER(status) IN ('PENDING', 'APPROVED', 'READY')
        LIMIT 1
    ");

    $stm->execute([$user_id, $book_id]);

    if ($stm->fetch()) {
        temp('message', 'You already have an active reservation for this book.');
        redirect('/Student/activities/reservation.php');
    }

    // Create reservation
    $stm = $_db->prepare("
        INSERT INTO reservation
        (
            user_id,
            book_id,
            reservation_date,
            expiry_date,
            status
        )
        VALUES (?, ?, NOW(), NULL, 'Pending')
    ");

    $stm->execute([$user_id, $book_id]);

    temp('message', 'Reservation submitted successfully!');
    redirect('/Student/activities/reservation.php');
}


// ==========================================
// HANDLE RESERVATION ACTIONS
// ==========================================

if (is_post()) {

    $reservation_id = post('reservation_id');
    $action = post('action');

    if (
        $reservation_id !== '' &&
        ctype_digit((string)$reservation_id)
    ) {

        try {

            $_db->beginTransaction();

            // Lock reservation record
            $stm = $_db->prepare("
                SELECT *
                FROM reservation
                WHERE reservation_id = ?
                AND user_id = ?
                FOR UPDATE
            ");

            $stm->execute([
                $reservation_id,
                $user_id
            ]);

            $r = $stm->fetch();

            if (!$r) {
                throw new Exception('Reservation not found.');
            }

            $reservation_status = strtoupper(trim($r->status));


            // ==================================
            // CANCEL RESERVATION
            // ==================================

            if (
                $action === 'cancel' &&
                $reservation_status === 'PENDING'
            ) {

                $stm = $_db->prepare("
                    UPDATE reservation
                    SET status = 'Cancelled'
                    WHERE reservation_id = ?
                    AND user_id = ?
                    AND UPPER(status) = 'PENDING'
                ");

                $stm->execute([
                    $reservation_id,
                    $user_id
                ]);

                if ($stm->rowCount() !== 1) {
                    throw new Exception('Unable to cancel reservation.');
                }

                $_db->commit();

                temp('message', 'Reservation cancelled successfully.');

            }


            // ==================================
            // COLLECT BOOK
            // ==================================

            elseif (
                $action === 'collect' &&
                $reservation_status === 'READY'
            ) {

                // Check collection expiry
                if (
                    empty($r->expiry_date) ||
                    date('Y-m-d') > $r->expiry_date
                ) {

                    throw new Exception(
                        'Your collection period has expired. Please contact the librarian.'
                    );

                }

                // Create borrowing record
                // Borrowing period: 8 days
                $stm = $_db->prepare("
                    INSERT INTO borrowing
                    (
                        user_id,
                        book_id,
                        borrow_date,
                        due_date,
                        return_date,
                        status,
                        fine_amount
                    )
                    VALUES (
                        ?,
                        ?,
                        CURDATE(),
                        DATE_ADD(CURDATE(), INTERVAL 8 DAY),
                        NULL,
                        'APPROVAL',
                        0.00
                    )
                ");

                $stm->execute([
                    $user_id,
                    $r->book_id
                ]);

                // Update reservation status
                $stm = $_db->prepare("
                    UPDATE reservation
                    SET status = 'COMPLETED'
                    WHERE reservation_id = ?
                    AND user_id = ?
                    AND UPPER(status) = 'READY'
                ");

                $stm->execute([
                    $reservation_id,
                    $user_id
                ]);

                if ($stm->rowCount() !== 1) {
                    throw new Exception('Unable to complete reservation.');
                }

                $_db->commit();

                temp(
                    'message',
                    'Book collected successfully! Your borrowing period is 8 days.'
                );

            }


            // ==================================
            // INVALID ACTION
            // ==================================

            else {

                throw new Exception('Invalid reservation action.');

            }

        }

        catch (Throwable $e) {

            if ($_db->inTransaction()) {
                $_db->rollBack();
            }

            temp('message', $e->getMessage());

        }

    }

    else {

        temp('message', 'Invalid reservation request.');

    }

    redirect('/Student/activities/reservation.php');
}


// ==========================================
// GET RESERVATION STATISTICS
// ==========================================

$stm = $_db->prepare("
    SELECT
        COUNT(*) AS total,

        COALESCE(SUM(UPPER(status) = 'PENDING'), 0) AS pending,

        COALESCE(SUM(UPPER(status) = 'APPROVED'), 0) AS approved,

        COALESCE(SUM(UPPER(status) = 'READY'), 0) AS ready,

        COALESCE(SUM(UPPER(status) = 'COMPLETED'), 0) AS completed,

        COALESCE(SUM(UPPER(status) = 'REJECTED'), 0) AS rejected,

        COALESCE(SUM(UPPER(status) = 'CANCELLED'), 0) AS cancelled

    FROM reservation
    WHERE user_id = ?
");

$stm->execute([$user_id]);
$stats = $stm->fetch();


// ==========================================
// GET MEMBER RESERVATIONS
// ==========================================

$stm = $_db->prepare("
    SELECT
        r.reservation_id,
        r.reservation_date,
        r.expiry_date,
        r.status,

        b.book_id,
        b.title,
        b.author,
        b.image

    FROM reservation r

    INNER JOIN book b
        ON r.book_id = b.book_id

    WHERE r.user_id = ?

    ORDER BY r.reservation_date DESC,
             r.reservation_id DESC
");

$stm->execute([$user_id]);

$reservations = $stm->fetchAll();


// ==========================================
// GET MESSAGE
// ==========================================

$message = temp('message');

include '../../_head.php';

?>

<link rel="stylesheet" href="/css/app.css">
<link rel="stylesheet" href="/css/reservation.css">

<main class="main-content">

    <div class="reservation-page">

        <!-- PAGE HEADER -->

        <div class="reservation-header">

            <div>
                <h1>My Reservations</h1>
                <p>View and manage your book reservations.</p>
            </div>

        </div>


        <!-- SUCCESS / ERROR MESSAGE -->

        <?php if ($message): ?>

            <div class="reservation-message">
                <?= encode($message) ?>
            </div>

        <?php endif; ?>


        <!-- ======================================
             RESERVATION STATISTICS
        ======================================= -->

       
<div class="reservation-stats">

    <!-- TOTAL RESERVATIONS -->

    <div class="stat-card total-card">
        <div class="stat-icon">📚</div>

        <div class="stat-content">
            <p class="stat-label">Total Reservations</p>
            <h2><?= $stats->total ?></h2>
        </div>
    </div>


    <!-- PENDING -->

    <div class="stat-card pending-card">
        <div class="stat-icon">⏳</div>

        <div class="stat-content">
            <p class="stat-label">Pending</p>
            <h2><?= $stats->pending ?></h2>
        </div>
    </div>


    <!-- READY TO COLLECT -->

    <div class="stat-card ready-card">
        <div class="stat-icon">📖</div>

        <div class="stat-content">
            <p class="stat-label">Ready to Collect</p>
            <h2><?= $stats->ready ?></h2>
        </div>
    </div>

</div>


        <!-- ======================================
             RESERVATION HISTORY CARD
        ======================================= -->

        <div class="reservation-table-card">

            <!-- TABLE HEADING -->

            <div class="table-heading">

                <div>
                    <h2>Reservation History</h2>
                    <p>All your book reservation records.</p>
                </div>

                <span class="record-count" id="recordCount">
                    <?= count($reservations) ?> Records
                </span>

            </div>


            <?php if (empty($reservations)): ?>

                <!-- EMPTY STATE -->

                <div class="reservation-empty">

                    <div class="empty-icon">📚</div>

                    <h3>No Reservations Yet</h3>

                    <p>You have not made any book reservations.</p>

                    <a href="/student/book/browse.php">
                        Browse Books
                    </a>

                </div>

            <?php else: ?>


                <!-- STATUS FILTER TABS -->

                <div class="filter-tabs" id="filterTabs">

                    <button
                        type="button"
                        class="filter-tab active"
                        data-filter="all"
                    >
                        All
                    </button>

                    <button
                        type="button"
                        class="filter-tab"
                        data-filter="pending"
                    >
                        Pending
                    </button>

                    <button
                        type="button"
                        class="filter-tab"
                        data-filter="approved"
                    >
                        Approved
                    </button>

                    <button
                        type="button"
                        class="filter-tab"
                        data-filter="ready"
                    >
                        Ready
                    </button>

                    <button
                        type="button"
                        class="filter-tab"
                        data-filter="completed"
                    >
                        Completed
                    </button>

                    <button
                        type="button"
                        class="filter-tab"
                        data-filter="rejected"
                    >
                        Rejected
                    </button>

                    <button
                        type="button"
                        class="filter-tab"
                        data-filter="cancelled"
                    >
                        Cancelled
                    </button>

                </div>


                <!-- RESERVATION TABLE -->

                <div class="table-responsive">

                    <table class="reservation-table">

                        <thead>

                            <tr>
                                <th>No.</th>
                                <th>Book Details</th>
                                <th>Reservation Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php

                            $no = 1;

                            foreach ($reservations as $r):

                                $status = strtoupper(trim($r->status));

                                $status_class = strtolower($status);

                            ?>

                                <tr data-status="<?= encode($status_class) ?>">

                                    <!-- NUMBER -->

                                    <td>
                                        <?= $no++ ?>
                                    </td>


                                    <!-- BOOK DETAILS -->

                                    <td>

                                        <div class="reservation-book">

                                            <?php if (!empty($r->image)): ?>

                                                <img
                                                    src="/admin/book_photo/<?= encode($r->image) ?>"
                                                    alt="<?= encode($r->title) ?>"
                                                    onerror="this.outerHTML='<div class=&quot;book-placeholder&quot;>📚</div>'"
                                                >

                                            <?php else: ?>

                                                <div class="book-placeholder">
                                                    📚
                                                </div>

                                            <?php endif; ?>


                                            <div class="book-info">

                                                <strong>
                                                    <?= encode($r->title) ?>
                                                </strong>

                                                <small>
                                                    By <?= encode($r->author) ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- RESERVATION DATE -->

                                    <td>
                                        <?= date('d M Y', strtotime($r->reservation_date)) ?>
                                    </td>


                                    <!-- EXPIRY DATE -->

                                    <td>

                                        <?php if (!empty($r->expiry_date)): ?>

                                           <div class="expiry-date">
                                              <?= date('d M Y', strtotime($r->expiry_date)) ?>
                                           </div>
                                            <?php if ($status === 'READY'):

                                                $days_left = (int)floor(
                                                    (strtotime($r->expiry_date) - strtotime('today')) / 86400
                                                );

                                            ?>

                                                <small class="expiry-note <?= $days_left <= 1 ? 'expiry-soon' : '' ?>">

                                                    <?php if ($days_left < 0): ?>

                                                        Expired

                                                    <?php elseif ($days_left === 0): ?>

                                                        Expires today

                                                    <?php else: ?>

                                                        <?= $days_left ?>
                                                        day<?= $days_left > 1 ? 's' : '' ?>
                                                        left

                                                    <?php endif; ?>

                                                </small>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span class="no-action">—</span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span class="status-badge <?= encode($status_class) ?>">
                                            <?= encode($status) ?>
                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>


                                        <!-- PENDING: CANCEL -->

                                        <?php if ($status === 'PENDING'): ?>

                                            <form
                                                method="post"
                                                onsubmit="return confirm('Are you sure you want to cancel this reservation?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?= (int)$r->reservation_id ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="cancel"
                                                >

                                                <button
                                                    type="submit"
                                                    class="cancel-btn"
                                                >
                                                    Cancel
                                                </button>

                                            </form>


                                        <!-- APPROVED: WAIT FOR RETURN -->

                                        <?php elseif ($status === 'APPROVED'): ?>

                                            <span class="waiting-book">
                                                Waiting for Book Return
                                            </span>


                                        <!-- READY: COLLECT BOOK -->

                                        <?php elseif ($status === 'READY'): ?>

                                            <?php if (
                                                !empty($r->expiry_date) &&
                                                date('Y-m-d') <= $r->expiry_date
                                            ): ?>

                                                <form
                                                    method="post"
                                                    onsubmit="return confirm('Are you sure you want to collect this book?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="reservation_id"
                                                        value="<?= (int)$r->reservation_id ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="collect"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="collect-btn"
                                                    >
                                                        Collect Book
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <span class="expired-action">
                                                    Collection Expired
                                                </span>

                                            <?php endif; ?>


                                        <!-- COMPLETED -->

                                        <?php elseif ($status === 'COMPLETED'): ?>

                                            <span class="completed-action">
                                                Collected
                                            </span>


                                        <!-- CANCELLED / REJECTED -->

                                        <?php elseif (in_array($status, ['CANCELLED', 'REJECTED'])): ?>

                                            <a
                                                class="reserve-again-btn"
                                                href="/student/activities/reservation.php?id=<?= (int)$r->book_id ?>"
                                            >
                                                Reserve Again
                                            </a>


                                        <!-- OTHER STATUS -->

                                        <?php else: ?>

                                            <span class="no-action">—</span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>


                    <!-- EMPTY FILTER MESSAGE -->

                    <p class="filter-empty" id="filterEmpty" hidden>
                        No reservations match this filter.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<!-- ==========================================
     STATUS FILTER JAVASCRIPT
========================================== -->

<script>

(function () {

    const tabs = document.querySelectorAll('.filter-tab');

    const rows = document.querySelectorAll(
        '.reservation-table tbody tr'
    );

    const count = document.getElementById('recordCount');

    const empty = document.getElementById('filterEmpty');

    if (!tabs.length) return;


    tabs.forEach(tab => {

        tab.addEventListener('click', () => {

            const filter = tab.dataset.filter;

            let visible = 0;

            // Active tab
            tabs.forEach(t => {
                t.classList.toggle('active', t === tab);
            });

            // Filter rows
            rows.forEach(row => {

                const show =
                    filter === 'all' ||
                    row.dataset.status === filter;

                row.style.display = show ? '' : 'none';

                if (show) {
                    visible++;
                }

            });

            // Update record count
            count.textContent =
                visible + (visible === 1 ? ' Record' : ' Records');

            // Show empty message
            empty.hidden = visible !== 0;

        });

    });

})();

</script>


<?php include '../../_footer.php'; ?>
