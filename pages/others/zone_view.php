<?php include('zone.php') ?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Zone/Area List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#con-close-modal">Create Zone</button>

    </div>
    <div class="card-body">
        <table
            class="table bordered-table mb-0"
            id="zoneTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL.No</th>
                    <th scope="col">Zone/Area</th>
                    <?php if ($obj->userWorkPermission('edit')) { ?>
                        <th scope="col">Action</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                foreach ($viewzone as $value) { ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td>
                            <a href="javascript:void(0)" class="text-primary-600"><?php echo isset($value['zone_name']) ? $value['zone_name'] : NULL; ?></a>
                        </td>
                        <td>
                            <a
                                href="javascript:void(0)"
                                class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center zoneidupdate" data-zoneid="<?php echo $value['zone_id'] ?>" data-zonename="<?php echo $value['zone_name'] ?>" data-bs-toggle="modal" data-bs-target="#con-close-modal">
                                <!--<iconify-icon icon="lucide:edit"></iconify-icon>-->
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </td>
                    </tr>
                <?php   } ?>
            </tbody>
        </table>
    </div>
</div>

<!--zone  modal content -->
<div class="modal fade" id="con-close-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Zone</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name="zone_id" id="zone_id">
                                <div class="mb-3">
                                    <label for="zone_name" class="form-label">Zone/Area*</label>
                                    <input type="text" class="form-control" onchange="checkOnlyNumber()" name="zone_name" id="zone_name" placeholder="Zone" data-error-message="Please provide a zone name." required>
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
</div>
<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        $('#zoneTable').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
        });
    });
    var elements = document.getElementsByClassName('zoneidupdate');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {
            var dataId = this.getAttribute('data-zoneid');
            var zonename = this.getAttribute('data-zonename');
            document.getElementById('zone_id').value = dataId;
            document.getElementById('zone_name').value = zonename;
        });
    }
    function checkOnlyNumber() {
        var zone_name = document.getElementById('zone_name').value;
        const hasLetters = /[a-zA-Z]/; // Matches any letter
        const hasNumbers = /\d/; // Matches any number
        if (!(zone_name.match(hasLetters)) && zone_name.match(hasNumbers)) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Input must contain either letters or letters mixed with numbers!",
            });
            document.getElementById('zone_name').value = "";
        }
    }
</script>
<?php $obj->end_script(); ?>