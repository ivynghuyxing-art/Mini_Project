
<?php

include '../../_base.php';

auth(['member']);

$_title = 'Book Details';

$book_id = (int)req('book_id');

// Current logged-in member
$current_user_id = $_SESSION['user']->user_id;


// ==========================================
// 1. GET BOOK DETAILS
// ==========================================

$stm = $_db->prepare("
    SELECT
        b.book_id,
        b.title,
        b.image,
        b.author,
        b.description,
        c.name AS category_name
    FROM book b
    LEFT JOIN category c
        ON b.category_id = c.category_id
    WHERE b.book_id = ?
");

$stm->execute([$book_id]);

$book = $stm->fetch();

if (!$book) {
    temp('info', 'Book not found.');
    redirect('/Student/book/browse.php');
}


// ==========================================
// 2. GET BOOK RATING STATISTICS
// ==========================================

$stm = $_db->prepare("
    SELECT
        COUNT(*) AS total_ratings,
        AVG(rating) AS average_rating
    FROM feedback
    WHERE book_id = ?
");

$stm->execute([$book_id]);

$rating_stats = $stm->fetch();

$total_ratings = (int)$rating_stats->total_ratings;

$average_rating = $total_ratings > 0
    ? number_format((float)$rating_stats->average_rating, 1)
    : '0.0';

$rounded_rating = (int)round((float)$average_rating);


// ==========================================
// 3. GET MEMBER REVIEWS
// ==========================================

$stm = $_db->prepare("
    SELECT
        f.feedback_id,
        f.user_id,
        f.rating,
        f.comment,
        f.feedback_date,
        u.username,
        u.profile_photo
    FROM feedback f

    INNER JOIN `user` u
        ON f.user_id = u.user_id

    WHERE f.book_id = ?

    ORDER BY
        f.feedback_date DESC,
        f.feedback_id DESC
");

$stm->execute([$book_id]);

$reviews = $stm->fetchAll();


// ==========================================
// 4. INCLUDE HEADER
// ==========================================

include '../../_head.php';

?>

<link rel="stylesheet" href="/css/app.css">

<div class="book-details-page">

    <!-- ==================================
         BOOK DETAILS
    =================================== -->

    <div class="book-details-card">

        <!-- Book Cover -->

        <div class="book-details-cover">

            <img
                src="/images/Book_pic/<?= encode($book->image) ?>"
                alt="<?= encode($book->title) ?>"
            >

        </div>


        <!-- Book Information -->

        <div class="book-details-info">

            <h1>
                <?= encode($book->title) ?>
            </h1>

            <p class="book-author">
                By <?= encode($book->author) ?>
            </p>


            <!-- Category -->

            <div class="book-category">

                <?= encode($book->category_name ?? 'Uncategorized') ?>

            </div>


            <!-- Description -->

            <div class="book-description">

                <h3>About This Book</h3>

                <p>
                    <?= nl2br(encode($book->description)) ?>
                </p>

            </div>


            <!-- Action Buttons -->

            <div class="book-details-actions">

                <a
                    href="/Student/book/browse.php"
                    class="back-btn"
                >
                    Back to Browse Books
                </a>

                <a
                    href="/Student/activities/my_borrowing.php?book_id=<?= (int)$book->book_id ?>"
                    class="borrow-btn"
                >
                    Borrow This Book
                </a>

            </div>

        </div>

    </div>


    <!-- ==================================
         BOOK RATINGS AND REVIEWS
    =================================== -->

    <section class="book-reviews">

        <div class="reviews-header">

            <h2>Book Ratings &amp; Reviews</h2>

            <p>
                See what other members think about this book.
            </p>

        </div>


        <!-- ==================================
             RATING SUMMARY
        =================================== -->

        <div class="rating-summary">

            <div class="average-rating">

                <h1>
                    <?= encode($average_rating) ?> / 5
                </h1>

                <div class="review-stars">

                    <?php for ($i = 1; $i <= 5; $i++): ?>

                        <?= $i <= $rounded_rating ? '★' : '☆' ?>

                    <?php endfor; ?>

                </div>

                <p>
                    <?= $total_ratings ?> Member Rating(s)
                </p>

            </div>

        </div>


        <!-- ==================================
             MEMBER COMMENTS
        =================================== -->

        <div class="reviews-list">

            <h3>Member Comments</h3>


            <?php if ($reviews): ?>

                <?php foreach ($reviews as $review): ?>

                    <div class="review-item">

                        <!-- Member Information and Rating -->

                        <div class="review-top">


                            <!-- Member Profile -->

                            <div class="review-member">

                                <div class="member-avatar">

                                    <?php if (!empty($review->profile_photo)): ?>

                                        <img
                                            src="/images/profile_pic/<?= encode($review->profile_photo) ?>"
                                            alt="<?= encode($review->username) ?>"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        >

                                        <span class="avatar-fallback" style="display:none;">
                                            <?= strtoupper(substr($review->username, 0, 1)) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="avatar-fallback">
                                            <?= strtoupper(substr($review->username, 0, 1)) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- Username and Date -->

                                <div class="review-user-info">

                                    <strong>

                                        <?= encode($review->username) ?>

                                        <?php if (
                                            (int)$review->user_id === (int)$current_user_id
                                        ): ?>

                                            <span class="you-label">
                                                (You)
                                            </span>

                                        <?php endif; ?>

                                    </strong>

                                    <small>
                                        <?= date(
                                            'd M Y',
                                            strtotime($review->feedback_date)
                                        ) ?>
                                    </small>

                                </div>

                            </div>


                            <!-- Individual Rating -->

                            <div class="review-stars">

                                <?php for ($i = 1; $i <= 5; $i++): ?>

                                    <?= $i <= (int)$review->rating ? '★' : '☆' ?>

                                <?php endfor; ?>

                            </div>

                        </div>


                        <!-- Member Comment -->

                        <div class="review-comment">

                            <?= nl2br(encode($review->comment)) ?>

                        </div>

                    </div>

                <?php endforeach; ?>


            <?php else: ?>


                <!-- No Reviews -->

                <div class="no-reviews">

                    <div class="no-reviews-icon">
                        ★
                    </div>

                    <h3>No Reviews Yet</h3>

                    <p>
                        Be the first member to rate this book!
                    </p>

                </div>


            <?php endif; ?>

        </div>

    </section>

</div>


<?php

include '../../_footer.php';

?>
