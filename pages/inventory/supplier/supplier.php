<?php
// Fetch suppliers from database
$suppliers = $obj->view_all('suppliers');
?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">Supplier List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#addSupplierModal">Create Supplier</button>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="supplierTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Supplier Name</th>
                    <th scope="col">Contact Info</th>
                    <th scope="col">Address</th>
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
<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="addSupplierLabel">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSupplierForm">
                    <div class="mb-3">
                        <label for="supplierName" class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" id="supplierName" required>
                    </div>
                    <div class="mb-3">
                        <label for="contactInfo" class="form-label">Contact Info</label>
                        <input type="text" class="form-control" id="contactInfo" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" required>
                    </div>
                    <button type="submit" class="btn btn-success">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="editSupplierLabel">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSupplierForm">
                    <input type="hidden" id="editSupplierId">
                    <div class="mb-3">
                        <label for="editSupplierName" class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" id="editSupplierName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editContactInfo" class="form-label">Contact Info</label>
                        <input type="text" class="form-control" id="editContactInfo" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="editAddress" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#supplierTable').DataTable({
            ajax: {
                url: './pages/inventory/supplier/supplier_ajax.php',
                dataSrc: '',
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'supplier_name'
                },
                {
                    data: 'contact_info'
                },
                {
                    data: 'address'
                },
                {
                    data: 'FullName'
                },
                {
                    data: null,
                    render: function(data) {
                        return `
                            <button class="btn btn-warning btn-sm edit-btn" data-id="${data.supplier_id}" data-name="${data.supplier_name}" data-contact="${data.contact_info}" data-address="${data.address}" data-bs-toggle="modal" data-bs-target="#editSupplierModal">Edit</button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="${data.supplier_id}">Delete</button>
                        `;
                    },
                },
            ],
        });

        // Add Supplier Logic
        $('#addSupplierForm').submit(function(e) {
            e.preventDefault();
            const supplier_name = $('#supplierName').val();
            const contact_info = $('#contactInfo').val();
            const address = $('#address').val();

            $.ajax({
                url: './pages/inventory/supplier/supplier_ajax.php',
                method: 'POST',
                data: {
                    supplier_name,
                    contact_info,
                    address
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#addSupplierModal').modal('hide');
                        Swal.fire({
                            title: 'Success',
                            text: 'Supplier added successfully!',
                            icon: 'success',
                            confirmButtonText: 'Ok'
                        });
                        table.ajax.reload();
                        $('#addSupplierForm')[0].reset();
                    } else {
                        alert('Failed to add supplier!');
                    }
                },
            });
        });

        // Edit Supplier
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const supplier_name = $(this).data('name');
            const contact_info = $(this).data('contact');
            const address = $(this).data('address');
            $('#editSupplierId').val(id);
            $('#editSupplierName').val(supplier_name);
            $('#editContactInfo').val(contact_info);
            $('#editAddress').val(address);
        });

        // Edit Supplier Logic
        $('#editSupplierForm').submit(function(e) {
            e.preventDefault();
            const id = $('#editSupplierId').val();
            const supplier_name = $('#editSupplierName').val();
            const contact_info = $('#editContactInfo').val();
            const address = $('#editAddress').val();

            $.ajax({
                url: './pages/inventory/supplier/supplier_ajax.php',
                method: 'POST',
                data: {
                    id,
                    supplier_name,
                    contact_info,
                    address
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#editSupplierModal').modal('hide');
                        Swal.fire({
                            title: 'Success',
                            text: 'Supplier updated successfully!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,


                        });
                        table.ajax.reload();
                    } else {
                        alert('Failed to update supplier!');
                    }
                },
            });
        });

        // Delete Supplier
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
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
                        url: './pages/inventory/supplier/supplier_ajax.php',
                        method: 'POST',
                        data: {
                            id,
                            action: 'delete'
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