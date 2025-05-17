<?php
// Fetch Purchases from database
$products = $obj->raw_sql("SELECT * FROM products WHERE deleted_at IS NULL");
$suppliers = $obj->raw_sql("SELECT * FROM suppliers WHERE deleted_at IS NULL");
?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">Purchase List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">Add Purchase</button>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="purchaseTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Product Name</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Batch No</th>
                    <th scope="col">Supplier</th>
                    <th scope="col">Purchase Date</th>
                    <th scope="col">Created By</th>
                    <?php if ($obj->userWorkPermission('edit') || $obj->userWorkPermission('delete')) { ?>
                        <th scope="col">Action</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="purchaseModalLabel">Add Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="purchase-form">
                    <div class="mb-3">
                        <label for="product" class="form-label">Product</label>
                        <select id="product" name="product" class="form-control wizard-required" required>
                            <option value="">Select</option>
                            <?php foreach ($products as $product) { ?>
                                <option value="<?= $product['product_id']; ?>"><?= $product['product_name']; ?></option>
                            <?php } ?>
                        </select>
                        <div class="wizard-form-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control wizard-required" required>
                        <div class="wizard-form-error"></div>
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
                    <button type="submit" class="btn btn-success">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Purchase Modal (Modal for Editing Purchase)-->
<div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-labelledby="editPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs" id="editPurchaseModalLabel">Edit Purchase</h5>
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
                        <input type="number" id="edit-quantity" name="quantity" class="form-control" readonly required>
                    </div>
                    <button type="button" id="add-product-model" class="btn btn-warning mb-3">
                        <i class="fa fa-plus"></i>
                    </button>
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
                        <input type="number" id="returnQuantity" name="returnQuantity" value="0" class="form-control" readonly required>
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
                    <button type="submit" class="btn btn-success">submit Return</button>
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
                        const currentTime = new Date().getTime();
                        const createdAt = new Date(row.created_at).getTime();

                        const diffMinutes = (currentTime - createdAt) / (1000 * 60);
                        return `
                        ${diffMinutes <60 ? `
                <button class="btn btn-warning btn-sm edit-btn"data-productId=${row.product_id} data-id="${row.purchase_id}"  data-qty="${row.quantity}" data-supplierId="${row.supplier_id}" data-purchaseDate="${row.purchase_date}" data-rtq="${row.return_qty}"  data-bs-toggle="modal" data-bs-target="#editPurchaseModal">Edit</button>` : ''}
${row.quantity > row.return_qty ? `
        <button class="btn btn-info btn-sm return-btn" 
            data-productId="${row.product_id}" 
            data-id="${row.purchase_id}" 
            data-supplierId="${row.supplier_id}" 
            data-rtq="${row.return_qty}"
            data-qty="${row.quantity}"
            data-bs-toggle="modal"
            data-bs-target="#returnModal">
            Return                    
        </button>
    ` : ''}
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
            // $('#returnQuantity').val(qty);
            $('#returnSupplierId').val(supplier_id);

            $('#returnQuantity').attr('data-qty', qty);

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
                url: './pages/inventory/purchase/purchase_ajax.php',
                method: 'GET',
                data: {
                    purchaseId: id
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        if (data.models.length == 0) {
                            $('#rtq').text(`Product Return Quantity: 0`);
                        }
                        data.models.forEach((model, index) => {
                            index++;
                            // alert(model.serial_no);

                            $('#rtq').text(`Product Return Quantity: ${index}`);
                            const modelHTML = `
            <div class="form-check" id="row-${index}">
                <input class="form-check-input mt-2" type="checkbox" id="checkbox-${index}" value="${model.id}" data-row="row-${index}">
                <label class="form-check-label" for="checkbox-${index}" style="font-weight: bold;margin-left: 10px;">
                    Model No: ${model.model_no}, Serial No: ${model.serial_no}, Expire Date: ${model.expire_date}
                </label>
            </div>
        `;
                            $('#return-dynamic-fields').append(modelHTML);
                        });
                    }
                }
            });
        });
        $('#return-dynamic-fields').on('change', '.form-check-input', function() {
            // alert('changed');
            let currentQuantity = parseInt($('#returnQuantity').val());
            if ($(this).is(':checked')) {
                // Increment quantity when checked
                $('#returnQuantity').val(currentQuantity + 1);
            } else {
                if (currentQuantity > 0) {
                    $('#returnQuantity').val(currentQuantity - 1); // Decrease quantity
                }
            }
        });
        $('#returnModal').on('hide.bs.modal', function() {
            // ডাইনামিক ফিল্ড রিসেট
            $('#return-dynamic-fields').html('');
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
            $('#return-dynamic-fields .form-check-input').each(function() {

                if ($(this).is(':checked')) {
                    models.push($(this).val());
                }
            });
            // console.log(models);

            // alert(id: ${id}, product: ${product}, quantity: ${quantity}, supplier: ${supplier}, return_date: ${return_date}, reason: ${reason});
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

            // Fetch and render the existing models
            $.ajax({
                url: './pages/inventory/purchase/purchase_ajax.php',
                method: 'GET',
                data: {
                    purchaseId: purchaseId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        let modelIndex = 1;
                        data.models.forEach((model) => {
                            const dynamicRow = `
                        <div class="row mb-3" data-index="${modelIndex}" data-model-id="${model.id}">
                            <div class="col">
                                <label for="model-no-${model.id}" class="form-label">Model No ${modelIndex}</label>
                                <input type="text" id="edit-model-no-${model.id}" data-id="${model.id}" name="edit_model_no_${modelIndex}" class="form-control" value="${model.model_no}" required>
                            </div>
                            <div class="col">
                                <label for="serial-no-${modelIndex}" class="form-label">Serial No ${modelIndex}</label>
                                <input type="text" id="edit-serial-no-${model.id}" name="edit_serial_no_${modelIndex}" class="form-control" value="${model.serial_no}" required>
                            </div>
                            <div class="col">
                                <label for="expire-date-${modelIndex}" class="form-label">Expire Date ${modelIndex}</label>
                                <input type="date" id="edit-expire-date-${model.id}" name="edit_expire_date_${modelIndex}" class="form-control" value="${model.expire_date}" required>
                            </div>
                            <div class="col">
                                <label for="expire-date-${modelIndex}" class="form-label "> </label>
                                <a type="button" style="margin-top:30px;" class="btn btn-success  saveBtnOfEditPurchaseModel"><i class="fas fa-file-upload"></i></a>
                                <a type="button" style="margin-top:30px;" class="btn btn-danger deleteBtnOfEditPurchaseModel"><i class="fas fa-trash-alt"></i></a>
                            </div>
                        </div>`;
                            $('#edit-dynamic-fields').append(dynamicRow);
                            modelIndex++;
                        });
                    }
                }
            });
            // Logic for handling saving/editing each model
            $(document).on('click', '.saveBtnOfEditPurchaseModel', function(e) {
                e.preventDefault();

                const isNewRow = $(this).hasClass('btn-info');
                const row = $(this).closest('.row');
                const modelIndex = row.data('index');
                const modelId = isNewRow ? 'new' : $(this).closest('.row').find('input[name^="edit_model_no_"]').data('id');

                // Ensure you are selecting the correct model ID dynamically
                const modelNo = isNewRow ? $(`#edit-model-no-new-${modelIndex}`).val() : $(`#edit-model-no-${modelId}`).val();
                const serialNo = isNewRow ? $(`#edit-serial-no-new-${modelIndex}`).val() : $(`#edit-serial-no-${modelId}`).val();
                const expireDate = isNewRow ? $(`#edit-expire-date-new-${modelIndex}`).val() : $(`#edit-expire-date-${modelId}`).val();
                const purchaseId = $('#edit-purchase-id').val(); // Fetch purchaseId from the hidden input

                const requestData = {
                    modelId,
                    modelNo,
                    serialNo,
                    expireDate
                };

                // console.log(requestData); // Ensure the request data is as expected
                const button = $(this);

                if (modelId === 'new') {
                    // Handle insertion of a new model
                    $.ajax({
                        url: './pages/inventory/purchase/product_model_ajax.php',
                        method: 'POST',
                        data: {
                            modelNo,
                            serialNo,
                            expireDate,
                            purchaseId
                        }, // Send all necessary data in the request
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'Success',
                                    text: 'Model Inserted successfully!',
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                button.prop('disabled', true); // Disable the Save button
                                button.text('Saved'); // Change button text to indicate saved
                                row.find('.deleteBtnOfEditPurchaseModel').hide();
                                row.find('input').prop('readonly', true);
                            } else {
                                alert('Failed to Insert model!');
                            }
                        }
                    });
                } else {
                    // Handle updating an existing model
                    $.ajax({
                        url: './pages/inventory/purchase/product_model_ajax.php',
                        method: 'POST',
                        data: requestData, // Send the data for updating
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'Success',
                                    text: 'Model updated successfully!',
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            } else {
                                alert('Failed to update model!');
                            }
                        }
                    });
                }
            });

            $(document).on('click', '.deleteBtnOfEditPurchaseModel', function() {
                const row = $(this).closest('.row');
                const modelId = row.data('model-id');

                if (modelId) {
                    // For saved rows, call AJAX to delete from database
                    $.ajax({
                        url: './pages/inventory/purchase/product_model_ajax.php',
                        method: 'POST',
                        data: {
                            action: 'delete',
                            modelId: modelId
                        },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'Success',
                                    text: 'Model deleted successfully!',
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                row.remove(); // Remove the row from the DOM
                                const currentQty = parseInt($('#edit-quantity').val());
                                $('#edit-quantity').val(currentQty - 1);
                            } else {
                                alert('Failed to delete model!');
                            }
                        }
                    });
                } else {
                    // For unsaved rows, just remove them directly
                    row.remove();
                    const currentQty = parseInt($('#edit-quantity').val());
                    $('#edit-quantity').val(currentQty - 1);
                }
            });

        });

        // Handle the add product model button click to add a new row
        $('#add-product-model').on('click', function() {
            const modelIndex = $('#edit-dynamic-fields').children().length + 1;
            const dynamicRow = `
            <div class="row mb-3" data-index="${modelIndex}">
                <div class="col">
                    <label for="model-no-new-${modelIndex}" class="form-label">Model No ${modelIndex}</label>
                    <input type="text" id="edit-model-no-new-${modelIndex}" name="edit_model_no_${modelIndex}" class="form-control" required>
                </div>
                <div class="col">
                    <label for="serial-no-new-${modelIndex}" class="form-label">Serial No ${modelIndex}</label>
                    <input type="text" id="edit-serial-no-new-${modelIndex}" name="edit_serial_no_${modelIndex}" class="form-control" required>
                </div>
                <div class="col">
                    <label for="expire-date-new-${modelIndex}" class="form-label">Expire Date ${modelIndex}</label>
                    <input type="date" id="edit-expire-date-new-${modelIndex}" name="edit_expire_date_${modelIndex}" class="form-control" required>
                </div>
                <div class="col">
                    <label for="expire-date-${modelIndex}" class="form-label"> </label>
                    <button type="button" class="btn btn-info  saveBtnOfEditPurchaseModel mt-3"><i class="fas fa-file-export"></i></button>
                    <button type="button" class="btn btn-danger  deleteBtnOfEditPurchaseModel mt-3"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>`;
            $('#edit-dynamic-fields').append(dynamicRow);

            // Update the quantity based on new rows
            const currentQty = parseInt($('#edit-quantity').val());
            $('#edit-quantity').val(currentQty + 1);
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