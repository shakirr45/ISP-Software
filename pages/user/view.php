<?php include('package.php') ?>


<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Package List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#con-close-modal">Create Package</button>

    </div>
    <div class="card-body">
        <table
            class="table bordered-table mb-0"
            id="dataTable"
            data-page-length="10">
            <thead>
                <tr>
                    
                </tr>
            </thead>
            <tbody>
                
            </tbody>
        </table>
    </div>
</div>
<!-- end row-->

<!-- update modal content -->
<div class="modal fade" id="con-close-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Package</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit" class="btn btn-success waves-effect waves-light">Save changes</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>


<script>
    var elements = document.getElementsByClassName('passwordchange');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {
            var dataId = this.getAttribute('data-passwordid');
            document.getElementById('passwordid').value = dataId;
        });
    }

    document.getElementById('passwordForm').addEventListener('submit', function(event) {
        var password = document.getElementById('newPassword').value;
        var confirmPassword = document.getElementById('confirmPassword').value;
        if (password !== confirmPassword) {
            alert('Passwords do not match. Please try again.');  
            document.getElementById('confirmPassword').value="";          
            event.preventDefault();
        }
    });
</script>
<?php $obj->end_script(); ?>