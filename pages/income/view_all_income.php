<?php

include('income.php');
$previewDate = date('d M Y', strtotime($dateform)) . ' to ' . date('d M Y', strtotime($dateto));

?>
<?php
// $currentYear = date("Y");
// $previousYear = $currentYear - 1;
// $PreviousYear = $obj->rawSql('select count(acc_id), MONTH(entry_date) as month from tbl_account where YEAR(entry_date)=2023 and acc_type = 2 group by MONTH(entry_date)');
// $CurrentYear = $obj->rawSql('select count(acc_id), MONTH(entry_date) as month from tbl_account where YEAR(entry_date)=2024 and acc_type = 2 group by MONTH(entry_date)');

// // Create arrays to store the counts for each month (1-12)
// $previousYearData = array_fill(0, 12, 0); // Default all months to 0
// $currentYearData = array_fill(0, 12, 0); // Default all months to 0

// // Map the fetched data to the corresponding months (index 0 represents January)
// foreach ($PreviousYear as $data) {
//     $previousYearData[$data['month'] - 1] = $data["count(acc_id)"];
// }
// foreach ($CurrentYear as $data) {
//     $currentYearData[$data['month'] - 1] = $data["count(acc_id)"];
// }

// // Prepare data for JavaScript
// $previousData = implode(',', $previousYearData);
// $currentData = implode(',', $currentYearData);

// // Combine all data for calculating max value
// $allData = array_merge($previousYearData, $currentYearData);
// $maxValue = max($allData);
// $maxData = $maxValue + ($maxValue); // Add 50% margin to max value

// char end php
?>
<style>
    .table-responsive {
        overflow-x: auto;
    }
</style>
<div class="row mt-3">
    <div class="col-md-12">
        <form action="" method="POST">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">View Income <?php echo $previewDate ?></h6>
                    <div class="row mb-4">
                        <div class="col-md-4">

                        </div>
                        <div class="col-md-3">
                            <label for="date-from">From Date</label>
                            <input type="date" class="form-control "
                                value="<?php echo date('d-m-Y', strtotime($dateform)); ?>" placeholder="Date"
                                name="dateform"
                                id="new_flight_date" required>
                        </div>
                        <div class="col-md-3">
                            <label for="date-to">To Date</label>
                            <input style="color: black;" id="old_flight_date"
                                value="<?php echo date('d-m-Y', strtotime($dateto)); ?>" type="date"
                                class="form-control "
                                placeholder="Date" name="dateto" required>
                        </div>
                        <div class="col-md-2" style="margin-top: 22px;">
                            <input type="submit" name="search" class="btn btn-secondary" value="Search">
                        </div>
                    </div>
                    <button type="button" class="btn btn-success waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#con-close-modal"><i class="mdi mdi-plus me-1"></i> Add New Income</button>
                    <br>
                    <br>
                    <div class="card basic-data-table">
                        <div class="card-body table-responsive">
                            <table
                                class="table bordered-table mb-0 datatable"
                                id="dataTable"
                                data-page-length="10">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Head</th>
                                        <th scope="col">Sub head</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Last upload</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = '0';
                                    $totalExpense = 0;
                                    foreach ($expenseDetails as $value) {
                                        $i++;
                                        $totalExpense += intval($value['acc_amount']);

                                    ?>
                                        <?php

                                        $viewAccount = $obj->getSingleData("tbl_accounts_head", ['where' => ['acc_id', '=', $value['acc_head']]]);
                                        $viewHead2  = $obj->getSingleData("tbl_accounts_head", ['where' => ['acc_id', '=', $value['acc_sub_head']]]);
                                        ?>
                                        <tr>
                                            <td><?php echo $i; ?></td>
                                            <td><?php echo date("d-m-Y", strtotime(isset($value['entry_date']) ? $value['entry_date'] : "2016-02-1")); ?></td>
                                            <td><?php echo isset($viewAccount['acc_name']) ? $viewAccount['acc_name'] : NULL; ?></td>
                                            <td><?php echo isset($viewHead2['acc_name']) ? $viewHead2['acc_name'] : NULL; ?></td>
                                            <td><?php echo isset($value['acc_amount']) ? $value['acc_amount'] : NULL; ?></td>
                                            <td><?php echo isset($value['acc_description']) ? $value['acc_description'] : NULL; ?></td>
                                            <td>
                                                <?php
                                                if (date('Y-m-d', strtotime($value['entry_date'])) ==  date('Y-m-d', strtotime($value['last_update']))) {
                                                    echo "Same Date";
                                                } else {
                                                    echo 'Updated at ' . date('d-m-Y', strtotime($value['last_update']));
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">

                                                    <button type="button" class="btn btn-warning waves-effect waves-light account_sub_head_update"
                                                        data-acc_id="<?php echo @$value['acc_id'] ?>"
                                                        data-parent_id="<?php echo @$value['acc_head'] ?>"
                                                        data-acc_sub_head="<?php echo @$value['acc_sub_head'] ?>"
                                                        data-acc_desc="<?php echo @$value['acc_description'] ?>"
                                                        data-amount="<?php echo @$value['acc_amount'] ?>"

                                                        data-bs-toggle="modal"
                                                        data-bs-target="#con-close-modal"><span class="fas fa-edit"></span></button>


                                                    <a onclick="return confirm('Are You Sure To Delete This Expense ?');"
                                                        href="?page=view_all_income&delete-token=<?php echo isset($value['acc_id']) ? $value['acc_id'] : NULL; ?>"
                                                        class="btn btn-danger waves-effect waves-light btn-sm">
                                                        Delete <span class="glyphicon glyphicon-remove"></span>
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4">Total Income</th>
                                        <th colspan="4"><?php echo $totalExpense; ?> tk</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div> <!-- end card body-->
            </div> <!-- end card -->
        </form>
    </div><!-- end col-->
</div>

<button class="btn btn-success btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;" id="graphicalViewButton">Graphical View</button>

<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Monthly Income Comparison</h6>
        </div>
        <div class="card-body p-24">
            <div id="apex-line-test"></div>
        </div>
    </div>
</div>
<div class="col-xxl-6 graphicalChart" style="display: none;">
    <div class="shadow-7 p-20 radius-12 bg-base h-100">
        <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
            <h6 class="card-title mb-2 fw-bold text-lg">Statistic Comparison</h6>

        </div>
        <div class="position-relative">
            <div id="income_view_bar_chart" class="text-style"></div>
        </div>
    </div>
</div>
<div class="col-12 graphicalChart" style="display: none;">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                <h6 class="card-title mb-2 fw-bold text-lg">User Comparison</h6>
            </div>

            <ul class="d-flex flex-wrap align-items-center justify-content-center mt-3 gap-3">
                <li class="d-flex align-items-center gap-2">
                    <span class="w-12-px h-12-px rounded-circle bg-primary-600"></span>
                    <span class="text-secondary-light text-sm fw-semibold">Paid:
                        <span class="text-primary-light fw-bold" id="paid">0</span>
                    </span>
                </li>
                <li class="d-flex align-items-center gap-2">
                    <span class="w-12-px h-12-px rounded-circle bg-danger"></span>
                    <span class="text-secondary-light text-sm fw-semibold">Unpaid:
                        <span class="text-primary-light fw-bold" id="unpaid">0</span>
                    </span>
                </li>
                <li class="d-flex align-items-center gap-2">
                    <span class="w-12-px h-12-px rounded-circle bg-yellow"></span>
                    <span class="text-secondary-light text-sm fw-semibold">Partially Paid:
                        <span class="text-primary-light fw-bold" id="partiallyPaid">0</span>
                    </span>
                </li>
            </ul>

            <div class="mt-40">
                <div id="income_view_column_chart" class=""></div>
            </div>

        </div>
    </div>
</div>



<!-- zone  modal content -->


<!-- sample modal content -->
<div class="modal fade" id="con-close-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Add Income</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name="account_id" id="account_id">
                                <div class="mb-3">

                                    <label for="parent_id" class="form-label">Account Head</label>
                                    <select name="parent_id" id="parent_id" class="form-control" required>
                                        <option value="">Select</option>
                                        <?php foreach ($viewAccountHead as $value): ?>
                                            <option value="<?php echo $value['acc_id'] ?>"><?php echo $value['acc_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">

                                    <label for="child_id" class="form-label">Account Sub-head</label>
                                    <select name="child_id" id="child_id" class="form-control" required>
                                        <option value="">Select</option>
                                        <?php foreach ($viewAccountSubHead as $value): ?>
                                            <option value="<?php echo $value['acc_id'] ?>"><?php echo $value['acc_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="name_id" class="form-label">Amount</label>
                                    <input type="text" id="amount" name="amount" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="description_id" class="form-label">Account Details</label>
                                    <input type="text" id="description_id" name="details" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit" class="btn btn-success waves-effect waves-light">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div><!-- /.modal -->

<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
        });
    });
    var elements = document.getElementsByClassName('account_sub_head_update');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {
            var dataId = this.getAttribute('data-acc_id');
            var parent_id = this.getAttribute('data-parent_id');
            var acc_sub_head = this.getAttribute('data-acc_sub_head');
            var accdes = this.getAttribute('data-acc_desc');
            var amount = this.getAttribute('data-amount');
            document.getElementById('account_id').value = dataId;
            document.getElementById('parent_id').value = parent_id;
            document.getElementById('child_id').value = acc_sub_head;
            document.getElementById('description_id').value = accdes;
            document.getElementById('amount').value = amount;

        });
    }
</script>
<script>
    $(document).ready(function() {
        $('#graphicalViewButton').click(function() {
            $('.graphicalChart').show()

            $.ajax({
                type: "GET",
                url: "./pages/income/income_view_line_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    income_view_line_chart(response.previousData, response.previousYear, response.currentData, response.currentYear, response.maxData);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/income/income_view_bar_chart_ajax.php",
                dataType: "json",
                success: function(res) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    income_view_bar_chart(res.totalBill, res.totalCharge, res.totalOther, res.totalIncome); // To check the full response
                    // alert(res.totalBill); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/income/income_view_column_chart_ajax.php",
                dataType: "json",
                success: function(res) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    income_view_column_chart(res.paidAgents, res.unpaidAgents, res.partiallyPaidAgents, res.currentYear); // To check the response
                    // alert(res.totalBill); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });

        })

        function income_view_column_chart(paidAgents, unpaidAgents, partiallyPaidAgents, currentYear) {
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            // Convert the comma-separated strings to arrays of numbers
            const paidAgentsDataArray = paidAgents.split(',').map(Number);
            const unpaidAgentsDataArray = unpaidAgents.split(',').map(Number);
            const partiallyPaidAgentsDataArray = partiallyPaidAgents.split(',').map(Number);
            let sumPaid = paidAgentsDataArray.reduce((a, b) => a + b, 0);
            let sumUnpaid = unpaidAgentsDataArray.reduce((a, b) => a + b, 0);
            let sumPartiallyPaid = partiallyPaidAgentsDataArray.reduce((a, b) => a + b, 0);
            document.getElementById('paid').textContent = sumPaid;
            document.getElementById('unpaid').textContent = sumUnpaid;
            document.getElementById('partiallyPaid').textContent = sumPartiallyPaid;

            var options = {
                series: [{
                        name: `Paid Agents - ${currentYear}`,
                        data: paidAgentsDataArray
                    }, {
                        name: `Unpaid Agents - ${currentYear}`,
                        data: unpaidAgentsDataArray
                    },
                    {
                        name: `Partially Agents - ${currentYear}`,
                        data: partiallyPaidAgentsDataArray
                    },
                ],
                chart: {
                    type: 'bar',
                    height: 350
                },
                colors: ['#008000', '#FF0000', '#ff9966'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent', 'transparent', 'transparent']
                },
                xaxis: {
                    categories: months,
                },
                yaxis: {
                    max: 200,
                    min: 0,
                    tickAmount: 10,
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val
                        }
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#income_view_column_chart"), options);
            chart.render();
        }

        function income_view_bar_chart(totalBill, totalCharge, totalOther, totalIncome) {
            const totalArr = [totalBill, totalCharge, totalOther, totalIncome];
            var options = {
                series: [{
                    data: totalArr
                }],
                chart: {
                    type: 'bar',
                    height: 270,
                    toolbar: {
                        show: false
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        distributed: true, // Enables individual bar styling
                        barHeight: '22px'
                    }
                },
                dataLabels: {
                    enabled: false
                },
                grid: {
                    show: true,
                    borderColor: '#ddd',
                    strokeDashArray: 0,
                    position: 'back',
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },
                    yaxis: {
                        lines: {
                            show: false
                        }
                    },
                },
                xaxis: {
                    categories: ['Total Bill Collection', 'Connection Charge', 'Total Other Income', 'Total Opening Income'],
                    labels: {
                        formatter: function(value) {
                            return (value / 1000).toFixed(0) + 'k';
                        }
                    }
                },
                legend: {
                    show: false
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: "horizontal",
                        shadeIntensity: 0.5,
                        gradientToColors: ['#C98BFF', '#FFDC90', '#94FF9B', '#FFAC89', '#A3E2FE'],
                        inverseColors: false,
                        opacityFrom: 1,
                        opacityTo: 1,
                        stops: [0, 100]
                    }
                },
                colors: [
                    '#8501F8',
                    '#FF9F29',
                    '#00D40E',
                    '#F84B01',
                    '#2FBCFC'
                ]
            };

            var chart = new ApexCharts(document.querySelector("#income_view_bar_chart"), options);
            chart.render();
        }

        function income_view_line_chart(previousData, previousYear, currentData, currentYear, maxData) {
            // Define colors for the series
            let colors = ["#6658dd", "#1abc9c"];
            const dataColors = $("#apex-line-test").data("colors");
            if (dataColors) {
                colors = dataColors.split(",");
            }

            // Static month names for both years
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            // Convert the comma-separated strings to arrays of numbers
            const previousDataArray = previousData.split(',').map(Number);
            const currentDataArray = currentData.split(',').map(Number);

            const tickAmount = Math.ceil(maxData / 1000);
            const options = {
                chart: {
                    height: 380,
                    type: "line",
                    zoom: {
                        enabled: false
                    },
                    toolbar: {
                        show: false
                    }
                },
                colors: colors,
                dataLabels: {
                    enabled: true
                },
                stroke: {
                    width: [3, 3],
                    curve: "smooth"
                },
                series: [{
                    name: `Previous - ${previousYear}`,
                    data: previousDataArray // Use the actual array of numbers
                }, {
                    name: `Current - ${currentYear}`,
                    data: currentDataArray // Use the actual array of numbers
                }],
                grid: {
                    row: {
                        colors: ["transparent", "transparent"],
                        opacity: 0.2
                    },
                    borderColor: "#f1f3fa"
                },
                markers: {
                    style: "inverted",
                    size: 6
                },
                xaxis: {
                    categories: months, // Use static month names
                    title: {
                        text: "Month"
                    }
                },
                yaxis: {
                    title: {
                        text: "Income Count"
                    },
                    min: 0, // Start y-axis from 0 for better readability
                    max: maxData, // Use maxData to dynamically set the maximum value of the y-axis
                    tickAmount: 10 // Calculate dynamic ticks based on maxData
                },
                legend: {
                    position: "top",
                    horizontalAlign: "right",
                    floating: true,
                    offsetY: -25,
                    offsetX: -25
                },
                responsive: [{
                    breakpoint: 600,
                    options: {
                        chart: {
                            toolbar: {
                                show: false
                            }
                        },
                        legend: {
                            show: false
                        }
                    }
                }]
            };
            var chart = new ApexCharts(document.querySelector("#apex-line-test"), options);
            chart.render();
        }
    });
</script>
<?php $obj->end_script(); ?>