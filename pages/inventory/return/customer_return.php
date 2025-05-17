<?php
$bcustomers = $obj->view_all('tbl_agent');
$bproducts = $obj->view_all('products');
$bbatches = $obj->raw_sql("SELECT * FROM `stock` WHERE `deleted_at` IS NULL");
?>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <h6 class="text-md text-neutral-500">Customer List</h6>
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label for="customer-filter" class="form-label">Customer</label>
                            <div class="position-relative">
                                <select id="customer-filter" class="form-control">
                                    <option value="">All Customers</option>
                                    <?php foreach ($bcustomers as $customer): ?>
                                        <option value="<?php echo $customer['ag_id']; ?>"><?php echo $customer['ag_name']; ?> </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label for="product-filter" class="form-label">Product</label>
                            <div class="position-relative">
                                <select id="product-filter" class="form-control">
                                    <option value="">All Products</option>
                                    <?php foreach ($bproducts as $product): ?>
                                        <option value="<?php echo $product['product_id']; ?>"><?php echo $product['product_name']; ?> </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="batch-filter" class="form-label">Batch</label>
                            <div class="position-relative">
                                <select id="batch-filter" class="form-control">
                                    <option value="">All Batches</option>
                                    <?php foreach ($bbatches as $batch): ?>
                                        <option value="<?php echo $batch['batch_id']; ?>"><?php echo $batch['batch_id']; ?> </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="date-from" class="form-label">Return From Date</label>
                            <div class="position-relative">
                                <input type="date" id="date-from" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="date-to" class="form-label">Return To Date</label>
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
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">Customer Return Product List</h5>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="return-datatable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Product</th>
                    <th scope="col">Batch No</th>
                    <th scope="col">ReturnQuantity</th>
                    <th scope="col">Model No / Serial No / Expire Date</th>
                    <th scope="col">Return Date</th>
                    <th scope="col">Reason</th>
                    <th scope="col">Entry By</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<!-- end row-->



<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#return-datatable').DataTable({
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` + // Show entries and search in one row
                `<"row"<"col-sm-12 text-end"B>>` + // Buttons in a separate row
                `<"row dt-layout-row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true,
            serverSide: true,
            ajax: {
                url: "./pages/inventory/return/customer_return_ajax.php",
                type: "GET",
                data: function(d) {
                    d.customer = $('#customer-filter').val(); // Get customer filter value
                    d.batch = $('#batch-filter').val(); // Get batch filter value
                    d.datefrom = $('#date-from').val(); // Get date from filter
                    d.dateto = $('#date-to').val(); // Get date to filter
                    d.product = $('#product-filter').val(); // Get product filter value
                },

                dataSrc: function(json) {
                    console.log("Data received from server:", json); // Log the response
                    return json.data; // Return the array of rows
                },
                error: function(xhr, error, thrown) {
                    console.error("Error fetching data:", xhr.responseText);
                    alert("Failed to fetch data. Please try again.");
                },
            },
            columns: [{
                    data: 'sl',
                    orderable: false
                },
                {
                    data: 'ag_name',
                    orderable: true
                },
                {
                    data: 'product_name',
                    orderable: true
                },
                {
                    data: 'batch_id',
                    orderable: true
                },
                {
                    data: 'qty_return',
                    orderable: true
                },
                {
                    data: 'model_id',
                    orderable: true,
                    render: function(data, type, row) {
                        if (data.length > 0) {
                            // Create a list of model data with custom HTML structure
                            var model_numbers = data.map(function(item) {
                                var formattedExpireDate = item.expire_date ? new Date(item.expire_date).toLocaleDateString('en-US', {
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric'
                                }).replace(',', '') : 'N/A';
                                return '<li style="color: #4CAF50; font-size: 14px; line-height: 1.6;">' +
                                    '<strong>' + item.model_no + '</strong> / ' +
                                    item.serial_no + ' / ' +
                                    '<span style="color: #FF5722;">' + formattedExpireDate + '</span>' +
                                    '</li>';
                            });

                            // Wrap the list in a <ul> and return it as HTML
                            return '<ul style="padding-left: 20px;">' + model_numbers.join('') + '</ul>';
                        } else {
                            // If no data is found, return a styled message
                            return '<span style="color: #f44336; font-size: 14px;">No model data found</span>';
                        }
                    }
                },
                {
                    data: 'return_date',
                    orderable: true,
                    render: function(data, type, row) {
                        return data ? new Date(data).toLocaleDateString('en-US', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        }).replace(',', '') : '';
                    }
                },
                {
                    data: 'return_reason',
                    orderable: true
                },
                {
                    data: 'FullName',
                    orderable: true
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
            ], // Order by 'Agent Name' column
        });

        // Trigger table reload when filters are changed
        $('#customer-filter, #batch-filter,#product-filter, #date-from, #date-to').on('change', function() {
            table.ajax.reload();
        });
    });
</script>



<?php $obj->end_script(); ?>