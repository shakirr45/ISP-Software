<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label class="form-label">Select Year</label>
                            <div class="position-relative">
                                <select id="select-year" name="dateYear" class="form-control">
                                    <option value="">Select Year</option>
                                    <?php
                                    $currentYear = date('Y');
                                    for ($year = 2010; $year <= $currentYear; $year++) {
                                    ?>
                                        <option <?= ($year == $currentYear) ? 'selected' : '' ?> value="<?= $year ?>">
                                            <?= $year ?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <!-- Form Wizard End -->
        </div>
    </div>
</div>
<div class="col-md-12">
    <div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Monthly Statement</h5>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="balance-datatable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Month</th>
                    <th scope="col">Opening Balance</th>
                    <th scope="col">Customer Payment</th>
                    <th scope="col">Others Payment</th>
                    <th scope="col">Connection Charge</th>
                    <th scope="col">Opening Amount</th>
                    <th scope="col">Total</th>
                    <th scope="col">Expense Statement</th>
                    <th scope="col">Closing Balance</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <div id="summary-table" class="card-body table-responsive">
            <table class="table" style="width: 70%">
                <tbody id="total-summary">
                    <!-- This table will hold the summary data -->
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>




<!-- end row-->

<!-- Graphical View Part -->
<button class="btn btn-success btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;"
    id="graphicalViewButton">Graphical View</button>

<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Yearly Balance Comparison</h6>
        </div>
        <div class="card-body p-24">
            <div id="yearly_column_chart"></div>
        </div>
    </div>
</div>
<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Two Year Balance Comparison</h6>
        </div>
        <div class="card-body p-24">
            <div id="two_yearly_column_chart"></div>
        </div>
    </div>
</div>


<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#balance-datatable').DataTable({
            dom: `<"row"<"col-sm-6 d-flex align-items-center"B l><"col-sm-6 text-end"f>>` + // Buttons and Show entries dropdown together
                `<"row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right ,
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true,
            // serverSide: true,
            ajax: {
                url: "./pages/balance_sheet/yearly_balance_report_ajax.php",
                type: "GET",
                data: function(d) {
                    d.dateYear = $("#select-year").val();
                }
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'month'
                },
                {
                    data: 'opening_balance'
                },
                {
                    data: 'customer_payment'
                },
                {
                    data: 'others_payment'
                },
                {
                    data: 'connection_charge'
                },
                {
                    data: 'opening_amount'
                },
                {
                    data: 'total'
                },
                {
                    data: 'expense_statement'
                },
                {
                    data: 'closing_balance'
                }
            ],
            buttons: [{
                extend: "pdfHtml5",
                className: "btn-light",
                text: "Download PDF",
                download: "download",
                customize: function(doc) {
                    // Adding total summary to PDF content
                    let yearly_total_income = $("#yearly_total_income").text();
                    let yearly_expense = $("#yearly_expense").text();
                    let yearly_profit = $("#yearly_profit").text();

                    doc.content.push({
                        table: {
                            body: [
                                ["Yearly Total Income:", yearly_total_income],
                                ["Yearly Total Expense:", yearly_expense],
                                ["Yearly Profit:", yearly_profit]
                            ]
                        },
                        margin: [0, 50, 0, 50]
                    });

                    doc.content.splice(0, 1, {
                        text: "The Yearly Balance report of " + $("#select-year").val(),
                        fontSize: 16,
                        bold: true,
                        alignment: 'center',
                        margin: [0, 0, 0, 20],
                    });
                }
            }],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search customers...",
                lengthMenu: "Show _MENU_ entries",
                emptyTable: "No data available in table"
            },
            lengthMenu: [10, 25, 50, 100, 500],
            order: [
                [1, 'asc']
            ],
            drawCallback: function(settings) {
                $('#total-summary').html(`
                        <tr>
                            <th>Yearly Total Income:</th>
                            <td id="yearly_total_income">${settings.json?.yearly_total_income.toFixed(2)} <b>Taka</b></td>
                            <th></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Total Yearly Expense:</th>
                            <td id="yearly_expense">${settings.json?.yearly_expense.toFixed(2)} <b>Taka</b></td>
                            <th></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Yearly Profit:</th>
                            <td id="yearly_profit">${settings.json?.yearly_profit.toFixed(2)} <b>Taka</b></td>
                            <th></th>
                            <td></td>
                        </tr>
                    `);
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            },

            initComplete: function(settings, json) {

            }
        });

        // Trigger table reload when filters are changed
        $('#select-year').on('change', function() {
            table.ajax.reload();
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Function to fetch and render yearly data
        function fetchAndRenderChart() {
            $('.graphicalChart').show()

            // Get the selected year from the dropdown
            let dYear = $("#select-year").val(); // Get the selected year correctly

            // Prepare the data to send in the request
            let dateYear = {
                dateYear: dYear
            };

            // AJAX call to fetch data for the chart
            $.ajax({
                type: "GET",
                url: "./pages/balance_sheet/yearly_column_chart_ajax.php", // URL to fetch the data
                data: dateYear,
                dataType: "json",
                success: function(res) {

                    // Call the function to render the yearly column chart
                    yearly_column_view_chart(res.incomeData, res.expenseData, res.maxData);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });

            $.ajax({
                type: "GET",
                url: "./pages/balance_sheet/two_year_column_chart_ajax.php", // URL to fetch the data
                data: dateYear,
                dataType: "json",
                success: function(res) {

                    // Call the function to render the yearly column chart
                    two_years_column_view_chart(res.incomeData, res.expenseData, res.maxData);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        }


        // Function to render the yearly column chart
        function two_years_column_view_chart(totalIncome, totalExpense, maxData) {
            let dYear = $("#select-year").val(); // Get the selected year


            var options = {
                series: [{
                    name: 'Total Income PreviousYear',
                    data: totalIncome?.previousYear, // Total income data for each month
                }, {
                    name: 'Total Expenses PreviousYear',
                    data: totalExpense?.previousYear // Total expense data for each month
                }, {
                    name: 'Total Income CurrentYear',
                    data: totalIncome?.currentYear, // Total income data for each month
                }, {
                    name: 'Total Expenses CurrentYear',
                    data: totalExpense?.currentYear // Total expense data for each month
                }, ],
                colors: ['#0A5EB0', '#E195AB', '#28a745', '#dc3545', ],
                chart: {
                    type: 'bar',
                    height: 350
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 5,
                        borderRadiusApplication: 'end'
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
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], // Months for x-axis
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
                    max: maxData, // Set the max value of the y-axis dynamically from the data
                    min: 0,
                    tickAmount: 5, // Dynamic based on your data's max value
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return (val / 1000).toFixed(0) + " k"; // Formatting the tooltip
                        }
                    }
                }
            };

            // Clear existing chart before rendering a new one
            $("#two_yearly_column_chart").html("");

            // Initialize and render the chart with the specified options
            var chart = new ApexCharts(document.querySelector("#two_yearly_column_chart"), options);
            chart.render();
        }

        // Function to render the yearly column chart
        function yearly_column_view_chart(totalIncome, totalExpense, maxData) {
            let dYear = $("#select-year").val(); // Get the selected year

            // Check if the received data is already an array and directly use it
            const totalIncomeArray = Array.isArray(totalIncome) ? totalIncome : totalIncome.split(',').map(Number);
            const totalExpenseArray = Array.isArray(totalExpense) ? totalExpense : totalExpense.split(',').map(Number);

            var options = {
                series: [{
                    name: 'Total Income',
                    data: totalIncomeArray, // Total income data for each month
                }, {
                    name: 'Total Expenses',
                    data: totalExpenseArray // Total expense data for each month
                }],
                colors: ['#28a745', '#dc3545'],
                chart: {
                    type: 'bar',
                    height: 350
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 5,
                        borderRadiusApplication: 'end'
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
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], // Months for x-axis
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
                    max: maxData, // Set the max value of the y-axis dynamically from the data
                    min: 0,
                    tickAmount: 5, // Dynamic based on your data's max value

                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return (val / 1000).toFixed(2) + " k"; // Formatting the tooltip
                        }
                    }
                }
            };

            // Clear existing chart before rendering a new one
            $("#yearly_column_chart").html("");

            // Initialize and render the chart with the specified options
            var chart = new ApexCharts(document.querySelector("#yearly_column_chart"), options);
            chart.render();
        }

        // Trigger chart reload on button click
        $('#graphicalViewButton').click(function() {
            fetchAndRenderChart(); // Call the function to fetch data and render the chart
        });

        // Trigger chart reload when dropdown values change (i.e., year is changed)
        $('#select-year').on('change', function() {
            fetchAndRenderChart(); // Call the function to fetch data and render the chart
        });
    });
</script>
<?php $obj->end_script(); ?>