<?php
if (isset($_POST['search'])) {

    $dateform =  $_POST['dateform'];
    $dateto =  $_POST['dateto'];
    //$expenseDetails = $obj->getAllData("vw_account", "entry_date BETWEEN '" . date('Y-m-d', strtotime($dateform)) . "' and '" . date('Y-m-d', strtotime($dateto)) . "' AND acc_type='1' ORDER BY entry_date DESC");
    $expenseDetails = $obj->rawSql("SELECT * FROM tbl_account WHERE MONTH(entry_date)='$dateform' AND YEAR(entry_date)='$dateto' AND acc_type = '1' ORDER BY entry_date DESC");
} else {
    $firsDayOfMonth = new DateTime('first day of this month');
    $dateform = $firsDayOfMonth->format('Y-m-d');
    $dateto = date('Y-m-d');
    $expenseDetails = $obj->rawSql("SELECT * FROM tbl_account WHERE MONTH(entry_date) = MONTH(CURRENT_DATE) AND YEAR(entry_date) = YEAR(CURRENT_DATE) AND acc_type = '1' ORDER BY entry_date DESC;");
}

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
                                <select class="form-control wizard-required" name="dateform">
                                     <option value="">Select Month</option>
                                <?php
                                // Get the current month number
                                $currentMonth = date('n'); // 'n' returns the month number without leading zeros (1-12)

                                // Loop through all months
                                for ($m = 1; $m <= 12; $m++) {
                                    // Get the month name
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                    // Set the selected attribute for the current month
                                    $selected = ($m == $currentMonth) ? 'selected' : '';
                                    // Output the option
                                    echo "<option value=\"$m\" $selected>$monthName</option>";
                                }
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label for="date-to" class="form-label">To Date</label>
                            <div class="position-relative">
                                <select class="form-control wizard-required" name="dateto">
                                    <option value="">Select Year</option>
                                <?php
                                // Get the current year
                                $currentYear = date('Y'); // Current year (e.g., 2024)
                                $startYear = 2015; // Change this to the starting year you want
                                $endYear = $currentYear + 10; // Change this to the ending year if needed

                                // Loop through the years from startYear to endYear
                                for ($year = $startYear; $year <= $endYear; $year++) {
                                    // Set the selected attribute for the current year
                                    $selected = ($year == $currentYear) ? 'selected' : '';
                                    // Output the option
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                                </select>
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
        <h5 class="card-title mb-0">View Expense Report Sheet</h5>
    </div>
                    <div class="card-body table-responsive">
                        <table class="table bordered-table mb-0 datatable"
                                id="expense-report-table"
                                data-page-length="10">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>

                                    <th>Account Head</th>
                                    <th>Sub head</th>
                                    <th>Expence</th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = '0';
                                $totalExpense = 0;
                                foreach ($expenseDetails as $value) {
                                    $i++;
                                    $totalExpense += intval($value['acc_amount']);
                                    $achead = $value['acc_head'];
                                    $acsubhead = $value['acc_sub_head'];
                                    $viewAccountHead = $obj->getSingleData("tbl_accounts_head", ['where' => ['acc_id', '=', $achead]]);
                                    $viewAccountSubHead  = $obj->getSingleData("tbl_accounts_head", ['where' => ['acc_id', '=', $acsubhead]]);

                                    // $viewAccountHead = $obj->rawSql("SELECT * FROM tbl_accounts_head WHERE acc_id = '$achead';");
                                    //$viewAccountSubHead  = $obj->rawSql("SELECT * FROM tbl_accounts_head WHERE acc_id = '$acsubhead';");

                                ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>

                                        <td><?php echo isset($viewAccountHead['acc_name']) ? $viewAccountHead['acc_name'] : NULL; ?></td>
                                        <td><?php echo isset($viewAccountSubHead['acc_name']) ? $viewAccountSubHead['acc_name'] : NULL; ?></td>
                                        <td><?php echo isset($value['acc_amount']) ? $value['acc_amount'] : NULL; ?></td>

                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total Expense</th>
                                    <th colspan="4"><?php echo $totalExpense; ?> tk</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div> <!-- end card body-->
</div>

<button class="btn btn-success btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;" id="graphicalViewButton">Graphical View</button>
<!-- chart start -->
<!-- Loading Bar (hidden by default) -->

<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Incomes/Expenses Amount Count</h6>
        </div>
        <div class="card-body p-24">
            <div id="expense_view_bar_chart"></div>
        </div>
    </div>
</div>
<?php $obj->start_script(); ?>

<script>
    $(document).ready(function() {
        $('#expense-report-table').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` +
            `<"row"<"col-sm-12 text-end"B>>` +
            `<"row dt-layout-row"<"col-sm-12"tr>>` +
            `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`,
        buttons: [
            { extend: 'copy', className: 'btn btn-primary btn-sm' },
            { extend: 'excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm' },
            { extend: 'print', className: 'btn btn-info btn-sm' }
        ],
        responsive: true,
        processing: true,
        serverSide: false, // Set this to true if using server-side processing
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true
        });

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
</script>

<script>
    $(document).ready(function() {
        // Add event listener for the button click
        $('#graphicalViewButton').click(function() {
            $('.graphicalChart').show()
            // Perform AJAX request when the button is clicked
            // $.ajax({
            //     type: "GET",
            //     url: "./pages/expence/expence_report_ajax.php",
            //     dataType: "json",
            //     success: function(response) {
            //         // Call the function to render the line chart with the data from the server
            //         cutomer_view_line_chart(response.previousData, response.previousYear, response.currentData, response.currentYear, response.maxData);
            //         console.log(response.previousData); // To check the response
            //         console.log(response); // To check the full response
            //     },
            //     error: function(xhr, status, error) {
            //         console.error("Error fetching data:", error);
            //     }
            // });

            $.ajax({
                type: "GET",
                url: "./pages/expence/expense_view_bar_chart_ajax.php",
                dataType: "json",
                success: function(res) {
                    // Call the function to render the line chart with the data from the server
                    expense_view_bar_chart(res.incomeData, res.expensetData, res.currentYear, res.maxData);
                    console.log(res.pakageCountData); // To check the response
                    console.log(res); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        });
    });

    function expense_view_bar_chart(incomeData, expensetData, currentYear, maxData) {

        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        // Convert the comma-separated strings to arrays of numbers
        const incomeDataArray = incomeData.split(',').map(Number);
        const expenseDataArray = expensetData.split(',').map(Number);
        var options = {
            series: [{
                    name: `Income - ${currentYear}`,
                    data: incomeDataArray
                }, {
                    name: `Expenses - ${currentYear}`,
                    data: expenseDataArray
                }

            ],
            chart: {
                type: 'bar',
                height: 350
            },
            colors: ['#008000', '#FF0000'],
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
                colors: ['transparent']
            },
            xaxis: {
                categories: months,
            },
            yaxis: {},
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


        var chart = new ApexCharts(document.querySelector("#expense_view_bar_chart"), options);
        chart.render();
    }


    // function cutomer_view_line_chart(previousData, previousYear, currentData, currentYear, maxData) {
    //     // Define colors
    //     let colors = ["#6658dd", "#1abc9c"];
    //     const dataColors = $("#apex-line-test").data("colors");
    //     if (dataColors) {
    //         colors = dataColors.split(",");
    //     }

    //     // Static month names for both years
    //     const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    //     // Calculate the tick amount for y-axis dynamically
    //     const tickAmount = Math.ceil(maxData / 1000); // Adjust this based on your maxData

    //     // Chart options
    //     const options = {
    //         chart: {
    //             height: 380,
    //             type: "line",
    //             zoom: {
    //                 enabled: false
    //             },
    //             toolbar: {
    //                 show: false
    //             }
    //         },
    //         colors: colors,
    //         dataLabels: {
    //             enabled: true
    //         },
    //         stroke: {
    //             width: [3, 3],
    //             curve: "smooth"
    //         },
    //         series: [{
    //             name: `Previous - ${previousYear}`,
    //             data: previousData // Use the actual array of numbers
    //         }, {
    //             name: `Current - ${currentYear}`,
    //             data: currentData // Use the actual array of numbers
    //         }],
    //         title: {
    //             text: "Expence by Month",
    //             align: "left",
    //             style: {
    //                 fontSize: "14px",
    //                 color: "#666"
    //             }
    //         },
    //         grid: {
    //             row: {
    //                 colors: ["transparent", "transparent"],
    //                 opacity: 0.2
    //             },
    //             borderColor: "#f1f3fa"
    //         },
    //         markers: {
    //             style: "inverted",
    //             size: 6
    //         },
    //         xaxis: {
    //             categories: months, // Use static month names
    //             title: {
    //                 text: "Month"
    //             }
    //         },
    //         yaxis: {
    //             min: 0, // Start y-axis from 0 for better readability
    //             max: maxData, // Use maxData to dynamically set the maximum value of the y-axis
    //             tickAmount: tickAmount // Calculate dynamic ticks based on maxData
    //         },
    //         legend: {
    //             position: "top",
    //             horizontalAlign: "right",
    //             floating: true,
    //             offsetY: -25,
    //             offsetX: -25
    //         },
    //         responsive: [{
    //             breakpoint: 600,
    //             options: {
    //                 chart: {
    //                     toolbar: {
    //                         show: false
    //                     }
    //                 },
    //                 legend: {
    //                     show: false
    //                 }
    //             }
    //         }]
    //     };

    //     // Render the chart
    //     const chart = new ApexCharts(document.querySelector("#customer_view_line_chart"), options);
    //     chart.render();
    // }
    //end line chart 
</script>

<?php $obj->end_script(); ?>