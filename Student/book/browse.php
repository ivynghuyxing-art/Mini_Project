
<?php

include '../../_base.php';

auth(['member']);

$_title = 'Browse Books';
include '../../_head.php';

$search_term = get('search');
$category_id = get('category_id');

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

$stm = $_db->prepare("
SELECT * FROM category
");

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
            <form action="browse.php" method="get" class="search-form">
                <input type="text" name="search" value="<?= encode($search_term ?? '') ?>" placeholder="Search by title, author, or genre" class="search-input">
                
                <select name="category_id" class="category-select">
                    <option value=""> All Categories</option>
                    <?php foreach ($categories as $category):?>
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

        <div class="book-grid">
            <?php if($books):?>
                <?php foreach ($books as $book):?>
                    <div class="book-card">
                        <div class="book-cover">
                            <?php if (!empty($book->image)):?>
                                <img src ="<?= encode($book->image) ?>" alt="<?= encode($book->title) ?>">
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

                            <?php if ($book->available_quantity > 0):?>
                                <p class="availability available">
                                    Available
                                </p>
                            <?php else:?>
                                <p class="availability unavailable">
                                    Unavailable
                                </p>
                            <?php endif;?>
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