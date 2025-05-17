<?php
$currentYear = date("Y");
$previousYear = $currentYear - 1;
$PreviousYear = $obj->rawSql('select count(agid), MONTH(generate_at) as month from customer_billing where YEAR(generate_at)=2023 group by MONTH(generate_at)');
$CurrentYear = $obj->rawSql('select count(agid), MONTH(generate_at) as month from customer_billing where YEAR(generate_at)=2024 group by MONTH(generate_at)');


// Create arrays to store the counts for each month (1-12)
$previousYearData = array_fill(0, 12, 0); // Default all months to 0
$currentYearData = array_fill(0, 12, 0); // Default all months to 0

// Map the fetched data to the corresponding months (index 0 represents January)
foreach ($PreviousYear as $data) {
    $previousYearData[$data['month'] - 1] = $data["count(agid)"];
}
foreach ($CurrentYear as $data) {
    $currentYearData[$data['month'] - 1] = $data["count(agid)"];
}

// Prepare data for JavaScript
$previousData = implode(',', $previousYearData);
$currentData = implode(',', $currentYearData);

// Combine all data for calculating max value
$allData = array_merge($previousYearData, $currentYearData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.50); // Add 50% margin to max value

// char end php
?>

<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <div>
                                <label for="date-from">From Date</label>
                                <input type="date" id="date-from" class="form-control">

                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div>
                                <label for="date-to">To Date</label>
                                <input type="date" id="date-to" class="form-control">
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
    <div class="card-body table-responsive">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Discount List</h5>
    </div>
    <div class="card-body table-responsive">
        <table class="table bordered-table mb-0 datatable"
            id="customer-datatable"
            data-page-length="10">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th style="text-align: left;">Date</th>
                    <th>Agent Name</th>
                    <th>Agent address</th>
                    <th>Agent Phone</th>
                    <th>Agent Email</th>
                    <th>Bonus Amount</th>
                    <!-- <th>Total bonus</th> -->

                </tr>
            </thead>
            <tbody> </tbody>
        </table>
    </div>
    </div>

</div>
</div>
 <!-- end card body-->



<!-- chart start  future intigration 2025 (pore add hobe)-->
<button class="btn btn-success btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;"
    id="graphicalViewButton">Graphical View</button>


<div class="col-md-12 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Monthly Discount Comparison</h6>
        </div>
        <div class="card-body p-24">
            <div id="discount_view_line_chart"></div>
        </div>
    </div>
</div>
<!-- chart end -->

<?php $obj->start_script(); ?>



<script>
    $(document).ready(function() {
        var table = $('#customer-datatable').DataTable({
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` + // Show entries and search in one row
                `<"row"<"col-sm-12 text-end"B>>` + // Buttons in a separate row
                `<"row dt-layout-row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right,
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true, // Show the processing indicator
            serverSide: true, // Enable server-side processing
            ajax: {
                url: "./pages/bonus/ajax_bonus.php", // URL to your PHP file
                type: "GET",
                data: function(d) {
                    d.datefrom = $('#date-from').val(); // Get date from filter
                    d.dateto = $('#date-to').val(); // Get date to filter
                }
            },
            columns: [{
                    data: 'sl',
                    orderable: false
                },
                {
                    data: 'generate_at'
                },
                {
                    data: 'ag_name'
                },
                {
                    data: 'ag_office_address'
                },
                {
                    data: 'ag_mobile_no'
                },
                {
                    data: 'ag_email'
                },
                {
                    data: 'totaldiscount',
                    render: function(data, type, row) {
                        return '<a class="btn btn-info waves-effect waves-light btn-sm" href="?page=same_bonus&token=' + row['ag_id'] + '"> ' + data + ' </a>';
                    }
                }
            ],
            buttons: [{
                    extend: "copy",
                    className: "btn-light"
                },
                {
                    extend: "print",
                    className: "btn-light"
                },
                {
                    extend: "pdf",
                    className: "btn-light"
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search customers...",
                lengthMenu: "Show _MENU_ entries",
                emptyTable: "No data available in table"
            },
            drawCallback: function() {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            },
            lengthMenu: [10, 25, 50, 100, 500],
            order: [
                [1, 'asc']
            ], // Order by 'generate_at', adjust as needed
            initComplete: function(settings, json) {
                $('#totalBonus').text(json.totalBonusAmount);
            },
            drawCallback: function(settings) {
                var json = settings.json; // Get the JSON response
                $('#totalBonus').text(json.totalBonusAmount);
            }
        });

        // Trigger table reload when filters are changed
        $('#date-from, #date-to').on('change', function() {
            table.ajax.reload();
        });
    });
</script>




<!-- chart start -->
<script>
    $(document).ready(function() {
        $('#graphicalViewButton').click(function() {
            $('.graphicalChart').show()

            $.ajax({
                type: "GET",
                url: "./pages/bonus/two_year_column_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    discount_view_line_chart(response?.discountData?.previousYear, response?.previousYear, response?.discountData?.currentYear, response?.currentYear, response?.maxData);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });

        })

        function discount_view_line_chart(previousData, previousYear, currentData, currentYear, maxData) {
            // Define colors for the series
            let colors = ["#6658dd", "#1abc9c"];
            const dataColors = $("#apex-line-test").data("colors");
            if (dataColors) {
                colors = dataColors.split(",");
            }

            // Static month names for both years
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
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
                    data: previousData,
                }, {
                    name: `Current - ${currentYear}`,
                    data: currentData
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

            var chart = new ApexCharts(document.querySelector("#discount_view_line_chart"), options);
            chart.render();
        }
    })
</script>
<!-- chart end -->






<?php $obj->end_script(); ?>