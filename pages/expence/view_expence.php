<?php

include('expence.php');



?>


<div class="row mt-3">
    <div class="col-md-12">
        <form action="" method="POST">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">View Expense Information of <?php echo $previewDate; ?></h4>
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
                        <div class="col-md-2" style="margin-top: 22px;">
                            <input type="submit" name="search" class="btn btn-secondary" value="Search">
                     </div>
                    </div>
                    
                    <button type="button" class="btn btn-success waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#con-close-modal"><i class="mdi mdi-plus me-1"></i>Add New Expense</button>
                    <br>
                    <br>
                    <div class="card basic-data-table">
                    <div class="card-body  table-responsive">
                        <table class="table bordered-table mb-0"
                            id="expense-table"
                            data-page-length="10">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" >#</th>
                                    <th scope="col" >Date</th>
                                    <th scope="col" >Head</th>
                                    <th scope="col" >Sub head</th>
                                    <th scope="col" >Amount</th>
                                    <th scope="col" >Description</th>
                                    <th scope="col" >Last upload</th>
                                    <th scope="col" >Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = '0';
                                $totalExpense = 0;
                                foreach ($expenseDetails as $value) {
                                    $i++;
                                    $totalExpense += intval($value['acc_amount']);

                                ?>
                                    <?php

                                    $viewAccount = $obj->getSingleData("tbl_accounts_head", ['where' => ['acc_id', '=', $value['acc_head']]]);
                                    $viewHead2  = $obj->getSingleData("tbl_accounts_head", ['where' => ['acc_id', '=', $value['acc_sub_head']]]);
                                    ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo date("d-m-Y", strtotime(isset($value['entry_date']) ? $value['entry_date'] : "2016-02-1")); ?></td>
                                        <td><?php echo isset($viewAccount['acc_name']) ? $viewAccount['acc_name'] : NULL; ?></td>
                                        <td><?php echo isset($viewHead2['acc_name']) ? $viewHead2['acc_name'] : NULL; ?></td>
                                        <td><?php echo isset($value['acc_amount']) ? $value['acc_amount'] : NULL; ?></td>
                                        <td><?php echo isset($value['acc_description']) ? $value['acc_description'] : NULL; ?></td>
                                        <td>
                                            <?php
                                            if (date('Y-m-d', strtotime($value['entry_date'])) ==  date('Y-m-d', strtotime($value['last_update']))) {
                                                echo  date('Y-m-d', strtotime($value['entry_date']));
                                            } else {
                                                echo 'Updated at ' . date('d-m-Y', strtotime($value['last_update']));
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">

                                                <button type="button" class="btn btn-warning waves-effect waves-light account_sub_head_update"
                                                    data-acc_id="<?php echo @$value['acc_id'] ?>"
                                                    data-parent_id="<?php echo @$value['acc_head'] ?>"
                                                    data-acc_sub_head="<?php echo @$value['acc_sub_head'] ?>"
                                                    data-acc_desc="<?php echo @$value['acc_description'] ?>"
                                                    data-amount="<?php echo @$value['acc_amount'] ?>"

                                                    data-bs-toggle="modal"
                                                    data-bs-target="#con-close-modal"><span class="fas fa-edit"></span></button>


                                                <a onclick="return confirm('Are You Sure To Delete This Expense ?');"
                                                    href="?page=view_expence&delete-token=<?php echo isset($value['acc_id']) ? $value['acc_id'] : NULL; ?>"
                                                    class="btn btn-danger waves-effect waves-light btn-sm">
                                                    Delete <span class="glyphicon glyphicon-remove"></span>
                                                </a>

                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4">Total Expense</th>
                                    <th colspan="4"><?php echo $totalExpense; ?> tk</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div> <!-- end card body-->
            </div> <!-- end card -->
        </form>
    </div><!-- end col-->
</div>
</div>



<!--zone  modal content -->
<div class="modal fade" id="con-close-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="exampleModalLabel" class="modal-title fs-5">Add new expense</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" name="account_id" id="account_id">
                            <div class="mb-3">

                                <label for="parent_id" class="form-label">Account Head</label>
                                <select name="parent_id" id="parent_id" class="form-control" required>
                                    <option value="">Select</option>
                                    <?php foreach ($viewAccountHead as $value): ?>
                                        <option value="<?php echo $value['acc_id'] ?>"><?php echo $value['acc_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">

                                <label for="child_id" class="form-label">Account Sub-head</label>
                                <select name="child_id" id="child_id" class="form-control" required>
                                    <option value="">Select</option>
                                    <?php foreach ($viewAccountSubHead as $value): ?>
                                        <option value="<?php echo $value['acc_id'] ?>"><?php echo $value['acc_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="name_id" class="form-label">Amount</label>
                                <input type="text" id="amount" name="amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="description_id" class="form-label">Account Details</label>
                                <input type="text" id="description_id" name="details" class="form-control">
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="submit" class="btn btn-success waves-effect waves-light">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div><!-- /.modal -->

<?php $obj->start_script(); ?>

<script>
        $(document).ready(function() {
        $('#expense-table').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` +
            `<"row"<"col-sm-12 text-end"B>>` +
            `<"row dt-layout-row"<"col-sm-12"tr>>` +
            `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`,
        buttons: [
            { extend: 'copy', className: 'btn btn-primary btn-sm' },
            { extend: 'excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm' },
            { extend: 'print', className: 'btn btn-info btn-sm' }
        ],
        responsive: true,
        processing: true,
        serverSide: false, // Set this to true if using server-side processing
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true
        });
    });
    var elements = document.getElementsByClassName('account_sub_head_update');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {
            var dataId = this.getAttribute('data-acc_id');
            var parent_id = this.getAttribute('data-parent_id');
            var acc_sub_head = this.getAttribute('data-acc_sub_head');
            var accdes = this.getAttribute('data-acc_desc');
            var amount = this.getAttribute('data-amount');
            document.getElementById('account_id').value = dataId;
            document.getElementById('parent_id').value = parent_id;
            document.getElementById('child_id').value = acc_sub_head;
            document.getElementById('description_id').value = accdes;
            document.getElementById('amount').value = amount;

        });
    }
</script>
<!-- 
<script>
    $(document).ready(function() {

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
</script> -->

<?php $obj->end_script(); ?>