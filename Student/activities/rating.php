
<?php

require_once '../../_base.php';

auth(['member']);

$_title = 'My Ratings';

$user_id = $_user->user_id;

$message = '';
$error = '';

$edit_rating = null;

// ==========================================
// HANDLE POST REQUEST
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ======================================
    // ADD RATING
    // ======================================

    if ($action === 'add') {

        $book_id = (int)($_POST['book_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($book_id <= 0) {
            $error = 'Please select a book.';
        }
        elseif ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5.';
        }
        elseif ($comment === '') {
            $error = 'Please enter your comment.';
        }
        else {

            // Check whether book exists
            $stm = $_db->prepare("
                SELECT book_id
                FROM book
                WHERE book_id = ?
            ");

            $stm->execute([$book_id]);

            if (!$stm->fetch()) {

                $error = 'Book not found.';

            }
            else {

                // Check duplicate rating
                $stm = $_db->prepare("
                    SELECT feedback_id
                    FROM feedback
                    WHERE user_id = ?
                    AND book_id = ?
                ");

                $stm->execute([$user_id, $book_id]);

                if ($stm->fetch()) {

                    $error = 'You have already rated this book.';

                }
                else {

                    // Insert rating
                    $stm = $_db->prepare("
                        INSERT INTO feedback
                        (user_id, book_id, rating, comment, feedback_date)
                        VALUES (?, ?, ?, ?, NOW())
                    ");

                    $stm->execute([
                        $user_id,
                        $book_id,
                        $rating,
                        $comment
                    ]);

                    header('Location: rating.php?success=added');
                    exit;
                }
            }
        }
    }

    // ======================================
    // UPDATE RATING
    // ======================================

    elseif ($action === 'update') {

        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        $book_id = (int)($_POST['book_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($feedback_id <= 0) {
            $error = 'Invalid rating.';
        }
        elseif ($book_id <= 0) {
            $error = 'Please select a book.';
        }
        elseif ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5.';
        }
        elseif ($comment === '') {
            $error = 'Please enter your comment.';
        }
        else {

            // Check whether rating belongs to current member
            $stm = $_db->prepare("
                SELECT feedback_id
                FROM feedback
                WHERE feedback_id = ?
                AND user_id = ?
            ");

            $stm->execute([$feedback_id, $user_id]);

            if (!$stm->fetch()) {

                $error = 'Rating not found.';

            }
            else {

                // Check duplicate book rating
                $stm = $_db->prepare("
                    SELECT feedback_id
                    FROM feedback
                    WHERE user_id = ?
                    AND book_id = ?
                    AND feedback_id != ?
                ");

                $stm->execute([
                    $user_id,
                    $book_id,
                    $feedback_id
                ]);

                if ($stm->fetch()) {

                    $error = 'You have already rated this book.';

                }
                else {

                    // Update rating
                    $stm = $_db->prepare("
                        UPDATE feedback
                        SET book_id = ?,
                            rating = ?,
                            comment = ?
                        WHERE feedback_id = ?
                        AND user_id = ?
                    ");

                    $stm->execute([
                        $book_id,
                        $rating,
                        $comment,
                        $feedback_id,
                        $user_id
                    ]);

                    header('Location: rating.php?success=updated');
                    exit;
                }
            }
        }
    }

    // ======================================
    // DELETE RATING
    // ======================================

    elseif ($action === 'delete') {

        $feedback_id = (int)($_POST['feedback_id'] ?? 0);

        if ($feedback_id > 0) {

            $stm = $_db->prepare("
                DELETE FROM feedback
                WHERE feedback_id = ?
                AND user_id = ?
            ");

            $stm->execute([
                $feedback_id,
                $user_id
            ]);

            header('Location: rating.php?success=deleted');
            exit;
        }
    }
}

// ==========================================
// SUCCESS MESSAGE
// ==========================================

if (isset($_GET['success'])) {

    $success = $_GET['success'];

    if ($success === 'added') {
        $message = 'Rating submitted successfully!';
    }
    elseif ($success === 'updated') {
        $message = 'Rating updated successfully!';
    }
    elseif ($success === 'deleted') {
        $message = 'Rating deleted successfully!';
    }
}

// ==========================================
// GET EDIT RATING
// ==========================================

$edit_id = (int)($_GET['edit'] ?? 0);

if ($edit_id > 0) {

    $stm = $_db->prepare("
        SELECT
            feedback_id,
            book_id,
            rating,
            comment
        FROM feedback
        WHERE feedback_id = ?
        AND user_id = ?
    ");

    $stm->execute([$edit_id, $user_id]);

    $edit_rating = $stm->fetch();

    if (!$edit_rating) {
        $error = 'Rating not found.';
    }
}

// ==========================================
// GET ALL BOOKS
// ==========================================

$stm = $_db->query("
    SELECT book_id, title, author
    FROM book
    ORDER BY title
");

$books = $stm->fetchAll();

// ==========================================
// GET MEMBER RATING HISTORY
// ==========================================

$stm = $_db->prepare("
    SELECT
        f.feedback_id,
        f.book_id,
        f.rating,
        f.comment AS comments,
        f.feedback_date AS submission_date,
        b.title,
        b.author
    FROM feedback f
    INNER JOIN book b
        ON f.book_id = b.book_id
    WHERE f.user_id = ?
    ORDER BY f.feedback_date DESC
");

$stm->execute([$user_id]);

$ratings = $stm->fetchAll();

// ==========================================
// RATING STATISTICS
// ==========================================

$stm = $_db->prepare("
    SELECT
        COUNT(*) AS total_ratings,
        AVG(rating) AS average_rating
    FROM feedback
    WHERE user_id = ?
");

$stm->execute([$user_id]);

$stats = $stm->fetch();

$total_ratings = $stats->total_ratings ?? 0;

$average_rating = $stats->average_rating
    ? number_format($stats->average_rating, 1)
    : '0.0';

// ==========================================
// PAGE HEADER
// ==========================================

include '../../_head.php';

?>

<link rel="stylesheet" href="/css/app.css">

<main class="main-content">
    <div class="rating-page">

    <!-- PAGE HEADER -->

    <div class="rating-header">

        <h1>My Ratings</h1>

        <p>
            Share your thoughts and rate the books you have read.
        </p>

    </div>

    <!-- SUCCESS MESSAGE -->

    <?php if ($message): ?>

        <div class="message success-message">
            <?= encode($message) ?>
        </div>

    <?php endif; ?>

    <!-- ERROR MESSAGE -->

    <?php if ($error): ?>

        <div class="message error-message">
            <?= encode($error) ?>
        </div>

    <?php endif; ?>

    <!-- RATING STATISTICS -->

    <div class="rating-stats">

        <div class="stat-card">

            <h3>Total Ratings</h3>

            <p><?= encode($total_ratings) ?></p>

        </div>

        <div class="stat-card">

            <h3>Average Rating</h3>

            <p>
                <?= encode($average_rating) ?> / 5
                <span class="average-star">★</span>
            </p>

        </div>

    </div>

    <!-- RATING FORM -->

    <section class="rating-form">

        <h2>
            <?= $edit_rating ? 'Edit Your Rating' : 'Rate a Book' ?>
        </h2>

        <form method="post" action="rating.php">

            <?php if ($edit_rating): ?>

                <input
                    type="hidden"
                    name="action"
                    value="update"
                >

                <input
                    type="hidden"
                    name="feedback_id"
                    value="<?= encode($edit_rating->feedback_id) ?>"
                >

            <?php else: ?>

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >

            <?php endif; ?>

            <!-- SELECT BOOK -->

            <div class="form-group">

                <label for="book_id">
                    Select Book
                </label>

                <select
                    name="book_id"
                    id="book_id"
                    required
                >

                    <option value="">
                        -- Select a Book --
                    </option>

                    <?php foreach ($books as $book): ?>

                        <option
                            value="<?= encode($book->book_id) ?>"
                            <?= (
                                $edit_rating &&
                                $edit_rating->book_id == $book->book_id
                            ) ? 'selected' : '' ?>
                        >

                            <?= encode($book->title) ?>
                            - <?= encode($book->author) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <!-- STAR RATING -->

            <div class="form-group">

                <label>
                    Your Rating
                </label>

                <div class="star-rating">

                    <?php for ($i = 5; $i >= 1; $i--): ?>

                        <input
                            type="radio"
                            id="star<?= $i ?>"
                            name="rating"
                            value="<?= $i ?>"
                            <?= (
                                $edit_rating &&
                                $edit_rating->rating == $i
                            ) ? 'checked' : '' ?>
                            required
                        >

                        <label for="star<?= $i ?>">
                            ★
                        </label>

                    <?php endfor; ?>

                </div>

            </div>

            <!-- COMMENT -->

            <div class="form-group">

                <label for="comment">
                    Your Comment
                </label>

                <textarea
                    name="comment"
                    id="comment"
                    placeholder="Write your thoughts about this book..."
                    required
                ><?= $edit_rating ? encode($edit_rating->comment) : '' ?></textarea>

            </div>

            <!-- BUTTONS -->

            <button
                type="submit"
                class="btn btn-submit"
            >

                <?= $edit_rating ? 'Update Rating' : 'Submit Rating' ?>

            </button>

            <?php if ($edit_rating): ?>

                <a
                    href="rating.php"
                    class="btn btn-cancel"
                >
                    Cancel
                </a>

            <?php endif; ?>

        </form>

    </section>

    <!-- RATING HISTORY -->

    <section class="rating-history">

        <h2>My Rating History</h2>

        <?php if (empty($ratings)): ?>

            <div class="empty-message">

                <p>You have not submitted any ratings yet.</p>

            </div>

        <?php else: ?>

            <?php foreach ($ratings as $r): ?>

                <div class="rating-item">

                    <h3>
                        <?= encode($r->title) ?>
                    </h3>

                    <div class="rating-author">

                        By <?= encode($r->author) ?>

                        <span> | </span>

                        <?= encode(
                            date(
                                'd M Y',
                                strtotime($r->submission_date)
                            )
                        ) ?>

                    </div>

                    <!-- DISPLAY STARS -->

                    <div class="display-stars">

                        <?php for ($i = 1; $i <= 5; $i++): ?>

                            <?= $i <= $r->rating ? '★' : '☆' ?>

                        <?php endfor; ?>

                    </div>

                    <!-- DISPLAY COMMENT -->

                    <div class="rating-comment">
                        <?= encode($r->comments) ?>
                    </div>

                    <!-- ACTION BUTTONS -->

                    <div class="rating-actions">

                        <a
                            href="rating.php?edit=<?= encode($r->feedback_id) ?>"
                            class="btn btn-edit"
                        >
                            Edit
                        </a>

                        <form
                            method="post"
                            action="rating.php"
                            onsubmit="return confirm('Are you sure you want to delete this rating?');"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="delete"
                            >

                            <input
                                type="hidden"
                                name="feedback_id"
                                value="<?= encode($r->feedback_id) ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-delete"
                            >
                                Delete
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

        </div>

</main>

<?php

include '../../_footer.php';

?>
