<?php 
include '../../_base.php';


$search_term = get('search');
$category_id = get('category_id');

$all_book = [];

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

    $all_book = $stm->fetchAll();

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

    $all_book = $stm->fetchAll();

} elseif ($category_id) {

    // Filter by category
    $stm = $_db->prepare("
        SELECT book.*,category.name
        FROM book
        JOIN category ON book.category_id = category.category_id
        WHERE book.category_id = ?
    ");

    $stm->execute([$category_id]);
    $all_book = $stm->fetchAll();

} else {

    // Display all books
    $stm = $_db->prepare("
        SELECT book.*,category.name
        FROM book
        JOIN category ON book.category_id = category.category_id
    ");

    $stm->execute();
    $all_book = $stm->fetchAll();
}

$stm = $_db->prepare("
SELECT * FROM category
");

$stm->execute();
$categories = $stm->fetchAll();

$_title = 'Book ';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Library Hub Management' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>

<form method="get" action="read_book.php">
    <a href="/admin/home.php">Back to home</a>
    <div class="search-section">
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
    </div>

        <div class="book-count">
            Showing <strong><?= count($all_book) ?></strong> 
        </div>

        <a href="add-book.php">+ Add new book</a>
    
   <div class="book-grid">
                <?php foreach ($all_book as $book):?>
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

                            <section>
                                <a href="update.php?book_id=<?= $book->book_id ?>">>Update</a>
                                <a href="delete.php?book_id=<?= $book->book_id ?>">Delete</a>
                            </section>
                        </div>
                            
                    </div>
                <?php endforeach;?>
        </div>
    </div>

</form>


<?php
 include '../../_footer.php';
?>