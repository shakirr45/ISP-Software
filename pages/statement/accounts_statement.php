<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label for="zone-filter" class="form-label">Zone</label>
                            <div class="position-relative">
                                <select id="zone-filter" class="form-control">
                                    <option value="">All Zones</option>
                                    <?php foreach ($obj->getAllData('tbl_zone') as $zone): ?>
                                        <option value="<?php echo $zone['zone_id']; ?>"><?php echo $zone['zone_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label for="billing-person-filter" class="form-label">Billing Person</label>
                            <div class="position-relative">
                                <select id="billing-person-filter" class="form-control">
                                    <option value="">All Billing Persons</option>
                                    <?php foreach ($obj->getAllData('vw_user_info') as $bp): ?>
                                        <option value="<?php echo $bp['UserId']; ?>"><?php echo $bp['FullName']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label for="collection-person-filter" class="form-label">Collection Person</label>
                            <div class="position-relative"><select id="collection-person-filter" class="form-control">
                                    <option value="">All Billing Persons</option>
                                    <?php foreach ($obj->getAllData('vw_user_info') as $bp): ?>
                                        <option value="<?php echo $bp['UserId']; ?>"><?php echo $bp['FullName']; ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                        </div>
                        <div class="col-sm-2">
                            <label for="date-from" class="form-label">From Date</label>
                            <div class="position-relative">
                                <input type="date" id="date-from" value="<?php echo date('Y-m-01') ?>" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="date-to" class="form-label">To Date</label>
                            <div class="position-relative">
                                <input type="date" id="date-to" value="<?php echo date('Y-m-t') ?>" class="form-control">
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <!-- Form Wizard End -->
        </div>
    </div>
</div>
<div class="col-md-12">
    <div class="card h-100 p-0 radius-12">
    <div class="card-body p-24">
        <div class="mt-24">
            <div class="row mt-24 gy-0">
                <div class="col-xxl-3 col-sm-6">
                    <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-1">
                        <div class="card-body p-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                <div class="d-flex align-items-center gap-2">
                                    <span class="mb-0 w-48-px h-48-px bg-primary-600 flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6 mb-0">
                                        <iconify-icon icon="hugeicons:invoice-03" class="icon"></iconify-icon>
                                    </span>
                                    <div>
                                        <p class="mb-2 fw-medium text-secondary-light text-sm">Total Balance : </p>
                                        <h6 class="fw-semibold"><span id="balance">0</span></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-2">
                        <div class="card-body p-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                <div class="d-flex align-items-center gap-2">
                                    <span class="mb-0 w-48-px h-48-px bg-success-main flex-shrink-0 text-white d-flex justify-content-center align-items-center rounded-circle h6">
                                        <iconify-icon icon="solar:wallet-bold" class="icon"></iconify-icon>
                                    </span>
                                    <div>
                                        <p class="mb-2 fw-medium text-secondary-light text-sm">Total Credit : </p>
                                        <h6 class="fw-semibold"><span id="credit_total">0</span></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <div class="card p-3 shadow-none radius-8 border h-100 bg-gradient-end-3">
                        <div class="card-body p-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">

                                <div class="d-flex align-items-center gap-2">
                                    <span class="mb-0 w-48-px h-48-px bg-yellow text-white flex-shrink-0 d-flex justify-content-center align-items-center rounded-circle h6">
                                        <iconify-icon icon="iconamoon:discount-fill" class="icon"></iconify-icon>
                                    </span>
                                    <div>
                                        <p class="mb-2 fw-medium text-secondary-light text-sm">Total Debit :</p>
                                        <h6 class="fw-semibold"><span id="debit_total">0</span></h6>
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
        <h5 class="card-title mb-0">Account Statement</h5>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="account-datatable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Date</th>
                    <th scope="col">Particular</th>
                    <th scope="col">Head/Customer</th>
                    <th scope="col">EntryBy</th>
                    <th scope="col">Credit</th>
                    <th scope="col">Debit</th>
                    <th scope="col">Balance</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
</div>


<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {

        var table = $('#account-datatable').DataTable({
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` + // Show entries and search in one row
                `<"row"<"col-sm-12 text-end"B>>` + // Buttons in a separate row
                `<"row dt-layout-row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right,
            keys: true,
            stateSave: true,
            searching:false,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "./pages/statement/accounts_statement_ajax.php",
                type: "GET",
                data: function(d) {
                    d.zone = $('#zone-filter').val();
                    d.bid = $('#billing-person-filter').val();
                    d.cid = $('#collection-person-filter').val();
                    d.date = $('#date-from').val() + '/' + $('#date-to').val();
                },
                // error: function(xhr, error, thrown) {
                //         alert('Error loading data: ' + error);
                //     }
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'entry_date'
                },
                {
                    data: 'acc_description'
                },
                {
                    data: 'acchead'
                },
                {
                    data: 'FullName'
                },
                {
                    data: 'credit'
                },
                {
                    data: 'debit'
                },
                {
                    data: 'balance'
                }
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
            // lengthMenu: [10, 25, 50, 100, 500],
            // order: [[1, 'asc']],
            initComplete: function(settings, json) {
                $('#debit_total').text(json.debit_total); // Update total bill after DataTable is initialized
                $('#credit_total').text(json.credit_total); // Update total bill after DataTable is initialized
                $('#balance').text(json.balance); // Update total bill after DataTable is initialized
                console.log(json);

            },
            drawCallback: function(settings) {
                var json = settings.json;
                $('#debit_total').text(json.debit_total); // Update total bill after each table draw
                $('#credit_total').text(json.credit_total); // Update total bill after each table draw
                $('#balance').text(json.balance); // Update total bill after each table draw\
                console.log(json);
            }
        });

        // Trigger table reload when filters are changed
        $('#zone-filter, #billing-person-filter, #collection-person-filter, #date-to').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
<?php $obj->end_script(); ?>