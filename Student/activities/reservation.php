
<?php

require_once '../../_base.php';

auth(['member']);

$_title = 'My Reservations';

// ==========================================
// CANCEL RESERVATION
// ==========================================

if (is_post()) {

    $reservation_id = post('reservation_id');

    if ($reservation_id !== '' && ctype_digit($reservation_id)) {

        $stm = $_db->prepare("
            UPDATE reservation
            SET status = 'Cancelled'
            WHERE reservation_id = ?
            AND user_id = ?
            AND status = 'Pending'
        ");

        $stm->execute([
            $reservation_id,
            $_user->user_id
        ]);

        if ($stm->rowCount() > 0) {
            temp('message', 'Reservation cancelled successfully.');
        }
        else {
            temp('message', 'Unable to cancel this reservation.');
        }
    }

    redirect('/student/activities/reservation.php');
}


// ==========================================
// GET RESERVATION STATISTICS
// ==========================================

$stm = $_db->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Approved'), 0) AS approved,
        COALESCE(SUM(status = 'Rejected'), 0) AS rejected
    FROM reservation
    WHERE user_id = ?
");

$stm->execute([$_user->user_id]);

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
    ORDER BY r.reservation_date DESC
");

$stm->execute([$_user->user_id]);

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

            <!-- TOTAL -->
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


            <!-- APPROVED -->
            <div class="stat-card approved-card">

                <div class="stat-icon">✓</div>

                <div class="stat-content">
                    <p class="stat-label">Approved</p>
                    <h2><?= $stats->approved ?></h2>
                </div>

            </div>


            <!-- REJECTED -->
            <div class="stat-card rejected-card">

                <div class="stat-icon">✕</div>

                <div class="stat-content">
                    <p class="stat-label">Rejected</p>
                    <h2><?= $stats->rejected ?></h2>
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

                <span class="record-count">
                    <?= count($reservations) ?> Records
                </span>

            </div>


            <?php if (empty($reservations)): ?>


                <!-- ==================================
                     EMPTY STATE
                =================================== -->

                <div class="reservation-empty">

                    <div class="empty-icon">📚</div>

                    <h3>No Reservations Yet</h3>

                    <p>
                        You have not made any book reservations.
                    </p>

                    <a href="/student/book/browse.php">
                        Browse Books
                    </a>

                </div>


            <?php else: ?>


                <!-- ==================================
                     RESERVATION TABLE
                =================================== -->

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
                            ?>

                                <tr>

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
                                        <?= date(
                                            'd M Y',
                                            strtotime($r->reservation_date)
                                        ) ?>
                                    </td>


                                    <!-- EXPIRY DATE -->
                                    <td>
                                        <?= date(
                                            'd M Y',
                                            strtotime($r->expiry_date)
                                        ) ?>
                                    </td>


                                    <!-- STATUS -->
                                    <td>

                                        <span class="status-badge <?= strtolower(encode($r->status)) ?>">
                                            <?= encode($r->status) ?>
                                        </span>

                                    </td>


                                    <!-- ACTION -->
                                    <td>

                                        <?php if ($r->status === 'Pending'): ?>

                                            <form
                                                method="post"
                                                onsubmit="return confirm('Are you sure you want to cancel this reservation?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?= $r->reservation_id ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="cancel-btn"
                                                >
                                                    Cancel
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="no-action">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>


                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php endif; ?>

        </div>

    </div>

</main>


<?php include '../../_footer.php'; ?>
