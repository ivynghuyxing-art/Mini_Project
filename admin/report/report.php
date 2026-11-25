<?php

include '../../_base.php';
auth(['admin']);

$_title='Report Management';

$report_type = post('report_type','borrowing');
$date_range = post('date_range','1');
$category_id = post('category_id','');

if(!in_array($date_range,['1','3','6','12'],true)){
    $date_range='1';
}
if($category_id !== '' && !ctype_digit((string)$category_id)){
    $category_id = '';
}

$start_date = date('Y-m-d',strtotime("-$date_range month"));

$total_borrowings = 0;
$returned_books = 0;
$overdue_books = 0;
$currently_borrowed = 0;
$borrowings = [];

$category_stm = $_db->query("SELECT DISTINCT category_id FROM book WHERE category_id IS NOT NULL  ORDER BY category_id");
$categories = $category_stm->fetchAll();

//Total borrowings
if($category_id === ''){
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date<=CURDATE()");
    $stm -> execute([$start_date]);
}else{
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date <= CURDATE() AND bk.category_id =?");
    $stm -> execute([$start_date,$category_id]);
}
$total_borrowings = $stm -> fetchColumn();

//Returned book
if ($category_id ===''){
    $stm = $_db -> prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date <= CURDATE() AND bor.status ='RETURN'");
    $stm ->execute([$start_date]);
}else {
    $stm = $_db -> prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bn.book_id = bor.book_id WHERE bor.borrow_date > = ? AND bor.borrow_date <= CURDATE() AND bor.status ='RETURN' AND bk.category_id =?");
    $stm -> execute([$start_date,$category_id]);
}
$returned_books = $stm->fetchColumn();

//currently borrowed
if ($category_id === ''){
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <= CURDATE() AND bor.status = 'APPROVAL' AND bor.due_date >= CURDATE()");
    $stm -> execute([$start_date]);
}else{
    $stm = $_db -> prepare ("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date <=CURDATE() AND bor.status ='APPROVAL' AND bor.due_date >= CURDATE() AND bk.category_id =?");
    $stm->execute ([$start_date, $category_id]);
}
$currently_borrowed = $stm->fetchColumn();

//overdue books
if($category_id === ''){
    $stm = $_db -> prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <=CURDATE() AND (bor.status = 'OVERDUE' OR(bor.status ='APPROVAL' AND bor.due_date < CURDATE()))");
    $stm ->execute([$start_date]);
}else{
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date >= ? AND bor.borrow_date <= CURDATE() AND (bor.status = 'OVERDUE' OR (bor.status ='APPROVAL' AND bor.due_date< CURDATE())) AND bk.category_id =?");
    $stm -> execute([$start_date, $category_id]);
}
$overdue_books =$stm ->fetchColumn();

//borrowing details 
if($category_id ===''){
    $stm = $_db-> prepare("SELECT bor.borrowing_id, bor.book_id ,bor.borrow_date,bor.due_date, bor.return_date,bor.status FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <= CURDATE() ORDER BY bor.borrow_date DESC");
    $stm ->execute([$start_date]);
}else{
    $stm = $_db-> prepare("SELECT bor.borrowing_id, bor.book_id ,bor.borrow_date,bor.due_date, bor.return_date,bor.status FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <= CURDATE() AND bk.category_id =? ORDER BY bor.borrow_date DESC");
    $stm ->execute([$start_date,$category_id]);
}
$borrowings = $stm->fetchAll();

include '../../admin_header.php';
?>
<link rel="stylesheet" href = '../../css/app.css'>
<div class= "report-container">
    <div class = "report-header">
        <div>
            <h2>Report Management</h2>
            <p>Generate, analyse and export library reports.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class = "filters">
        <h2>Report Filter</h2>
        <form id="reportForm" method ="POST">
            <div class = "filter-group">
                <label for = "reportType"> Report Type</label>
                <select name = "report_type" id ="reportType">
                    <option value="borrowing">Borrowing</option>
                    <option value="book">Book Inventory</option>
                    <option value="member">Member</option>
                    <option value="fine">Fine</option>
                    <option value="reservation">Reservation</option>
                </select>
            </div>

            <div class ="filter-group">
                <label for="dateRange">Date Range</label>
                <select name="date_range" id ="dateRange">
                    <option value ="1">Last 1 month</option>
                    <option value ="3">Last 3 month</option>
                    <option value ="6">Last 6 month</option>
                    <option value ="12">Last 12 month</option>
                </select>
            </div>

            <div class ="filter-group">
                <label for ="category">Category</label>
                <select name="category_id" id ="category">
                    <option value ="">All category</option>
                </select>
            </div>

            <button type ="submit" class ="generate-btn">Generate Report</button>
        </form>
    </div>

    <div id ="reportResult">
            <div class ="statistics-container">
                <div class="stat-card">
                    <div class = "stat-icon">📚</div>
                        <div class = stat-info>
                            <h3>Total borrowings</h3>
                            <p><?= $total_borrowings ?></p>
                        </div>
                </div>

                <div class="stat-card">
                    <div class = "stat-icon">✅</div>
                        <div class = stat-info>
                            <h3>Returned Books</h3>
                            <p><?= $returned_books ?></p>
                        </div>
                </div>

                <div class="stat-card">
                    <div class = "stat-icon">📖</div>
                        <div class = stat-info>
                            <h3>Currently Borrow</h3>
                            <p><?= $currently_borrowed ?></p>
                        </div>
                </div>

                  <div class="stat-card">
                    <div class = "stat-icon">📖</div>
                        <div class = stat-info>
                            <h3>Overdue Books</h3>
                            <p><?= $overdue_books ?></p>
                        </div>
                </div>
            </div>

            <div class ="empty-report">
                <div class="report-icon">📊</div>
                    <h3>No Report Generated</h3>
                    <p>Select your filters and click Generate Report.</p>
            </div>
    </div>
</div>

<script src="../../js/app.js"></script>

<?php
include '../../_footer.php';
?>