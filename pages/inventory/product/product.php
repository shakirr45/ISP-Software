<?php
$categories = $obj->view_all('product_categories');
?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">Product List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#addProductModal">Create Product</button>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="productTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Unit Type</th>
                    <th scope="col">Category</th>
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



<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="addProductLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm">
                    <div class="mb-3">
                        <label for="productName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="productName" required>
                    </div>
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU</label>
                        <input type="text" class="form-control" id="sku" required>
                    </div>
                    <div class="mb-3">
                        <label for="unitType" class="form-label">Unit Type</label>
                        <select id="unitType" class="form-control wizard-required" required>
                            <option value="1">Piece</option>
                            <option value="2">Meter</option>
                            <option value="3">Foot</option>
                        </select>
                        <div class="wizard-form-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="productCategory" class="form-label">Category</label>
                        <select id="productCategory" class="form-control wizard-required" required>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?= $category['category_id']; ?>"><?= $category['category_name']; ?></option>
                            <?php } ?>
                        </select>
                        <div class="wizard-form-error"></div>
                    </div>
                    <button type="submit" class="btn btn-success">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="editProductLabel">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProductForm">
                    <input type="hidden" id="editProductId">
                    <div class="mb-3">
                        <label for="editProductName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="editProductName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProductSku" class="form-label">SKU</label>
                        <input type="text" class="form-control" id="editProductSku" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProductUnitType" class="form-label">Unit Type</label>
                        <select id="editProductUnitType" class="form-control wizard-required" required>
                            <option value="1">Piece</option>
                            <option value="2">Meter</option>
                            <option value="3">Foot</option>
                        </select>
                        <div class="wizard-form-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="editProductCategory" class="form-label">Category</label>
                        <select id="editProductCategory" class="form-control wizard-required" required>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?= $category['category_id']; ?>"><?= $category['category_name']; ?></option>
                            <?php } ?>
                        </select>
                        <div class="wizard-form-error"></div>
                    </div>
                    <button type="submit" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>



<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#productTable').DataTable({
            ajax: {
                url: './pages/inventory/product/product_ajax.php',
                dataSrc: '',
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'product_name'
                },
                {
                    data: 'sku'
                },
                {
                    data: 'unit_type',
                    render: function(data) {
                        return data == '1' ? 'Piece' : data == '2' ? 'Meter' : 'Foot';
                    }
                },
                {
                    data: 'category_name'
                },
                {
                    data: 'FullName'
                },
                {
                    data: null,
                    render: function(data) {
                        return `
                <button class="btn btn-warning btn-sm edit-btn" data-id="${data.product_id}" data-name="${data.product_name}" data-sku="${data.sku}" data-unit_type="${data.unit_type}" data-category_id="${data.category_id}" data-bs-toggle="modal" data-bs-target="#editProductModal">Edit</button>
                <button class="btn btn-danger btn-sm delete-btn" data-id="${data.product_id}">Delete</button>
              `;
                    },
                },
            ],
        });

        $('#addProductForm').submit(function(e) {
            e.preventDefault();
            const product_name = $('#productName').val();
            const sku = $('#sku').val();
            const unit_type = $('#unitType').val();
            const category_id = $('#productCategory').val();

            $.ajax({
                url: './pages/inventory/product/product_ajax.php',
                method: 'POST',
                data: {
                    product_name,
                    sku,
                    unit_type,
                    category_id
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#addProductModal').modal('hide');
                        table.ajax.reload();
                        $('#addProductForm')[0].reset();
                    } else {
                        alert('Failed to add product!');
                    }
                },
            });

        });

        // Edit Product
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const product_name = $(this).data('name');
            const sku = $(this).data('sku');
            const unit_type = $(this).data('unit_type');
            const category_id = $(this).data('category_id');
            $('#editProductId').val(id);
            $('#editProductName').val(product_name);
            $('#editProductSku').val(sku);
            // Manually set selected option in Unit Type dropdown
            $('#editProductUnitType option').each(function() {
                if ($(this).val() == unit_type) {
                    $(this).prop('selected', true); // Set the selected attribute
                } else {
                    $(this).prop('selected', false); // Remove the selected attribute from others
                }
            });

            // Manually set selected option in Category dropdown
            $('#editProductCategory option').each(function() {
                if ($(this).val() == category_id) {
                    $(this).prop('selected', true); // Set the selected attribute
                } else {
                    $(this).prop('selected', false); // Remove the selected attribute from others
                }
            });
        });
        $('#editProductForm').submit(function(e) {
            e.preventDefault();
            const id = $('#editProductId').val();
            const product_name = $('#editProductName').val();
            const sku = $('#editProductSku').val();
            const unit_type = $('#editProductUnitType').val();
            const category_id = $('#editProductCategory').val();
            console.log(id, product_name, sku, unit_type, category_id);
            $.ajax({
                url: './pages/inventory/product/product_ajax.php',
                method: 'POST',
                data: {
                    id,
                    product_name,
                    sku,
                    unit_type,
                    category_id
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#editProductModal').modal('hide');
                        table.ajax.reload();
                    } else {
                        alert('Failed to update product!');
                    }
                },
            });
        });

        // Delete Product
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            const action = 'delete';
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: './pages/inventory/product/product_ajax.php',
                        method: 'POST',
                        data: {
                            id,
                            action
                        },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                table.ajax.reload();
                            }
                        },
                    });
                }
            });
        });

    });
</script>
<?php $obj->end_script(); ?>