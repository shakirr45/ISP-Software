<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">Product Category List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Create Category</button>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="categoryTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Category Name</th>
                    <th scope="col">Description</th>
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



<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="addCategoryLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="editCategoryLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editCategoryDescription" rows="3" required></textarea>
                    </div>
                    <input type="hidden" id="editCategoryId">
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#categoryTable').DataTable({
            ajax: {
                url: './pages/inventory/product/product_category_ajax.php',
                dataSrc: '',
            },
            columns: [{
                    data: 'category_id'
                },
                {
                    data: 'category_name'
                },
                {
                    data: 'description'
                },
                {
                    data: 'FullName'
                },
                {
                    data: null,
                    render: function(data) {
                        return `
                <button class="btn btn-warning btn-sm edit-btn" data-id="${data.category_id}" data-name="${data.category_name}" data-description="${data.description}" data-bs-toggle="modal" data-bs-target="#editCategoryModal">Edit</button>
              `;
                    },
                },
            ],
        });

        // Add Category
        $('#addCategoryForm').submit(function(e) {
            e.preventDefault();
            const name = $('#categoryName').val();
            const description = $('#categoryDescription').val();

            $.ajax({
                url: './pages/inventory/product/product_category_ajax.php',
                method: 'POST',
                data: {
                    name,
                    description
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        table.ajax.reload();
                        $('#addCategoryModal').modal('hide');
                        $('#addCategoryForm')[0].reset();
                    }
                },
            });
        });

        // Populate Edit Modal
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const description = $(this).data('description');
            $('#editCategoryId').val(id);
            $('#editCategoryName').val(name);
            $('#editCategoryDescription').val(description);
        });

        // Edit Category
        $('#editCategoryForm').submit(function(e) {
            e.preventDefault();
            const id = $('#editCategoryId').val();
            const name = $('#editCategoryName').val();
            const description = $('#editCategoryDescription').val();
            $.ajax({
                url: './pages/inventory/product/product_category_ajax.php',
                method: 'POST',
                data: {
                    id,
                    name,
                    description
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        table.ajax.reload();
                        $('#editCategoryModal').modal('hide');
                    }
                },
            });
        });
    });
</script>
<?php $obj->end_script(); ?>