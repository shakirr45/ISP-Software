<?php

if (isset($_POST['search'])) {

    $dateform = date('Y-m-d', strtotime($_POST['dateform']));
    $dateto = date('Y-m-d', strtotime($_POST['dateto']));
    //$expenseDetails = $obj->getAllData("vw_account", "entry_date BETWEEN '" . date('Y-m-d', strtotime($dateform)) . "' and '" . date('Y-m-d', strtotime($dateto)) . "' AND acc_type='1' ORDER BY entry_date DESC");
    $connection_charge = $obj->rawSql("SELECT * FROM tbl_account WHERE entry_date BETWEEN '$dateform' AND '$dateto' AND acc_type = '4' ORDER BY entry_date DESC");
} else {
    $firsDayOfMonth = new DateTime('first day of this month');
    $dateform = $firsDayOfMonth->format('Y-m-d');
    $dateto = date('Y-m-d');
    $connection_charge = $obj->rawSql("SELECT * FROM tbl_account WHERE MONTH(entry_date) = MONTH(CURRENT_DATE) AND YEAR(entry_date) = YEAR(CURRENT_DATE) AND acc_type = '4' ORDER BY entry_date DESC;");

    //$obj->getAllData("vw_account", "MONTH(entry_date)='" . date('m') . "' and YEAR(entry_date)='" . date('Y') . "'  AND acc_type='1' ORDER BY entry_date DESC");
}
$previewDate = date('d M Y', strtotime($dateform)) . ' to ' . date('d M Y', strtotime($dateto));



?>
<!-- chart start php -->
<?php
$PreviousYear = $obj->rawSql('select  count(acc_id) from  tbl_account  where YEAR(entry_date)= 2023 and acc_type = 4 group by MONTH(entry_date)');
$CurrentYear = $obj->rawSql('select count(acc_id) from  tbl_account  where YEAR(entry_date)=2024 and acc_type = 4 group by MONTH(entry_date)');

$previousData = implode(',', array_map(function ($item) {
    return $item["count(acc_id)"];
}, $PreviousYear));
$currentData = implode(',', array_map(function ($item) {
    return $item["count(acc_id)"];
}, $CurrentYear));
$allData = array_merge(explode(',', $previousData), explode(',', $currentData));

$maxValue = max(array_map('intval', $allData));
// $minData = min(array_map('intval', $allData));
$maxData = $maxValue + ($maxValue * 0.50);
?>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            
        <form action="" method="POST">
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label for="date-from" class="form-label">From Date</label>
                            <div class="position-relative">
                                <input type="date" id="date-from" name="dateform" class="form-control wizard-required">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label for="date-to" class="form-label">To Date</label>
                            <div class="position-relative">
                                <input type="date" id="date-to" name="dateto" class="form-control wizard-required">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="date-to" class="form-label "></label>
                            <div class="position-relative mt-1">
                                <input type="submit" name="search" class="btn btn-secondary" value="Search">
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </form>
            <!-- Form Wizard End -->
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Connection Charge List</h5>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="dataTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Date</th>
                    <th scope="col">Customer Name</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Address</th>
                    <th scope="col">Agent Email</th>
                    <th scope="col">Connection Charge</th>
                    <th scope="col">Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = '0';
                $totalExpense = 0;
                foreach ($connection_charge as $value) {
                    $i++;
                    $totalExpense += intval($value['acc_amount']);
                    $viewAgent = $obj->getSingleData("tbl_agent", ['where' => ['ag_id', '=', $value['agent_id']]]);
                ?>

                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo date("d-m-Y", strtotime(isset($value['entry_date']) ? $value['entry_date'] : "2016-02-1")); ?></td>
                        <td><?php echo isset($viewAgent['ag_name']) ? $viewAgent['ag_name'] : NULL; ?></td>
                        <td><?php echo isset($viewAgent['ag_mobile_no']) ? $viewAgent['ag_mobile_no'] : NULL; ?></td>
                        <td><?php echo isset($viewAgent['ag_office_address']) ? $viewAgent['ag_office_address'] : NULL; ?></td>
                        <td><?php echo isset($viewAgent['ag_email']) ? $viewAgent['ag_email'] : NULL; ?></td>
                        <td><?php echo isset($value['acc_amount']) ? $value['acc_amount'] : NULL; ?></td>

                        <!-- <td><?php echo $result ?></td> -->

                        <td><?php echo isset($value['acc_description']) ? $value['acc_description'] : NULL; ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
</div>
    <button class="btn btn-success btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;" id="graphicalViewButton">Graphical View</button>


    <div id="loading-spinner" class="spinner-border" role="status" style="display:none; position: fixed; top: 50%; left: 50%; z-index: 9999;">
        <span class="sr-only">Loading...</span>
    </div>
    <div class="col-md-12 graphicalChart" style="display: none;">
        <div class="card h-100 p-0">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="card-title text-lg fw-semibold mb-0">Monthly Connection Charge Trends</h6>
            </div>
            <div class="card-body p-24">
                <div id="connection_view_line_chart"></div>
            </div>
        </div>
    </div>




<?php $obj->start_script(); ?>



<!-- <script>
    $(document).ready(function() {

        $('input[name="dateform"]').datepicker({
            autoclose: true,
            toggleActive: true,
            format: 'dd-mm-yyyy'
        });

        $('input[name="dateto"]').datepicker({
            autoclose: true,
            toggleActive: true,
            format: 'dd-mm-yyyy'
        });
    });
</script> -->

<!-- <script>
    $(document).ready(function() {

        $('input[name="dateform"]').datepicker({
            autoclose: true,
            toggleActive: true,
            format: 'dd-mm-yyyy'
        });

        $('input[name="dateto"]').datepicker({
            autoclose: true,
            toggleActive: true,
            format: 'dd-mm-yyyy'
        });
    });
</script> -->

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
        });
        $('#graphicalViewButton').click(function() {
            $('loading-spinner').show();
            $('.graphicalChart').show();

            $.ajax({
                type: "GET",
                url: "./pages/income/connection_view_line_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    connection_view_line_chart(response.previousData, response.previousYear, response.currentData, response.currentYear, response.maxData);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });

        })

        function connection_view_line_chart(previousData, previousYear, currentData, currentYear, maxData) {
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

            var options = {
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
                    data: previousDataArray,
                }, {
                    name: `Current - ${ currentYear}`,
                    data: currentDataArray
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
                    categories: months, // Use fixed month names
                    title: {
                        text: "Month"
                    }
                },
                yaxis: {
                    title: {
                        text: "Connection Charge Count"
                    },
                    min: 0,
                    max: maxData,
                    tickAmount: 5, // Dynamic based on your data's max value
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

            var chart = new ApexCharts(document.querySelector("#connection_view_line_chart"), options);
            chart.render();
        }
    })
</script>
<?php $obj->end_script(); ?>