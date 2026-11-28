
<?php

include '../../_base.php';

auth(['member']);

$_title = 'Browse Books';
include '../../_head.php';

// ==========================================
// 1. GET SEARCH AND CATEGORY FILTER
// ==========================================

$message = temp('message');

$search_term = post('search', '');
$category_id = post('category_id', '');

$books = [];

// ==========================================
// 2. GET BOOKS
// ==========================================

if ($search_term && $category_id) {

    // Search by title, author and category
    $stm = $_db->prepare("
        SELECT book.*, category.name
        FROM book
        JOIN category
            ON book.category_id = category.category_id
        WHERE (book.title LIKE ? OR book.author LIKE ?)
        AND book.category_id = ?
    ");

    $stm->execute([
        "%$search_term%",
        "%$search_term%",
        $category_id
    ]);

    $books = $stm->fetchAll();

} elseif ($search_term) {

    // Search by title or author
    $stm = $_db->prepare("
        SELECT book.*, category.name
        FROM book
        JOIN category
            ON book.category_id = category.category_id
        WHERE book.title LIKE ?
        OR book.author LIKE ?
    ");

    $stm->execute([
        "%$search_term%",
        "%$search_term%"
    ]);

    $books = $stm->fetchAll();

} elseif ($category_id) {

    // Filter by category
    $stm = $_db->prepare("
        SELECT book.*, category.name
        FROM book
        JOIN category
            ON book.category_id = category.category_id
        WHERE book.category_id = ?
    ");

    $stm->execute([$category_id]);

    $books = $stm->fetchAll();

} else {

    // Display all books
    $stm = $_db->prepare("
        SELECT book.*, category.name
        FROM book
        JOIN category
            ON book.category_id = category.category_id
    ");

    $stm->execute();

    $books = $stm->fetchAll();
}

// Display maximum 10 books
$books = array_slice($books, 0, 10);


// ==========================================
// 3. GET CATEGORIES
// ==========================================

$stm = $_db->prepare("
    SELECT * FROM category
");

$stm->execute();

$categories = $stm->fetchAll();
// ==========================================
// GET TOP 5 POPULAR BOOKS
// ==========================================

$stm = $_db->prepare("
    SELECT
        b.book_id,
        b.title,
        b.author,
        b.image,
        AVG(f.rating) AS average_rating,
        COUNT(f.feedback_id) AS total_reviews
    FROM book b
    JOIN feedback f ON b.book_id = f.book_id
    GROUP BY b.book_id, b.title, b.author, b.image
    ORDER BY average_rating DESC, total_reviews DESC
    LIMIT 5
");

$stm->execute();
$popular_books = $stm->fetchAll();

?>

<main class="main-content" id="main-content">

    <div class="browse-banner">

        <!-- PAGE HEADER -->
        <div class="banner-text">
            <h1>Browse Books</h1>
            <p>Discover books available</p>
        </div>


        <!-- SEARCH AND CATEGORY FILTER -->
        <div class="search-section">

            <form action="browse.php" method="post" class="search-form">

                <div class="search-input-wrapper">

                    <input
                        type="text"
                        id="searchInput"
                        name="search"
                        value="<?= encode($search_term) ?>"
                        placeholder="Search by title, author, or genre"
                        class="search-input"
                    >

                    <button
                        type="button"
                        id="clearSearch"
                        class="clear-search"
                        onclick="clearSearchInput()"
                        aria-label="Clear search"
                    >
                        x
                    </button>

                </div>


                <!-- CATEGORY FILTER -->
                <select name="category_id" class="category-select">

                    <option value="">All Categories</option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int)$category->category_id ?>"
                            <?= $category_id == $category->category_id ? 'selected' : '' ?>
                        >
                            <?= encode($category->name) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <button type="submit" class="search-button">
                    Search
                </button>

            </form>

        </div>

<!-- ======================================
     POPULAR BOOKS
====================================== -->

<section class="popular-books-section">

    <div class="popular-books-header">
        <div>
            <h2>Popular Books</h2>
            <p>Discover books loved by our members.</p>
        </div>

        <span class="popular-badge">Top 5</span>
    </div>

    <div class="popular-books-grid">

        <?php if ($popular_books): ?>

            <?php foreach ($popular_books as $index => $popular): ?>

                <div class="popular-book-card">

                    <div class="popular-rank">
                        <?= $index + 1 ?>
                    </div>

                    <a href="/Student/book/book_details.php?book_id=<?= (int)$popular->book_id ?>">
                        <img
                            src="/images/Book_pic/<?= encode($popular->image) ?>"
                            alt="<?= encode($popular->title) ?>"
                        >
                    </a>

                    <div class="popular-book-info">

                        <h3>
                            <?= encode($popular->title) ?>
                        </h3>

                        <p>
                            By <?= encode($popular->author) ?>
                        </p>

                        <div class="popular-rating">
                            <span>★</span>
                            <?= number_format($popular->average_rating, 1) ?> / 5
                        </div>

                        <small>
                            <?= (int)$popular->total_reviews ?> Reviews
                        </small>

                        <a
                            href="/Student/book/book_details.php?book_id=<?= (int)$popular->book_id ?>"
                            class="popular-details-btn"
                        >
                            View Book
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>No book reviews available yet.</p>

        <?php endif; ?>

    </div>

</section>

        <!-- BOOK COUNT -->
        <div class="book-count">

            Showing <strong><?= count($books) ?></strong>

        </div>


        <!-- SHOW ALL BOOKS -->
        <div class="show-all-section" style="justify-content: flex-end;">

            <a href="all_book.php" class="show-all-btn">
                Show All Books →
            </a>

        </div>


        <!-- SUCCESS MESSAGE -->
        <?php if ($message): ?>

            <p class="success-message">
                <?= encode($message) ?>
            </p>

        <?php endif; ?>


        <!-- ======================================
             BOOK GRID
        ====================================== -->

        <div class="book-grid">

            <?php if ($books): ?>

                <?php foreach ($books as $book): ?>

                    <div class="book-card">


                        <!-- BOOK COVER -->
                        <div class="book-cover">

                            <a href="book_details.php?book_id=<?= (int)$book->book_id ?>">

                                <?php if (!empty($book->image)): ?>

                                    <img
                                        src="/images/Book_pic/<?= encode($book->image) ?>"
                                        alt="<?= encode($book->title) ?>"
                                    >

                                <?php else: ?>

                                    <div class="no-cover">
                                        No cover
                                    </div>

                                <?php endif; ?>

                            </a>

                        </div>


                        <!-- BOOK INFORMATION -->
                        <div class="book-info">


                            <!-- BOOK TITLE -->
                            <h3>

                                <a href="book_details.php?book_id=<?= (int)$book->book_id ?>">
                                    <?= encode($book->title) ?>
                                </a>

                            </h3>


                            <!-- AUTHOR -->
                            <p>
                                Author: <?= encode($book->author) ?>
                            </p>


                            <!-- CATEGORY -->
                            <span class="category-tag">
                                <?= encode($book->name) ?>
                            </span>


                            <!-- ======================================
                                 BOOK AVAILABILITY
                            ====================================== -->

                            <?php if ($book->available_quantity > 0): ?>

                                <!-- BOOK AVAILABLE -->

    <p class="available-status">
        <span></span> Available
    </p>


                                <!-- BORROW BUTTON -->
                                <a
                                    href="../activities/my_borrowing.php?book_id=<?= (int)$book->book_id ?>"
                                    class="borrowing-btn"
                                >
                                    Borrow
                                </a>


                            <?php else: ?>

                                <!-- BOOK UNAVAILABLE -->

                                <p class="unavailable-status">
                                    <span>●</span> Unavailable
                                </p>


                                <!-- RESERVE BUTTON -->
                                <a
                                    href="../activities/reservation.php?id=<?= (int)$book->book_id ?>"
                                    class="reservation-btn"
                                >
                                    Reserve Book
                                </a>


                            <?php endif; ?>


                            <!-- ======================================
                                 BOOK DETAILS BUTTON
                                 Always displayed regardless of stock
                            ====================================== -->

                            <a
                                href="/Student/book/book_details.php?book_id=<?= (int)$book->book_id ?>"
                                class="details-btn"
                            >
                                Book Details
                            </a>


                            <!-- ======================================
                                 ADD TO WISHLIST
                            ====================================== -->

                            <form
                                method="post"
                                action="/student/engagement/wishlist.php"
                            >

                                <input
                                    type="hidden"
                                    name="add_id"
                                    value="<?= (int)$book->book_id ?>"
                                >

                                <button
                                    type="submit"
                                    class="wishlist-btn"
                                >
                                    ♡ Add to Wishlist
                                </button>

                            </form>


                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="no-books-message">
                    Sorry. We couldn't find any books matching your criteria.
                </p>

            <?php endif; ?>

        </div>

    </div>

</main>

<?php include '../../_footer.php'; ?>
