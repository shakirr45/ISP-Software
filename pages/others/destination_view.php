<?php include('zone.php') ?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Destination Area List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#con-close-modal">Create Destination</button>

    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="destinationTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL.No</th>
                    <th scope="col">Destination</th>
                    <th scope="col">SubZone</th>
                    <th scope="col">Zone</th>
                    <?php if ($obj->userWorkPermission('edit')) { ?>
                        <th scope="col">Action</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                foreach ($viewdestination as $value) { ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo isset($value['zone_name']) ? $value['zone_name'] : NULL; ?></td>
                        <td><?php echo isset($value['subname']) ? $value['subname'] : NULL; ?></td>
                        <td><?php echo isset($value['zonename']) ? $value['zonename'] : NULL; ?></td>
                        <td>
                            <a
                                href="javascript:void(0)"
                                class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center zoneidupdate"
                                data-zoneid="<?php echo @$value['zoneid'] ?>"
                                data-subzoneid="<?php echo @$value['subid'] ?>"
                                data-destinationid="<?php echo @$value['zone_id'] ?>"
                                data-destinationname="<?php echo @$value['zone_name'] ?>"
                                data-bs-toggle="modal" data-bs-target="#con-close-modal">
                                <!-- <iconify-icon icon="lucide:edit"></iconify-icon> -->
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </td>
                    </tr>
                <?php   } ?>
            </tbody>
        </table>
    </div>
</div>

<!--destination  modal content -->
<div id="con-close-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fs-5">Destination Area</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name="destination_id" id="destination_id">
                                <div class="mb-3">
                                    <label class="form-label">Zone/Area*</label>
                                    <select id="toptevelzone" class="form-control" required>
                                        <option value="">Select</option>
                                        <?php foreach ($selectsubzone as $value) : ?>
                                            <option value="<?php echo $value['zoneid'] ?>"><?php echo $value['zonename'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="zonesub">
                                    <input type="hidden" name="sub_id" id="sub_id">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="destination_name" class="form-label">Destination*</label>
                                    <input type="text" onchange="checkOnlyNumber()" class="form-control" name="destination_name" id="destination_name" placeholder="Destination name" required data-error-message="Please provide a destination name.">
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
        $('#destinationTable').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
        });
        $('#toptevelzone').on('change', function() {
            $('#zonesub').html('');
            $.get("./pages/others/zone_ajax.php", {
                zone_id: $(this).val()
            }, function(result) {
                $('#zonesub').html(result);
            });
        });
    });
    var elements = document.getElementsByClassName('zoneidupdate');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {

            document.getElementById('toptevelzone').value = this.getAttribute('data-zoneid');
            document.getElementById('sub_id').value = this.getAttribute('data-subzoneid');
            document.getElementById('destination_id').value = this.getAttribute('data-destinationid');
            document.getElementById('destination_name').value = this.getAttribute('data-destinationname');
        });
    }

    function checkOnlyNumber() {
        var zone_name = document.getElementById('destination_name').value;
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