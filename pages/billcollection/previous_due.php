<?php include('billcollection.php') ?>
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
                                    <?php foreach ($przones as $zone): ?>
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
                                    <?php foreach ($prBillingperson as $bp): ?>
                                        <option value="<?php echo $bp['UserId']; ?>"><?php echo $bp['FullName']; ?> </option>
                                    <?php endforeach; ?>
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
<div class="col=md-12">
    <div class="card h-100 p-0 radius-12">
    <div class="card-body p-24">
        <div class="mt-24">
            <div class="row mt-24 gy-0">
                <div class="col-xxl-3 col-sm-6">
                    <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-3">
                        <div class="card-body p-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                <div class="d-flex align-items-center gap-2">
                                    <span class="mb-0 w-48-px h-48-px bg-yellow text-white flex-shrink-0 d-flex justify-content-center align-items-center rounded-circle h6">
                                        <iconify-icon icon="iconamoon:discount-fill" class="icon"></iconify-icon>
                                    </span>
                                    <div>
                                        <span class="mb-2 fw-medium text-secondary-light text-sm">Total Due Bill</span>
                                        <h6 class="fw-semibold" id="totalbill">0</h6>
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
                    <?php if ($obj->userWorkPermission('edit')) { ?>
                        <th scope="col">Action</th>
                    <?php } ?>
                    <th scope="col">ID</th>
                    <th scope="col">IP</th>
                    <th scope="col">Name</th>
                    <th scope="col">Address</th>
                    <th scope="col">Mobile No</th>
                    <th scope="col">Package</th>
                    <th scope="col">Monthly Bill</th>
                    <th scope="col">Total Due</th>
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


<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body m-3">
                <form id="paymentForm">
                    <input type="hidden" id="ag_id" name="ag_id" class="form-control">
                    <div class="form-group mt-3 mb-1">
                        <label for="amount">Due Amount:</label> <strong><span id="due-amount">0</span> BDT*</strong>
                        <label for="amount">Pay Amount:</label>
                        <input type="text" id="amount" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group mb-1">
                        <label for="discount">Discount Amount:</label>
                        <input type="text" id="discount" name="discount" class="form-control" value="0">
                    </div>
                    <div class="form-group mb-1">
                        <label for="discription">Description:</label>
                        <textarea id="discription" name="discription" class="form-control"></textarea>
                    </div>
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary" id="submit-btn">Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>




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
            responsive: true,
            pagingType: "full_numbers",
            processing: true, // Show the processing indicator
            serverSide: true, // Enable server-side processing
            ajax: {
                url: "./pages/billcollection/previous_due_ajax.php", // URL to your PHP file
                type: "GET",
                data: function(d) {
                    d.zone = $('#zone-filter').val(); // Get zone filter value
                    d.bid = $('#billing-person-filter').val(); // Get billing person filter
                    d.monthyear = $('#month-year').val(); // Get date from filter
                }
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'ag_id',
                    render: function(data, type, row) {
                        return `<button class='btn btn-success waves-effect waves-light btn-sm' data-name='` + row['ag_name'] + `'  data-id='` + row['ag_id'] + `' data-due='` + row['dueadvance'] + `' onclick="openPaymentModal(this)">Pay</button>`;
                    }
                },
                {
                    data: 'cus_id',
                    render: function(data, type, row) {
                        return '<a class="btn btn-info waves-effect waves-light btn-sm" href="?page=customer_ledger&token=' + row['ag_id'] + '"> ' + data + ' </a> <br>' +
                            ' <button class="btn btn-secondary waves-effect waves-light btn-sm send-sms mt-1" data-customerid=' + row['ag_id'] + '><i class="fas fa-envelope"></i></button>'
                    }
                },
                {
                    data: 'ip'
                },
                {
                    data: 'ag_name',
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'ag_office_address',
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'ag_mobile_no'
                },
                {
                    data: 'mb'
                },
                {
                    data: 'monthlybill'
                }, // Bill Amount
                {
                    data: 'dueadvance'
                }, // Bill Amount
                {
                    data: 'zone_name',
                    render: function(data, type, row) {
                        return "<span class='text-wrap'>" + data + "</span>";
                    }
                },
                {
                    data: 'billingperson',
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
            ],
            initComplete: function(settings, json) {
                $('#totalbill').text(json.totalbill);
            },
            drawCallback: function(settings) {
                var json = settings.json;
                // Update total bill after each table draw
                $('#totalbill').text(json.totalbill);
                // console.log(json);

            }
        });

        // Trigger table reload when filters are changed
        $('#zone-filter, #billing-person-filter, #month-year').on('change', function() {
            table.ajax.reload();
        });
    });
</script>

<script>
    var clickbtn;

    function openPaymentModal(button) {
        clickbtn = button;
        button.disabled = true;
        // Retrieve data-id and data-due values from the button
        let paymentId = button.getAttribute('data-id');
        let paymentName = button.getAttribute('data-name');
        let dueAmount = button.getAttribute('data-due');

        // Set modal input values
        document.getElementById('ag_id').value = paymentId;
        document.getElementById('due-amount').innerText = dueAmount;
        document.getElementById('amount').value = dueAmount;

        const date = new Date();
        document.getElementById('discription').innerText = `Bill collection for ${date.toLocaleString('default', { month: 'long' })}-${date.getFullYear()} From Customer ${paymentName}`;


        // Reset submit button state
        const submitButton = document.getElementById('submit-btn');
        submitButton.disabled = false;
        submitButton.innerText = 'Submit Payment';

        // Show the modal
        $('#paymentModal').modal('show');
    }


    $('#paymentForm').on('submit', function(e) {

        const submitButton = document.getElementById('submit-btn');
        submitButton.disabled = true;
        submitButton.innerText = 'Processing...';
        e.preventDefault();
        let formData = $(this).serialize();

        $.ajax({
            url: './pages/billcollection/billpay_ajax.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                // console.log(response);

                if (response.success) {
                    document.getElementById('submit-btn').innerText = 'Completed';
                    //   alert(response.message);
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: response.message || "Pay Operations processed successfully!",
                        showConfirmButton: false,
                        timer: 1500,
                    });
                    $('#customer-datatable').DataTable().ajax.reload();
                    $('#paymentModal').modal('hide');
                }
            },
            error: function() {
                alert('Error processing payment.');
                clickbtn.disabled = false;
            }
        });
    });

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
</script>
<?php $obj->end_script(); ?>