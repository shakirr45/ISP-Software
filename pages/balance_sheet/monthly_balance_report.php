<?php
$monthArray = array(
    "1" => 'January',
    "2" => 'February',
    "3" => 'March',
    "4" => 'April',
    "5" => 'May',
    "6" => 'June',
    "7" => 'July',
    "8" => 'August',
    "9" => 'September',
    "10" => 'October',
    "11" => 'November',
    "12" => 'December',
);
?>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label class="form-label">Select Month</label>
                            <div class="position-relative">
                                <select id="select-month" name="dateMonth" class="form-control">
                                    <option value="">Select Month</option>
                                    <?php
                                    $currentMonth = date('n');
                                    foreach ($monthArray as $monthKey => $monthVal) {
                                    ?>
                                        <option <?= ($monthKey == $currentMonth) ? 'selected' : '' ?> value="<?= $monthKey ?>">
                                            <?= $monthVal ?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
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

                    <th scope="col">Date</th>
                    <th scope="col">Bill Collection</th>
                    <th scope="col">Connection Charge</th>
                    <th scope="col">Others Income</th>
                    <th scope="col">Opening Amount</th>
                    <th scope="col">Expense</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot class="pb-3">
                <tr>
                    <th>Total:</th>
                    <th id="total-bill-collection"></th>
                    <th id="total-connection-charge"></th>
                    <th id="total-others-income"></th>
                    <th id="total-opening-amounts"></th>
                    <th id="total-expense"></th>
                </tr>


            </tfoot>
        </table>
        <div id="summary-table" class="table-responsive">
            <table class="table" style="width: 70%">
                <tbody id="total-summary">
                    <!-- This table will hold the summary data -->
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>




<br>
<!-- Graphical View Part -->
<button class="btn btn-success btn-lg col-md-12 mb-1" style="padding: 10px;margin-bottom: 16px;font-size: 21px;"
    id="graphicalViewButton">Graphical View</button>

<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Monthly Balance Summary</h6>
        </div>
        <div class="card-body p-24">
            <div id="monthly_column_chart"></div>
        </div>
    </div>
</div>
<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Monthly Total Balance</h6>
        </div>
        <div class="card-body p-24">
            <div id="monthly_donut_chart"></div>
        </div>
    </div>
</div>
<div class="col-md-12 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Compare of Current And Previoues Years Income</h6>
        </div>
        <div class="card-body p-24">
            <div id="currentAndPreviousChart"></div>
        </div>
    </div>
</div>

<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#balance-datatable').DataTable({
            dom: `<"row"<"col-sm-6 d-flex align-items-center"B l><"col-sm-6 text-end"f>>` + // Buttons and Show entries dropdown together
                `<"row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true,
            ajax: {
                url: "./pages/balance_sheet/monthly_balance_report_ajax.php",
                type: "GET",
                data: function(d) {
                    d.dateMonth = $("#select-month").val();
                    d.dateYear = $("#select-year").val();
                }
            },
            columns: [{
                    data: 'date'
                },
                {
                    data: 'bill_collection'
                },
                {
                    data: 'connection_charge'
                },
                {
                    data: 'others_income'
                },
                {
                    data: 'opening_amounts'
                },
                {
                    data: 'expense'
                }
            ],
            buttons: [{
                extend: "pdfHtml5",
                className: "btn-light",
                text: "Download PDF",
                download: "download",
                customize: function(doc) {
                    let tableData = $('#balance-datatable').DataTable().data().toArray();
                    let totalBillCollection = 0;
                    let totalConnectionCharge = 0;
                    let totalOthersIncome = 0;
                    let totalOpeningAmounts = 0;
                    let totalExpense = 0;

                    tableData.forEach(row => {
                        totalBillCollection += parseFloat(row.bill_collection || 0);
                        totalConnectionCharge += parseFloat(row.connection_charge || 0);
                        totalOthersIncome += parseFloat(row.others_income || 0);
                        totalOpeningAmounts += parseFloat(row.opening_amounts || 0);
                        totalExpense += parseFloat(row.expense || 0);
                    });

                    let totalIncome = totalBillCollection + totalConnectionCharge + totalOthersIncome + totalOpeningAmounts;
                    let cashInHand = totalIncome - totalExpense;

                    doc.content.push({
                        table: {
                            body: [
                                ["Opening Balance:", totalOpeningAmounts.toFixed(2)],
                                ["Total Income:", totalIncome.toFixed(2)],
                                ["Total Expense:", totalExpense.toFixed(2)],
                                ["Cash In Hand:", cashInHand.toFixed(2)]
                            ]
                        },
                        margin: [0, 50, 0, 50]
                    });

                    doc.content.splice(0, 1, {
                        text: "The Monthly Statement of " + $("#select-month option:selected").text().trim() + " " + $("#select-year").val(),
                        fontSize: 11,
                        bold: true,
                        alignment: 'center',
                        margin: [0, 0, 0, 20],
                    });
                }
            }],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                emptyTable: "No data available in table"
            },
            lengthMenu: [10, 25, 50, 100, 500],
            order: [
                [1, 'asc']
            ],
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();

                function getTotal(columnIndex) {
                    return api
                        .column(columnIndex)
                        .data()
                        .reduce(function(a, b) {
                            return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                        }, 0);
                }

                // Calculate totals for individual columns
                let totalBillCollection = getTotal(1);
                let totalConnectionCharge = getTotal(2);
                let totalOthersIncome = getTotal(3);
                let totalOpeningAmounts = getTotal(4);
                let totalExpense = getTotal(5);

                // Calculate the final total (Total Income)
                let totalIncome = totalBillCollection + totalConnectionCharge + totalOthersIncome + totalOpeningAmounts;
                let cashInHand = totalIncome - totalExpense;

                // Update footer cells with totals
                $(api.column(1).footer()).html(totalBillCollection.toFixed(2));
                $(api.column(2).footer()).html(totalConnectionCharge.toFixed(2));
                $(api.column(3).footer()).html(totalOthersIncome.toFixed(2));
                $(api.column(4).footer()).html(totalOpeningAmounts.toFixed(2));
                $(api.column(5).footer()).html(totalExpense.toFixed(2));

                // Add summary row with total amounts at the end
                $('#total-summary').html(`
                            <tr>
                                <th>Opening Balance:</th>
                                <td>${totalOpeningAmounts.toFixed(2)} Taka</td>
                                <th>Total Income:</th>
                                <td>${totalIncome.toFixed(2)} Taka</td>
                            </tr>
                            <tr>
                                <th>Total Expense:</th>
                                <td>${totalExpense.toFixed(2)} Taka</td>
                                <th>Cash In Hand:</th>
                                <td>${cashInHand.toFixed(2)} Taka</td>
                            </tr>
                        `);
            },
            drawCallback: function() {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            },

            initComplete: function(settings, json) {
                // Add summary data from json response
                var totalOpeningAmount = json.totalOpeningAmount;
                var totalIncome = json.totalIncome;
                var totalExpense = json.totalExpense;
                var cashInHand = json.cashInHand;

                $('#total-summary').html(`
                        <tr>
                            <th>Opening Balance:</th>
                            <td>${totalOpeningAmount.toFixed(2)} <b>Taka</b></td>
                            <th>Total Income:</th>
                            <td>${totalIncome.toFixed(2)} <b>Taka</b></td>
                        </tr>
                        <tr>
                            <th>Total Expense:</th>
                            <td>${totalExpense.toFixed(2)} <b>Taka</b></td>
                            <th>Cash In Hand:</th>
                            <td>${cashInHand.toFixed(2)} <b>Taka</b></td>
                        </tr>
                    `);
            },
            drawCallback: function(settings) {
                var json = settings.json; // Get the JSON response
            }
        });

        // Trigger table reload when filters are changed
        $('#select-month, #select-year').on('change', function() {
            table.ajax.reload();
        });
    });
</script>

<script>
    $(document).ready(function() {

        function getMonthName(monthNumber) {
            const monthNames = [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            return monthNames[monthNumber - 1]; // Adjust for zero-based index
        }
        // Event listener for button click or dropdown change
        function fetchAndRenderChart() {
            $('#loading-spinner').show();
            $('.graphicalChart').show()

            let dMonth = $("#select-month").val();
            let dYear = $("#select-year").val();

            // Get the selected month and year
            let dateYear = {
                dateMonth: dMonth,
                dateYear: dYear
            };

            // AJAX call to fetch data and render chart
            $.ajax({
                type: "GET",
                url: "./pages/balance_sheet/monthly_column_chart_ajax.php",
                data: dateYear,
                dataType: "json",
                success: function(res) {
                    $('#loading-spinner').hide();

                    // Render the chart with the data from the server
                    monthly_column_view_chart(res.totalBill, res.totalCharge, res.totalOther, res.totalIncome);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/balance_sheet/monthly_donut_chart_ajax.php",
                data: dateYear,
                dataType: "json",
                success: function(res) {
                    $('#loading-spinner').hide();

                    // Render the chart with the data from the server
                    monthly_donut_view_chart(res.totalIncome, res.totalExpense);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });

            $.ajax({
                type: "GET",
                url: "./pages/balance_sheet/monthly_line_chart_ajax.php",
                data: dateYear,
                dataType: "json",
                success: function(res) {
                    const currentYearData = res.currentData.split(",").map((elem) => Number(elem));
                    const previousYearData = res.previousData.split(",").map((elem) => Number(elem));
                    $('#loading-spinner').hide();
                    monthly_line_chart_view_chart(currentYearData, previousYearData, res.maxData, res.currentYearLabel, res.previousYearLabel);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        }

        function monthly_donut_view_chart(totalIncome, totalExpense) {
            // Get the selected month and year
            let dMonth = $("#select-month").val();
            let dYear = $("#select-year").val();
            let monthName = getMonthName(dMonth);
            monthName = monthName + "-" + dYear;

            // Convert totalExpense to a number if it's a string
            totalIncome = parseFloat(totalIncome) || 0; // Fallback to 0 if undefined
            totalExpense = parseFloat(totalExpense) || 0; // Fallback to 0 if undefined

            // ApexCharts expects `series` to be a flat array
            var options = {
                series: [totalIncome, totalExpense], // Use a flat array for donut chart
                chart: {
                    type: 'donut',
                    height: 350
                },
                labels: ['Total Income', 'Total Expense'], // Labels for the data
                colors: ['#28a745', '#dc3545'], // Colors for the data
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '20px',
                                    fontFamily: 'Helvetica, Arial, sans-serif',
                                    fontWeight: 700,
                                    color: '#000' // Adjusted color for better visibility
                                },
                                value: {
                                    show: true,
                                    fontSize: '20px',
                                    fontFamily: 'Helvetica, Arial, sans-serif',
                                    fontWeight: 700,
                                    color: '#000' // Adjusted color for better visibility
                                },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'Total Balance',
                                    fontSize: '15px',
                                    fontFamily: 'Helvetica, Arial, sans-serif',
                                    fontWeight: 400,
                                    color: '#000', // Adjusted color for better visibility
                                    formatter: function(w) {
                                        const income = w.globals.series[0]; // First value is income
                                        const expense = w.globals.series[1]; // Second value is expense
                                        return income - expense; //
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 0,
                    colors: ['#fff']
                },
                fill: {
                    opacity: 1
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            // Clear existing chart before rendering
            $("#monthly_donut_chart").html("");

            // Render the chart
            var chart = new ApexCharts(document.querySelector("#monthly_donut_chart"), options);
            chart.render();
        }


        // Render the chart
        function monthly_column_view_chart(totalBill, totalCharge, totalOther, totalIncome) {
            let dMonth = $("#select-month").val();
            let dYear = $("#select-year").val();
            let monthName = getMonthName(dMonth);
            monthName = monthName + "-" + dYear;

            var options = {
                series: [{
                        name: 'Total Bill Collection',
                        data: [totalBill]
                    }, // Wrap in an array
                    {
                        name: 'Total Connection Charge',
                        data: [totalCharge]
                    }, // Wrap in an array
                    {
                        name: 'Total Other Income',
                        data: [totalOther]
                    }, // Wrap in an array
                    {
                        name: 'Total Opening Income',
                        data: [totalIncome]
                    } // Wrap in an array
                ],
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
                    categories: [monthName], // Use the month name for the X-axis
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " BDT";
                        }
                    }
                }
            };

            // Clear existing chart before rendering
            $("#monthly_column_chart").html("");

            // Render the chart
            var chart = new ApexCharts(document.querySelector("#monthly_column_chart"), options);
            chart.render();
        }

        // Render the chart
        function monthly_line_chart_view_chart(currentYearData, previousYearData, maxData, currentYearLabel, previousYearLabel) {
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
                colors: ['#FF8383', '#77B6EA'],
                dataLabels: {
                    enabled: true
                },
                stroke: {
                    width: [3, 3],
                    curve: "smooth"
                },
                series: [{
                        name: previousYearLabel,
                        data: previousYearData
                        // data: [28, 29, 33, 36, 32, 32, 33, 33, 36, 32, 32, 33]
                    },
                    {
                        name: currentYearLabel,
                        data: currentYearData
                        // data: [12, 11, 14, 18, 17, 13, 13, 33, 36, 32, 32, 33]
                    }
                ],
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
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'aug', 'sep', 'oct', 'nov', 'dec'], // Use fixed month names
                    title: {
                        text: "Month"
                    }
                },
                yaxis: {
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

            // Clear existing chart before rendering
            $("#currentAndPreviousChart").html("");

            var chart = new ApexCharts(document.querySelector("#currentAndPreviousChart"), options);
            chart.render();

        }


        // Trigger chart reload on button click
        $('#graphicalViewButton').click(function() {
            fetchAndRenderChart();
        });

        // Trigger chart reload when dropdown values change
        $('#select-month, #select-year').on('change', function() {
            fetchAndRenderChart();
        });
    });
</script>
<?php $obj->end_script(); ?>