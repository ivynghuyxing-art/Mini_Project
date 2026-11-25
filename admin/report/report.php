<?php

include '../../_base.php';
auth(['admin']);

$_title='Report Management';

$report_type = post('report_type','borrowing');
$date_range = post('date_range','1');

if(!in_array($date_range,['1','3','6','12','all'],true)){
    $date_range='1';
}

if($date_range === 'all'){
    $start_date = '1900-01-01';
}else{
    $start_date = date ('Y-m-d',strtotime("-$date_range months"));
}

$total_borrowings = 0;
$returned_books = 0;
$overdue_books = 0;
$currently_borrowed = 0;
$borrowings = [];

//Total borrowings
$stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date<=CURDATE()");
$stm -> execute([$start_date]);
$total_borrowings = $stm -> fetchColumn();

//Returned book
$stm = $_db -> prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date <= CURDATE() AND bor.status ='RETURN'");
$stm ->execute([$start_date]);
$returned_books = $stm->fetchColumn();

//currently borrowed
$stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <= CURDATE() AND bor.status = 'APPROVAL' AND bor.due_date >= CURDATE()");
$stm -> execute([$start_date]);
$currently_borrowed = $stm->fetchColumn();

//overdue books
$stm = $_db -> prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <=CURDATE() AND (bor.status = 'OVERDUE' OR(bor.status ='APPROVAL' AND bor.due_date < CURDATE()))");
$stm ->execute([$start_date]);
$overdue_books =$stm ->fetchColumn();

//borrowing details 
$stm = $_db-> prepare("SELECT bor.borrowing_id, bk.title ,bor.borrow_date,bor.due_date, bor.return_date,bor.status FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <= CURDATE() ORDER BY bor.borrow_date DESC");
$stm ->execute([$start_date]);
$borrowings = $stm->fetchAll();

$chart_period = post('chart_period','daily');

if(!in_array($chart_period,['daily','weekly','monthly','yearly'],true)){
    $chart_period= 'daily';
}

$chart_labels=[]; //x-asis
$chart_values=[]; //y-axis

if($chart_period === 'daily'){
    $group_expression = "DATE_FORMAT(bor.borrow_date,'%Y-%m-%d')";
}else if($chart_period === 'weekly'){
   $group_expression = "CONCAT(YEAR(bor.borrow_date), '-W', LPAD(WEEK(bor.borrow_date, 1), 2, '0'))";
}else if($chart_period === 'monthly'){
    $group_expression = "DATE_FORMAT(bor.borrow_date,'%Y-%m')";
}else{
    $group_expression ="YEAR(bor.borrow_date)";
}
$stm = $_db->prepare("SELECT $group_expression AS period_label, COUNT(*) AS total FROM borrowing bor WHERE bor.borrow_date >=? AND bor.borrow_date<= CURDATE() GROUP BY period_label ORDER BY period_label");
$stm -> execute([$start_date]);
$chart_data = $stm->fetchAll();

//seperate label and value
foreach($chart_data as $data){
    $chart_labels[]= $data->period_label;
    $chart_values[]=(int)$data->total;
}

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
            <input type="hidden" name="chart_period" value="<?= encode($chart_period) ?>">
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
                    <option value ="1" <?= $date_range === '1'?'selected':'' ?>>Last 1 month</option>
                    <option value ="3" <?= $date_range === '3'?'selected':'' ?>>Last 3 month</option>
                    <option value ="6" <?= $date_range === '6'?'selected':''?>>Last 6 month</option>
                    <option value ="12" <?= $date_range === '12'?'selected':'' ?>>Last 12 month</option>
                    <option value="all" <?= $date_range === 'all' ? 'selected':'' ?>>All Time</option>
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
                    <div class = "stat-icon">⏰</div>
                        <div class = stat-info>
                            <h3>Overdue Books</h3>
                            <p><?= $overdue_books ?></p>
                        </div>
                </div>
            </div>
            <div class = "chart-grid">
                <div class = "chart-container">
                    <div class = "chart-header">
                        <div>
                            <h2>Borrowing Trends</h2>
                            <p>Overview of borrowing activities</p>
                        </div>

                        <form method="post" class = "chart-filter" id="chartFilter">
                            <input type ="hidden" name ="date_range" id="dateRangeHidden" value ="<?= encode($date_range) ?>">
                            <input type="hidden" name="report_type" value="<?= encode($report_type) ?>">
                            <select name="chart_period" id="chartPeriod" onchange="updateChartFilter()">
                                <option value="daily"<?= $chart_period === 'daily' ?'selected':'' ?>>
                                    Daily
                                </option>

                                <option value="weekly"<?= $chart_period === 'weekly'?'selected':'' ?>>
                                    Weekly
                                </option>

                                <option value="monthly"<?= $chart_period === 'monthly'?'selected':'' ?>>
                                    Monthly
                                </option>

                                <option value="yearly"<?= $chart_period === 'yearly'?'selected':'' ?>>
                                    Yearly
                                </option>
                            </select>
                        </form>
                    </div>
                        <div class = "chart-content">
                            <canvas id = "borrowingChart"></canvas>
                        </div>
                </div>
            </div>

        </div>
            

            <div class = "report-details">
                <h2>Borrowing Details</h2>
                <table class ="report-table">
                    <thead>
                        <tr>
                            <th>Borrowing ID </th>
                            <th> Book Title </th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($borrowings as $borrowing): ?>
                            <tr>
                                <td><?= encode($borrowing->borrowing_id) ?></td>
                                <td><?= encode($borrowing->title) ?></td>
                                <td><?= encode($borrowing->borrow_date) ?></td>
                                <td><?= encode($borrowing->due_date) ?></td>
                                <td><?= ($borrowing->return_date && $borrowing->return_date != '0000-00-00')?encode($borrowing->return_date):"Not Returned" ?></td>
                                <td><?= encode($borrowing->status) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pass PHP data to JavaScript -->
<script>
    const chartLabels = <?= json_encode($chart_labels) ?>;
    const chartValues = <?= json_encode($chart_values) ?>;

    function updateChartFilter() {
    const dateRange = document.getElementById('dateRange').value;

    document.getElementById('dateRangeHidden').value = dateRange;

    document.getElementById('chartFilter').submit();
}
</script>

<!-- Load app.js -->
<script src="../../js/app.js"></script>

<?php
include '../../_footer.php';
?>