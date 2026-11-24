<?php

include '../../_base.php';
auth(['admin']);

$_title='Report Management';
include '../../admin_header.php';

$report_type = post('report_type','borrowing');
$date_range = post('date_range','1');

if(!in_array($date_range,['1','3','6','12'],true)){
    $date_range='1';
}
$start_date = date('Y-m-d',strtotime("-$date_range month"));

$total_borrowings = 0;

if(is_post()){
    $stm = $_db->prepare("SELECT COUNT(*) AS total_borrowings FROM borrowing WHERE borrow_date BETWEEN ? AND CURDATE()");
    $stm->execute([$start_date]);
    $total_borrowings = $stm->fetch()->total_borrowings;
}

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
        <form id="reportForm">
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
        <?php if (is_post()): ?>

            <div class ="statistics-container">
                <div class="stat-card">
                    <h3>Total borrowings</h3>
                    <p><?= $total_borrowings ?></p>
                </div>
            </div>
        <?php else:?>
            <div class ="empty-report">
                <div class="report-icon">📊</div>
                    <h3>No Report Generated</h3>
                    <p>Select your filters and click Generate Report.</p>
                </i>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../../js/app.js"></script>

<?php
include '../../_footer.php';
?>