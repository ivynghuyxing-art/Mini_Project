
<?php

require_once '../../_base.php';

auth(['admin']);

$_title = 'Feedback Management';

// ==========================================
// SEARCH AND FILTER
// ==========================================

$search = trim(get('search', '') ?? '');
$rating_filter = get('rating', '') ?? '';


// ==========================================
// DELETE FEEDBACK
// ==========================================

if (is_post()) {

    $feedback_id = post('feedback_id');

    if (
        $feedback_id !== '' &&
        ctype_digit((string)$feedback_id)
    ) {

        $stm = $_db->prepare("
            DELETE FROM feedback
            WHERE feedback_id = ?
        ");

        $stm->execute([$feedback_id]);

        if ($stm->rowCount() > 0) {
            temp('message', 'Feedback deleted successfully.');
        }
        else {
            temp('message', 'Feedback not found.');
        }

    }
    else {
        temp('message', 'Invalid feedback ID.');
    }

    redirect('/admin/Feedback/rating.php');
}


// ==========================================
// GET FEEDBACK STATISTICS
// ==========================================

$stm = $_db->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(AVG(rating), 0) AS average_rating,
        COALESCE(SUM(rating <= 2), 0) AS low_ratings
    FROM feedback
");

$stats = $stm->fetch();


// ==========================================
// GET ALL FEEDBACK
// ==========================================

$sql = "
    SELECT
        f.feedback_id,
        f.user_id,
        f.book_id,
        f.rating,
        f.comment,
        f.feedback_date,
        b.title
    FROM feedback f

    INNER JOIN book b
        ON f.book_id = b.book_id

    WHERE 1 = 1
";

$params = [];


// Search by Feedback ID, Member ID, Book Title or Comment

if ($search !== '') {

    $sql .= "
        AND (
            CAST(f.feedback_id AS CHAR) LIKE ?
            OR CAST(f.user_id AS CHAR) LIKE ?
            OR b.title LIKE ?
            OR f.comment LIKE ?
        )
    ";

    $keyword = "%$search%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}


// Filter by rating

if (
    in_array((string)$rating_filter, ['1', '2', '3', '4', '5'], true)
) {

    $sql .= " AND f.rating = ? ";

    $params[] = $rating_filter;

}


$sql .= "
    ORDER BY f.feedback_date DESC, f.feedback_id DESC
";

$stm = $_db->prepare($sql);
$stm->execute($params);

$feedbacks = $stm->fetchAll();


// ==========================================
// GET MESSAGE
// ==========================================

$message = temp('message');


// ==========================================
// ADMIN HEADER
// ==========================================

include '../../admin_header.php';

?>

<link rel="stylesheet" href="/css/app.css">



<main class="main-content">

    <div class="admin-feedback-page">


        <!-- ==================================
             PAGE HEADER
        =================================== -->

        <div class="admin-feedback-header">

            <div>
                <h1>Feedback Management</h1>

                <p>
                    Manage and review member ratings and comments.
                </p>
            </div>

        </div>


        <!-- ==================================
             SUCCESS / ERROR MESSAGE
        =================================== -->

        <?php if ($message): ?>

            <div class="admin-feedback-message">
                <?= encode($message) ?>
            </div>

        <?php endif; ?>


        <!-- ==================================
             FEEDBACK STATISTICS
        =================================== -->

        <div class="admin-feedback-stats">


            <!-- TOTAL RATINGS -->

            <div class="admin-feedback-stat-card total">

                <div class="admin-feedback-stat-icon">
                    ★
                </div>

                <div>
                    <p>Total Ratings</p>

                    <h2>
                        <?= (int)$stats->total ?>
                    </h2>
                </div>

            </div>


            <!-- AVERAGE RATING -->

            <div class="admin-feedback-stat-card average">

                <div class="admin-feedback-stat-icon">
                    ☆
                </div>

                <div>
                    <p>Average Rating</p>

                    <h2>
                        <?= number_format((float)$stats->average_rating, 1) ?>
                        <small>/ 5</small>
                    </h2>
                </div>

            </div>


            <!-- LOW RATINGS -->

            <div class="admin-feedback-stat-card low">

                <div class="admin-feedback-stat-icon">
                    !
                </div>

                <div>
                    <p>Low Ratings (1–2 Stars)</p>

                    <h2>
                        <?= (int)$stats->low_ratings ?>
                    </h2>
                </div>

            </div>


        </div>


        <!-- ==================================
             FEEDBACK RECORDS
        =================================== -->

        <div class="admin-feedback-card">


            <!-- TABLE HEADER -->

            <div class="admin-feedback-card-header">

                <div>
                    <h2>Feedback Records</h2>

                    <p>
                        View and manage all member ratings.
                    </p>
                </div>

                <span class="admin-feedback-record-count">
                    <?= count($feedbacks) ?>
                    <?= count($feedbacks) === 1 ? 'Record' : 'Records' ?>
                </span>

            </div>


            <!-- ==================================
                 SEARCH AND FILTER
            =================================== -->

            <form method="get" class="admin-feedback-filter">

                <input
                    type="text"
                    name="search"
                    placeholder="Search feedback ID, member ID, book title..."
                    value="<?= encode($search) ?>"
                >


                <select name="rating">

                    <option value="">All Ratings</option>

                    <?php for ($i = 5; $i >= 1; $i--): ?>

                        <option
                            value="<?= $i ?>"
                            <?= (string)$rating_filter === (string)$i ? 'selected' : '' ?>
                        >
                            <?= $i ?> Star<?= $i > 1 ? 's' : '' ?>
                        </option>

                    <?php endfor; ?>

                </select>


                <button type="submit" class="feedback-search-btn">
                    Search
                </button>


                <a
                    href="/admin/Feedback/rating.php"
                    class="feedback-reset-btn"
                >
                    Reset
                </a>

            </form>


            <!-- ==================================
                 FEEDBACK TABLE
            =================================== -->

            <div class="admin-feedback-table-wrapper">

                <table class="admin-feedback-table">

                    <thead>

                        <tr>
                            <th>No.</th>
                            <th>Feedback ID</th>
                            <th>Member ID</th>
                            <th>Book</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($feedbacks)): ?>

                            <?php
                            $no = 1;
                            foreach ($feedbacks as $f):
                                $rating = max(0, min(5, (int)$f->rating));
                            ?>

                                <tr>

                                    <!-- NUMBER -->

                                    <td>
                                        <?= $no++ ?>
                                    </td>


                                    <!-- FEEDBACK ID -->

                                    <td>
                                        #<?= (int)$f->feedback_id ?>
                                    </td>


                                    <!-- MEMBER ID -->

                                    <td>
                                        <span class="feedback-member-id">
                                            <?= (int)$f->user_id ?>
                                        </span>
                                    </td>


                                    <!-- BOOK TITLE -->

                                    <td class="feedback-book-title">
                                        <?= encode($f->title) ?>
                                    </td>


                                    <!-- RATING -->

                                    <td>

                                        <div class="feedback-rating">

                                            <span class="feedback-stars">
                                                <?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?>
                                            </span>

                                            <small>
                                                <?= $rating ?>/5
                                            </small>

                                        </div>

                                    </td>


                                    <!-- COMMENT -->

                                    <td class="feedback-comment">
                                        <?= encode($f->comment) ?>
                                    </td>


                                    <!-- DATE -->

                                    <td>
                                        <?= date('d M Y', strtotime($f->feedback_date)) ?>
                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <form
                                            method="post"
                                            onsubmit="return confirm('Are you sure you want to delete this feedback? This action cannot be undone.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="feedback_id"
                                                value="<?= (int)$f->feedback_id ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="feedback-delete-btn"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="feedback-empty">

                                    <div class="feedback-empty-icon">
                                        ★
                                    </div>

                                    <strong>No Feedback Found</strong>

                                    <p>
                                        There are no feedback records matching your search.
                                    </p>

                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>


<?php include '../../_footer.php'; ?>
