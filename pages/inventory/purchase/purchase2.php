<?php
// Fetch Purchases from database
$products = $obj->raw_sql("SELECT * FROM products WHERE deleted_at IS NULL");
$suppliers = $obj->raw_sql("SELECT * FROM suppliers WHERE deleted_at IS NULL");
?>
<div class="mt-5">
    <h1>Purchases</h1>
    <button id="addPurchaseBtn" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">Add Purchase</button>

    <!-- DataTable for Purchases -->
    <table id="purchaseTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Sl.</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Batch No</th>
                <th>Supplier</th>
                <th>Purchase Date</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Populated via AJAX -->
        </tbody>
    </table>
</div>

<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="purchaseModalLabel">Add Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="purchase-form">
                    <div class="mb-3">
                        <label for="product" class="form-label">Product</label>
                        <select id="product" name="product" class="form-control">
                            <option value="">Select</option>
                            <?php foreach ($products as $product) { ?>
                                <option value="<?= $product['product_id']; ?>"><?= $product['product_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" required>
                    </div>
                    <div id="dynamic-fields"></div>
                    <div class="mb-3">
                        <label for="supplier" class="form-label">Supplier</label>
                        <select id="supplier" name="supplier" class="form-control">
                            <option value="">Select</option>
                            <?php foreach ($suppliers as $supplier) { ?>
                                <option value="<?= $supplier['supplier_id']; ?>"><?= $supplier['supplier_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="purchase-date" class="form-label">Purchase Date</label>
                        <input type="date" id="purchase-date" name="purchase_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Purchase Modal (Modal for Editing Purchase)-->
<div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-labelledby="editPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPurchaseModalLabel">Edit Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-purchase-form">
                    <input type="hidden" id="edit-purchase-id" name="id">
                    <div class="mb-3">
                        <label for="edit-product" class="form-label">Product</label>
                        <select id="edit-product" name="product" class="form-control">
                            <option value="">Select</option>
                            <?php foreach ($products as $product) { ?>
                                <option value="<?= $product['product_id']; ?>"><?= $product['product_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-quantity" class="form-label">Quantity</label>
                        <input type="number" id="edit-quantity" name="quantity" class="form-control" required>
                    </div>
                    <div id="edit-dynamic-fields"></div>
                    <div class="mb-3">
                        <label for="edit-supplier" class="form-label">Supplier</label>
                        <select id="edit-supplier" name="supplier" class="form-control">
                            <option value="">Select</option>
                            <?php foreach ($suppliers as $supplier) { ?>
                                <option value="<?= $supplier['supplier_id']; ?>"><?= $supplier['supplier_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-purchase-date" class="form-label">Purchase Date</label>
                        <input type="date" id="edit-purchase-date" name="purchase_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Return Purchase Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnModalLabel">Return Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="returnForm">
                    <div class="mb-3">
                        <label for="returnProductId" class="form-label">Product</label>
                        <select id="returnProductId" name="returnProductId" class="form-control" disabled>
                            <option value="">Select</option>
                            <?php foreach ($products as $product) { ?>
                                <option value="<?= $product['product_id']; ?>"><?= $product['product_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <input type="hidden" id="returnPurchaseId">
                    <div class="mb-3">
                        <label for="returnSupplierId" class="form-label">Supplier</label>
                        <select id="returnSupplierId" name="returnSupplierId" class="form-control" disabled>
                            <option value="">Select</option>
                            <?php foreach ($suppliers as $supplier) { ?>
                                <option value="<?= $supplier['supplier_id']; ?>"><?= $supplier['supplier_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="returnQuantity" class="form-label">Quantity</label>
                        <input type="number" id="returnQuantity" name="returnQuantity" class="form-control" readonly required>
                        <label for="rtq" class="form-label" id="rtq"></label>
                    </div>

                    <div id="return-dynamic-fields"></div>

                    <div class="mb-3">
                        <label for="ReturnDate" class="form-label">Return Date</label>
                        <input type="date" id="returnDate" name="returnDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="returnReason" class="form-label">Reason for Return</label>
                        <textarea id="returnReason" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Submir Return</button>
                </form>
            </div>
        </div>
    </div>

</div>







<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        document.getElementById("quantity").addEventListener("input", function() {
            const quantity = parseInt(this.value) || 0; // Get the quantity or default to 0
            const dynamicFieldsContainer = document.getElementById("dynamic-fields");

            // Clear previous fields
            dynamicFieldsContainer.innerHTML = "";

            for (let i = 1; i <= quantity; i++) {
                // Create a row for Model No and Serial No
                const rowDiv = document.createElement("div");
                rowDiv.className = "row mb-3";

                // Create Model No field
                const modelNoDiv = document.createElement("div");
                modelNoDiv.className = "col-md-4";
                modelNoDiv.innerHTML = `
                <label for="model-no-${i}" class="form-label">Model No ${i}</label>
                <input type="text" id="model-no-${i}" name="model_no_${i}" class="form-control" required>
            `;
                rowDiv.appendChild(modelNoDiv);

                // Create Serial No field
                const serialNoDiv = document.createElement("div");
                serialNoDiv.className = "col-md-4";
                serialNoDiv.innerHTML = `
                <label for="serial-no-${i}" class="form-label">Serial No ${i}</label>
                <input type="text" id="serial-no-${i}" name="serial_no_${i}" class="form-control" required>
            `;
                rowDiv.appendChild(serialNoDiv);
                // Create expire date field
                const expireDateDiv = document.createElement("div");
                expireDateDiv.className = "col-md-4";
                expireDateDiv.innerHTML = `
                <label for="expire-date-${i}" class="form-label">Expire Date ${i}</label>
                <input type="date" id="expire-date-${i}" name="expire_date_${i}" class="form-control" required>
            `;
                rowDiv.appendChild(expireDateDiv);
                // Append the row to the container
                dynamicFieldsContainer.appendChild(rowDiv);
            }
        });
        document.getElementById("edit-quantity").addEventListener("input", function() {
            const quantity = parseInt(this.value) || 0; // Get the quantity or default to 0
            const dynamicFieldsContainer = document.getElementById("edit-dynamic-fields");

            // Clear previous fields
            dynamicFieldsContainer.innerHTML = "";

            for (let i = 1; i <= quantity; i++) {
                // Create a row for Model No and Serial No
                const rowDiv = document.createElement("div");
                rowDiv.className = "row mb-3";

                // Create Model No field
                const modelNoDiv = document.createElement("div");
                modelNoDiv.className = "col-md-4";
                modelNoDiv.innerHTML = `

                <label for="edit-model-no-${i}" class="form-label">Model No ${i}</label>
                <input type="text" id="edit-model-no-${i}" name="edit_model_no_${i}" class="form-control" required>
            `;
                rowDiv.appendChild(modelNoDiv);

                // Create Serial No field
                const serialNoDiv = document.createElement("div");
                serialNoDiv.className = "col-md-4";
                serialNoDiv.innerHTML = `
                <label for="edit-serial-no-${i}" class="form-label">Serial No ${i}</label>
                <input type="text" id="edit-serial-no-${i}" name="edit_serial_no_${i}" class="form-control" required>
            `;
                rowDiv.appendChild(serialNoDiv);
                // Create expire date field
                const expireDateDiv = document.createElement("div");
                expireDateDiv.className = "col-md-4";
                expireDateDiv.innerHTML = `
                <label for="edit-expire-date-${i}" class="form-label">Expire Date ${i}</label>
                <input type="date" id="edit-expire-date-${i}" name="edit_expire_date_${i}" class="form-control" required>
            `;
                rowDiv.appendChild(expireDateDiv);
                // Append the row to the container
                dynamicFieldsContainer.appendChild(rowDiv);
            }
        });
        // Initialize DataTable
        const table = $('#purchaseTable').DataTable({
            ajax: {
                url: './pages/inventory/purchase/purchase_ajax.php',
                dataSrc: '',
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'product_name'
                },
                {
                    data: 'quantity'
                },
                {
                    data: 'batch_id'
                },
                {
                    data: 'supplier_name'
                },
                {
                    data: 'purchase_date',
                    // render: function(data) {
                    //     return moment(data).format('YYYY-MM-DD');
                    // }
                },
                {
                    data: 'FullName'
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                <button class="btn btn-warning btn-sm edit-btn"data-productId=${row.product_id} data-id="${row.purchase_id}"  data-qty="${row.quantity}" data-supplierId="${row.supplier_id}" data-purchaseDate="${row.purchase_date}" data-rtq="${row.return_qty}"  data-bs-toggle="modal" data-bs-target="#editPurchaseModal">Edit</button>
                <button class="btn btn-info btn-sm return-btn" 
                    data-productId="${row.product_id}" 
                    data-id="${row.purchase_id}" 
                    data-supplierId="${row.supplier_id}" 
                    data-rtq="${row.return_qty}"
                    data-qty="${row.quantity}" data-bs-toggle="modal" data-bs-target="#returnModal">
                    Return                    
                </button>
            `;
                    }
                },
            ],
        });
        // Return Purchase
        $(document).on('click', '.return-btn', function() {
            const id = $(this).attr('data-id');
            const product_id = $(this).attr('data-productId'); // Use .attr() to get the value directly
            const quantity = $(this).attr('data-qty');
            const supplier_id = $(this).attr('data-supplierId');
            const rtq = $(this).attr('data-rtq');
            qty = quantity - rtq;
            $('#returnPurchaseId').val(id);
            $('#returnProductId').val(product_id);
            $('#returnQuantity').val(qty);
            $('#returnSupplierId').val(supplier_id);

            $('#returnQuantity').attr('data-qty', qty);

            $('#rtq').text(`Product Return Quantity: ${qty}`);

            $.ajax({
                url: './pages/inventory/purchase/purchase_ajax.php',
                method: 'GET',
                data: {
                    purchaseId: id
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        data.models.forEach((model, index) => {
                            index++;
                            // alert(model.serial_no);
                            const modelHTML = `
                        <div class="dynamic-row" id="row-${index}"> 
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="model-no-${index}" class="form-label">Model No</label>
                                    <input type="text" id="model-no-${index}" name="return_model_no_${index}" class="form-control" value="${model.model_no}" readonly required>
                                </div>
                                <div class="col-md-3">
                                    <label for="serial-no-${index}" class="form-label">Serial No</label>
                                    <input type="text" id="serial-no-${index}" name="return_serial_no_${index}" class="form-control" value="${model.serial_no}" readonly required>
                                </div>
                                <div class="col-md-3">
                                    <label for="expire-date-${index}" class="form-label">Expire Date</label>
                                    <input type="date" id="expire-date-${index}" name="return_expire_date_${index}" class="form-control"
                                    value="${model.expire_date}" readonly required>
                                </div>
                                <div class="col-md-1" style="text-align: center; padding-top: 2rem;">
                                    <button type="button" class="btn btn-danger remove-btn btn-sm" data-row="row-${index}">Remove</button>
                                </div>
                            </div>
                        </div>
                           `;
                            $('#return-dynamic-fields').append(modelHTML);
                        });
                    }
                }
            });
        });
        $('#return-dynamic-fields').on('click', '.remove-btn', function() {
            const row = $(this).data('row');
            $('#return-dynamic-fields').find(`#${row}`).remove();

            let currentQuantity = parseInt($('#returnQuantity').val());
            if (currentQuantity > 0) {
                $('#returnQuantity').val(currentQuantity - 1); // Decrease quantity
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
        // Return Purchase Logic
        $('#returnForm').submit(function(e) {
            e.preventDefault();
            const id = $('#returnPurchaseId').val();
            const product = $('#returnProductId').val();
            const quantity = $('#returnQuantity').val();
            const supplier = $('#returnSupplierId').val();
            const return_date = $('#returnDate').val();
            const reason = $('#returnReason').val();

            const models = [];
            $('#return-dynamic-fields .row').each(function() {
                const modelNo = $(this).find('input[name^="return_model_no_"]').val();
                const serialNo = $(this).find('input[name^="return_serial_no_"]').val();
                const expireDate = $(this).find('input[name^="return_expire_date_"]').val();

                models.push({
                    modelNo,
                    serialNo,
                    expireDate
                });
            });
            // console.log(models);

            // alert(`id: ${id}, product: ${product}, quantity: ${quantity}, supplier: ${supplier}, return_date: ${return_date}, reason: ${reason}`);
            $.ajax({
                url: './pages/inventory/return/supplier_return_ajax.php',
                method: 'POST',
                data: {
                    id,
                    product,
                    quantity,
                    supplier,
                    return_date,
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
                        table.ajax.reload();
                        $('#returnForm')[0].reset();
                    } else {
                        alert('Failed to add return!');
                    }
                },
            });
        });

        // Add Purchase Logic
        $('#purchase-form').submit(function(e) {
            e.preventDefault();
            const product = $('#product').val();
            const quantity = $('#quantity').val();
            const supplier = $('#supplier').val();
            const purchase_date = $('#purchase-date').val();
            const batch_id = 'batch_' + (Date.now() % 1000000) + Math.floor(Math.random() * 1000);

            // Gather all dynamic Model No and Serial No fields
            const modelNos = [];
            const serialNos = [];
            const expireDates = [];

            // Loop through the dynamically generated fields
            $('#dynamic-fields .row').each(function() {
                const modelNo = $(this).find('input[name^="model_no_"]').val();
                const serialNo = $(this).find('input[name^="serial_no_"]').val();
                const expireDate = $(this).find('input[name^="expire_date_"]').val();

                if (modelNo && serialNo && expireDate) {
                    expireDates.push(expireDate);
                    modelNos.push(modelNo);
                    serialNos.push(serialNo);
                }
            });

            $.ajax({
                url: './pages/inventory/purchase/purchase_ajax.php',
                method: 'POST',
                data: {
                    product,
                    quantity,
                    supplier,
                    purchase_date,
                    batch_id,
                    modelNos,
                    serialNos,
                    expireDates
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#addPurchaseModal').modal('hide');
                        Swal.fire({
                            title: 'Success',
                            text: 'Purchase added successfully!',
                            icon: 'success',
                            confirmButtonText: 'Ok'
                        });
                        table.ajax.reload();
                        $('#purchase-form')[0].reset();
                    } else {
                        alert('Failed to add purchase!');
                    }
                },
            });
        });

        // Edit Purchase
        $('#purchaseTable').on('click', '.edit-btn', function() {
            const productId = $(this).data('productid');
            const purchaseId = $(this).data('id');
            const qty = $(this).data('qty');
            const supplierId = $(this).data('supplierid');
            const purchaseDate = $(this).data('purchasedate');

            $('#edit-product').val(productId);
            $('#edit-quantity').val(qty);
            $('#edit-supplier').val(supplierId);
            $('#edit-purchase-date').val(purchaseDate);
            $('#edit-purchase-id').val(purchaseId);
            $('#edit-dynamic-fields').html('');

            $.ajax({
                url: './pages/inventory/purchase/purchase_ajax.php',
                method: 'GET',
                data: {
                    purchaseId: purchaseId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        data.models.forEach((model, index) => {
                            index++;
                            const dynamicRow = `
                            <div class="row mb-3">
                                <div class="col">
                                <label for="model-no-${index}" class="form-label">Model No ${index}</label>
                                    <input type="text" id="edit-model-no-${index}" name="edit_model_no_${index}" class="form-control" value="${model.model_no}" required>
                                </div>
                                <div class="col">
                                <label for="serial-no-${index}" class="form-label">Serial No ${index}</label>
                                    <input type="text" id="edit-serial-no-${index}" name="edit_serial_no_${index}" class="form-control" value="${model.serial_no}" required>
                                </div>
                                <div class="col">
                                <label for="expire-date-${index}" class="form-label">Expire Date ${index}</label>
                                    <input type="date" id="edit-expire-date-${index}" name="edit_expire_date_${index}" class="form-control" value="${model.expire_date}" required>
                                </div>
                            </div>`;
                            $('#edit-dynamic-fields').append(dynamicRow);
                        });
                    }
                }
            });
        });
        // Edit Purchase Logic
        $('#edit-purchase-form').submit(function(e) {
            e.preventDefault();
            const id = $('#edit-purchase-id').val();
            const product = $('#edit-product').val();
            const quantity = $('#edit-quantity').val();
            const supplier = $('#edit-supplier').val();
            const purchase_date = $('#edit-purchase-date').val();
            const batch_id = 'batch_' + (Date.now() % 1000000) + Math.floor(Math.random() * 1000);
            const models = [];
            $('#edit-dynamic-fields .row').each(function() {
                const modelNo = $(this).find('input[name^="edit_model_no_"]').val();
                const serialNo = $(this).find('input[name^="edit_serial_no_"]').val();
                const expireDate = $(this).find('input[name^="edit_expire_date_"]').val();

                models.push({
                    modelNo,
                    serialNo,
                    expireDate
                });
            });
            $.ajax({
                url: './pages/inventory/purchase/purchase_ajax.php',
                method: 'POST',
                data: {
                    id,
                    product,
                    quantity,
                    supplier,
                    purchase_date,
                    batch_id,
                    models
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#editPurchaseModal').modal('hide');
                        Swal.fire({
                            title: 'Success',
                            text: 'Purchase updated successfully!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,


                        });
                        table.ajax.reload();
                    } else {
                        alert('Failed to update purchase!');
                    }
                },
            });
        });
    });
</script>
<?php $obj->end_script(); ?>