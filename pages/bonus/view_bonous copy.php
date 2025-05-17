<?php 

if (isset($_POST['search'])) {
   
    $dateform = date('Y-m-d', strtotime($_POST['dateform']));
    $dateto = date('Y-m-d', strtotime($_POST['dateto']));
    //$expenseDetails = $obj->getAllData("vw_account", "entry_date BETWEEN '" . date('Y-m-d', strtotime($dateform)) . "' and '" . date('Y-m-d', strtotime($dateto)) . "' AND acc_type='1' ORDER BY entry_date DESC");
    $bonus = $obj->rawSql("SELECT * FROM bonus WHERE entry_date BETWEEN '$dateform' AND '$dateto' GROUP BY ag_id ");


} else {
    $firsDayOfMonth = new DateTime('first day of this month');
    $dateform = $firsDayOfMonth->format('Y-m-d');
    $dateto = date('Y-m-d');
    $bonus = $obj->rawSql("SELECT * FROM bonus WHERE MONTH(entry_date) = MONTH(CURRENT_DATE) AND YEAR(entry_date) = YEAR(CURRENT_DATE) GROUP BY ag_id ;");

    //$obj->getAllData("vw_account", "MONTH(entry_date)='" . date('m') . "' and YEAR(entry_date)='" . date('Y') . "'  AND acc_type='1' ORDER BY entry_date DESC");
}
$previewDate = date('d M Y', strtotime($dateform)) . ' to ' . date('d M Y', strtotime($dateto));



 ?>
<div class="row">

    <div class="col-md-12">
    <form action="" method="POST">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">View Discount <?php echo $previewDate ?></h4>
                <p class="text-muted font-13 mb-4">Discount</p>
                <div class="row mb-4">
                <div class="col-md-4">
                        
                    </div>
                    <div class="col-md-3">
                        <label for="date-from">From Date</label>
                        <input type="date" class="form-control "
                               value="<?php echo date('d-m-Y', strtotime($dateform)); ?>" placeholder="Date"
                               name="dateform"
                               id="new_flight_date" required>
                    </div>
                    <div class="col-md-3">
                        <label for="date-to">To Date</label>
                        <input style="color: black;" id="old_flight_date"
                               value="<?php echo date('d-m-Y', strtotime($dateto)); ?>" type="date"
                               class="form-control "
                               placeholder="Date" name="dateto" required>
                    </div>
                    <div class="col-md-2">
                    <input type="submit" name="search" class="btn btn-primary" value="Search">
                    </div>
                </div>
                <!-- <button type="button" class="btn btn-info waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#con-close-modal"><i class="mdi mdi-plus me-1"></i> Add New Income</button> -->
                <br>
                <br>
                <div class="table-responsive">
                    <table  class="table dt-responsive activate-select table-striped nowrap w-100 datatable ">
                        <thead class="table-light">                                   
                            <tr>
                                <th >#</th>                               
                                <th>Date</th>
                                <th>Agent Name</th>
                                <th>Agent Phone</th>
                                <th>Agent address</th>
                                <th>Agent Email</th>
                                <th>Bonus Amount</th>
                               
                                <th>Description</th>
                              
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                $i = '0';
                $totalExpense = 0;
                foreach ($bonus as $value) {
                    $i++;
                    $totalExpense += intval($value['amount']);
                    $viewAgent = $obj->getSingleData("tbl_agent",['where'=>['ag_id','=',$value['ag_id']]]);
                    ?>
                    
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo date("d-m-Y", strtotime(isset($value['entry_date']) ? $value['entry_date'] : "2016-02-1")); ?></td>
                        <td><?php echo isset($viewAgent['ag_name']) ? $viewAgent['ag_name'] : NULL; ?></td>
                        <td><?php echo isset($viewAgent['ag_mobile_no']) ? $viewAgent['ag_mobile_no'] : NULL; ?></td>
                        <td><?php echo isset($viewAgent['ag_office_address']) ? $viewAgent['ag_office_address'] : NULL; ?></td>
                        <td><?php echo isset($viewAgent['ag_email']) ? $viewAgent['ag_email'] : NULL; ?></td>
                        <td><?php echo isset($value['amount']) ? $value['amount'] : NULL; ?></td>
                        
                        
                       
                        <td><?php echo isset($value['description']) ? $value['description'] : NULL; ?></td>
                    </tr>
                    <?php
                }
                ?>
                        </tbody>     
                <tfoot>
                    <!-- <tr>
                        <th colspan="6">Total Income</th>
                        <th colspan="4"><?php echo $totalExpense; ?> tk</th>
                    </tr> -->
                </tfoot>                                                   
                    </table>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </form>
    </div><!-- end col-->
</div>

<script>
    $(document).ready(function () {

        $('input[name="dateform"]').datepicker({
            autoclose: true,
            toggleActive: true,
            format: 'dd-mm-yyyy'
        });

        $('input[name="dateto"]').datepicker({
            autoclose: true,
            toggleActive: true,
            format: 'dd-mm-yyyy'
        });
    });

</script>

