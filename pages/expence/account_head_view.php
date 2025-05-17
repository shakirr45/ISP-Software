<?php include('account_head.php') ?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Account Head List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#con-close-modal">Create Head</button>

    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
                            id="head-table"
                            data-page-length="10">
            <thead>
                <tr>
                                <th>#</th>                               
                                <th scope="col" >Account Name</th>
                                <th scope="col" >Account Description</th>
                                <th scope="col" >Edit head</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php  $i = 1; foreach ($viewAccountHead as $value) : ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo isset($value['acc_name']) ? $value['acc_name'] : NULL;  ?></td>
                                <td><?php echo isset($value['acc_desc']) ? $value['acc_desc'] : NULL;  ?></td>
                                <td><button type="button" class="btn btn-warning waves-effect waves-light account_head_update" 
                                 data-acc_id="<?php echo @$value['acc_id'] ?>"
                                 data-acc_name="<?php echo @$value['acc_name'] ?>" 
                                 data-acc_desc="<?php echo @$value['acc_desc'] ?>" 
                                 data-acc_active="<?php echo @$value['acc_status'] ?>" 
                                 
                                 data-bs-toggle="modal" 
                                 data-bs-target="#con-close-modal"><span  class="fas fa-edit"></span></button></td>
                            </tr>                  
                        <?php   endforeach; ?>
                        </tbody>                                                        
                    </table>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>
</div>



<!--head  modal content -->
<div class="modal fade" id="con-close-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="exampleModalLabel" class="modal-title fs-5">Account Head</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">  
            <div class="modal-body">                        
                <div class="modal-body p-4">                            
                    <div class="row">
                        <div class="col-md-12">
                        <input  type="hidden" name="head_id" id="head_id">
                            <div class="mb-3">
                            <label for="name_id" class="form-label">Account Head Name*</label>
                            <input type="text" id="name_id" name="name" class="form-control" required>                             
                            </div>
                        </div> 
                        <div class="col-md-12">
                            <div class="mb-3">
                            <label for="description_id" class="form-label">Account Head Details</label>
                            <input type="text" id="description_id" name="details" class="form-control" >
                            </div>
                        </div>        
                        <div class="col-md-12">
                            <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                                 <select id="active_id" name="status" class="form-control" >
                                 <option value="1">Active</option>
                                 <option value="0">Inactive</option>
                                 </select> 
                            </div>
                        </div>                    
                    </div>                          
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="submit" class="btn btn-success waves-effect waves-light">Save changes</button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div><!-- /.modal -->   
<?php $obj->start_script(); ?>
<script>
      $(document).ready(function() {
        $('#head-table').DataTable({
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
    var elements = document.getElementsByClassName('account_head_update');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {
            var dataId = this.getAttribute('data-acc_id');
            var accname = this.getAttribute('data-acc_name');
            var accdes = this.getAttribute('data-acc_desc');
            var accactive = this.getAttribute('data-acc_active');
            document.getElementById('head_id').value = dataId;
            document.getElementById('name_id').value = accname;
            document.getElementById('description_id').value = accdes;
            document.getElementById('active_id').value = accactive;
            
        });
    }
</script>
<?php $obj->end_script(); ?>