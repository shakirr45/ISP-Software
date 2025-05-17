<?php
// Fetch Purchases from database
$products = $obj->raw_sql("
SELECT stock.*,
p.product_name
FROM stock
LEFT JOIN products p ON stock.product_id = p.product_id
 WHERE stock.current_stock > 0");
$editProducts = $obj->raw_sql("
SELECT stock.*,
p.product_name
FROM stock
LEFT JOIN products p ON stock.product_id = p.product_id
 WHERE stock.current_stock >= 0");
$customers = $obj->raw_sql("SELECT * FROM vw_agent WHERE ag_status=1 AND deleted_at IS NULL");

$sales = $obj->raw_sql(
    "
    SELECT sales.*,
    p.product_name,
    ag.ag_name,
    _createuser.FullName
    FROM sales
    LEFT JOIN products p ON sales.product_id = p.product_id
    LEFT JOIN _createuser ON sales.created_by = _createuser.UserId
    LEFT JOIN tbl_agent ag ON sales.customer_id = ag.ag_id
    WHERE sales.quantity > 0 order by sales.sale_id desc;
    "
);
$i = 1; // Initialize counter
foreach ($sales as &$row) {
    $row['sl'] = $i++; // Add the row number
    $model_ids = json_decode($row['model_id'], true);  // Decode as an array

    if (!is_array($model_ids)) {
        $model_ids = [];  // Ensure it's an array (in case of decoding errors or wrong format)
    }

    $model_numbers = [];  // Initialize an array to store model numbers
    foreach ($model_ids as $key => $model_id) {
        // Ensure the model_id is numeric or valid
        $model = $obj->rawSqlSingle("SELECT * FROM product_model WHERE id = $model_id");

        // If the model is found, store all relevant data in an associative array
        if ($model) {
            $row['model_no'] = $model['model_no'];
            $row['serial_no'] = $model['serial_no'];
            $row['expire_date'] = $model['expire_date'];
            $model_numbers[$key] = [
                'model_no'    => $model['model_no'],
                'serial_no'   => $model['serial_no'],
                'expire_date' => $model['expire_date']
            ];
        }
        $row['model_numbers'] = $model_numbers;
    }

    // Now assign the model_numbers array back to 'model_id' (or another key if needed)

}
?>

<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">Sales List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#addSaleModal">Add Sale</button>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="saleTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Product Name</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">ReturnQuantity</th>
                    <th scope="col">Batch No</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Model No / Serial No / Expire Date</th>
                    <th scope="col">Sale Date</th>
                    <th scope="col">Created By</th>
                    <?php if ($obj->userWorkPermission('edit') || $obj->userWorkPermission('delete')) { ?>
                        <th scope="col">Action</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($sales as $value) : ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo isset($value['product_name']) ? $value['product_name'] : NULL; ?></td>
                        <td><?php echo isset($value['quantity']) ? $value['quantity'] : NULL; ?></td>
                        <td><?php echo isset($value['return_qty']) ? $value['return_qty'] : NULL; ?></td>
                        <td><?php echo isset($value['batch_id']) ? $value['batch_id'] : NULL; ?></td>
                        <td><?php echo isset($value['ag_name']) ? $value['ag_name'] : NULL; ?></td>
                        <td>
                            <?php if ($value['model_numbers']) {
                                foreach ($value['model_numbers'] as $model) {
                                    // Format as "Jan 1, 2025"
                                    $formattedExpireDate = date('M j, Y', strtotime($model['expire_date']));
                                    echo '<li style="color: #4CAF50; font-size: 14px; line-height: 1.6; list-style-type: none;">' .
                                        '<strong>' . htmlspecialchars($model['model_no']) . '/ ' .
                                        htmlspecialchars($model['serial_no']) . '</strong> / ' .
                                        '<span style="color: #FF5722;">' . htmlspecialchars($formattedExpireDate) . '</span>' .
                                        '</li>';
                                }
                            } ?>

                        </td>
                        <td><?php echo isset($value['sale_date']) ? $value['sale_date'] : NULL; ?></td>
                        <td><?php echo isset($value['FullName']) ? $value['FullName'] : NULL; ?></td>
                        <td>
                            <?php
                            date_default_timezone_set('UTC');
                            $created_at = isset($value['created_at']) ? strtotime($value['created_at']) : 0; // created_at কে টাইমস্ট্যাম্প এ রূপান্তর
                            $current_time = time(); // বর্তমান সময় টাইমস্ট্যাম্পে

                            $time_diff = $current_time - $created_at; // সময়ের পার্থক্য বের করুন (সেকেন্ডে)
                            if ($time_diff <= 3600) :
                            ?>
                                <button class="btn btn-warning btn-sm edit-btn"
                                    data-productId="<?php echo isset($value['product_id']) ? $value['product_id'] : NULL; ?>"
                                    data-id="<?php echo isset($value['sale_id']) ? $value['sale_id'] : NULL; ?>"
                                    data-qty="<?php echo isset($value['quantity']) ? $value['quantity'] : NULL; ?>"
                                    data-customerId="<?php echo isset($value['customer_id']) ? $value['customer_id'] : NULL; ?>"
                                    data-saleDate="<?php echo isset($value['sale_date']) ? $value['sale_date'] : NULL; ?>"
                                    data-batchId="<?php echo isset($value['batch_id']) ? $value['batch_id'] : NULL; ?>"
                                    data-stockId="<?php echo isset($value['stock_id']) ? $value['stock_id'] : NULL; ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editSaleModal">Edit
                                </button>
                            <?php endif; ?>
                            <?php if ($value['quantity'] > $value['return_qty']) : ?>
                                <button class="btn btn-info btn-sm return-btn"
                                    data-productId="<?php echo isset($value['product_id']) ? $value['product_id'] : NULL; ?>"
                                    data-id="<?php echo isset($value['sale_id']) ? $value['sale_id'] : NULL; ?>"
                                    data-customerId="<?php echo isset($value['customer_id']) ? $value['customer_id'] : NULL; ?>"
                                    data-qty="<?php echo isset($value['quantity']) ? $value['quantity'] : NULL; ?>"
                                    data-batchId="<?php echo isset($value['batch_id']) ? $value['batch_id'] : NULL; ?>"
                                    data-stockId="<?php echo isset($value['stock_id']) ? $value['stock_id'] : NULL; ?>"
                                    data-saleDate="<?php echo isset($value['sale_date']) ? $value['sale_date'] : NULL; ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#returnModal">
                                    Return
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="saleModalLabel">Add Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sale-form">
                    <div class="mb-3">
                        <label for="customer" class="form-label">Customer</label>
                        <select id="customer" name="customer" class="form-control">
                            <option value="">Select</option>
                            <?php foreach ($customers as $customer) { ?>
                                <option value="<?= $customer['ag_id']; ?>"><?= $customer['ag_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="product" class="form-label">Product</label>
                        <select id="product" name="product" class="form-control">
                            <option value="">Select</option>
                            <?php foreach ($products as $product) { ?>
                                <option value="<?= $product['stock_id']; ?>" data-batch='<?= $product['batch_id']; ?>' data-id="<?= $product['product_id']; ?>"><?= $product['product_name'] . "(" . $product['batch_id'] . ")"; ?></option>
                                <?= $product['product_name'] . " (" . $product['batch_id'] . ")"; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="batchId" class="form-label">Batch Id</label>
                        <input type="text" id="batchId" name="batch_id" class="form-control" readonly required>
                    </div>
                    <!-- select multiple  Model -->
                    <div class="mb-3">
                        <label for="model_products" class="form-label">Model No / Serial No</label>
                        <select
                            class="form-control"
                            id="model_products"
                            name="model_products[]"
                            data-placeholder="Choose ..."
                            multiple>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" max="" min="" value="0" readonly required>
                        <label for="qty" class="form-label" id="qty"></label>
                    </div>

                    <div class="mb-3">
                        <label for="sale-date" class="form-label">Sale Date</label>
                        <input type="date" id="sale-date" name="sale_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Sale Modal -->
<div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="editSaleModalHeader">
                <h5 class="modal-title" id="editSaleLabel">Edit Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="editSaleModalClose"></button>
            </div>
            <div class="modal-body" id="editSaleModalBody">
                <form id="editSaleForm">
                    <input type="hidden" id="editSaleId">
                    <div class="mb-3">
                        <label for="editCustomerId" class="form-label">Customer</label>
                        <select id="editCustomerId" name="editCustomerId" class="form-control">
                            <option value="">Select</option>
                            <?php foreach ($customers as $customer) { ?>
                                <option value="<?= $customer['ag_id']; ?>"><?= $customer['ag_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select id="editProductId" name="editProductId" class="form-control" disabled>
                            <option value="">Select</option>
                            <?php foreach ($editProducts as $product) { ?>
                                <option value="<?= $product['product_id']; ?>" data-id="<?= $product['stock_id']; ?>"><?= $product['product_name'] . " (" . $product['batch_id'] . ")"; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editBatchId" class="form-label">Batch Id</label>
                        <input type="text" id="editBatchId" name="editBatchId" class="form-control" readonly required>
                    </div>
                    <div class="mb-3">
                        <label for="model_products" class="form-label">Model No / Serial No</label>
                        <select
                            class="form-control"
                            id="edit_model_products"
                            name="edit_model_products[]"
                            data-placeholder="Choose ..."
                            multiple>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editQuantity" class="form-label">Quantity</label>
                        <input type="number" id="editQuantity" name="editQuantity" class="form-control" max="" min="" readonly value="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSaleDate" class="form-label">Sale Date</label>
                        <input type="date" id="editSaleDate" name="editSaleDate" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Return Sale Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnModalLabel">Return Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="returnForm">
                    <input type="hidden" id="returnSaleId">
                    <div class="mb-3">
                        <label for="returnCustomerId" class="form-label">Customer</label>
                        <select id="returnCustomerId" name="returnCustomerId" class="form-control" disabled>
                            <option value="">Select</option>
                            <?php foreach ($customers as $customer) { ?>
                                <option value="<?= $customer['ag_id']; ?>"><?= $customer['ag_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select id="returnProductId" name="returnProductId" class="form-control" disabled>
                            <option value="">Select</option>
                            <?php foreach ($editProducts as $product) { ?>
                                <option value="<?= $product['stock_id']; ?>" data-id="<?= $product['product_id']; ?>"><?= $product['product_name'] . "(" . $product['batch_id'] . ")"; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="returnBatchId" class="form-label">Batch Id</label>
                        <input type="text" id="returnBatchId" name="returnBatchId" class="form-control" readonly required>
                    </div>
                    <div class="mb-3">
                        <label for="returnQuantity" class="form-label">Quantity</label>
                        <input type="number" id="returnQuantity" name="returnQuantity" class="form-control" max="" min="" value="0" readonly required>
                        <label for="rtq" class="form-label" id="rtq"></label>
                    </div>
                    <div id="return-dynamic-fields">
                        <label for="textfield">Please Select Return Product!</label>
                    </div>
                    <div class="mb-3">
                        <label for="returnSaleDate" class="form-label">Return Date</label>
                        <input type="date" id="returnSaleDate" name="returnSaleDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="returnReason" class="form-label">Reason for Return</label>
                        <textarea id="returnReason" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Submit Return</button>
                </form>
            </div>
        </div>
    </div>
</div>







<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        $('#model_products').select2({
            placeholder: "Choose ...",
            allowClear: false,
            width: '100%', // Adjust width
            dropdownParent: $('#addSaleModal')
        });
        $('#edit_model_products').select2({
            placeholder: "Choose ...",
            allowClear: false,
            width: '100%', // Adjust width
            dropdownParent: $('#editSaleModal')
        });
        $('#product').on('change', function() {
            const id = $(this).find(":selected").val(); // Get selected product ID
            const batch = $(this).find(":selected").data('batch'); // Get selected product batch

            // AJAX request to fetch data for model_products
            $.ajax({
                url: './pages/inventory/sales/sales_batch_ajax.php', // Your PHP endpoint
                method: 'GET',
                data: {
                    id: id,
                    batch: batch
                },
                success: function(response) {
                    const data = JSON.parse(response); // Parse the JSON response from server
                    if (data.success) {
                        // Update batchId field and quantity
                        const qty = data.quantity;
                        $('#batchId').val(data.batch_id);
                        $('#quantity').attr('data-qty', qty);
                        $('#qty').text(`Product Quantity: ${qty}`);

                        // Populate the model_products multiple-select dropdown
                        const select = $('#model_products');
                        select.empty(); // Clear previous options

                        if (data.modelIds.length > 0) {
                            data.modelIds.forEach(item => {
                                var formattedExpireDate = item.expire_date ? new Date(item.expire_date).toLocaleDateString('en-US', {
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric'
                                }).replace(',', '') : 'N/A';
                                const optionText = `${item.model_no} / ${item.serial_no} / ${formattedExpireDate}`;
                                select.append(`<option value="${item.id}">${optionText}</option>`);
                            });

                            // Refresh Select2 if used
                            select.trigger('change');
                        } else {
                            Swal.fire({
                                title: 'Warning',
                                text: 'Product Stock Out',
                                icon: 'warning',
                                confirmButtonText: 'Ok'
                            });
                        }
                    } else {
                        alert('Failed to load models.');
                    }
                },
                error: function() {
                    alert('An error occurred while fetching models.');
                }
            });
        });

        $('#model_products').on('change', function() {
            const selectedOptions = $(this).val(); // Get all selected values as an array
            const totalQty = selectedOptions ? selectedOptions.length : 0; // Count the number of selected options

            // Update the quantity input field
            $('#quantity').val(totalQty);
        });
        $('#edit_model_products').on('change', function() {
            const selectedOptions = $(this).val(); // Get all selected values as an array
            const totalQty = selectedOptions ? selectedOptions.length : 0; // Count the number of selected options

            // Update the quantity input field
            $('#editQuantity').val(totalQty);
        });



        function updateQuantityFields() {
            const id = $('#editProductId').find(":selected").val(); // Get the selected product ID
            // console.log(id);
            const sale = $('#editSaleId').val();
            $.ajax({
                url: './pages/inventory/sales/get_product_model_ajax.php',
                method: 'GET',
                data: {
                    id,
                    sale
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    console.log(data);
                    if (data.success) {
                        const select = $('#edit_model_products');
                        select.empty(); // Clear previous options


                        if (data.modelIds.length > 0) {
                            data.modelIds.forEach(item => {
                                var formattedExpireDate = item.expire_date ? new Date(item.expire_date).toLocaleDateString('en-US', {
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric'
                                }).replace(',', '') : 'N/A';
                                if (item.sold == 1) {
                                    const optionText = `${item.model_no} / ${item.serial_no} / ${formattedExpireDate}`;
                                    select.append(`<option value="${item.id}" selected>${optionText}</option>`);
                                } else {
                                    const optionText = `${item.model_no} / ${item.serial_no} / ${formattedExpireDate}`;
                                    select.append(`<option value="${item.id}">${optionText}</option>`);
                                }
                            });

                            // Refresh Select2 if used
                            select.trigger('change');
                        }
                    }
                },
            });
        }


        $('#quantity').on('input', function() {
            var currentQty = $(this).val();

            var qty = $(this).attr('data-qty');

            // Check if the input exceeds the max quantity
            if (parseInt(currentQty) >= parseInt(qty)) {
                // Show an alert
                Swal.fire({
                    title: 'Error',
                    text: 'Quantity exceeds the maximum allowed value!',
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });

                // Reset the input to the max value
                $(this).val(qty);
            }
        });
        $('#editQuantity').on('input', function() {
            var currentQty = $(this).val();
            var qty = $(this).attr('data-qty');

            // Check if the input exceeds the max quantity
            if (parseInt(currentQty) > parseInt(qty)) {
                // Show an alert
                Swal.fire({
                    title: 'Error',
                    text: 'Quantity exceeds the maximum allowed value!',
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });

                // Reset the input to the max value
                $(this).val(qty);
            }
        });
        // Initialize DataTable
        const table = $('#saleTable').DataTable({
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
        });

        // Add Sale Logic
        $('#sale-form').submit(function(e) {
            e.preventDefault();
            const customer = $('#customer').val();
            const stock_id = $('#product').find(":selected").val();
            const product = $('#product').find(":selected").data('id');
            const sale_date = $('#sale-date').val();
            const quantity = $('#quantity').val();
            const batch_id = $('#batchId').val();
            const model_products = $('#model_products').val();
            $.ajax({
                url: './pages/inventory/sales/sales_ajax.php',
                method: 'POST',
                data: {
                    product,
                    quantity,
                    customer,
                    sale_date,
                    batch_id,
                    stock_id,
                    model_products
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#addSaleModal').modal('hide');
                        Swal.fire({
                            title: 'Success',
                            text: 'Sale added successfully!',
                            icon: 'success',
                            confirmButtonText: 'Ok'
                        });
                        window.location.reload();
                        $('#sale-form')[0].reset();
                    } else {
                        alert('Failed to add sale!');
                    }
                },
            });
        });

        // Edit Sale
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).attr('data-id');
            const product_id = $(this).attr('data-productId'); // Use .attr() to get the value directly
            const quantity = $(this).attr('data-qty');
            const customer_id = $(this).attr('data-customerId');
            const sale_date = $(this).attr('data-saleDate');
            const batch_id = $(this).attr('data-batchId');
            const stock_id = $(this).attr('data-stockId');
            // alert(batch_id);
            $('#editSaleId').val(id);
            $('#editProductId').val(product_id);
            $('#editProductId').attr('data-id', stock_id);
            $('#editQuantity').val(quantity);
            $('#editCustomerId').val(customer_id);
            $('#editSaleDate').val(sale_date);
            $('#editBatchId').val(batch_id);


            updateQuantityFields();

        });
        // Edit Sale Logic
        $('#editSaleForm').submit(function(e) {
            e.preventDefault();
            const id = $('#editSaleId').val();
            const product = $('#editProductId').find(":selected").val();
            const stock_id = $('#editProductId').find(":selected").data('id');
            const quantity = $('#editQuantity').val();
            const customer = $('#editCustomerId').val();
            const sale_date = $('#editSaleDate').val();
            const batch_id = $('#editBatchId').val();
            const models = $('#edit_model_products').val();
            console.log(`id: ${id}, product: ${product}, quantity: ${quantity}, customer: ${customer}, sale_date: ${sale_date}, batch_id: ${batch_id}, stock_id: ${stock_id}, models: ${models}`);
            $.ajax({
                url: './pages/inventory/sales/sales_ajax.php',
                method: 'POST',
                data: {
                    id,
                    product,
                    quantity,
                    customer,
                    sale_date,
                    batch_id,
                    stock_id,
                    models
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#editSaleModal').modal('hide');
                        Swal.fire({
                            title: 'Success',
                            text: 'Sale updated successfully!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,


                        });
                        window.location.reload();
                        $('#editSaleForm')[0].reset();
                    } else {
                        alert('Failed to update sale!');
                    }
                },
            });
        });

        // Return Sale
        $(document).on('click', '.return-btn', function() {
            const id = $(this).attr('data-id');
            const product_id = $(this).attr('data-productId'); // Use .attr() to get the value directly

            const customer_id = $(this).attr('data-customerId');
            const sale_date = $(this).attr('data-saleDate');
            const batch_id = $(this).attr('data-batchId');
            const stock_id = $(this).attr('data-stockId');
            const quantity = $(this).attr('data-qty');
            $('#returnSaleId').val(id);
            $('#returnProductId').val(stock_id);
            $('#returnProductId').attr('data-id', product_id);
            $('#returnCustomerId').val(customer_id);
            $('#returnBatchId').val(batch_id);
            // $('#returnReason').val('');

            $('#returnQuantity').attr('data-qty', quantity);

            $('#rtq').text(`Product Return Quantity: ${quantity}`);
            Swal.fire({
                title: 'Warning',
                text: 'Please Select Return Products!',
                icon: 'warning',
                showCancelButton: false,
                showConfirmButton: true,
                confirmButtonText: 'Ok',
                timer: 1500,
            });

            $.ajax({
                url: './pages/inventory/sales/sales_ajax.php',
                method: 'GET',
                data: {
                    sale: id,
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        data.models.forEach((model, index) => {
                            index++;
                            const modelHTML = `
            <div class="form-check form-check-info" id="row-${index}">
                <input class="form-check-input" type="checkbox" id="checkbox-${index}" value="${model.id}" data-row="row-${index}" style="margin-top: 5px;">
                <label class="form-check-label" for="checkbox-${index}" style="font-weight: bold; margin-left: 5px;">
                    Model No: ${model.model_no}, Serial No: ${model.serial_no}, Expire Date: ${model.expire_date}
                </label>
            </div>
        `;
                            $('#return-dynamic-fields').append(modelHTML);
                        });
                    }

                },
            });
        });
        $('#returnModal').on('hide.bs.modal', function() {
            // ডাইনামিক ফিল্ড রিসেট
            $('#return-dynamic-fields').html('');
            console.log('Modal is being closed. All dynamic fields have been reset.');
        });

        $('#return-dynamic-fields').on('change', '.form-check-input', function() {
            let currentQuantity = parseInt($('#returnQuantity').val()) || 0; // Get the current quantity

            if ($(this).is(':checked')) {
                // Increment quantity when checked
                $('#returnQuantity').val(currentQuantity + 1);
            } else {
                // Decrement quantity when unchecked (if greater than 0)
                if (currentQuantity > 0) {
                    $('#returnQuantity').val(currentQuantity - 1);
                }
            }
        });


        $('#returnQuantity').on('input', function() {
            var currentQty = $(this).val();
            var maxQty = $(this).attr('data-qty');

            // Check if the input exceeds the max quantity
            if (parseInt(currentQty) > parseInt(maxQty)) {
                // Show an alert
                Swal.fire({
                    title: 'Error',
                    text: 'Quantity exceeds the maximum allowed value!',
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });

                // Reset the input to the max value
                $(this).val(maxQty);
            }
        });
        // Return Sale Logic
        $('#returnForm').submit(function(e) {
            e.preventDefault();
            const id = $('#returnSaleId').val();
            const stock_id = $('#returnProductId').find(":selected").val();
            const quantity = $('#returnQuantity').val();
            const customer = $('#returnCustomerId').val();
            const sale_date = $('#returnSaleDate').val();
            const batch_id = $('#returnBatchId').val();
            const product = $('#returnProductId').find(":selected").data('id');
            const reason = $('#returnReason').val();
            let models = [];
            // console.log($('#return-dynamic-fields .form-check-input')); // Check if the selector matches any elements

            $('#return-dynamic-fields .form-check-input').each(function() {
                // console.log($(this)); // Check each checkbox element
                if ($(this).is(':checked')) {
                    models.push($(this).val());
                }
            });
            console.log(models);
            $.ajax({
                url: './pages/inventory/return/customer_return_ajax.php',
                method: 'POST',
                data: {
                    id,
                    product,
                    quantity,
                    customer,
                    sale_date,
                    batch_id,
                    stock_id,
                    reason,
                    models
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#returnModal').modal('hide');
                        Swal.fire({
                            title: 'Success',
                            text: 'Return added successfully!',
                            icon: 'success',
                            confirmButtonText: 'Ok'
                        });
                        window.location.reload();
                        $('#returnForm')[0].reset();
                    } else {
                        alert('Failed to add return!');
                    }
                },
            });
        });
    });
</script>
<?php $obj->end_script(); ?>