<?php include('package.php') ?>


<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Package List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#con-close-modal">Create Package</button>

    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="dataTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL.No</th>
                    <th scope="col">Package</th>
                    <th scope="col">Profile</th>
                    <th scope="col">Monthly Bill</th>
                    <?php if ($obj->userWorkPermission('edit')) { ?>
                        <th scope="col">Action</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                foreach ($obj->getAllData("tbl_package") as $value) { ?>
                    <tr>
                        <td><?php echo $i++; ?>
                        </td>
                        <td>
                            <a href="javascript:void(0)" class="text-primary-600"><?php echo $value['package_name'] ?></a>
                        </td>
                        <td><?php echo $value['net_speed'] ?></td>
                        <td><?php echo $value['bill_amount'] ?></td>
                        <td>
                            <a
                                href="javascript:void(0)"
                                class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center idupdate" data-packageid="<?php echo $value['package_id'] ?>" data-netspeed="<?php echo $value['net_speed'] ?>" data-billm="<?php echo $value['bill_amount'] ?>" data-packagename="<?php echo $value['package_name'] ?>" data-mkid="<?php echo $value['mikrotik_id'] ?>" data-bs-toggle="modal" data-bs-target="#con-close-modal">
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
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name="package_id" id="package_id">
                                <?php $allMikrotik = $obj->getAllData('mikrotik_user', ['where' => ['status', '=', 1]]);
                                if ($allMikrotik): ?>
                                    <div class="mb-3">
                                        <label for="mikrotikuserid" class="form-label">Mikrotik</label>
                                        <select id="mikrotikuserid" name="mikrotikuserid" class="form-control" required data-error-message="Please select a Mikrotik.">
                                            <option value="">Select</option>
                                            <?php foreach ($allMikrotik as $mikrotiklist) : ?>
                                                <option value="<?= htmlspecialchars($mikrotiklist['id']) ?>"><?= htmlspecialchars($mikrotiklist['mik_ip']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div id="speedmk"></div>

                                <div class="mb-3">
                                    <label for="net_speed" class="form-label">Mikrotik Profile Name*</label>
                                    <input type="text" class="form-control" name="net_speed" id="net_speed" placeholder="Mikrotik Profile Name" required data-error-message="Please provide a profile name.">
                                </div>

                                <div class="mb-3">
                                    <label for="package_name" class="form-label">Package Name*</label>
                                    <input type="text" class="form-control" onchange="checkOnlyNumber()" name="package_name" id="package_name" placeholder="Package Name" required data-error-message="Please provide a package name.">
                                </div>

                                <div class="mb-3">
                                    <label for="bill_amount" class="form-label">Monthly Bill*</label>
                                    <input type="number" class="form-control" oninput="checkNegative()" name="bill_amount" id="bill_amount" min="0" placeholder="Monthly Bill Amount" required data-error-message="Please provide the monthly bill amount.">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit" class="btn btn-success waves-effect waves-light">Save Package</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<!-- <div id="con-close-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Package</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" name="package_id" id="package_id">

                            <?php $allMikrotik = $obj->getAllData('mikrotik_user', ['where' => ['status', '=', 1]]);
                            if ($allMikrotik): ?>
                                <div class="mb-3">
                                    <label for="package_name" class="form-label">Mikrotik</label>
                                    <select id="mikrotikuserid" name="mikrotikuserid" class="form-control" required>
                                        <option value="">Select</option>
                                        <?php
                                        foreach ($allMikrotik as $mikrotiklist) :
                                            echo '<option value="' . htmlspecialchars($mikrotiklist['id']) . '">' . htmlspecialchars($mikrotiklist['mik_ip']) . '</option>';
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div id="speedmk"></div>

                            <div class="mb-3">
                                <label for="package_name" class="form-label">Mikrotik Profile Name*</label>

                                <input type="text" class="form-control" name="net_speed" id="net_speed" placeholder="Mikrotik Profile Name" required>
                            </div>
                            <div class="mb-3">
                                <label for="package_name" class="form-label">Package Name*</label>
                                <input type="text" class="form-control" name="package_name" id="package_name" placeholder="Package Name" required>
                            </div>
                            <div class="mb-3">
                                <label for="bill_amount" class="form-label">Monthly Bill*</label>
                                <input type="number" class="form-control" name="bill_amount" id="bill_amount" placeholder="Monthly Bill Amount" required>
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
</div> -->
<!-- /.modal -->
<?php $obj->start_script(); ?>
<!-- <script>
        let table = new DataTable("#dataTable");
        table.columns.adjust().draw();
    </script> -->


<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
        });
    });



    var elements = document.getElementsByClassName('idupdate');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {
            document.getElementById('package_id').value = this.getAttribute('data-packageid');
            document.getElementById('package_name').value = this.getAttribute('data-packagename');
            document.getElementById('net_speed').value = this.getAttribute('data-netspeed');
            document.getElementById('bill_amount').value = this.getAttribute('data-billm');
            document.getElementById('mikrotikuserid').value = this.getAttribute('data-mkid');
        });
    }

    $('#mikrotikuserid').on('change', function() {
        $('#speedmk').html('');
        $('#net_speed').val();
        $('#net_speed').removeAttr('readonly');
        $.get("./pages/others/mikrotik_ajax.php", {
            mikrotikid: $(this).val()
        }, function(result) {

            $('#speedmk').html(result);
            $('#packagelist').on('change', function() {
                $('#net_speed').attr('readonly', 'readonly');
                $('#net_speed').val($('#packagelist').val());
            });

        });
    });
</script>
<script>
    function checkNegative() {
        var bill_amount = document.getElementById('bill_amount').value;
        if (bill_amount < 0) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Please enter a positive amount.",
            });
            document.getElementById('bill_amount').value = "0";
        }
    }
    function checkOnlyNumber() {
        var package_name = document.getElementById('package_name').value;
        const hasLetters = /[a-zA-Z]/; // Matches any letter
        const hasNumbers = /\d/; // Matches any number
        if (!(package_name.match(hasLetters)) && package_name.match(hasNumbers)) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Input must contain either letters or letters mixed with numbers!",
            });
            document.getElementById('package_name').value = "";
        }
    }
</script>
<?php $obj->end_script(); ?>