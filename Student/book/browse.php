
<?php

include '../../_base.php';

auth(['member']);

$_title = 'Browse Books';
include '../../_head.php';

$message = temp('message');
$search_term = post('search');
$category_id = post('category_id');

$books = [];

if ($search_term && $category_id) {

    // Search by title, author and category
    $stm = $_db->prepare("
        SELECT book.*,category.name
        FROM book
        JOIN category ON book.category_id = category.category_id
        WHERE (title LIKE ? OR author LIKE ?)
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
        SELECT book.*,category.name
        FROM book
        JOIN category ON book.category_id = category.category_id
        WHERE title LIKE ? OR author LIKE ?
    ");

    $stm->execute([
        "%$search_term%",
        "%$search_term%"
    ]);

    $books = $stm->fetchAll();

} elseif ($category_id) {

    // Filter by category
    $stm = $_db->prepare("
        SELECT book.*,category.name
        FROM book
        JOIN category ON book.category_id = category.category_id
        WHERE book.category_id = ?
    ");

    $stm->execute([$category_id]);
    $books = $stm->fetchAll();

} else {

    // Display all books
    $stm = $_db->prepare("
        SELECT book.*,category.name
        FROM book
        JOIN category ON book.category_id = category.category_id
    ");

    $stm->execute();
    $books = $stm->fetchAll();
}
$books = array_slice($books, 0, 10);

$stm = $_db->prepare(" SELECT * FROM category ");

$stm->execute();
$categories = $stm->fetchAll();

?>

<main class = "main-content" id="main-content">
    <div class="browse-banner">
        <div class="banner-text">
            <h1> Browse Books</h1>
            <p> Discover book available</p>
        </div>

        <div class="search-section">
            <form action="browse.php" method="post" class="search-form">

                <div class="search-input-wrapper">
                    <input
                        type="text"
                        id="searchInput"
                        name="search"
                        value="<?= encode($search_term ?? '') ?>"
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

                <select name="category_id" class="category-select">
                    <option value="">All Categories</option>

                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= $category->category_id ?>"
                            <?= $category_id == $category->category_id ? 'selected' : '' ?>
                        >
                            <?= encode($category->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="search-button">Search</button>

            </form>

        </div>
        
        <div class="book-count">
            Showing <strong><?= count($books) ?></strong> 
        </div>

        <div class="show-all-section" style="justify-content: flex-end;">
            <a href="all_book.php" class="show-all-btn">
                Show All Books →
            </a>
        </div>

        <?php if($message): ?>
            <p class="success-message">
                <?= encode($message) ?>
            </p>
        <?php endif; ?>

        <div class="book-grid">
            <?php if($books):?>
                <?php foreach ($books as $book):?>
                    <div class="book-card">
                        <div class="book-cover">
                            <?php if (!empty($book->image)):?>
                                <img src="/images/Book_pic/<?=encode($book->image)?>" alt="<?=encode($book->title)?>">
                            <?php else:?>
                                <div class="no-cover"> 
                                    No cover
                                </div>
                            <?php endif;?>

                        </div>

                        <div class="book-info">
                            <h3>
                                <?= encode($book->title) ?>
                            </h3>
                            <p>Author: <?= encode($book->author) ?></p>
                            <span class="category-tag">
                                <?= encode($book->name) ?>
                            </span>

                       
<?php if ($book->available_quantity > 0): ?>

    <!-- BOOK AVAILABLE -->

    <p class="available-status">
        <span></span> Available
    </p>

    <a href="../activities/my_borrowing.php?book_id=<?= $book->book_id ?>"
       class="borrowing-btn">
        Borrow
    </a>

<?php else: ?>

    <!-- BOOK UNAVAILABLE -->

    <p class="unavailable-status">
        <span>●</span> Unavailable
    </p>

    <a href="../activities/reservation.php?id=<?= $book->book_id ?>"
       class="reservation-btn">
        Reserve Book
    </a>

<?php endif; ?>


                            <form method="post" action="/student/engagement/wishlist.php">
                                <input type="hidden" name="add_id" value="<?= $book->book_id ?>">
                                <button type="submit" class="wishlist-btn">♡ Add to Wishlist</button>
                            </form>
                        </div>
                            
                    </div>
                <?php endforeach;?>
            <?php else:?>
                <p class="no-books-message">
                    Sorry. We couldn't find any books matching your criteria.
                </p>
            
            <?php endif;?>
        </div>


    </div>
</main> 