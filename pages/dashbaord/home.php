<?php
include('dashboard.php');
$stock = $obj->raw_sql("
    SELECT stock.*,
    p.product_name,
    s.supplier_name,
     _createuser.FullName
    FROM stock
    LEFT JOIN products p ON stock.product_id = p.product_id
    LEFT JOIN suppliers s ON stock.supplier_id = s.supplier_id
    LEFT JOIN _createuser ON stock.created_by = _createuser.UserId
    where stock.deleted_at is null order by stock.stock_id desc limit 5 offset 0");
$i = 1; // Initialize counter
$minimum_threshold = 0;
foreach ($stock as &$row) {
    $row['sl'] = $i++; // Add the row number
    $row['minimum_threshold'] = $minimum_threshold;
}
?>

<div class="row g-4">
<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-3">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="flowbite:users-group-solid" class="icon"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <?php echo $obj->Total_Count("tbl_agent", "ag_status='1'"); ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">Total Active Customer</span>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-3">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="flowbite:users-group-solid" class="icon"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <?= number_format($totalBill['total'] ?? 0, 2) ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">Total Bill Collection</span>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-2">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-purple flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="solar:wallet-bold" class="text-white text-2xl"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <?php echo $monthBill['tbillgenerate'] ?? 0; ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">
                    <?php echo date('F') . " Bill Total"; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-5">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-red flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="fa6-solid:file-invoice-dollar" class="text-white text-2xl"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                
                    <?php
                        $totalBill = $monthBill['tbillgenerate'] ?? 0;
                        $collectedAmount = $get_all_collection['amount'] ?? 0;
                        echo $totalBill - $collectedAmount;
                    ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">
                    <?php echo date('F') . " Due Bill"; ?>
                </span>
            </div>
        </div>
    </div>
</div>


<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-4">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-success-main flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="mdi:calendar-month" class="text-white text-2xl"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <span data-plugin="counterup">
                        <?php echo $get_all_collection['amount'] === null ? "0" : $get_all_collection['amount']; ?>
                    </span>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">
                    <?php echo date('F') . " Collection"; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-5">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-red flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="mdi:account-off" class="text-white text-2xl"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <?php echo $obj->Total_Count("tbl_agent", "ag_status='0'"); ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">
                    Total Inactive Customer
                </span>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-4">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-success-main flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="mdi-account-plus-outline" class="text-white text-2xl"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <?php echo $obj->Total_Count("tbl_agent", "ag_status='1' AND MONTH(entry_date)='$currentNumericMonth' AND YEAR(entry_date)='$currentNumericYear'"); ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">
                    New Customer
                </span>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-3">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="mdi:lightning-bolt" class="text-white text-2xl"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <?php echo $get_connection_charge['amount'] === null ? "0" : $get_connection_charge['amount']; ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">
                    <?php echo  date('F') . " C.Charge"; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-3 col-sm-6">
    <div class="card px-16 py-12 shadow-none radius-8 border h-auto bg-gradient-start-2">
        <div class="card-body p-0 d-flex align-items-center justify-content-between">
            <!-- Icon Section -->
            <div class="w-64-px h-64-px radius-16 bg-base-50 d-flex justify-content-center align-items-center me-16">
                <span class="w-40-px h-40-px bg-purple flex-shrink-0 text-white d-flex justify-content-center align-items-center radius-8 h6 mb-0">
                    <iconify-icon icon="mdi-account-tag-outline" class="text-white text-2xl"></iconify-icon>
                </span>
            </div>
            <!-- Text Section -->
            <div class="text-end">
                <h6 class="fw-semibold my-1 text-neutral-600">
                    <?php echo $obj->Total_Count("tbl_agent", "ag_status='2'"); ?>
                </h6>
                <span class="mb-0 fw-medium text-secondary-light text-md">
                    Total Free Customer
                </span>
            </div>
        </div>
    </div>
</div>


</div>

<br><br>

<!-- Dashboard Widget End -->
<div class="col-xxl-8">
    <div class="card h-100 radius-8 border-0">
        <div class="card-body p-24">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                <div>
                    <h6 class="mb-2 fw-bold text-lg text-neutral-600">Revenue Statistics</h6>
                    <span class="text-sm fw-medium text-secondary-light">Monthly earning overview</span>
                </div>
                <!-- <div class="">
                    <select id="timeRangeSelector" class="form-select form-select-sm w-auto bg-base border text-secondary-light">
                        <option value="Yearly">Yearly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Today">Today</option>
                    </select>
                </div> -->
                <div class=" d-flex flex-wrap">
                    <div class="me-40">
                        <span class="text-secondary-light text-sm mb-1">Income</span>
                        <div>
                            <h6 id="dynamicIncome" class="fw-semibold d-inline-block mb-0 text-neutral-600">$0</h6>
                        </div>
                    </div>
                    <div>
                        <span class="text-secondary-light text-sm mb-1">Expenses</span>
                        <div>
                            <h6 id="dynamicExpenses" class="fw-semibold d-inline-block mb-0 text-neutral-600">$0</h6>
                        </div>
                    </div>
                </div>
            </div>

            <div id="income_view_column"></div>
        </div>
    </div>
</div>

<!-- Revenue Statistics End -->

<!-- Statistics Start -->
<div class="col-xxl-4">
    <div class="card h-100 radius-8 border-0">
        <div class="card-body p-24">
            <h6 class="mb-2 fw-bold text-lg text-neutral-600">Total Bill Collection</h6>

            <div class="mt-24">
                <div id="dashboad_view_redial_chart" width="500" height="500">

                </div>
            </div>

        </div>
    </div>
</div>
<!-- <div class="col-md-8">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0">Column Charts</h6>
        </div>
        <div class="card-body">
            <div id="dashboard_view_combination_chart"></div>
        </div>
    </div>
</div> -->


<div class="col-xxl-12">
    <div class="card h-100">
        <div class="card-body p-24 mb-8">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                <h6 class="mb-2 fw-bold text-lg text-neutral-600 mb-0">Yearly Revenue </h6>
            </div>
            <ul class="d-flex flex-wrap align-items-center justify-content-center my-3 gap-24">
                <li class="d-flex flex-column gap-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="w-8-px h-8-px rounded-pill bg-primary-600"></span>
                        <span class="text-secondary-light text-sm fw-semibold">Previous Year Revenue </span>
                    </div>
                    <div class="d-flex align-items-center gap-8">
                        <h6 class="mb-0" id="previous-revenue">0 BDT</h6>

                    </div>
                </li>
                <li class="d-flex flex-column gap-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="w-8-px h-8-px rounded-pill bg-warning-600"></span>
                        <span class="text-secondary-light text-sm fw-semibold">Current Year Revenue </span>
                    </div>
                    <div class="d-flex align-items-center gap-8">
                        <h6 class="mb-0" id="current-revenue">0 BDT</h6>
                    </div>
                </li>
            </ul>
            <div id="revenueChart" class="apexcharts-tooltip-style-1"></div>
        </div>
    </div>
</div>
<!-- Statistics End -->
<div class="col-xxl-12">
    <div class="card h-100">
        <div class="card-body p-24">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
                <h6 class="mb-2 fw-bold text-lg text-neutral-600 mb-0">Latest Transactions</h6>
            </div>
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="transection-table">
                    <thead>
                        <tr>
                            <th scope="col">SL.</th>
                            <th scope="col">Name</th>
                            <th scope="col">Phone </th>
                            <th scope="col">Amount</th>
                            <th scope="col" class="text-center">Type</th>
                            <th scope="col">Date</th>
                            <th scope="col">Created By</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="col-xxl-7">
    <div class="card h-100">
        <div class="card-body p-24">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
                <h6 class="mb-2 fw-bold text-lg text-neutral-600 mb-0">Latest Complains</h6>
            </div>
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="data-table">
                    <thead>
                        <tr>
                            <th scope="col">SL.</th>
                            <th scope="col">Name</th>
                            <th scope="col">Phone </th>
                            <th scope="col">Complain</th>
                            <th scope="col">Date</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="col-xxl-5">
    <div class="card h-100">
        <div class="card-body p-24">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
                <h6 class="mb-2 fw-bold text-lg text-neutral-600 mb-0">Stock Report</h6>
                <a href="?page=stock" class="text-primary-600 hover-text-primary d-flex align-items-center gap-1">
                    View All
                    <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                </a>
            </div>
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Items</th>
                            <th scope="col">Batch No</th>
                            <th scope="col">
                                <div class="max-w-112 mx-auto">
                                    <span>Stock</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock as $item): ?>
                            <?php
                            $qty = $item['current_stock'];
                            $percentage = ($qty / 50) * 100;
                            ?>
                            <tr>
                                <td><?= $item['product_name'] ?></td>
                                <td><?= $item['batch_id'] ?></td>
                                <td>
                                    <div class="max-w-112 mx-auto">
                                        <div class="w-100">
                                            <div class="progress progress-sm rounded-pill" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                                                <div
                                                    <?php if ($percentage < 20): ?>
                                                    class="progress-bar bg-danger-main rounded-pill"
                                                    <?php elseif ($percentage < 50 &&  $percentage > 20): ?>
                                                    class="progress-bar bg-warning-main rounded-pill"
                                                    <?php elseif ($percentage == 0): ?>
                                                    class="progress-bar bg-secondary-light rounded-pill"
                                                    <?php else: ?>
                                                    class="progress-bar bg-success-main rounded-pill"
                                                    <?php endif; ?>

                                                    style="width: <?= $percentage ?>%;"></div>
                                            </div>
                                            <?php if ($percentage < 20): ?>
                                                <span class="mt-12 text-secondary-main text-sm fw-medium">Low Stock</span>
                                            <?php elseif ($percentage < 50): ?>
                                                <span class="mt-12 text-secondary-main text-sm fw-medium">Medium Stock</span>
                                            <?php elseif ($percentage == 0): ?>
                                                <span class="mt-12 text-secondary-light text-sm fw-medium">Out of Stock</span>
                                            <?php else: ?>
                                                <span class="mt-12 text-secondary-main text-sm fw-medium">High Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="centermodal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fs-4" id="myCenterModalLabel">Change Status</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="mb-3 col-md-12">
                        <input type="hidden" id="modalComplainId">
                        <label for="ComplainStatus" class="form-label">Status</label>
                        <select id="ComplainStatus" class="form-control">
                            <option value="">Choose</option>
                            <option value="1">Pending</option>
                            <option value="2">Processing</option>
                            <option value="3">Solved</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" id="changedStatus" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<?php $obj->start_script(); ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Fetch data from the server
        fetch('./pages/dashbaord/up-down_chart_data_ajax.php')
            .then(response => response.json())
            .then(data => {
                // // Update Total Income and Expense
                // document.getElementById('dynamicIncome').textContent = `$${data.totalIncome.toLocaleString()}`;
                // document.getElementById('dynamicExpenses').textContent = `$${data.totalExpense.toLocaleString()}`;
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

                const incomedata = data.incomeData.split(',').map(Number);
                const expensedata = data.expenseData.split(',').map(Number);
                const sumIncome = incomedata.reduce((accumulator, currentValue) => accumulator + currentValue, 0)
                const sumExpense = expensedata.reduce((accumulator, currentValue) => accumulator + currentValue, 0)

                document.getElementById('dynamicIncome').textContent = `${sumIncome.toLocaleString()} BDT`;
                document.getElementById('dynamicExpenses').textContent = `${sumExpense.toLocaleString()} BDT`;
                let expenseNegative = expensedata.map(value => -Math.abs(value));

                // Generate Up-Down Bar Chart
                var options = {
                    series: [{
                            name: "Income",
                            data: incomedata,
                        },
                        {
                            name: "Expenses",
                            data: expenseNegative,
                        },
                    ],
                    chart: {
                        stacked: true,
                        type: "bar",
                        height: 263,
                        fontFamily: "Poppins, sans-serif",
                        toolbar: {
                            show: false,
                        },
                    },
                    colors: ["#487FFF", "#EF4A00"],
                    plotOptions: {
                        bar: {
                            columnWidth: "8",
                            borderRadius: [2],
                            borderRadiusWhenStacked: "all",
                        },
                    },
                    stroke: {
                        width: [5, 5]
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    legend: {
                        show: true,
                        position: "top",
                    },
                    yaxis: {
                        show: false,
                        title: {
                            text: undefined,
                        },
                        labels: {
                            formatter: function(y) {
                                return y.toFixed(0) + "";
                            },
                        },
                    },
                    xaxis: {
                        show: false,
                        categories: months,
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false,
                        },
                        labels: {
                            show: true,
                            style: {
                                colors: "#d4d7d9",
                                fontSize: "10px",
                                fontWeight: 500,
                            },
                        },
                    },
                    tooltip: {
                        enabled: true,
                        shared: true,
                        intersect: false,
                        theme: "dark",
                        x: {
                            show: false,
                        },
                    },
                };


                var chart = new ApexCharts(document.querySelector("#income_view_column"), options);
                chart.render();
            })
            .catch(error => console.error("Error fetching chart data:", error));
    });
</script>
<style>
    .apexcharts-track path {
        stroke: #FF8042 !important;
    }
</style>

<script>
    $(document).ready(function() {
        // Make sure the AJAX call fetches data successfully
        $.ajax({
            url: './pages/dashbaord/dashboard_view_redial_chart_ajax.php', // Your PHP script URL
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                // Validate the response and calculate the percentage
                if (response.totalBill > 0) {
                    var totalBill = response.totalBill;
                    var totalBillCollection = response.totalBillCollection;
                    var percentage = Math.round((totalBillCollection / totalBill) * 100);

                    if (percentage > 100) {
                        percentage = 100;
                    }

                    var barTextColor = '#45B369'; // Bar color
                    // Render the chart
                    var options = {
                        series: [percentage], // Dynamically set the percentage
                        chart: {
                            height: 350,
                            type: 'radialBar',
                        },
                        plotOptions: {
                            radialBar: {
                                hollow: {
                                    size: '50%', // Set hollow area
                                },
                            },
                        },
                        colors: [barTextColor], // Radial bar color
                        labels: ['Total Collection'], // Label
                    };

                    var chart = new ApexCharts(document.querySelector("#dashboad_view_redial_chart"), options);
                    chart.render();
                } else {
                    console.error("Invalid response: totalBill is 0 or undefined.");

                    var totalBill = 5000;
                    var totalBillCollection = 2000;
                    var percentage = (totalBillCollection / totalBill) * 100;

                    var barTextColor = 'rgb(9, 142, 54)'; // Bar color
                    // Render the chart
                    var options = {
                        series: [percentage], // Dynamically set the percentage
                        chart: {
                            height: 350,
                            type: 'radialBar',
                        },
                        plotOptions: {
                            radialBar: {
                                hollow: {
                                    size: '50%', // Set hollow area
                                },
                            },
                        },
                        colors: [barTextColor], // Radial bar color
                        labels: ['Total Collection'], // Label
                    };

                    var chart = new ApexCharts(document.querySelector("#dashboad_view_redial_chart"), options);
                    chart.render();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error fetching data:", error);
            }
        });
        // $.ajax({
        //     url: './pages/dashbaord/dashboard_view_combination_chart_ajax.php', // Your PHP script URL
        //     method: 'GET',
        //     dataType: 'json',
        //     success: function(response) {


        //         const countIds = [];
        //         const totalTakas = [];
        //         // Static month names for both years
        //         const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        //         // Iterate over totalData to separate countId and total_taka
        //         response.totalData.forEach(data => {
        //             if (typeof data === 'object') {
        //                 // If data is an object, push the respective values into the arrays
        //                 countIds.push(data.countId);
        //                 totalTakas.push(data.total_taka);
        //             } else {
        //                 // If the data is 0, push 0 for consistency
        //                 countIds.push(0);
        //                 totalTakas.push(0);
        //             }
        //         });
        //         let maxData = Math.max(...totalTakas.map(value => Number(value)));
        //         let maxIds = Math.max(...countIds.map(value => Number(value)));
        //         maxData = maxData + (maxData * 0.50);
        //         maxIds = maxIds + (maxIds * 0.50);


        //         var options = {
        //             series: [{
        //                     name: "Revenue",
        //                     type: "column", // Bar chart for revenue
        //                     data: totalTakas, // Revenue data
        //                 },
        //                 {
        //                     name: "Sales",
        //                     type: "line", // Line chart for sales
        //                     data: countIds, // Sales data
        //                 },
        //             ],
        //             chart: {
        //                 height: 350,
        //                 type: "line",
        //                 stacked: false,
        //             },
        //             stroke: {
        //                 width: [0, 4], // No stroke for bars, 4px stroke for the line
        //                 curve: "smooth", // Smooth line for sales data
        //             },
        //             plotOptions: {
        //                 bar: {
        //                     columnWidth: "50%", // Width of the bars
        //                 },
        //             },
        //             dataLabels: {
        //                 enabled: true,
        //                 style: {
        //                     fontSize: "12px",
        //                     colors: ["#000000"],
        //                 },
        //                 offsetY: -5, // Position data labels above the bars
        //             },
        //             labels: months,
        //             xaxis: {
        //                 categories: months
        //             },
        //             yaxis: [{
        //                     title: {
        //                         text: "Net Revenue",
        //                     },
        //                     min: 0,
        //                     max: maxData,
        //                 },
        //                 {
        //                     opposite: true,
        //                     title: {
        //                         text: "Number of Sales",
        //                     },
        //                     min: 0,
        //                     max: maxIds,
        //                 },
        //             ],
        //             colors: ["#00E396", "#007BFF"], // Green for revenue (bars) and blue for sales (line)
        //             tooltip: {
        //                 shared: true,
        //                 intersect: false,
        //                 y: {
        //                     formatter: function(value) {
        //                         return value.toFixed(0);
        //                     },
        //                 },
        //             },
        //         };

        //         var chart = new ApexCharts(document.querySelector("#dashboard_view_combination_chart"), options);
        //         chart.render();
        //     }
        // });

        $.ajax({
            url: "./pages/dashbaord/dashboard_view_revenue_chart_ajax.php",
            method: 'GET',
            dataType: 'json',
            success: function(res) {

                let colors = ["#6658dd", "#1abc9c"];
                const dataColors = $("#apex-line-test").data("colors");
                if (dataColors) {
                    colors = dataColors.split(",");
                }


                // Static month names for both years
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

                const previousDataArray = res.previousData.split(',').map(Number);
                const currentDataArray = res.currentData.split(',').map(Number);
                const sumPreviousIncome = previousDataArray.reduce((accumulator, currentValue) => accumulator + currentValue, 0)
                const sumCurrentIncome = currentDataArray.reduce((accumulator, currentValue) => accumulator + currentValue, 0)

                document.getElementById('previous-revenue').textContent = `${sumPreviousIncome.toLocaleString()} BDT`;
                document.getElementById('current-revenue').textContent = `${sumCurrentIncome.toLocaleString()} BDT`;
                const tickAmount = Math.ceil(res.maxData / 1000);
                const max = res.maxData;
                var options = {
                    series: [{
                        name: `Previous - ${res.previousYear}`,
                        data: previousDataArray
                    }, {
                        name: `Current - ${res.currentYear}`,
                        data: currentDataArray
                    }],
                    legend: {
                        show: false
                    },
                    chart: {
                        type: 'area',
                        width: '100%',
                        height: 270,
                        toolbar: {
                            show: false
                        },
                        padding: {
                            left: 0,
                            right: 0,
                            top: 0,
                            bottom: 0
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3,
                        colors: ["#487FFF", "#FF9F29"], // Use two colors for the lines
                        lineCap: 'round'
                    },
                    grid: {
                        show: true,
                        borderColor: '#D1D5DB',
                        strokeDashArray: 1,
                        position: 'back',
                        xaxis: {
                            lines: {
                                show: false
                            }
                        },
                        yaxis: {
                            lines: {
                                show: true
                            }
                        },
                        row: {
                            colors: undefined,
                            opacity: 0.5
                        },
                        column: {
                            colors: undefined,
                            opacity: 0.5
                        },
                        padding: {
                            top: -20,
                            right: 0,
                            bottom: -10,
                            left: 0
                        },
                    },
                    fill: {
                        type: 'gradient',
                        colors: ["487FFF", "#FF9F29"], // Use two colors for the gradient
                        gradient: {
                            shade: 'light',
                            type: 'vertical',
                            shadeIntensity: 0.5,
                            gradientToColors: [undefined, `#FF9F2900`], // Apply transparency to both colors
                            inverseColors: false,
                            opacityFrom: [0.4, 0.6], // Starting opacity for both colors
                            opacityTo: [0.3, 0.3], // Ending opacity for both colors
                            stops: [0, 100],
                        },
                    },
                    markers: {
                        colors: ["487FFF", "#FF9F29"], // Use two colors for the markers
                        strokeWidth: 3,
                        size: 0,
                        hover: {
                            size: 10
                        }
                    },
                    xaxis: {
                        labels: {
                            show: false
                        },
                        categories: months,
                        tooltip: {
                            enabled: false
                        },
                        labels: {
                            formatter: function(value) {
                                return value;
                            },
                            style: {
                                fontSize: "14px"
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value) {
                                return (value / 1000).toFixed(0) + "k";
                            },
                            style: {
                                fontSize: "14px"
                            }
                        },
                    },
                    tooltip: {
                        x: {
                            format: 'dd/MM/yy HH:mm'
                        }
                    }
                };
                var chart = new ApexCharts(document.querySelector(`#revenueChart`), options);
                chart.render();
            }
        });


        var transectionTable = $('#transection-table').DataTable({
            paging: false,
            searching: false,
            ordering: false,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: './pages/dashbaord/get_transection_ajax.php', // Replace with your API endpoint
                dataSrc: 'data', // This points to the part of the response where your data is located
            },
            columns: [{
                    data: 'sl',
                    defaultContent: 'N/A'
                },
                {
                    data: 'customer_name',
                    defaultContent: 'N/A'
                },
                {
                    data: 'phone',
                    defaultContent: 'N/A',

                },
                {
                    data: 'amount',
                    defaultContent: 'N/A'
                },
                {
                    data: 'type',
                    className: 'text-center',

                    render: function(data, type, row) {
                        let statusClass = '';
                        let statusLabel = '';
                         if (data == 1) {
            statusClass = 'bg-danger';
            statusLabel = 'Expenses';
        } else if (data == 2) {
            statusClass = 'bg-primary';
            statusLabel = 'Other Income';
        } else if (data == 3) {
            statusClass = 'bg-success';
            statusLabel = 'Bill Collection';
        } else if (data == 4) {
            statusClass = 'bg-success';
            statusLabel = 'Connection Charge';
        } else if (data == 5) {
            statusClass = 'bg-secondary';
            statusLabel = 'Opening Income';
        }
                        return `<span class="${statusClass} text-white px-24 py-4 rounded-pill fw-medium text-sm">${statusLabel}</span>`;
                    }
                },
                {
                    data: 'entry_date',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('en-US', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        }).replace(',', '') : '';
                    }


                },
                {
                    data: 'entry_by',
                    defaultContent: 'N/A'
                },
                // {
                //     data: null,
                //     render: function(data) {
                //         return `<a href="#centermodal" data-bs-toggle="modal" data-id="${data.id}" class="btn btn-xs btn-light status-modal"><i class="mdi mdi-pencil"></i></a>`;
                //     }
                // }
            ]
        });
        var dataTable = $('#data-table').DataTable({
            paging: false,
            searching: false,
            ordering: false,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: './pages/dashbaord/get_complain_ajax.php', // Replace with your API endpoint
                dataSrc: 'data', // This points to the part of the response where your data is located
            },
            columns: [{
                    data: 'sl',
                    defaultContent: 'N/A'
                },
                {
                    data: 'customer_name',
                    defaultContent: 'N/A'
                },
                {
                    data: 'phone',
                    defaultContent: 'N/A',

                },
                {
                    data: 'complain_date',
                    defaultContent: 'N/A',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('en-US', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        }).replace(',', '') : '';
                    }
                },
                {
                    data: 'complain_name',
                    defaultContent: 'N/A'
                },
                {
                    data: 'status_name',
                    render: function(data, type, row) {
                        let statusClass = '';
                        let statusLabel = '';
                        switch (data) {
                            case '1':
                                statusClass = 'bg-danger';
                                statusLabel = 'Pending';
                                break;
                            case '2':
                                statusClass = 'bg-primary';
                                statusLabel = 'Processing';
                                break;
                            case '3':
                                statusClass = 'bg-success';
                                statusLabel = 'Resolved';
                                break;
                            default:
                                statusClass = 'bg-secondary';
                                statusLabel = 'Unknown';
                                break;
                        }
                        return `<span class="${statusClass} text-white px-24 py-4 rounded-pill fw-medium text-sm">${statusLabel}</span>`;
                    }
                },
                {
                    data: null,
                    render: function(data) {
                        return `<a href="#centermodal" data-bs-toggle="modal" data-id="${data.id}" class="btn btn-xs btn-light status-modal"><i class="mdi mdi-pencil"></i></a>`;
                    }
                }
            ]
        });

        // Handle status update when the button is clicked
        $('#changedStatus').on('click', function() {
            // Collect data
            const complainId = $('#modalComplainId').val();
            const statusId = $('#ComplainStatus').val();

            // Validate status selection
            if (!statusId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please select a status'
                });
                return;
            }

            // Prepare AJAX data
            const formData = {
                id: complainId,
                status: statusId
            };

            // AJAX request to update the status
            $.ajax({
                url: './pages/complain/update_status.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                dataType: 'json',
                success: function(response) {
                    // Hide the modal
                    $('#centermodal').modal('hide');
                    // Reload the DataTable to reflect the status update
                    dataTable.ajax.reload();
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred');
                }
            });
        });

        // Open the modal when a status is clicked
        $(document).on('click', '.status-modal', function() {
            // Get the ID from the clicked button
            const complainId = $(this).data('id');

            // Set the ID in a hidden input in the modal
            $('#modalComplainId').val(complainId);

            // Reset the status dropdown
            $('#ComplainStatus').val('');
        });




    });
</script>

<?php $obj->end_script(); ?>