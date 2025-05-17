<div class="d-flex justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <!-- Form Wizard Start -->
                <div class="form-wizard">
                    <fieldset class="wizard-fieldset show">
                        <div class="col-md-12 bg-teal-800">
                            <h6 class="text-md text-neutral-500 text-center">Custom SMS</h6>
                        </div>
                        <hr>
                        <form role="form" enctype="multipart/form-data" method="post" class="d-flex flex-column align-items-center w-100">
                            <div class="form-group w-100">
                                <label for="smsbody" class="form-label">Body</label>
                                <textarea name="smsbody" id="smsbody" class="form-control w-100" rows="4"><?php echo @$getCustomSMS['smsbody'] ?></textarea>
                            </div>
                            <div class="form-group pt-3">
                                <button type="submit" class="btn btn-success waves-effect waves-light btn-lg" name="sms_custom" value="sms_custom">Save</button>
                            </div>
                        </form>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
</div>



  <div class="col-md-12">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
 <div class="row gy-3">
     <div class="col-md-12 bg-teal-800"> <h6 class="text-md text-neutral-500">Custom SMS destination</h6>
                        </div>
                          <hr>
                        <div class="col-md-2 pb-3">
                            <label for="mb" class="form-label">Package*</label>
                            <select name="mb" id="mb" class="form-control" required>
                                <option value="">Select</option>
                                <?php foreach ($obj->getAllData("tbl_package", ($mikrotikget > 0) ? ['where' => [['type', '=', 1], ['mikrotik_id', '=', $mikrotikget]]] : ['where' => ['type', '=', 1]]) as $value): ?>
                                    <option data-bill="<?php echo $value['bill_amount'] ?>"
                                        value="<?php echo $value['net_speed'] ?>">
                                        <?php echo $value['package_name'] . " - " . $value["net_speed"] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 pb-3">
                            <div class="mb-3">
                                <label for="zone" class="form-label">Zone*</label>
                                <select name="zone" id="zone" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="All">All Zone</option>
                                    <?php foreach ($obj->getAllData("tbl_zone", ['where' => ['level', '=', '1']]) as $value): ?>
                                        <option value="<?php echo $value['zone_id'] ?>"><?php echo $value['zone_name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2 pb-3">
                            <label for="sub_zone" class="form-label">SubZone</label>
                            <select name="sub_zone" id="sub_zone" class="form-control">
                                <option value="0">Select</option>
                                <?php foreach ($subzones as $subzone): ?>
                                    <?php echo '<option value="' . $subzone['zone_id'] . '">' . $subzone['zone_name'] . '</option>'; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- <div class="col-md-2 pb-3">
                            <label for="destination" class="form-label">Destination</label>
                            <select name="destination" id="destination" class="form-control">
                                <option value="0">Select</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <?php echo '<option value="' . $destination['zone_id'] . '">' . $destination['zone_name'] . '</option>'; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 pb-3">
                            <label for="destination" class="form-label">Destination</label>
                            <select name="destination" id="destination" class="form-control">
                                <option value="0">Select</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <?php echo '<option value="' . $destination['zone_id'] . '">' . $destination['zone_name'] . '</option>'; ?>
                                <?php endforeach; ?>
                            </select>
                        </div> -->

                        <div class="col-md-2 pb-3">
                            <label for="mikrotik_disconnect" class="form-label">Disconnect Date</label>
                            <?php
                            $disconnectDays = $obj->rawSql("SELECT DISTINCT mikrotik_disconnect FROM tbl_agent ORDER BY mikrotik_disconnect ASC");
                            ?>
                            <select name="mikrotik_disconnect" id="mikrotik_disconnect" class="form-control">
                                <option value="0">Select</option>
                                <?php foreach ($disconnectDays as $disconnectDay): ?>
                                    <?php echo '<option value="' . $disconnectDay['mikrotik_disconnect'] . '">' . $disconnectDay['mikrotik_disconnect'] . '</option>'; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 pb-3">
                            <label for="billing_person_id" class="form-label">Billing Person</label>
                            <?php
                            $billingPersons = $obj->rawSql("SELECT DISTINCT tbl_agent.entry_by, user.UserName 
                                                    FROM tbl_agent 
                                                    LEFT JOIN _createuser AS user ON user.UserId = tbl_agent.entry_by");
                            ?>
                            <select name="billing_person_id" id="billing_person_id" class="form-control">
                                <option value="0">Select</option>
                                <?php foreach ($billingPersons as $billingperson): ?>
                                    <?php echo '<option value="' . $billingperson['entry_by'] . '">' . $billingperson['UserName'] . '</option>'; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>



                        <div class="col-md-2 pb-3">
                            <label for="ag_status" class="form-label">Status</label>
                            <select name="ag_status" id="ag_status" class="form-control">
                                <option value="1023">ALL</option>
                                <option value="1">Active</option>
                                <option value="0">InActive</option>
                                <option value="2">Free</option>
                                <option value="3">Discontinue</option>
                            </select>
                        </div>


                        <div class="col-md-1 pt-3 text-center">
                            <button type="button" class="btn btn-success waves-effect waves-light btn-lg"
                                data-sms_status="<?php echo @$getCustomSMS['status'] ?>"
                                data-sms_id="<?php echo @$getCustomSMS['status'] ?>" id="sms_custom_sent">Send</button>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
<?php $obj->start_script(); ?>

<script>
    $(document).ready(function() {
        $("#mb").on('change', function() {
            // Capture the selected value
            var selectedPackage = $("#mb option:selected").val();

            $.ajax({
                url: './pages/sms/zone_ajax.php',
                type: 'POST',
                data: {
                    package: selectedPackage
                },
                success: function(response) {
                    $("#zone").html('');
                    $("#zone").append(
                        '<option value="0">select</option>'
                    );
                    $("#zone").append(
                        '<option value="All">All Zone</option>'
                    );
                    response.forEach(function(item) {
                        $("#zone").append(
                            '<option value="' + item.zone_id + '">' + item.zone_name + '</option>'
                        );
                    });
                },
                error: function() {
                    // Handle error
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    // Optional actions after completion
                }
            });
        });


        $("#zone").on('change', function() {
            // Capture the selected value
            var selectedZone = $("#zone option:selected").val();

            $.ajax({
                url: './pages/sms/sub_zone_ajax.php',
                type: 'POST',
                data: {
                    zone: selectedZone
                },
                success: function(response) {
                    $("#sub_zone").html('');
                    $("#sub_zone").append(
                        '<option value="0">select</option>'
                    );
                    response.forEach(function(item) {
                        $("#sub_zone").append(
                            '<option value="' + item.zone_id + '">' + item.zone_name + '</option>'
                        );
                    });
                },
                error: function() {
                    // Handle error
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    // Optional actions after completion
                }
            });
        });

        $("#sub_zone").on('change', function() {
            // Capture the selected value
            var selectedSubZone = $("#sub_zone option:selected").val();

            $.ajax({
                url: './pages/sms/discounnect_days_ajax.php',
                type: 'POST',
                data: {
                    subZone: selectedSubZone
                },
                success: function(response) {
                    $("#mikrotik_disconnect").html('');
                    $("#mikrotik_disconnect").append(
                        '<option value="0">select</option>'
                    );
                    response.forEach(function(item) {
                        $("#mikrotik_disconnect").append(
                            '<option value="' + item.mikrotik_disconnect + '">' + item.mikrotik_disconnect + '</option>'
                        );
                    });
                },
                error: function() {
                    // Handle error
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    // Optional actions after completion
                }
            });
        });

        $("#mikrotik_disconnect").on('change', function() {
            // Capture the selected value
            var selectedDisconnectDays = $("#mikrotik_disconnect option:selected").val();

            $.ajax({
                url: './pages/sms/billing_person_ajax.php',
                type: 'POST',
                data: {
                    disconnectDay: selectedDisconnectDays
                },
                success: function(response) {
                    $("#billing_person_id").html('');
                    $("#billing_person_id").append(
                        '<option value="0">select</option>'
                    );
                    response.forEach(function(item) {
                        $("#billing_person_id").append(
                            '<option value="' + item.entry_by + '">' + item.UserName + '</option>'
                        );
                    });
                },
                error: function() {
                    // Handle error
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    // Optional actions after completion
                }
            });
        });
    });
</script>


<script>
    $(document).ready(function() {
        // Listen for the click event on the Send button
        $('#sms_custom_sent').on('click', function() {
            console.log('check mik', $('#mikrotik_disconnect').val());

            var $button = $(this); // Cache the button for easier reference
            var buttonText = $button.text(); // Store current button text

            // Disable the button and change text to "Loading"
            // $button.prop('disabled', true).text('Loading...').removeClass('btn-success').addClass('btn-info');

            // Collect the selected values
            var selectedData = {
                mb: $('#mb').val(),
                zone: $('#zone').val(),
                sub_zone: $('#sub_zone').val(),
                destination: $('#destination').val(),
                mikrotik_disconnect: $('#mikrotik_disconnect').val(),
                billing_person_id: $('#billing_person_id').val(),
                ag_status: $('#ag_status').val(),
                sms_status: $button.data('sms_status'),
                sms_id: $button.data('sms_id'),
                sms_body: $("#smsbody").val()
            };

            // Perform the AJAX request
            $.ajax({
                url: './pages/sms/sms_ajax.php', // Replace with your server-side endpoint
                type: 'POST',
                data: selectedData,
                success: function(response) {
                    try {
                        // Remove any non-JSON part (before the `{`)
                        var jsonStartIndex = response.indexOf("{");
                        if (jsonStartIndex > -1) {
                            var jsonString = response.substring(jsonStartIndex);
                            var jsonResponse = JSON.parse(jsonString);

                            // Display SweetAlert for error message if present
                            if (jsonResponse.error_message) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops!',
                                    text: jsonResponse.error_message,
                                });
                            } else if (jsonResponse.success_message) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: jsonResponse.success_message,
                                });
                            }
                        } else {
                            console.error("Invalid response format.");
                        }
                    } catch (e) {
                        console.error("Failed to parse response:", e);
                        console.error("Response received:", response);

                        // Fallback SweetAlert for unexpected errors
                        Swal.fire({
                            icon: 'error',
                            title: 'Unexpected Error',
                            text: 'Something went wrong. Please try again!',
                        });
                    }
                },
                error: function() {
                    // If AJAX fails, handle the error
                    $button.removeClass('btn-info').addClass('btn-danger').text('Not Sent');
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    // Re-enable the button after the request is completed (optional)
                    // You can either keep the button disabled until success or re-enable it here
                    // $button.prop('disabled', false); 
                }
            });
        });
    });



    $(document).ready(function() {

        // // When the button is clicked
        // $("#saveButtonsd").on("click", function(e) {
        //     e.preventDefault(); // Prevent the default form submission
        //     var formData = new FormData($("#addsms")[0]); // Get the form data
        //     // Perform the AJAX POST request
        //     $.ajax({
        //         url: './pages/sms/sms_ajax.php', // Change this to your server endpoint
        //         type: 'POST',
        //         data: formData,
        //         // processData: false, // Required for FormData
        //         // contentType: false, // Required for FormData
        //         success: function(response) {
        //             // Handle success response
        //             alert("SMS Body Saved Successfully!");
        //             console.log(response); // Log the server response for debugging
        //         },
        //         error: function(xhr, status, error) {
        //             // Handle error response
        //             alert("Error: " + error);
        //             console.log(xhr.responseText); // Log the error message
        //         }
        //     });
        // });

    });
</script>
<?php $obj->end_script(); ?>