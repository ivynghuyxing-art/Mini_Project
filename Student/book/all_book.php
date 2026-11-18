
<?php

include '../../_base.php';

auth(['member']);

$_title = 'All Books';
include '../../_head.php';

$message = temp('message');

// Get all books
$stm = $_db->prepare("
    SELECT book.*, category.name
    FROM book
    JOIN category ON book.category_id = category.category_id
");

$stm->execute();
$books = $stm->fetchAll();

?>

<main class="main-content" id="main-content">

    <div class="browse-banner">

        <div class="banner-text">
            <h1>All Books</h1>
            <p>Explore our complete book collection</p>
        </div>

        <div class="book-count">
            Showing <strong><?= count($books) ?></strong> books
        </div>

        
        <div class="show-all-section" style="justify-content:flex-start">
            <a href="browse.php" class="show-all-btn">
                ← Back to Browse
            </a>
        </div>

        <?php if ($message): ?>
            <p class="success-message">
                <?= encode($message) ?>
            </p>
        <?php endif; ?>

        <div class="book-grid">

            <?php if ($books): ?>

                <?php foreach ($books as $book): ?>

                    <div class="book-card">

                        <div class="book-cover">

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

                        </div>

                        <div class="book-info">

                            <h3>
                                <?= encode($book->title) ?>
                            </h3>

                            <p>
                                Author: <?= encode($book->author) ?>
                            </p>

                            <span class="category-tag">
                                <?= encode($book->name) ?>
                            </span>

                            <?php if ($book->available_quantity > 0): ?>

                                <p class="available-status">
                                    Available
                                </p>

                                <a
                                    href="../activities/borrowing.php?book_id=<?= $book->book_id ?>"
                                    class="borrowing-btn"
                                >
                                    Borrow
                                </a>

                            <?php else: ?>

                                <p class="unavailable-status">
                                    Unavailable
                                </p>

                                <a
                                    href="/Student/activities/reservation.php?id=<?= $book->book_id ?>"
                                    class="reservation-btn"
                                >
                                    Reservation
                                </a>

                            <?php endif; ?>

                            <form method="post" action="/student/engagement/wishlist.php">

                                <input
                                    type="hidden"
                                    name="add_id"
                                    value="<?= $book->book_id ?>"
                                >

                                <button type="submit" class="wishlist-btn">
                                    ♡ Add to Wishlist
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="no-books-message">
                    No books available.
                </p>

            <?php endif; ?>

        </div>

    </div>

</main>
