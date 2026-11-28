<?php

require_once '../../_base.php';

auth(['member']);

$_title = 'My Ratings';
$user_id = $_user->user_id;

$message = '';
$error = '';

// Folder that stores the book cover images (change to match your project)
define('COVER_URL', '/images/Book_pic/');

// How many ratings to show before "View all"
$page_size = 3;

// Get the cover image path of a book ('' if the book has no cover)
function cover_url($book) {
    $file = $book->cover_image ?? $book->cover ?? $book->image ?? $book->photo ?? '';
    return $file ? COVER_URL . $file : '';
}

// Show the book cover. If there is no image, show the title on a colour block
function book_cover($book, $size = '') {
    $url = cover_url($book);

    if ($url) {
        return '<div class="book-cover ' . $size . '" style="background-image: url(\'' . encode($url) . '\');"></div>';
    }

    return '<div class="book-cover ' . $size . '">' . encode($book->title) . '</div>';
}

// ==========================================
// HANDLE POST REQUEST
// ==========================================

if (is_post()) {

    $action      = $_POST['action'] ?? '';
    $feedback_id = (int)($_POST['feedback_id'] ?? 0);
    $book_id     = (int)($_POST['book_id'] ?? 0);
    $rating      = (int)($_POST['rating'] ?? 0);
    $comment     = trim($_POST['comment'] ?? '');

    // DELETE RATING
    if ($action === 'delete') {

        if ($feedback_id > 0) {

            $stm = $_db->prepare("
                DELETE FROM feedback
                WHERE feedback_id = ?
                AND user_id = ?
            ");

            $stm->execute([$feedback_id, $user_id]);

            header('Location: rating.php?success=deleted');
            exit;
        }
    }

    // ADD OR UPDATE RATING
    elseif ($action === 'add' || $action === 'update') {

        if ($action === 'update' && $feedback_id <= 0) {
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

            // Check whether book exists
            $stm = $_db->prepare("SELECT book_id FROM book WHERE book_id = ?");
            $stm->execute([$book_id]);
            $book_ok = $stm->fetch();

            // When updating, check the rating belongs to current member
            $own_ok = true;

            if ($action === 'update') {

                $stm = $_db->prepare("
                    SELECT feedback_id
                    FROM feedback
                    WHERE feedback_id = ?
                    AND user_id = ?
                ");

                $stm->execute([$feedback_id, $user_id]);
                $own_ok = (bool)$stm->fetch();
            }

            if (!$book_ok) {
                $error = 'Book not found.';
            }
            elseif (!$own_ok) {
                $error = 'Rating not found.';
            }
            else {

                // Check duplicate rating (feedback_id is 0 when adding)
                $stm = $_db->prepare("
                    SELECT feedback_id
                    FROM feedback
                    WHERE user_id = ?
                    AND book_id = ?
                    AND feedback_id != ?
                ");

                $stm->execute([$user_id, $book_id, $feedback_id]);

                if ($stm->fetch()) {
                    $error = 'You have already rated this book.';
                }
                elseif ($action === 'add') {

                    $stm = $_db->prepare("
                        INSERT INTO feedback
                        (user_id, book_id, rating, comment, feedback_date)
                        VALUES (?, ?, ?, ?, NOW())
                    ");

                    $stm->execute([$user_id, $book_id, $rating, $comment]);

                    header('Location: rating.php?success=added');
                    exit;
                }
                else {

                    $stm = $_db->prepare("
                        UPDATE feedback
                        SET book_id = ?,
                            rating = ?,
                            comment = ?
                        WHERE feedback_id = ?
                        AND user_id = ?
                    ");

                    $stm->execute([$book_id, $rating, $comment, $feedback_id, $user_id]);

                    header('Location: rating.php?success=updated');
                    exit;
                }
            }
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
// GET ALL BOOKS WITH CATEGORY
// ==========================================

$stm = $_db->query("
    SELECT
        b.*,
        c.name AS category_name
    FROM book b
    LEFT JOIN category c
        ON b.category_id = c.category_id
    ORDER BY b.title ASC
");

$books = $stm->fetchAll();

// ==========================================
// GET ALL CATEGORIES
// ==========================================

$stm = $_db->query("
    SELECT category_id, name
    FROM category
    ORDER BY name ASC
");

$categories = $stm->fetchAll();

// ==========================================
// GET MEMBER RATING HISTORY
// ==========================================

$stm = $_db->prepare("
    SELECT
        f.feedback_id,
        f.rating,
        f.comment AS comments,
        f.feedback_date AS submission_date,
        b.*
    FROM feedback f
    INNER JOIN book b
        ON f.book_id = b.book_id
    WHERE f.user_id = ?
    ORDER BY f.feedback_date DESC, f.feedback_id DESC
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
<link rel="stylesheet" href="/css/rating.css">

<main class="main-content">
    <div class="rating-page">

    <!-- PAGE HEADER -->

    <div class="rating-header">

        <div>
            <h1>My Ratings</h1>

            <p>
                Share your thoughts and rate the books you have read.
            </p>
        </div>

        <button type="button" class="btn btn-submit" id="open_add">
            ★ Rate a Book
        </button>

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

    <!-- RATING HISTORY -->

    <section class="rating-history">

        <h2>My Rating History</h2>

        <?php if (empty($ratings)): ?>

            <div class="empty-message">

                <p>You have not submitted any ratings yet.</p>

            </div>

        <?php else: ?>

            <div class="rating-list" id="rating_list">

            <?php foreach ($ratings as $n => $r): ?>

                <div class="rating-item <?= $n >= $page_size ? 'extra-item' : '' ?>">

                    <?= book_cover($r) ?>

                    <div class="rating-info">

                        <h3>
                            <a href="/Student/book/book_details.php?book_id=<?= (int)$r->book_id ?>"
                               class="rating-book-link">
                                <?= encode($r->title) ?>
                            </a>
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

                            <span class="rating-score"><?= (int)$r->rating ?>/5</span>

                        </div>

                        <!-- DISPLAY COMMENT -->

                        <div class="rating-comment">
                            <?= encode($r->comments) ?>
                        </div>

                        <!-- ACTION BUTTONS -->

                        <div class="rating-actions">

                            <button
                                type="button"
                                class="btn btn-edit"
                                data-id="<?= (int)$r->feedback_id ?>"
                                data-book-id="<?= (int)$r->book_id ?>"
                                data-title="<?= encode($r->title) ?>"
                                data-author="<?= encode($r->author) ?>"
                                data-cover="<?= encode(cover_url($r)) ?>"
                                data-rating="<?= (int)$r->rating ?>"
                                data-comment="<?= encode($r->comments) ?>"
                            >
                                Edit
                            </button>

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

                </div>

            <?php endforeach; ?>

            </div>

            <div class="rating-footer">

                <span id="showing_text"></span>

                <?php if (count($ratings) > $page_size): ?>

                    <button type="button" class="view-all-btn" id="view_all">
                        View all
                    </button>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

    </div>
</main>

<!-- ==========================================
     RATE / EDIT POP-UP
========================================== -->

<div class="modal-overlay" id="rating_modal">

    <div class="modal-box">

        <form method="post" action="rating.php" id="rating_form" novalidate>

            <input type="hidden" name="action" id="form_action" value="add">
            <input type="hidden" name="feedback_id" id="form_feedback_id" value="">
            <input type="hidden" name="book_id" id="book_id" value="">

            <div class="modal-header">

                <h2 id="modal_title">Rate a Book</h2>

                <button type="button" class="modal-close" id="close_modal">
                    &times;
                </button>

            </div>

            <div class="modal-body">

                <!-- SEARCH AND FILTER BOOK (only when adding) -->

                <div class="form-group" id="book_picker">

                    <div class="search-row">

                        <div>
                            <label for="book_search">Search Book</label>

                            <input
                                type="text"
                                id="book_search"
                                class="book-search-input"
                                placeholder="Search book title or author..."
                                autocomplete="off"
                            >
                        </div>

                        <div>
                            <label for="category_filter">Filter by Category</label>

                            <select id="category_filter" class="category-filter">

                                <option value="">All Categories</option>

                                <?php foreach ($categories as $category): ?>

                                    <option value="<?= (int)$category->category_id ?>">
                                        <?= encode($category->name) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                    </div>

                    <div class="book-search-results" id="book_results">

                        <?php foreach ($books as $book): ?>

                            <button
                                type="button"
                                class="book-result-item"
                                data-book-id="<?= (int)$book->book_id ?>"
                                data-title="<?= encode($book->title) ?>"
                                data-author="<?= encode($book->author) ?>"
                                data-category="<?= (int)$book->category_id ?>"
                                data-cover="<?= encode(cover_url($book)) ?>"
                            >

                                <?= book_cover($book, 'book-cover-tiny') ?>

                                <span class="book-result-text">

                                    <span class="book-result-title">
                                        <?= encode($book->title) ?>
                                    </span>

                                    <span class="book-result-author">
                                        By <?= encode($book->author) ?>
                                    </span>

                                </span>

                                <span class="book-result-category">
                                    <?= encode($book->category_name ?? 'Uncategorized') ?>
                                </span>

                            </button>

                        <?php endforeach; ?>

                        <p class="no-book-results" id="no_book_results" hidden>
                            No books found.
                        </p>

                    </div>

                </div>

                <!-- SELECTED BOOK -->

                <div class="selected-book" id="selected_book" style="display: none;"></div>

                <!-- STAR RATING -->

                <div class="form-group">

                    <label>Your Rating</label>

                    <div class="rating-row">

                        <div class="star-rating">

                            <?php for ($i = 5; $i >= 1; $i--): ?>

                                <input
                                    type="radio"
                                    id="star<?= $i ?>"
                                    name="rating"
                                    value="<?= $i ?>"
                                >

                                <label for="star<?= $i ?>">★</label>

                            <?php endfor; ?>

                        </div>

                        <span class="star-text" id="star_text"></span>

                    </div>

                </div>

                <!-- COMMENT -->

                <div class="form-group">

                    <label for="comment">Your Comment</label>

                    <textarea
                        name="comment"
                        id="comment"
                        maxlength="500"
                        placeholder="Share your thoughts about this book..."
                    ></textarea>

                    <div class="char-count">
                        <span id="char_count">0</span> / 500
                    </div>

                </div>

                <div class="modal-error" id="modal_error"></div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-cancel" id="cancel_modal">
                    Cancel
                </button>

                <button type="submit" class="btn btn-submit" id="submit_btn">
                    Submit Rating
                </button>

            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---------- elements ----------
    const modal = document.getElementById('rating_modal');
    const form = document.getElementById('rating_form');

    const modalTitle = document.getElementById('modal_title');
    const submitBtn = document.getElementById('submit_btn');
    const errorBox = document.getElementById('modal_error');

    const actionInput = document.getElementById('form_action');
    const feedbackInput = document.getElementById('form_feedback_id');
    const bookIdInput = document.getElementById('book_id');

    const picker = document.getElementById('book_picker');
    const searchInput = document.getElementById('book_search');
    const categoryFilter = document.getElementById('category_filter');
    const noResults = document.getElementById('no_book_results');
    const selectedBook = document.getElementById('selected_book');
    const bookItems = document.querySelectorAll('.book-result-item');

    const commentInput = document.getElementById('comment');
    const charCount = document.getElementById('char_count');
    const starText = document.getElementById('star_text');
    const starInputs = document.querySelectorAll('input[name="rating"]');

    const starWords = ['Poor', 'Fair', 'Good', 'Very good', 'Excellent'];


    // ---------- open and close the pop-up ----------
    function closeModal() {
        modal.classList.remove('show');
    }

    function openModal(mode, data) {

        form.reset();
        errorBox.textContent = '';

        bookItems.forEach(function (item) {
            item.classList.remove('selected');
        });

        if (mode === 'update') {

            // EDIT: the book cannot be changed, so hide the search area
            modalTitle.textContent = 'Edit Your Rating';
            submitBtn.textContent = 'Update Rating';
            actionInput.value = 'update';
            feedbackInput.value = data.id;
            bookIdInput.value = data.bookId;

            picker.style.display = 'none';
            showSelectedBook(data.title, data.author, data.cover, false);

            commentInput.value = data.comment;
            document.getElementById('star' + data.rating).checked = true;

        } else {

            // ADD
            modalTitle.textContent = 'Rate a Book';
            submitBtn.textContent = 'Submit Rating';
            actionInput.value = 'add';
            feedbackInput.value = '';
            bookIdInput.value = '';

            picker.style.display = 'block';
            searchInput.value = '';
            categoryFilter.value = '';
            filterBooks();

            selectedBook.innerHTML = '';
            selectedBook.style.display = 'none';
        }

        updateStarText();
        updateCharCount();

        modal.classList.add('show');
    }

    document.getElementById('open_add').addEventListener('click', function () {
        openModal('add', {});
    });

    document.querySelectorAll('.btn-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal('update', this.dataset);
        });
    });

    document.getElementById('close_modal').addEventListener('click', closeModal);
    document.getElementById('cancel_modal').addEventListener('click', closeModal);

    // click on the dark background to close
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // press Esc to close
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });


    // ---------- filter books ----------
    function filterBooks() {

        const keyword = searchInput.value.toLowerCase().trim();
        const categoryId = categoryFilter.value;

        let visibleCount = 0;

        bookItems.forEach(function (item) {

            const title = item.dataset.title.toLowerCase();
            const author = item.dataset.author.toLowerCase();
            const category = item.dataset.category;

            const matchesSearch =
                title.includes(keyword) ||
                author.includes(keyword);

            const matchesCategory =
                categoryId === '' || category === categoryId;

            if (matchesSearch && matchesCategory) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }

        });

        noResults.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', filterBooks);
    categoryFilter.addEventListener('change', filterBooks);


    // ---------- show the selected book (cover + title + author) ----------
    function showSelectedBook(title, author, cover, canChange) {

        selectedBook.innerHTML = '';

        const coverBox = document.createElement('div');
        coverBox.className = 'book-cover book-cover-small';

        if (cover !== '') {
            coverBox.style.backgroundImage = "url('" + cover + "')";
        }

        const textBox = document.createElement('div');
        textBox.className = 'selected-book-text';

        const bookTitle = document.createElement('strong');
        bookTitle.textContent = title;

        const bookAuthor = document.createElement('span');
        bookAuthor.textContent = 'By ' + author;

        textBox.appendChild(bookTitle);
        textBox.appendChild(bookAuthor);

        selectedBook.appendChild(coverBox);
        selectedBook.appendChild(textBox);

        // "Change" button: go back to the book list (only when adding)
        if (canChange) {

            const changeBtn = document.createElement('button');
            changeBtn.type = 'button';
            changeBtn.className = 'change-btn';
            changeBtn.textContent = 'Change';

            changeBtn.addEventListener('click', function () {

                bookIdInput.value = '';

                bookItems.forEach(function (book) {
                    book.classList.remove('selected');
                });

                selectedBook.style.display = 'none';
                picker.style.display = 'block';

            });

            selectedBook.appendChild(changeBtn);
        }

        selectedBook.style.display = 'flex';
    }

    // ---------- select a book from the list ----------
    bookItems.forEach(function (item) {

        item.addEventListener('click', function () {

            bookIdInput.value = this.dataset.bookId;
            errorBox.textContent = '';

            showSelectedBook(this.dataset.title, this.dataset.author, this.dataset.cover, true);
            picker.style.display = 'none';

            bookItems.forEach(function (book) {
                book.classList.remove('selected');
            });

            this.classList.add('selected');

        });

    });


    // ---------- star text and comment counter ----------
    function updateStarText() {

        const checked = document.querySelector('input[name="rating"]:checked');

        if (checked) {
            starText.textContent = checked.value + ' / 5 - ' + starWords[checked.value - 1];
        } else {
            starText.textContent = '';
        }
    }

    function updateCharCount() {
        charCount.textContent = commentInput.value.length;
    }

    starInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            updateStarText();
            errorBox.textContent = '';
        });
    });

    commentInput.addEventListener('input', updateCharCount);


    // ---------- validate before submit ----------
    form.addEventListener('submit', function (event) {

        let message = '';

        if (bookIdInput.value === '') {
            message = 'Please select a book first.';
        }
        else if (!document.querySelector('input[name="rating"]:checked')) {
            message = 'Please select a star rating.';
        }
        else if (commentInput.value.trim() === '') {
            message = 'Please write your comment.';
        }

        if (message !== '') {
            event.preventDefault();
            errorBox.textContent = message;
        }

    });


    // ---------- View all / Show less ----------
    const ratingList = document.getElementById('rating_list');
    const viewAllBtn = document.getElementById('view_all');
    const showingText = document.getElementById('showing_text');

    const totalRatings = <?= count($ratings) ?>;
    const pageSize = <?= (int)$page_size ?>;

    function updateShowingText() {

        let shown = totalRatings;

        if (totalRatings > pageSize && !ratingList.classList.contains('show-all')) {
            shown = pageSize;
        }

        showingText.textContent = 'Showing ' + shown + ' of ' + totalRatings + ' ratings';
    }

    if (ratingList) {

        updateShowingText();

        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', function () {

                ratingList.classList.toggle('show-all');

                if (ratingList.classList.contains('show-all')) {
                    viewAllBtn.textContent = 'Show less';
                } else {
                    viewAllBtn.textContent = 'View all';
                }

                updateShowingText();

            });
        }
    }

});
</script>

<?php

include '../../_footer.php';

?>