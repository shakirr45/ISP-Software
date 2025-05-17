<?php

include('customer.php');
if (isset($_POST['csvUpload'])) {
    $csvFile = $_FILES['csv-upload']['tmp_name'];
    if ($_FILES["csv-upload"]["size"] > 0) {
        $file = fopen($csvFile, "r");
        fgetcsv($file); // Skip the header row
        while (($row = fgetcsv($file)) !== false) {
            $data = $obj->rawSql("SELECT ag_id FROM tbl_agent ORDER BY ag_id DESC LIMIT 1");
            $ag_id = $data ? $data[0]["ag_id"] + 1 : 1;
            $STD = "CUS" . str_pad($ag_id, 5, '0', STR_PAD_LEFT);

            $entry_date = isset($row[12]) && strtotime($row[12]) ? date('Y-m-d', strtotime($row[12])) : null;
            $connection_date = isset($row[13]) && strtotime($row[13]) ? date('Y-m-d', strtotime($row[13])) : null;

            $fromInsert = [
                'cus_id' => $STD,
                'ag_name' => $row[0],
                'ip' => $row[1],
                'type' => 1,
                'mikrotik_disconnect' => $row[2],
                'taka' => $obj->convertBanglaToEnglishNumbers($row[3]),
                'mb' => $row[4],
                'int_mb' => intval($row[4]),
                'ag_status' => $row[5],
                'ag_mobile_no' => $obj->convertBanglaToEnglishNumbers($row[6]),
                'regular_mobile' => $obj->convertBanglaToEnglishNumbers($row[7]),
                'ag_office_address' => $row[8],
                'ag_email' => $row[9],
                'national_id' => $row[10],
                'gender' => $row[11],
                'billing_person_id' => $userId,
                'entry_by' => $userId,
                'entry_date' => $entry_date,
                'connection_date' => $connection_date,
            ];

            // if ($mikrotikConnect) {
            $fromInsert['mikrotik_id'] = 1;
            $fromInsert['queue_password'] = $row[14];
            // }

            $lastinsert = $obj->insertData('tbl_agent', $fromInsert);
            if ($lastinsert) {
                $obj->insertData('customer_billing', [
                    'agid' => $lastinsert,
                    'cusid' => $STD,
                    'monthlybill' => $obj->convertBanglaToEnglishNumbers($row[3]),
                    'generate_at' => '2024-01-01'
                ]);
                // if ($mikrotikConnect) {
                $obj->createNewSecret(1, $row[1], $row[14], $row[4]);
                if (($row[5] == 0) || ($row[5] == 3)) {
                    $obj->disableSingleSecret(1, $row[1]);
                }
                // }
            }
        }

        $obj->notificationStore("New Customer Add Successful", 'success');
        echo '<script>window.location="?page=customer_view";</script>';
    }
}


?>
<!-- chart-2 end -->
<style>
    .table-responsive {
        overflow-x: auto;
    }
</style>

<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label for="zone-filter" class="form-label">Zone</label>
                            <div>
                                <select id="zone-filter" class="form-control">
                                    <option value="">All Zones</option>
                                    <?php foreach ($obj->getAllData('tbl_zone') as $zone): ?>
                                        <option value="<?php echo $zone['zone_id']; ?>"><?php echo $zone['zone_name']; ?> </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label for="billing-person-filter" class="form-label">Billing Person</label>
                            <div class="position-relative">
                                <select id="billing-person-filter" class="form-control">
                                    <option value="">All Billing Persons</option>
                                    <?php foreach ($obj->getAllData('vw_user_info') as $bp): ?>
                                        <option value="<?php echo $bp['UserId']; ?>"><?php echo $bp['FullName']; ?> </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="status-filter" class="form-label">Status</label>
                            <div class="position-relative">
                                <select id="status-filter" class="form-control">
                                    <option value="">All</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="date-from" class="form-label">From Date</label>
                            <div class="position-relative">
                                <input type="date" id="date-from" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="date-to" class="form-label">To Date</label>
                            <div class="position-relative">
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
    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="mt-24">
                <div class="row mt-24 gy-0">
                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-1">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mb-0 w-48-px h-48-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                            <iconify-icon icon="hugeicons:invoice-03" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="mb-2 fw-medium text-secondary-light text-sm">Total Bill Collection</span>
                                            <h6 class="fw-semibold" id="totalbill">0</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-1">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mb-0 w-48-px h-48-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                            <iconify-icon icon="hugeicons:invoice-04" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="mb-2 fw-medium text-secondary-light text-sm">Total Active Customers</span>
                                            <h6 class="fw-semibold" id="totalcustomer"><?php echo $totalActiveCustomers['total']; ?></h6>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-1">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mb-0 w-48-px h-48-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                            <iconify-icon icon="hugeicons:invoice-01" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="mb-2 fw-medium text-secondary-light text-sm">Total Inactive Customers</span>
                                            <h6 class="fw-semibold" id="totalcustomer"><?php echo $totalInactiveCustomers['total']; ?></h6>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-start-5">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mb-0 w-48-px h-48-px border-pink-light-white bg-pink-light flex-shrink-0 text-pink d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                            <iconify-icon icon="ri:discount-percent-fill" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="mb-2 fw-medium text-secondary-light text-sm">Total Connection Charge</span>
                                            <h6 class="fw-semibold" id="totalconnectionfee">0</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<div class="col-md-12">
    <div class="card basic-data-table">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h5 class="card-title mb-0">Customer List</h5>
            <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#import">
                Import Customer <i class="fa-solid fa-arrow-up"></i> </button>
        </div>
        <div class="card-body table-responsive">
            <table
                class="table bordered-table mb-0"
                id="customer-datatable"
                data-page-length="10">
                <thead>
                    <tr>
                        <th scope="col">SL.</th>
                        <?php if ($obj->userWorkPermission('edit') || $obj->userWorkPermission('delete')) { ?>
                            <th scope="col">Action</th>
                        <?php } ?>
                        <th scope="col">ID</th>
                        <th scope="col">IP</th>
                        <th scope="col">Name</th>
                        <th scope="col">Address</th>
                        <th scope="col">Mobile No</th>
                        <th scope="col">Package</th>
                        <th scope="col">Bill Amount</th>
                        <th scope="col">Con.Date</th>
                        <th scope="col">Zone</th>
                        <th scope="col">B.Person</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="import" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="saleModalLabel">Upload CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 class="modal-title fs-5" id="saleModalLabel">CSV Structure</h5>
                <div class="card-body table-responsive">

                    <table class="table bordered-table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>IP</th>
                                <th>Dis. day</th>
                                <th>Bill</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Mobile</th>
                                <th>Mobile(Opt)</th>
                                <th>Address</th>
                                <th>Email</th>
                                <th>National ID</th>
                                <th>gender</th>
                                <th>Entry Date</th>
                                <th>Con. Date</th>
                                <th>Queue Password</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <form class="flex-row" action="" enctype="multipart/form-data" method="post">
                    <div class="col-xxl-4">
                        <label for="csv-upload" class="form-label">Upload CSV</label>
                        <div class="position-relative">
                            <input type="file" id="csv-upload" class="form-control" name="csv-upload">
                        </div>
                    </div>
                    <div class="col-md-2" style="margin-top: 22px;">

                        <button type="submit" name="csvUpload" class="btn btn-success">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- end row-->
<!-- <button class="btn btn-info btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;"
        id="graphicalViewButton">Graphical View</button> -->
<!-- chart start -->
<!-- Loading Bar (hidden by default) -->

<!-- <div class="row" style="background-color: white;">

    <div id="loading-spinner" class="spinner-border" role="status"
        style="display:none; position: fixed; top: 50%; left: 50%; z-index: 9999;">
        <span class="sr-only">Loading...</span>
    </div>

<div id="customer_view_line_chart" class="col-md-5 col-xl-6 mt-3"></div>
<div id="cutomer_view_donut_chart" class="col-md-7 col-xl-4 apex-charts pt-3" data-colors="#6658dd"></div>





</div>
<div class="row" style="background-color: white;">
    <div id="loading-spinner" class="spinner-border" role="status"
        style="display:none; position: fixed; top: 50%; left: 50%; z-index: 9999;">
        <span class="sr-only">Loading...</span>
    </div>
    <div id="customer_view_column_chart" class="col-md-6 mt-3"></div>
    <div id="customer_view_pie_chart" class="col-md-4  apex-charts mt-3"></div>

</div> -->



<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#customer-datatable').DataTable({
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` + // Show entries and search in one row
                `<"row"<"col-sm-12 text-end"B>>` + // Buttons in a separate row
                `<"row dt-layout-row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right

            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: false,
            pagingType: "full_numbers",
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            ajax: {
                url: "./pages/customer/customer_ajax.php", // URL to your PHP file
                type: "GET",
                data: function(d) {
                    d.zone = $('#zone-filter').val(); // Get zone filter value
                    d.bid = $('#billing-person-filter').val(); // Get billing person filter
                    d.status = $('#status-filter').val(); // Get status filter
                    d.datefrom = $('#date-from').val(); // Get date from filter
                    d.dateto = $('#date-to').val(); // Get date to filter
                }
            },
            columns: [{
                    data: 'sl',
                    orderable: false
                },
                <?php if ($obj->userWorkPermission('edit') || $obj->userWorkPermission('delete')) { ?> {
                        data: 'ag_id',
                        render: function(data, type, row) {
                            return ` <?php if ($obj->userWorkPermission('edit')) { ?>
                    <a
                    href="?page=customer_edit&token=` + data + `"
                    class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center idupdate">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <?php } ?>
                 <?php if ($obj->userWorkPermission('delete')) { ?>
                <a onclick="return confirmDelete()"
                    href="?page=customer_view&delete-token=` + data + `"
                    class="w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center"
                    >
                    <iconify-icon
                        icon="mingcute:delete-2-line"
                    ></iconify-icon>
                    </a>
                <?php } ?>
                `;
                        }
                    },
                <?php } ?> {
                    data: 'cus_id',
                    orderable: true,
                    render: function(data, type, row) {
                        return '<a class="btn btn-info waves-effect waves-light btn-sm" href="?page=customer_ledger&token=' + row['ag_id'] + '"> ' + data + ' </a>' +
                            ' <a href="#" class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center send-sms" data-customerid=' + row['ag_id'] + '><iconify-icon icon="mdi:sms"></iconify-icon></a>';
                    }
                },
                {
                    data: 'ip',
                    orderable: true
                },
                {
                    data: 'ag_name',
                    orderable: true,
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'ag_office_address',
                    orderable: true,
                    render: function(data, type, row) {
                        return (data != null) ? "<span class='text-wrap'>" + data + "</span>" : "N/A";
                    }
                },
                {
                    data: 'ag_mobile_no',
                    defaultContent: 'N/A',
                    orderable: true
                },
                {
                    data: 'mb',
                    defaultContent: 'N/A',
                    orderable: true
                },
                {
                    data: 'taka',
                    defaultContent: 'N/A',
                    orderable: true
                }, // Bill Amount
                {
                    data: 'connection_date',
                    orderable: true
                },
                {
                    data: 'zone_name',
                    orderable: true,
                    defaultContent: 'N/A',
                    render: function(data, type, row) {
                        return (data != null) ? "<span class='text-wrap'>" + data + "</span>" : "N/A";
                    }
                },
                {
                    data: 'FullName',
                    render: function(data, type, row) {
                        return (data != null) ? "<span class='text-wrap'>" + data + "</span>" : "N/A";
                    }
                },
                {
                    data: 'ag_status',
                    render: function(data, type, row) {
                        if (data == 1) {
                            return "<span class='text-success'>Active</span>";
                        } else {
                            return "<span class='text-danger'>Inactive</span>";
                        }
                    }
                },
            ],
            buttons: [{
                    extend: "copy"
                },
                {
                    extend: "print"
                },
                {
                    extend: "pdf"
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search customers...",
                lengthMenu: "Show _MENU_ entries",
                emptyTable: "No data available in table"
            },
            lengthMenu: [10, 25, 50, 100, 500],
            order: [
                [1, 'asc']
            ], // Order by 'cus_id', adjust as needed
            initComplete: function(settings, json) {
                // This function is called once the table has been fully initialized
                $('#totalbill').text(json.totalbill);
                $('#totalconnectionfee').text(json.totalconnectionFee);
            },
            drawCallback: function(settings) {
                var json = settings.json; // Get the JSON response

                // Update total bill and total connection fee after each table draw
                $('#totalbill').text(json.totalbill);
                $('#totalconnectionfee').text(json.totalconnectionFee);
            }
        });

        // Trigger table reload when filters are changed
        $('#zone-filter, #billing-person-filter, #status-filter, #date-from, #date-to').on('change', function() {
            table.ajax.reload();
        });


        // Handle SMS button click event
        $('#customer-datatable').on('click', '.send-sms', function() {
            $.get("./pages/customer/customer_sms_ajax.php", {
                token: $(this).data('customerid')
            }, function(result) {
                result = JSON.parse(result)
                if (result.response == true) {
                    Swal.fire({
                        icon: "success",
                        title: 'SMS was sent Successfull',
                        timer: 1500,
                        showConfirmButton: false,
                        position: "top-end"
                    });
                } else {
                    // alert(result.response.error_message);
                    Swal.fire({
                        icon: "error",
                        title: result.response.error_message,
                        timer: 1500,
                        showConfirmButton: false,
                        position: "top-end"
                    });
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