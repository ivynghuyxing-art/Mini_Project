
<?php

require_once '../../_base.php';

auth(['admin']);

$_title = 'Reservation Management';


// ==========================================
// HANDLE APPROVE / REJECT RESERVATION
// ==========================================

if (is_post()) {

    $reservation_id = post('reservation_id');
    $action = post('action');

    if (
        $reservation_id !== '' &&
        ctype_digit((string)$reservation_id) &&
        in_array($action, ['approve', 'reject'])
    ) {

        $new_status = ($action === 'approve')
            ? 'Approved'
            : 'Rejected';

        $stm = $_db->prepare("
            UPDATE reservation
            SET status = ?
            WHERE reservation_id = ?
            AND UPPER(status) = 'PENDING'
        ");

        $stm->execute([
            $new_status,
            $reservation_id
        ]);

        if ($stm->rowCount() > 0) {

            temp(
                'message',
                "Reservation $new_status successfully."
            );

        }
        else {

            temp(
                'message',
                'Unable to update reservation. Please check its current status.'
            );

        }

    }
    else {

        temp('message', 'Invalid reservation request.');

    }

    redirect('/admin/reservation/reservation.php');
}


// ==========================================
// GET RESERVATION STATISTICS
// ==========================================

$stm = $_db->query("
    SELECT

        COUNT(*) AS total,

        COALESCE(
            SUM(UPPER(status) = 'PENDING'),
            0
        ) AS pending,

        COALESCE(
            SUM(UPPER(status) = 'APPROVED'),
            0
        ) AS approved,

        COALESCE(
            SUM(UPPER(status) = 'READY'),
            0
        ) AS ready

    FROM reservation
");

$stats = $stm->fetch();


// ==========================================
// SEARCH AND FILTER
// ==========================================

$search = trim(get('search', '') ?? '');

$status = get('status', '') ?? '';

$allowed_status = [
    'Pending',
    'Approved',
    'Ready',
    'Completed',
    'Rejected',
    'Cancelled',
    'Expired'
];


// ==========================================
// GET ALL RESERVATIONS
// ==========================================

$sql = "
    SELECT
        r.reservation_id,
        r.user_id,
        r.book_id,
        r.reservation_date,
        r.expiry_date,
        r.status,

        b.title,
        b.author

    FROM reservation r

    INNER JOIN book b
        ON r.book_id = b.book_id

    WHERE 1 = 1
";

$params = [];


// ==========================================
// SEARCH BY RESERVATION ID,
// MEMBER ID OR BOOK TITLE
// ==========================================

if ($search !== '') {

    $sql .= "
        AND (
            CAST(r.reservation_id AS CHAR) LIKE ?
            OR CAST(r.user_id AS CHAR) LIKE ?
            OR b.title LIKE ?
        )
    ";

    $keyword = "%$search%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

}


// ==========================================
// FILTER BY STATUS
// ==========================================

if (in_array($status, $allowed_status)) {

    $sql .= " AND UPPER(r.status) = UPPER(?) ";

    $params[] = $status;

}


// ==========================================
// SORT NEWEST FIRST
// ==========================================

$sql .= "
    ORDER BY r.reservation_date DESC,
             r.reservation_id DESC
";

$stm = $_db->prepare($sql);

$stm->execute($params);

$reservations = $stm->fetchAll();


// ==========================================
// GET MESSAGE
// ==========================================

$message = temp('message');

include '../../admin_header.php';

?>

<link rel="stylesheet" href="/css/app.css">

<main class="main-content">

    <div class="admin-reservation-page">


        <!-- ==================================
             PAGE HEADER
        =================================== -->
    


        <div class="admin-reservation-header">

            <div>

                <h1>Reservation Management</h1>

                <p>
                    Manage and monitor all member book reservations.
                </p>

            </div>

        </div>


        <!-- SUCCESS MESSAGE -->

        <?php if ($message): ?>

            <div class="admin-reservation-message">
                <?= encode($message) ?>
            </div>

        <?php endif; ?>


        <!-- ==================================
             RESERVATION STATISTICS
        =================================== -->

        <div class="admin-reservation-stats">


            <!-- TOTAL -->

            <div class="admin-stat-card total">

                <div class="admin-stat-icon">📚</div>

                <div>
                    <p>Total Reservations</p>
                    <h2><?= $stats->total ?></h2>
                </div>

            </div>


            <!-- PENDING -->

            <div class="admin-stat-card pending">

                <div class="admin-stat-icon">⏳</div>

                <div>
                    <p>Pending</p>
                    <h2><?= $stats->pending ?></h2>
                </div>

            </div>


            <!-- APPROVED -->

            <div class="admin-stat-card approved">

                <div class="admin-stat-icon">✓</div>

                <div>
                    <p>Approved</p>
                    <h2><?= $stats->approved ?></h2>
                </div>

            </div>


            <!-- READY -->

            <div class="admin-stat-card ready">

                <div class="admin-stat-icon">📖</div>

                <div>
                    <p>Ready to Collect</p>
                    <h2><?= $stats->ready ?></h2>
                </div>

            </div>

        </div>


        <!-- ==================================
             RESERVATION RECORDS
        =================================== -->

        <div class="admin-reservation-card">


            <div class="admin-reservation-card-header">

                <div>

                    <h2>Reservation Records</h2>

                    <p>
                        View and manage member reservations.
                    </p>

                </div>

                <span class="admin-record-count">
                    <?= count($reservations) ?> Records
                </span>

            </div>


            <!-- ==================================
                 SEARCH AND FILTER
            =================================== -->

            <form method="get" class="reservation-filter">

                <input
                    type="text"
                    name="search"
                    placeholder="Search book title, member ID..."
                    value="<?= encode($search) ?>"
                >

                <select name="status">

                    <option value="">All Status</option>

                    <?php foreach ($allowed_status as $s): ?>

                        <option
                            value="<?= encode($s) ?>"
                            <?= strcasecmp($status, $s) === 0 ? 'selected' : '' ?>
                        >
                            <?= encode($s) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit" class="filter-btn">
                    Search
                </button>

                <a
                    href="/admin/reservation/reservation.php"
                    class="reset-btn"
                >
                    Reset
                </a>

            </form>


            <!-- ==================================
                 RESERVATION TABLE
            =================================== -->

            <div class="admin-reservation-table-wrapper">

                <table class="admin-reservation-table">

                    <thead>

                        <tr>
                            <th>No.</th>
                            <th>Reservation ID</th>
                            <th>Member ID</th>
                            <th>Book Details</th>
                            <th>Reservation Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($reservations)): ?>

                            <tr>

                                <td colspan="8" class="no-records">
                                    No reservation records found.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php

                            $no = 1;

                            foreach ($reservations as $r):

                                $current_status = strtoupper(
                                    trim($r->status)
                                );

                                $status_class = strtolower(
                                    $current_status
                                );

                            ?>

                                <tr>

                                    <!-- NUMBER -->

                                    <td>
                                        <?= $no++ ?>
                                    </td>


                                    <!-- RESERVATION ID -->

                                    <td>
                                        #<?= (int)$r->reservation_id ?>
                                    </td>


                                    <!-- MEMBER ID -->

                                    <td>
                                        <?= encode($r->user_id) ?>
                                    </td>


                                    <!-- BOOK DETAILS -->

                                    <td>

                                        <div class="admin-book-details">

                                            <strong>
                                                <?= encode($r->title) ?>
                                            </strong>

                                            <small>
                                                By <?= encode($r->author) ?>
                                            </small>

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

                                        <?php if (!empty($r->expiry_date)): ?>

                                            <?= date(
                                                'd M Y',
                                                strtotime($r->expiry_date)
                                            ) ?>

                                            <?php if ($current_status === 'READY'): ?>

                                                <?php

                                                $days_left = (int)floor(
                                                    (
                                                        strtotime($r->expiry_date)
                                                        - strtotime('today')
                                                    ) / 86400
                                                );

                                                ?>

                                                <small class="admin-expiry-note">

                                                    <?php if ($days_left < 0): ?>

                                                        Expired

                                                    <?php elseif ($days_left === 0): ?>

                                                        Expires today

                                                    <?php else: ?>

                                                        <?= $days_left ?> days left

                                                    <?php endif; ?>

                                                </small>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span class="admin-status <?= encode($status_class) ?>">
                                            <?= encode($current_status) ?>
                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <!-- PENDING -->

                                        <?php if ($current_status === 'PENDING'): ?>

                                            <div class="reservation-actions">

                                                <form
                                                    method="post"
                                                    onsubmit="return confirm('Approve this reservation?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="reservation_id"
                                                        value="<?= (int)$r->reservation_id ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="approve"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="approve-btn"
                                                    >
                                                        Approve
                                                    </button>

                                                </form>


                                                <form
                                                    method="post"
                                                    onsubmit="return confirm('Reject this reservation?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="reservation_id"
                                                        value="<?= (int)$r->reservation_id ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="reject"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="reject-btn"
                                                    >
                                                        Reject
                                                    </button>

                                                </form>

                                            </div>


                                        <!-- APPROVED -->

                                        <?php elseif ($current_status === 'APPROVED'): ?>

                                            <span class="admin-action-label waiting">
                                                Waiting for Book Return
                                            </span>


                                        <!-- READY -->

                                        <?php elseif ($current_status === 'READY'): ?>

                                            <span class="admin-action-label ready">
                                                Waiting for Collection
                                            </span>


                                        <!-- COMPLETED -->

                                        <?php elseif ($current_status === 'COMPLETED'): ?>

                                            <span class="admin-action-label completed">
                                                Collected
                                            </span>


                                        <!-- REJECTED -->

                                        <?php elseif ($current_status === 'REJECTED'): ?>

                                            <span class="admin-action-label inactive">
                                                Rejected
                                            </span>


                                        <!-- CANCELLED -->

                                        <?php elseif ($current_status === 'CANCELLED'): ?>

                                            <span class="admin-action-label inactive">
                                                Cancelled
                                            </span>


                                        <!-- EXPIRED -->

                                        <?php elseif ($current_status === 'EXPIRED'): ?>

                                            <span class="admin-action-label expired">
                                                Expired
                                            </span>


                                        <!-- OTHER STATUS -->

                                        <?php else: ?>

                                            <span class="admin-no-action">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>


                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>


<?php include '../../_footer.php'; ?>
