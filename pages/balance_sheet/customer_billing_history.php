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
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-sm-3">
                            <label class="form-label">Select Month</label>
                            <div class="position-relative">
                                <select id="select-month" name="dateMonth" class="form-control">
                                    <option value="">Select Month</option>
                                    <?php
                                    $currentMonth = date('n');
                                    foreach ($monthArray as $monthKey => $monthVal) {
                                    ?>
                                        <option <?= ($monthKey == $currentMonth) ? 'selected' : '' ?> value="<?= $monthKey ?>">
                                            <?= $monthVal ?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Select Year</label>
                            <div class="position-relative">
                                <select id="select-year" name="dateYear" class="form-control">
                                    <option value="">Select Year</option>
                                    <?php
                                    $currentYear = date('Y');
                                    for ($year = 2010; $year <= $currentYear; $year++) {
                                    ?>
                                        <option <?= ($year == $currentYear) ? 'selected' : '' ?> value="<?= $year ?>">
                                            <?= $year ?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Billing Type</label>
                            <div class="position-relative">
                                <select id="billing-type" name="billingType" class="form-control">
                                    <option selected value="previousdue">Due</option>
                                    <option value="dueadvance">Due Advance</option>
                                    <option value="totalgenerate">Total Generate</option>
                                    <option value="totaldiscount">Total Discount</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Zone</label>
                            <div class="position-relative">
                                <select id="zone-filter" name="zone" class="form-control">
                                    <option value="">All Zones</option>
                                    <?php
                                    $zoneInfo = $obj->rawSql("SELECT DISTINCT zone.zone_name, zone.zone_id
                                FROM `customer_billing` AS billing
                                LEFT JOIN tbl_agent AS agent ON billing.cusid = agent.cus_id
                                LEFT JOIN tbl_zone as zone ON agent.zone = zone.zone_id");
                                    foreach ($zoneInfo as $zone) {
                                    ?>
                                        <option value="<?php echo $zone['zone_id']; ?>"><?php echo $zone['zone_name']; ?> </option>
                                    <?php
                                    }
                                    ?>
                                </select>


                            </div>
                        </div>
                </fieldset>
            </div>
            <!-- Form Wizard End -->
        </div>
    </div>
</div>
<div class="col-md-12">
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Customer Billing History</h5>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="customer-billing-datatable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL</th>
                    <th scope="col">Bill Month</th>
                    <th scope="col">Name</th>
                    <th scope="col">ID</th>
                    <th scope="col">IP</th>
                    <th scope="col">Zone</th>
                    <th scope="col">Address</th>
                    <th scope="col">Mobile No</th>
                    <th scope="col">Package</th>
                    <th scope="col">Monthly Bill</th>
                    <th scope="col" id="billig-type-col">Due</th>
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
        var table = $('#customer-billing-datatable').DataTable({
            dom: `<"row"<"col-sm-6 d-flex align-items-center"B l><"col-sm-6 text-end"f>>` + // Buttons and Show entries dropdown together
                `<"row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Info left, pagination right,
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true,
            // serverSide: true,
            ajax: {
                url: "./pages/balance_sheet/customer_billing_history_ajax.php",
                type: "GET",
                data: function(d) {
                    d.dateMonth = $("#select-month").val();
                    d.dateYear = $("#select-year").val();
                    d.billingType = $("#billing-type").val();
                    d.zone = $("#zone-filter").val() ?? null;
                }
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'bill_month'
                },
                {
                    data: 'customer_name'
                },
                {
                    data: 'customer_id'
                },
                {
                    data: 'ip'
                },
                {
                    data: 'zone'
                },
                {
                    data: 'address'
                },
                {
                    data: 'mobile_no'
                },
                {
                    data: 'package'
                },
                {
                    data: 'monthly_bill'
                },
                {
                    data: 'due',
                    render: function(data, type, row) {
                        console.log(type);

                        return data;
                    }
                },
            ],
            buttons: [{
                extend: "pdfHtml5",
                className: "btn-light",
                text: "Download PDF",
                download: "download",
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
                console.log('dateYear', settings.json);

                $("#billig-type-col").text($("#billing-type option:selected").text())
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