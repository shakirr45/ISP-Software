<?php
$allAgentData = $obj->view_all_agent_for_excel('tbl_agent', 'ag_status = 1');
?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="text-md text-neutral-500">BTRC – Comprehensive Network and Client Data Report</h5>
    </div>
    <div class="card-body table-responsive">
        <table class="table bordered-table mb-0" id="report-table" data-page-length="10">
            <thead>
                <!-- Header Information -->
                <!-- <tr class="header-row">
                    <td colspan="4"><b>Category ISP Name:</b> BSD</td>
                    <td colspan="4"><b>Reporting Month:</b> <?php echo date("F"); ?></td>
                </tr>
                <tr class="header-row">
                    <td colspan="4"><b>Type of Category:</b> CAT-C</td>
                    <td colspan="4"><b>Year:</b> <?php echo date("Y"); ?></td>
                </tr>
                <tr class="header-row">
                    <td colspan="4"><b>Permitted Area/Thana:</b> Dhakshin Khan</td>
                    <td colspan="4"></td>
                </tr> -->

                <!-- Main Table Headers -->
                <tr>
                    <th scope="col">SL</th>
                    <th scope="col">Name of Operator</th>
                    <th scope="col">Type of Clients</th>
                    <th scope="col">Distribution Location</th>
                    <th scope="col">Name of Clients</th>
                    <th scope="col">Type of Connection</th>
                    <th scope="col">Type of Connectivity</th>
                    <th scope="col">Activation Date</th>
                    <th scope="col">Bandwidth Allocation (MB)</th>
                    <th scope="col">Allocated IP</th>
                    <th scope="col">House No</th>
                    <th scope="col">Road No</th>
                    <th scope="col">Area</th>
                    <th scope="col">District</th>
                    <th scope="col">Thana</th>
                    <th scope="col">Client Phone</th>
                    <th scope="col">Mail</th>
                    <th scope="col">Selling Bandwidth BDT (Excl. VAT)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($allAgentData as $agent) {
                    echo "<tr>";
                    echo "<td>" . $i++ . "</td>";
                    echo "<td>" . ($agent['Name_of_Operator'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Type_of_Clients'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Distribution_Location_Point'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Name_of_Clients'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Type_of_Connection'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Type_of_Connectivity'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Activation_Date'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Bandwidth_Allocation_MB'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Allowcated_Ip'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['House_No'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Road_No'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Area'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['District'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Thana'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Client_Phone'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Mail'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($agent['Selling_BandwidthBDT_Excluding_VAT'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$categoryIspName = "BSD";
$reportingMonth = date("F");
$typeOfCategory = "CAT-C";
$year = date("Y");
$permittedArea = "Uttara";
?>

<?php $obj->start_script(); ?>



<script>
    $(document).ready(function() {
        let messageTopContent = `
        Category ISP Name: <?php echo $categoryIspName; ?>\n
        Type of Category: <?php echo $typeOfCategory; ?>\n
        Reporting Month: <?php echo $reportingMonth; ?>\n
        Year: <?php echo $year; ?>\n
        Permitted Area/Thana: <?php echo $permittedArea; ?>
    `;

        $('#report-table').DataTable({
            dom: '<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>' +
                '<"row"<"col-sm-12 text-end"B>>' +
                '<"row dt-layout-row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>',
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            buttons: [{
                extend: "excel",
                className: "btn-light",
                title: "BTRC Report",
                messageTop: messageTopContent
            }]
        });
    });
</script>


<?php $obj->end_script(); ?>