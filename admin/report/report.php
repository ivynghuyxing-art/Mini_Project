<?php

include '../../_base.php';
auth(['admin']);

$_title='Report Management';

$report_type = post('report_type','borrowing');
$allowed_types = ['borrowing','book','member','fine','reservation'];

if(!in_array($report_type,$allowed_types,true)){
    $report_type = 'borrowing';
}

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

//inventory variables
$total_books = 0;
$total_quantity = 0;
$total_available = 0;
$inventory_books = [];

//total member 
$total_members = 0;
$members=[];

//fine
$total_fine = 0;
$paid_fine = 0;
$unpaid_fine = 0;
$fine_records=[];

//reservation
$total_reservations = 0;
$pending_reservations = 0;
$approved_reservations = 0;
$completed_reservations = 0;
$reservations=[];

//statistics
$statistics = [];
$details_headers =[];
$details_rows = [];

$record_per_page = 5;
$page = (int)post('page',1);

if($page<1){
    $page = 1;
}

$chart_period = post('chart_period','daily');

if(!in_array($chart_period,['daily','weekly','monthly','yearly'],true)){
    $chart_period= 'daily';
}

$chart_labels=[]; //x-asis
$chart_values=[]; //y-axis

if($report_type === 'borrowing'){

    //total borrowing
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date <= CURDATE()");
    $stm->execute([$start_date]);
    $total_borrowings = $stm->fetchColumn();

    //return booked
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date <= CURDATE() AND bor.status = 'RETURN'");
    $stm ->execute([$start_date]);
    $returned_books = $stm ->fetchColumn();

    //currently booked
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <= CURDATE() AND bor.status ='APPROVAL' AND bor.due_date >= CURDATE() ");
    $stm ->execute([$start_date]);
    $currently_borrowed = $stm -> fetchColumn();

    //overdue booked
    $stm = $_db->prepare("SELECT COUNT(*) FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >= ? AND bor.borrow_date <= CURDATE() AND (bor.status ='OVERDUE' OR (bor.status='APPROVAL' AND bor.due_date < CURDATE()))");
    $stm -> execute([$start_date]);
    $overdue_books = $stm->fetchColumn();

    $statistics = [
        ['Total Borrowings',$total_borrowings,'📚'],
        ['Returned Books',$returned_books,'✅'],
        ['Currently Borrowed',$currently_borrowed,'📖'],
        ['Overdue Books',$overdue_books,'⏰']
    ];

    //borrowing details 
    $stm = $_db->prepare("SELECT bor.borrowing_id, bk.title,bor.borrow_date, bor.due_date,bor.return_date, bor.status FROM borrowing bor JOIN book bk ON bk.book_id = bor.book_id WHERE bor.borrow_date >=? AND bor.borrow_date <= CURDATE() ORDER BY bor.borrow_date DESC");
    $stm ->execute([$start_date]);
    $borrowings=$stm->fetchAll();

    $details_headers=['Borrowing ID','Book Title','Borrow Date','Due Date','Return Date','Status'];
    foreach($borrowings as $borrowing){
        $details_rows[]=[
            $borrowing->borrowing_id,
            $borrowing->title,
            $borrowing->due_date,
            ($borrowing->return_date && $borrowing->return_date != '0000-00-00')?$borrowing->return_date :'Not Returned',
            $borrowing->status
        ];
    }

    //borrowing chart
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
}elseif($report_type === 'book'){

//total number of book records
    $stm = $_db->prepare("SELECT COUNT(*) FROM book");
    $stm -> execute();
    $total_books = $stm->fetchColumn();

//total quantity of all books
    $stm = $_db->prepare("SELECT COALESCE (SUM(quantity),0) FROM book");
    $stm -> execute();
    $total_quantity = $stm->fetchColumn();

//total available quantity
    $stm = $_db->prepare("SELECT COALESCE (SUM(available_quantity),0) FROM book");
    $stm->execute();
    $total_available = $stm->fetchColumn();

//out of stock
    $stm=$_db->prepare("SELECT COUNT(*) FROM book WHERE available_quantity=0");
    $stm->execute();
    $out_of_stock=$stm->fetchColumn();

    $statistics=[
        ['Total Books',$total_books,'📚'],
        ['Total Quantity',$total_quantity,'📦'],
        ['Available Quantity',$total_available,'✅'],
        ['Out of stock',$out_of_stock,'⚠️']
    ];

//inventory details
    $stm=$_db->prepare("SELECT book_id,title,quantity,available_quantity FROM book ORDER BY title ASC");
    $stm->execute();
    $inventory_books = $stm->fetchAll();

    $details_headers=['Book ID','Book Title','Total Quantity','Available Quantity'];

    foreach($inventory_books as $book){
        $details_rows[]=[
            $book->book_id,
            $book->title,
            $book->quantity,
            $book->available_quantity
        ];
    }

    $chart_labels=[
        'Total Quantity',
        'Available Quantity',
        'Out of Stock'
    ];

    $chart_values = [
        (int)$total_quantity,
        (int)$total_available,
        (int)$out_of_stock,
    ];
}

//member report
elseif($report_type === 'member'){
    $stm = $_db->prepare("SELECT COUNT(*) FROM user");
    $stm -> execute();
    $total_members = $stm->fetchColumn();

    $statistics =[
        ['Total Members',$total_members,'👥']
    ];

    //member details
    $stm = $_db->prepare("SELECT user_id,username,email,role,valid,email_verified FROM user ORDER BY user_id DESC");
    $stm ->execute();
    $members = $stm->fetchAll();

    $details_headers=[
        'Member ID',
        'Username',
        'Email',
        'Role',
        'Account Status',
        'Email Verification'
    ];

    foreach ($members as $member){
        $details_rows[]=[
            $member->user_id,
            $member->username,
            $member->email,
            ucfirst($member->role),
            $member->valid ==1 ?'Active' :'Inactive',
            $member -> email_verified ==1?'Verified' : 'Not Verified'
        ];
    }

    //member chart
    $chart_labels = ['Total Members'];
    $chart_values = [(int)$total_members];
}
//fine report


$total_records = count($details_rows);
$total_pages = max(1,(int)ceil($total_records/$record_per_page));

if($page > $total_pages){
    $page = $total_pages;
}
$offset = ($page -1) * $record_per_page;

$details_rows = array_slice($details_rows,$offset,$record_per_page);
//Chart titles
$chart_titles =[
    'borrowing' => 'Borrowing Trends',
    'book' => 'Inventory Overview',
    'member' => 'Member Overview',
    'fine' => 'Fine Overview',
    'reservation' => 'Reservation Trends'
];

$chart_descriptions=[
    'borrowing' => 'Overview of borrowing activities',
    'book' => 'Overview of book quantities',
    'member' => 'Overview of library members',
    'fine' => 'Overview of fine payments',
    'reservation' => 'Overview of reservation activities'
];

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
                    <option value="borrowing" <?= $report_type === 'borrowing' ? 'selected' : '' ?>>Borrowing</option>
                    <option value="book" <?= $report_type === 'book'?'selected':'' ?>>Book Inventory</option>
                    <option value="member" <?= $report_type === 'member' ? 'selected' :'' ?>>Member</option>
                    <option value="fine" <?= $report_type === 'fine' ?'selected' :'' ?>>Fine</option>
                    <option value="reservation" <?= $report_type === 'reservation' ? 'selected' :'' ?>>Reservation</option>
                </select>
            </div>

        <?php if($report_type === 'borrowing' || $report_type ==='fine' || $report_type ==='reservation'):?>
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
        <?php endif ;?>

            <button type ="submit" class ="generate-btn">Generate Report</button>
        </form>
    </div>

    <div id ="reportResult">
            <div class ="statistics-container">
                <?php foreach($statistics as $stat):?>
                    <div class ="stat-card">
                        <div class ="stat-icon">
                            <?= $stat[2] ?>
                        </div>

                        <div class ="stat-info">
                            <h3><?= encode($stat[0]) ?></h3>
                            <p><?= encode($stat[1]) ?></p>
                        </div>
                    </div>
                <?php endforeach;?>

            </div>
            <div class = "chart-grid">
                <div class = "chart-container">
                    <div class = "chart-header">
                        <div>
                            <h2><?= encode($chart_titles[$report_type]) ?></h2>
                            <p><?= encode($chart_descriptions[$report_type]) ?></p>
                        </div>
                    <?php if($report_type === 'borrowing' || $report_type ==='fine' || $report_type ==='reservation'):?>
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
                    <?php endif;?>
                    </div>
                        <div class = "chart-content">
                            <canvas id = "borrowingChart"></canvas>
                        </div>
                </div>
            </div>

        </div>
            

            <div class = "report-details">
                <h2><?= encode($chart_titles[$report_type]) ?> Details</h2>
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
    const reportType =<?= json_encode($report_type) ?>

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