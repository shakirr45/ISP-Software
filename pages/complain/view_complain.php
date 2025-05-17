<?php
$allComplain = $obj->view_all_complain_by_cond('tbl_complains');
$pendingComplains   = $obj->view_all_by_status('1');
$processingComplains  = $obj->view_all_by_status('2');
$solvedComplains    = $obj->view_all_by_status('3');
$notSolvedComplains = $obj->view_all_by_status('4');
?>

<style>
    .table-responsive {
        overflow-x: auto;
        width: auto;
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
                            <label for="customer-filter">Customer</label>
                            <select id="customer-filter" class="form-control">
                                <option value="">Select</option>
                                <?php foreach ($obj->getAllData('vw_agent') as $user): ?>
                                    <option value="<?php echo $user['ag_id']; ?>"><?php echo $user['ag_name']; ?> </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label for="complain-category-filter">Complain Category</label>
                            <select id="complain-category-filter" class="form-control">
                                <option value="">Select</option>
                                <?php foreach ($obj->getAllData('tbl_complain_templates') as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['template']; ?> </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label for="status-filter">Status</label>
                            <select id="status-filter" class="form-control">
                                <option value="">Select</option>
                                <option value="1">Pending</option>
                                <option value="2">Processing</option>
                                <option value="3">Solved</option>
                                <option value="4">Not Solved</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label for="priority-filter">Priority</label>
                            <select id="priority-filter" class="form-control">
                                <option value="">Select</option>
                                <option value="1">High</option>
                                <option value="2">Medium</option>
                                <option value="3">Low</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label for="assign-filter">Solve By/Assign To</label>
                            <select id="assign-filter" class="form-control">
                                <option value="">Select</option>
                                <?php foreach ($obj->getAllData('tbl_employee') as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>"><?php echo $employee['employee_name']; ?> </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label for="date-from">From Date</label>
                            <input type="date" id="date-from" class="form-control">
                        </div>
                        <div class="col-sm-3">
                            <label for="date-to">To Date</label>
                            <input type="date" id="date-to" class="form-control">
                        </div>
                    </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>


<div class="col-md-12">
    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="mt-24">
                <div class="row mt-24 gy-0">
                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-start-1">
                            <div class="card-body p-0">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-8">
                                    <!-- Icon on the Left -->
                                    <span class="mb-0 w-48-px h-48-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                        <iconify-icon icon="mingcute:balance-fill" class="icon"></iconify-icon>
                                    </span>

                                    <!-- Text Content on the Right -->
                                    <div class="text-end flex-grow-1">
                                        <h3 class="fw-semibold">
                                            <span data-plugin="counterup"><?php echo sizeof($allComplain); ?></span>
                                        </h3>
                                        <p class="mb-2 fw-medium text-secondary-light text-sm">Total Complains</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-3">
                            <div class="card-body p-0">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-8">
                                    <!-- Icon on the Left -->
                                    <span class="mb-0 w-48-px h-48-px bg-yellow flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                        <iconify-icon icon="mdi:cogs" class="icon"></iconify-icon>
                                    </span>

                                    <!-- Text Content on the Right -->
                                    <div class="text-end">
                                        <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo sizeof($processingComplains); ?></span></h3>
                                        <p class="text-muted mb-1 text-truncate">Processing Complains</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-2">
                            <div class="card-body p-0">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-8">
                                    <!-- Icon on the Left -->
                                    <span class="mb-0 w-48-px h-48-px bg-success-main flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                        <iconify-icon icon="mdi:checkbox-marked-circle-outline" class="icon"></iconify-icon>
                                    </span>

                                    <!-- Text Content on the Right -->
                                    <div class="text-end">
                                        <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo sizeof($solvedComplains) ?></span></h3>
                                        <p class="text-muted mb-1 text-truncate">Solve Complains</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-start-2">
                            <div class="card-body p-0">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-8">
                                    <!-- Icon on the Left -->
                                    <span class="mb-0 w-48-px h-48-px bg-purple flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                        <iconify-icon icon="mdi:timer-sand" class="icon"></iconify-icon>
                                    </span>

                                    <!-- Text Content on the Right -->
                                    <div class="text-end">
                                        <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo sizeof($pendingComplains); ?></span></h3>
                                        <p class="text-muted mb-1 text-truncate">Pending Complains</p>
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
<!-- end row-->

<div class="col-md-12">
    <div class="card basic-data-table">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h5 class="card-title mb-0">All complain</h5>
        </div>
        <div class="card-body table-responsive">

            <table class="table bordered-table mb-0 " id="complain-datatable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>IP</th>
                        <th>Address</th>
                        <th>Mobile No</th>
                        <th>Problem</th>
                        <th>details</th>
                        <th>Complain Date</th>
                        <th>Solve By</th>
                        <th>Solve Date</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody> </tbody>
            </table>
        </div>
    </div>
</div>



<!-- end row-->

<button class="btn btn-success btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;" id="graphicalViewButton">Graphical View</button>

<div class="row">

    <div id="loading-spinner" class="spinner-border" role="status" style="display:none; position: fixed; top: 50%; left: 50%; z-index: 9999;">
        <span class="sr-only">Loading...</span>
    </div>
    <!-- this div for line chart -->
    <div id="complain_view_line_chart" class="col-md-5 col-xl-6 mt-3"></div>
    <div id="complain_view_donut_chart" class="col-md-7 col-xl-4 apex-charts pt-3" data-colors="#6658dd"></div>
</div>
<div class="row">

    <div id="loading-spinner" class="spinner-border" role="status" style="display:none; position: fixed; top: 50%; left: 50%; z-index: 9999;">
        <span class="sr-only">Loading...</span>
    </div>
    <!-- this div for line chart -->
    <div id="complain_view_culumn_chart" class="col-md-5 col-xl-6 mt-3"></div>
    <div id="complain_view_pie_chart" class="col-md-7 col-xl-4 apex-charts pt-3"></div>
</div>

<!-- Center modal content -->
<div class="modal fade" id="centermodal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="exampleModalLabel" class="modal-title fs-5">Change Status</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="mb-3 col-md-12">
                        <input type="hidden" id="modalComplainId">
                        <label for="ComplainStatus" class="form-label">Status</label>
                        <select id="ComplainStatus" class="form-select">
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
                <button type="button" id="changedStatus" class="btn btn-success">Save changes</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
</div>


<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        const table = $('#complain-datatable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: true,
            pagingType: "full_numbers",
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` +
                `<"row"<"col-sm-12 text-end"B>>` +
                `<"row dt-layout-row"<"col-sm-12"tr>>` +
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`,
            ajax: {
                url: "./pages/complain/getComplainAjax.php",
                type: "GET",
                data: function(d) {
                    d.category = $('#complain-category-filter').val();
                    d.status = $('#status-filter').val();
                    d.priority = $('#priority-filter').val();
                    d.assign = $('#assign-filter').val();
                    d.datefrom = $('#date-from').val();
                    d.dateto = $('#date-to').val();
                    d.customer = $('#customer-filter').val();
                }
            },
            columns: [{
                    data: 'sl',
                    orderable: false
                },
                {
                    data: 'customer_name',
                    orderable: true
                },
                {
                    data: 'customer_ip',
                    orderable: true
                },
                {
                    data: 'address',
                    orderable: true
                },
                {
                    data: 'phone',
                    orderable: true
                },
                {
                    data: 'complain_name',
                    orderable: true
                },
                {
                    data: 'details',
                    orderable: true,
                    className: 'text-truncate', // Apply a class for styling
                    render: function(data) {
                        return `<div style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${data}</div>`;
                    }
                },
                {
                    data: 'complain_date',
                    orderable: true,
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('en-US', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        }).replace(',', '') : '';
                    }
                },
                {
                    data: 'solve_by',
                    orderable: true
                },
                {
                    data: 'solve_date',
                    orderable: true,
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('en-US', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        }).replace(',', '') : '';
                    }
                },
                {
                    data: 'status',
                    orderable: true,
                    render: function(data) {
                        if (data == 1) {
                            return "<span class='text-danger'>Pending</span>";
                        } else if (data == 2) {
                            return "<span class='text-info'>Processing</span>";
                        } else if (data == 3) {
                            return "<span class='text-success'>Solved</span>";
                        } else {
                            return "<span class='text-warning'>Not Solved</span>";
                        }
                    }
                },
                {
                    data: 'priority',
                    orderable: true,
                    render: function(data) {
                        if (data == 1) {
                            return "<span class='text-danger'>High</span>";
                        } else if (data == 2) {
                            return "<span class='text-warning'>Medium</span>";
                        } else if (data == 3) {
                            return "<span class='text-info'>Low</span>";
                        } else {
                            return "<span class='text-info'>Default</span>";
                        }
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                        <a href="?page=edit_complain&id=${data}" class='btn btn-primary waves-effect waves-light btn-sm'>Edit</a>
                        <a data-bs-toggle="modal" href="#centermodal" data-id="${row.id}" class='btn btn-success waves-effect waves-light btn-sm status-modal'>Status</a>
                        <button id="deleteBtn" data-delete-id="${row.id}" class="btn btn-danger waves-effect waves-light btn-sm delete-modal">Delete</button>`;
                    }
                }
            ],
            buttons: [{
                    extend: 'copy',
                    className: 'btn btn-primary btn-sm'
                },
                {
                    extend: 'excel',
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-danger btn-sm'
                },
                {
                    extend: 'print',
                    className: 'btn btn-info btn-sm'
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search complaints...",
                lengthMenu: "Show _MENU_ entries",
                emptyTable: "No data available in table"
            },
            drawCallback: function() {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            },
            lengthMenu: [10, 25, 50, 100, 500],
            order: [
                [1, 'asc']
            ],
            initComplete: function(settings, json) {
                $('#totalcomplaints').text(json.totalcomplaints || 0);
                $('#totalresolved').text(json.totalresolved || 0);
            }
        });

        $('#changedStatus').on('click', function() {
            const complainId = $('#modalComplainId').val();
            const statusId = $('#ComplainStatus').val();

            if (!statusId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please select a status'
                });
                return;
            }

            $.ajax({
                url: './pages/complain/update_status.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    id: complainId,
                    status: statusId
                }),
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: response.message
                    });
                    $('#centermodal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred'
                    });
                }
            });
        });

        $(document).on('click', '.status-modal', function() {
            const id = $(this).data('id');
            $('#modalComplainId').val(id);
            $('#ComplainStatus').val('');
            $('#statusModal').modal('show');
        });

        $(document).on('click', '.delete-modal', function() {
            const deleteId = $(this).data('delete-id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: './pages/complain/delete_complain.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            complain_id: deleteId
                        }),
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Your record has been deleted.',
                                'success'
                            );
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Something went wrong. Please try again later.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        $('.form-control').on('change', function() {
            table.ajax.reload(null, false);
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Add event listener for the button click
        $('#graphicalViewButton').click(function() {
            $('#loading-spinner').show()
            // Perform AJAX request when the button is clicked
            $.ajax({
                type: "GET",
                url: "./pages/complain/complain_view_line_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    complain_view_line_chart(response.previousData, response.previousYear, response.currentData, response.currentYear, response.maxData);
                    console.log(response.previousData); // To check the response
                    console.log(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/complain/complain_view_donut_chart_ajax.php",
                dataType: "json",
                success: function(res) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    complain_view_donut_chart(res.pakageCountData);
                    // console.log(res.pakageCountData); // To check the response
                    // console.log(res); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/complain/complain_view_culumn_char_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    complain_view_culumn_chart(response.customerData);
                    // console.log(response.customerData); // To check the response
                    // alert(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/complain/complain_view_pie_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    complain_view_pie_chart(response.currentMonthCount, response.previousMonthCount, response.currentMonth, response.previousMonth);
                    // console.log(res.pakageCountData); // To check the response
                    // alert(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        });
    });


    function complain_view_pie_chart(currentData, lastData, currentMonth, lastMonth) {
        var options = {
            series: [currentData, lastData],
            chart: {
                width: 580,
                type: 'pie',
            },
            labels: ["Current Month", "Previous Month"],
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var chart = new ApexCharts(document.querySelector("#complain_view_pie_chart"), options);
        chart.render();
    }

    function complain_view_line_chart(previousData, previousYear, currentData, currentYear, maxData) {
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
                text: "Complain Counts by Month",
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
                    text: "Complain Counts"
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
        const chart = new ApexCharts(document.querySelector("#complain_view_line_chart"), options);
        chart.render();
    }

    function complain_view_donut_chart(pakageCountData) {


        // Prepare data for the chart
        const labels = pakageCountData.map((item) => item.template);
        const series = pakageCountData.map((item) => item.count);

        // Chart options
        const colors = ["#6658dd"];
        const options = {
            series: series,
            labels: labels,
            chart: {
                type: "donut",
            },
            legend: {
                position: "right",
            },
            title: {
                text: "Complain Counts by Templates",
                align: "right",
                style: {
                    fontSize: "14px",
                    color: "#666"
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: "bottom",
                    },
                },
            }, ],
        };

        // Render the chart
        const chart = new ApexCharts(
            document.querySelector("#complain_view_donut_chart"),
            options
        );
        chart.render();
    }

    function complain_view_culumn_chart(customerData) {
        const seriesData = customerData.map((customer) => customer.complain_count);
        const categories = customerData.map((customer) => [customer.name]);

        // Colors array (optional customization)
        const colors = ["#6658dd", "#34c38f", "#f46a6a", "#50a5f1", "#f1b44c", "#f46a6a", "#0c0b0b", "#6c757d"];

        var options = {
            series: [{
                data: seriesData
            }],
            chart: {
                height: 350,
                type: 'bar',
                events: {
                    click: function(chart, w, e) {
                        // console.log(chart, w, e)
                    }
                }
            },
            colors: colors,
            plotOptions: {
                bar: {
                    columnWidth: '45%',
                    distributed: true,
                }
            },
            title: {
                text: "Count of Complaints by Customer for the Current Month",
                align: "left",
                style: {
                    fontSize: "14px",
                    color: "#666"
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: false
            },
            xaxis: {
                categories: categories,
                labels: {
                    style: {
                        colors: colors,
                        fontSize: '12px'
                    }
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#complain_view_culumn_chart"), options);
        chart.render();

    }
</script>

<?php $obj->end_script(); ?>