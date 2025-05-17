<!-- chart-2 end -->
<style>
    #loading-bar {
        width: 100%;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 9999;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Customer List</h4>
                <p class="text-muted font-13 mb-4"> All Customer</p>

                <div class="row mb-4">
                    <div class="col-md-2">
                        <label for="zone-filter">Zone</label>
                        <select id="zone-filter" class="form-control">
                            <option value="">All Zones</option>
                            <?php foreach ($obj->getAllData('tbl_zone') as $zone): ?>
                                <option value="<?php echo $zone['zone_id']; ?>"><?php echo $zone['zone_name']; ?> </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="billing-person-filter">Billing Person</label>
                        <select id="billing-person-filter" class="form-control">
                            <option value="">All Billing Persons</option>
                            <?php foreach ($obj->getAllData('vw_user_info') as $bp): ?>
                                <option value="<?php echo $bp['UserId']; ?>"><?php echo $bp['FullName']; ?> </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status-filter">Status</label>
                        <select id="status-filter" class="form-control">
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date-from">From Date</label>
                        <input type="date" id="date-from" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="date-to">To Date</label>
                        <input type="date" id="date-to" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <h4>Total Monthly Bill: <span id="totalbill">0</span></h4>
                    </div>
                    <div class="col-md-2">
                        <h4>Total Connection Fee: <span id="totalconnectionfee">0</span></h4>
                    </div>
                </div>


                <div class="table-responsive">
                    <table class="table dt-responsive activate-select table-striped nowrap w-100 " id="complain-datatable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>details</th>
                                <th>customer id</th>
                                <th>    complain date</th>
                            </tr>
                        </thead>
                        <tbody> </tbody>
                    </table>
                </div>

            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>
<!-- end row-->
<button class="btn btn-info btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;" id="graphicalViewButton">Graphical View</button>
<!-- chart start -->
<!-- Loading Bar (hidden by default) -->

<div class="row" style="background-color: white;">

    <div id="loading-spinner" class="spinner-border" role="status" style="display:none; position: fixed; top: 50%; left: 50%; z-index: 9999;">
        <span class="sr-only">Loading...</span>
    </div>
    <!-- this div for line chart -->
    <div id="customer_view_line_chart" class="col-md-5 col-xl-6 mt-3"></div>
    <div id="cutomer_view_donut_chart" class="col-md-7 col-xl-4 apex-charts pt-3" data-colors="#6658dd"></div>





</div>
<div class="row" style="background-color: white;">
    <div id="loading-spinner" class="spinner-border" role="status" style="display:none; position: fixed; top: 50%; left: 50%; z-index: 9999;">
        <span class="sr-only">Loading...</span>
    </div>
    <div id="customer_view_column_chart" class="col-md-6 mt-3"></div>
    <div id="customer_view_pie_chart" class="col-md-4  apex-charts mt-3"></div>

</div>



<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#complain-datatable').DataTable({
            dom: 'Bflrtip',
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true, // Show the processing indicator
            serverSide: true, // Enable server-side processing
            ajax: {
                url: "./pages/complain/complain_report_ajax.php", // URL to your PHP file
                type: "GET",
                data: function(d) {
                    console.log('sadi-debug', d);
                    
                    d.zone = $('#zone-filter').val(); // Get zone filter value
                    d.bid = $('#billing-person-filter').val(); // Get billing person filter
                    d.status = $('#status-filter').val(); // Get status filter
                    d.datefrom = $('#date-from').val(); // Get date from filter
                    d.dateto = $('#date-to').val(); // Get date to filter
                }
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'details',
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'customer_id',
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'complain_date',
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
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
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'></i>",
                    next: "<i class='mdi mdi-chevron-right'></i>"
                },
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
            ], // Order by 'cus_id', adjust as needed
            initComplete: function(settings, json) {
                // This function is called once the table has been fully initialized
                $('#totalbill').text(json.totalbill);
                $('#totalconnectionfee').text(json.totalconnectionFee);
                console.log('tempAllDAta1',json.tempAllDAta);
            },
            drawCallback: function(settings) {
                var json = settings.json; // Get the JSON response

                // Update total bill and total connection fee after each table draw
                $('#totalbill').text(json.totalbill);
                $('#totalconnectionfee').text(json.totalconnectionFee);
                console.log('tempAllDAta2',json.tempAllDAta);
            }

            
        });

        // Trigger table reload when filters are changed
        $('#zone-filter, #billing-person-filter, #status-filter, #date-from, #date-to').on('change', function() {
            table.ajax.reload();
        });

        // Handle SMS button click event
        $('#complain-datatable').on('click', '.send-sms', function() {
            $.get("./pages/customer/customer_sms_ajax.php", {
                token: $(this).data('customerid')
            }, function(result) {
                if (result == true) {
                    alert('SMS was sent Successfull');
                } else {
                    alert(result);
                }
            });
        });

    });
</script>


<!-- customer line chart -->
<script>
    $(document).ready(function() {
        // Add event listener for the button click
        $('#graphicalViewButton').click(function() {
            $('#loading-spinner').show()
            // Perform AJAX request when the button is clicked
            $.ajax({
                type: "GET",
                url: "./pages/customer/customer_view_line_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    cutomer_view_line_chart(response.previousData, response.previousYear, response.currentData, response.currentYear, response.maxData);
                    console.log(response.previousData); // To check the response
                    console.log(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/customer/customer_view_donut_chart_ajax.php",
                dataType: "json",
                success: function(res) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    cutomer_view_donut_chart(res.pakageCountData, res.mbbsCountData);
                    console.log(res.pakageCountData); // To check the response
                    console.log(res); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/customer/customer_view_column_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    customer_view_column_chart(response.previousData, response.previousYear, response.currentData, response.currentYear, response.maxData);
                    // console.log(res.pakageCountData); // To check the response
                    // alert(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/customer/customer_view_pie_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    customer_view_pie_chart(response.agCountData, response.zoneCountData);
                    // console.log(res.pakageCountData); // To check the response
                    // alert(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        });
    });



    function cutomer_view_line_chart(previousData, previousYear, currentData, currentYear, maxData) {
        // Define colors
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

        // Calculate the tick amount for y-axis dynamically
        const tickAmount = Math.ceil(maxData / 1000); // Adjust this based on your maxData

        // Chart options
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
            title: {
                text: "Agent Count by Month",
                align: "left",
                style: {
                    fontSize: "14px",
                    color: "#666"
                }
            },
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
                    text: "Customer Count"
                },
                min: 0, // Start y-axis from 0 for better readability
                max: maxData, // Use maxData to dynamically set the maximum value of the y-axis
                tickAmount: tickAmount // Calculate dynamic ticks based on maxData
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

        // Render the chart
        const chart = new ApexCharts(document.querySelector("#customer_view_line_chart"), options);
        chart.render();
    }
    //end line chart 


    function cutomer_view_donut_chart(pakageCountData, mbbsCountData) {
        colors = ["#6658dd"];
        // console.log(pakageCountData);
        // const mbbsCountDataArr = Array.from(mbbsCountData);
        const normalizedData = mbbsCountData.map(item => parseFloat(item.mb.trim()) || 0).filter(value => value > 0);

        (dataColors = $("#cutomer_view_donut_chart").data("colors")) &&
        (colors = dataColors.split(","));
        var options = {
            series: pakageCountData,
            labels: normalizedData.map((item) => "Mbbs: " + item),
            chart: {
                type: 'donut',
            },
            legend: {
                position: 'right',
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };
        (chart = new ApexCharts(
            document.querySelector("#cutomer_view_donut_chart"),
            options
        )).render();

    }

    function customer_view_pie_chart(agCountData, zoneCountData) {
        var options = {
            series: agCountData,
            chart: {
                width: 580,
                type: 'pie',
            },
            labels: zoneCountData.map((item) => "Zone: " + item),
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var chart = new ApexCharts(document.querySelector("#customer_view_pie_chart"), options);
        chart.render();
    }



    function customer_view_column_chart(previousData, previousYear, currentData, currentYear, maxData) {
        // Static month names for both years
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        // Convert the comma-separated strings to arrays of numbers
        const previousDataArray = previousData.split(',').map(Number);
        const currentDataArray = currentData.split(',').map(Number);
        var options = {
            series: [{
                name: `Previous - ${previousYear}`,
                data: previousDataArray
            }, {
                name: `Current - ${currentYear}`,
                data: currentDataArray
            }],
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
            yaxis: {
                title: {
                    text: "Inactive Customer Count"
                }
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

        var chart = new ApexCharts(document.querySelector("#customer_view_column_chart"), options);
        chart.render();
    }
</script>



<?php $obj->end_script(); ?>