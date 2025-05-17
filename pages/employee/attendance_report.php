<?php 
    include('employee.php');
    $token =isset($_GET['delete-token']) ? $_GET['delete-token']:NULL;

        $obj->deleteData("tbl_employee",['where'=>['id','=',$token]]);
    ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Employee</h4>
                <p class="text-muted font-13 mb-4">Employee</p>

                <button type="button" class="btn btn-info waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#con-close-modal"><i class="mdi mdi-plus me-1"></i> Add New Employee</button>
                <br>
                <br>
                <div class="table-responsive">
                    <table  class="table dt-responsive activate-select table-striped nowrap w-100 datatable ">
                        <thead class="table-light">                                   
                            <tr>

				 				
                                <th >SL</th>                               
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                          
                                <th>Attendance Date</th>
                                <th>In Time</th>
                                <th>Out Time</th>
                                <th>Status</th>
                
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $attendance = $obj->rawSql("SELECT * FROM attendance LEFT JOIN tbl_employee ON attendance.employee_id = tbl_employee.id");
                
                        $i = 1; foreach ($attendance as $value)
                       
                        { ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo isset($value['employee_id']) ? $value['employee_id'] : NULL;  ?></td>
                                <td><?php echo isset($value['employee_name']) ? $value['employee_name'] : NULL;  ?></td>
                                <td><?php echo isset($value['attendance_date']) ? $value['attendance_date'] : NULL;  ?></td>
                                <td><?php echo isset($value['in_time']) ? $value['in_time'] : NULL;  ?></td>
                                <td><?php echo isset($value['out_time']) ? $value['out_time'] : NULL;  ?></td>
                                <td><?php echo isset($value['status']) ? $value['status'] : NULL;  ?></td>
    
                            </tr>                  
                        <?php   } ?>
                        </tbody>                                                        
                    </table>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

