
<?php include('billcollection.php') ?>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <h6 class="text-md text-neutral-500">Customer List</h6>
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label for="zone-filter" class="form-label">Zone</label>
                            <div>
                                <select id="zone-filter" class="form-control">
                                    <option value="">All Zones</option>
                                    <?php foreach ($pzones as $zone): ?>
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
                                    <?php foreach ($pbillingPerson as $bp): ?>
                                        <option value="<?php echo $bp['UserId']; ?>"><?php echo $bp['FullName']; ?> </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="paid-filter" class="form-label">Advance Paid</label>
                            <div class="position-relative">
                                <select id="paid-filter" class="form-control">
                                    <option value="">All</option>
                                    <option value="2">Advance Paid</option>
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
                                            <span class="mb-2 fw-medium text-secondary-light text-sm">Total Monthly Bill</span>
                                            <h6 class="fw-semibold" id="totalbill">0</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-2">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mb-0 w-48-px h-48-px bg-success-main flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6">
                                            <iconify-icon icon="solar:wallet-bold" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="mb-2 fw-medium text-secondary-light text-sm">Total C.Month Paid:</span>
                                            <h6 class="fw-semibold" id="totalpaid">0</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-3">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mb-0 w-48-px h-48-px bg-yellow text-white flex-shrink-0 d-flex justify-content-center align-items-center rounded-circle h6">
                                            <iconify-icon icon="iconamoon:discount-fill" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="mb-2 fw-medium text-secondary-light text-sm">Total Advance</span>
                                            <h6 class="fw-semibold" id="totaladvance">0</h6>
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
            <h5 class="card-title mb-0">Paid Biller List</h5>
        </div>
        <div class="card-body table-responsive">
            <table
                class="table bordered-table mb-0"
                id="customer-datatable"
                data-page-length="10">
                <thead>
                    <tr>
                        <th scope="col">SL.</th>
                        <th scope="col">ID</th>
                        <th scope="col">IP</th>
                        <th scope="col">Name</th>
                        <th scope="col">Address</th>
                        <th scope="col">Mobile No</th>
                        <th scope="col">Package</th>
                        <th scope="col">Monthly Bill</th>
                        <th scope="col">Advance</th>
                        <th scope="col">Bill Date</th>
                        <th scope="col">C.M Paid</th>
                        <th scope="col">Paid date</th>
                        <th scope="col">Collected By</th>
                        <th scope="col">Zone</th>
                        <th scope="col">B.Person</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- end row-->


<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#customer-datatable').DataTable({
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` + // Show entries and search in one row
                `<"row"<"col-sm-12 text-end"B>>` + // Buttons in a separate row
                `<"row dt-layout-row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right
            searching: true,
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true, // Show the processing indicator
            serverSide: true, // Enable server-side processing
            ajax: {
                url: "./pages/billcollection/billpaid_ajax.php", // URL to your PHP file
                type: "GET",
                data: function(d) {
                    d.zone = $('#zone-filter').val(); // Get zone filter value
                    d.bid = $('#billing-person-filter').val(); // Get billing person filter
                    d.status = $('#paid-filter').val(); // Get status filter
                }
            },
            columns: [{
                    data: 'sl',
                    orderable: false
                },
                {
                    data: 'cus_id',
                    orderable: true,
                    render: function(data, type, row) {
                        return ` 
                        <a href="pages/pdf/invoice.php?token=` + row['ag_id'] + `" target="_blank">
                    <button class="btn btn-secondary waves-effect waves-light btn-sm"><i class="fas fa-print"></i></button></a>
                        <a class="btn btn-info waves-effect waves-light btn-sm" href="?page=customer_ledger&token=` + row['ag_id'] + `"> ` + data + ` </a> <br>
                            <button class="btn btn-success waves-effect waves-light btn-sm send-sms mt-1" data-customerid=' + row['ag_id'] + '><i class="fas fa-envelope"></i></button> `;
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
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'ag_mobile_no',
                    orderable: true
                },
                {
                    data: 'mb',
                    orderable: true
                },
                {
                    data: 'taka',
                    orderable: true
                }, // Bill Amount
                {
                    data: 'dueadvance',
                    orderable: true
                },
                {
                    data: 'mikrotik_disconnect',
                    orderable: true
                },
                {
                    data: 'cmpaid',
                    orderable: false
                },
                {
                    data: 'cmpaiddate',
                    orderable: false
                },
                {
                    data: 'collectedby',
                    orderable: true
                },
                {
                    data: 'zone_name',
                    orderable: true,
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'billingperson',
                    orderable: true,
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
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
            ], // Order by 'cus_id', adjust as needed
            initComplete: function(settings, json) {
                // This function is called once the table has been fully initialized
                $('#totalbill').text(json.totalbill);
                $('#totalpaid').text(json.totalpaid);
                $('#totaladvance').text(json.totaladvance);
                // console.log(json);
            },
            drawCallback: function(settings) {
                var json = settings.json; // Get the JSON response
                // console.log(json);

                // Update total bill and total connection fee after each table draw
                $('#totalbill').text(json.totalbill);
                $('#totalpaid').text(json.totalpaid);
                $('#totaladvance').text(json.totaladvance);
            }
        });

        // Trigger table reload when filters are changed
        $('#zone-filter, #billing-person-filter, #paid-filter').on('change', function() {
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
<?php $obj->end_script(); ?>