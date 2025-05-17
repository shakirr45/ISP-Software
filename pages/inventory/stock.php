<?php
// Fetch suppliers from database
$suppliers = $obj->view_all('suppliers');
?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">Paid Biller List</h5>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="supplierTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Product Name</th>
                    <th scope="col">Batch No</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Status</th>
                    <th scope="col">Supplier</th>
                    <th scope="col">Created By</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#supplierTable').DataTable({
            ajax: {
                url: './pages/inventory/stock_ajax.php',
                dataSrc: '',
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'product_name'
                },
                {
                    data: 'batch_id'
                },
                {
                    data: 'current_stock',
                    render: function(data, type, row) {
                        // Add "Low Stock" warning
                        return data == row.minimum_threshold ?
                            `<span class="low-stock text-danger">${data} (Stock Out)</span>` :
                            data;
                    },
                },

                {
                    data: 'current_stock',
                    render: function(data, type, row) {
                        var stock = data; // Get the current stock value
                        var buttonClass = '';
                        var buttonText = '';

                        // Apply logic to determine the button color and text
                        if (stock < 3 && stock > 0) {
                            buttonClass = 'btn-danger'; // Red for low stock
                            buttonText = 'Low Stock';
                        } else if (stock < 5 && stock > 0) {
                            buttonClass = 'btn-warning'; // Yellow for warning
                            buttonText = 'Warning';
                        } else if (stock == 0) {
                            buttonClass = 'btn-danger'; // Red for zero stock
                            buttonText = 'Stock Out';
                        } else {
                            buttonClass = 'btn-success'; // Green for sufficient stock
                            buttonText = 'Sufficient Stock';
                        }

                        // Return the button HTML with the determined class and text
                        return '<button class="btn ' + buttonClass + '">' + buttonText + '</button>';
                    }
                },
                {
                    data: 'supplier_name'
                }, {
                    data: 'FullName'
                }
                // {
                //     data: null,
                //     render: function(data) {
                //         return `
                //             <button class="btn btn-warning btn-sm edit-btn" data-id="${data.supplier_id}" data-name="${data.supplier_name}" data-contact="${data.contact_info}" data-address="${data.address}" data-bs-toggle="modal" data-bs-target="#editSupplierModal">Edit</button>
                //             <button class="btn btn-danger btn-sm delete-btn" data-id="${data.supplier_id}">Delete</button>
                //         `;
                //     },
                // },
            ],
        });

    });
</script>
<?php $obj->end_script(); ?>