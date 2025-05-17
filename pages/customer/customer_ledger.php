<?php
$monthArray = array(
    "1" => 'January',
    "2" => 'February',
    "3" => 'March',
    "4" => 'April',
    "5" => 'May',
    "6" => 'June',
    "7" => 'July',
    "8" => 'August',
    "9" => 'September',
    "10" => 'October',
    "11" => 'November',
    "12" => 'December',
);
?>

<!-- hidden inputs -->
<input hidden type="text" id="token" value="<?= $_GET["token"] ?>">

<div class="card h-100 p-0 radius-12">
    <div class="card-body p-24">
        <div class="row gy-4">
            <div class="col-xxl-6 col-md-6 user-grid-card mx-auto">
                <div class="position-relative border radius-16 overflow-hidden">
                    <img src="assets/images/user-grid/user-grid-bg1.png" alt="" class="w-100 object-fit-cover">
                    <div class="ps-16 pb-16 pe-16 text-center mt--50">
                        <img src="assets/images/user-grid/user-grid-img1.png" alt="" class="border br-white border-width-2-px w-100-px h-100-px rounded-circle object-fit-cover">
                        <h6 class="text-lg mb-0 mt-4">Name: <span id="agentName"></span></h6>
                        <span class="text-secondary-light mb-16">Customer Id: <span id="customer_id"></span></span>

                        <div class="position-relative bg-danger-gradient-light radius-8 p-12 d-flex align-items-center gap-4">
                            <div class="text-center w-50">
                                <h6 class="text-md mb-0">Package</h6>
                                <span class="text-secondary-light text-sm mb-0" id="package"></span>
                            </div>
                            <div class="text-center w-50">
                                <h6 class="text-md mb-0">IP Address</h6>
                                <span class="text-secondary-light text-sm mb-0" id="ipAddress"></span>
                            </div>
                            <div class="text-center w-50">
                                <h6 class="text-md mb-0">Taka</h6>
                                <span class="text-secondary-light text-sm mb-0" id="taka"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-24 gy-0">
            <div class="col-xxl-3 col-sm-6 pe-0">
                <div class="card-body p-20 bg-base border h-100 d-flex flex-column justify-content-center border-end-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">
                        <div>
                            <span class="mb-12 w-44-px h-44-px text-primary-600 bg-primary-light border border-primary-light-white flex-shrink-0 d-flex justify-content-center align-items-center radius-8 h6 mb-12">
                                <iconify-icon icon="solar:wallet-bold" class="icon"></iconify-icon>
                            </span>
                            <span class="mb-1 fw-medium text-secondary-light text-md">Bill Amount</span>
                            <h6 class="fw-semibold text-primary-light mb-1" id="bill_amount">0</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6 px-0">
                <div class="card-body p-20 bg-base border h-100 d-flex flex-column justify-content-center border-end-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">
                        <div>
                            <span class="mb-12 w-44-px h-44-px text-success bg-success-focus border border-success-light-white flex-shrink-0 d-flex justify-content-center align-items-center radius-8 h6 mb-12">
                                <iconify-icon icon="streamline:bag-dollar-solid" class="icon"></iconify-icon>
                            </span>
                            <span class="mb-1 fw-medium text-secondary-light text-md">Total Paid</span>
                            <h6 class="fw-semibold text-primary-light mb-1" id="total_paid_amount">0</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6 px-0">
                <div class="card-body p-20 bg-base border h-100 d-flex flex-column justify-content-center border-end-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">
                        <div>
                            <span class="mb-12 w-44-px h-44-px text-pink bg-pink-light border border-pink-light-white flex-shrink-0 d-flex justify-content-center align-items-center radius-8 h6 mb-12">
                                <iconify-icon icon="ri:discount-percent-fill" class="icon"></iconify-icon>
                            </span>
                            <span class="mb-1 fw-medium text-secondary-light text-md">Total Discount</span>
                            <h6 class="fw-semibold text-primary-light mb-1" id="total_discount_amount">0</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6 ps-0">
                <div class="card-body p-20 bg-base border h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">
                        <div>
                            <span class="mb-12 w-44-px h-44-px text-red bg-danger-focus border border-danger-light-white flex-shrink-0 d-flex justify-content-center align-items-center radius-8 h6 mb-12">
                                <iconify-icon icon="fa6-solid:file-invoice-dollar" class="icon"></iconify-icon>
                            </span>
                            <span class="mb-1 fw-medium text-secondary-light text-md">Due Amount</span>
                            <h6 class="fw-semibold text-primary-light mb-1" id="previous_due">0</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Customer Ledger</h5>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="customer-ledger-table"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL.</th>
                    <th scope="col">Date</th>
                    <th scope="col">Description</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Received By</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col">Total:</th>
                    <th scope="col"></th>
                    <th scope="col"></th>
                    <th scope="col" id="total-amount"></th>
                    <th scope="col"></th>
                    <th scope="col"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#customer-ledger-table').DataTable({
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
            // serverSide: true,
            ajax: {
                url: "./pages/customer/customer_ledger_ajax.php",
                type: "GET",
                data: function(d) {
                    d.token = $("#token").val();
                }
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'date'
                },
                {
                    data: 'description'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'received_by'
                },
                {
                    data: 'acc_id',
                    // width: '7%',
                    render: function(data) {
                        return `<a target="_blank" href="pages/print/payment_invoice.php?token=${data}" class="btn btn-xs btn-success">
                                       Print
                                    </a>`;
                    }
                },
            ],
            buttons: [{
                extend: "pdf",
                className: "btn-success",
                text: "Print Pdf",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },

                customize: function(doc) {
                    // Initialize the title with a base string
                    var title = 'View Individual Customer Payment';

                    // Update the title in the PDF
                    doc.content.splice(0, 1, {
                        text: title,
                        fontSize: 16,
                        bold: true,
                        alignment: 'center',
                        margin: [0, 0, 0, 20], // [left, top, right, bottom]
                    });
                },
            }],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search customers...",
                lengthMenu: "Show _MENU_ entries",
                emptyTable: "No data available in table"
            },
            lengthMenu: [10, 25, 50, 100, 500],
            order: [
                [1, 'asc']
            ],
            drawCallback: function(settings) {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                console.log('history', settings.json);
                $("#total-amount").text(settings.json?.totalAmount);
                $("#bill_amount").text(settings.json?.billing_info?.monthlybill);
                $("#total_paid_amount").text(settings.json?.billing_info?.totalpaid);
                $("#total_discount_amount").text(settings.json?.billing_info?.totaldiscount);
                $("#previous_due").text(settings.json?.billing_info?.dueadvance);
                // $("#billig-type-col").text($("#billing-type option:selected").text())

                // agent information
                const agentInfo = settings.json?.agentInfo;
                $("#agentName").text(agentInfo?.ag_name);
                $("#customer_id").text(agentInfo?.cus_id);
                $("#agentMobile").text(agentInfo?.ag_mobile_no);
                $("#package").text(agentInfo?.mb);
                $("#ipAddress").text(agentInfo?.ip);
                $("#taka").text(agentInfo?.taka);
            },
            initComplete: function(settings, json) {}
        });

        // Trigger table reload when filters are changed
        $('#select-month, #select-year, #billing-type, #zone-filter').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
<?php $obj->end_script(); ?>