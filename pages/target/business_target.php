<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h6 class="card-title mb-0">Business Target Management</h6>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#createModal">Create Target</button>

    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="targetTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL.</th>
                    <th scope="col">Target Name</th>
                    <th scope="col">Type</th>
                    <th scope="col">Year</th>
                    <th scope="col">Year Target</th>
                    <th scope="col">January</th>
                    <th scope="col">February</th>
                    <th scope="col">March</th>
                    <th scope="col">April</th>
                    <th scope="col">May</th>
                    <th scope="col">June</th>
                    <th scope="col">July</th>
                    <th scope="col">August</th>
                    <th scope="col">September</th>
                    <th scope="col">October</th>
                    <th scope="col">November</th>
                    <th scope="col">December</th>
                    <th scope="col">Created By</th>
                    <th class="col-md-1" scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>





<!-- </div>
</div> -->

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">Add New Target</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="target_name" class="form-label">Target Name</label>
                        <input type="text" class="form-control" id="add_target_name" name="target_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="add_type" name="type" required>
                            <option value="1">Bill Collection</option>
                            <option value="2">User Growth</option>
                            <option value="3">Monthly Complain Rate</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control" id="add_year" name="year" value="<?= date('Y') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="year_target" class="form-label">Year Target</label>
                        <input type="number" class="form-control" id="add_year_target" name="year_target" required>
                    </div>

                    <!-- Monthly Targets -->
                    <h6>Monthly Targets</h6>
                    <div class="row">
                        <?php
                        $months = [
                            'january',
                            'february',
                            'march',
                            'april',
                            'may',
                            'june',
                            'july',
                            'august',
                            'september',
                            'october',
                            'november',
                            'december'
                        ];
                        foreach ($months as $month): ?>
                            <div class="col-md-6">
                                <label for="<?= $month ?>_target" class="form-label"><?= ucfirst($month) ?> Target</label>
                                <input type="number" class="form-control" id="add_<?= $month ?>_target" name="<?= $month ?>_target" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" action="update_target.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Target</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden ID Field -->
                    <input type="hidden" id="edit_id" name="id">

                    <div class="mb-3">
                        <label for="edit_target_name" class="form-label">Target Name</label>
                        <input type="text" class="form-control" id="edit_target_name" name="target_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_type" class="form-label">Type</label>
                        <select class="form-select" id="edit_type" name="type" required>
                            <option value="1">Bill Collection</option>
                            <option value="2">User Growth</option>
                            <option value="3">Monthly Complain Rate</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_year" class="form-label">Year</label>
                        <input type="number" class="form-control" id="edit_year" name="year" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_year_target" class="form-label">Year Target</label>
                        <input type="number" class="form-control" id="edit_year_target" name="year_target" required>
                    </div>

                    <!-- Monthly Targets -->
                    <h6>Monthly Targets</h6>
                    <div class="row">
                        <?php foreach ($months as $month): ?>
                            <div class="col-md-6">
                                <label for="edit_<?= $month ?>_target" class="form-label"><?= ucfirst($month) ?> Target</label>
                                <input type="number" class="form-control" id="edit_<?= $month ?>_target" name="<?= $month ?>_target" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<button class="btn btn-success btn-lg" style="padding: 10px;margin-bottom: 16px;font-size: 21px;" id="graphicalViewButton">Graphical View</button>

<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="card-title text-lg fw-semibold mb-0">Yearly Target Comparison</h6>
        </div>
        <div class="card-body p-24">
            <div id="target_view_column_chart" class=""></div>
        </div>
    </div>
</div>
<div class="col-md-6 graphicalChart" style="display: none;">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0">Yearly Customer Growth</h6>
        </div>
        <div class="card-body p-24">
            <div id="target_view_line_chart"></div>
        </div>
    </div>
</div>

<div class="col-xxl-6 graphicalChart" style="display: none;">
    <div class="card h-100">
        <div class="card-body p-24 mb-8">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                <h6 class="card-title mb-2 fw-bold text-lg mb-0">Target Reduction Comparison</h6>
            </div>
            <div id="target_view_mixed_chart" class="apexcharts-tooltip-style-1"></div>
        </div>
    </div>
</div>
<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        $('#createForm').on('submit', function(e) {
            e.preventDefault();

            const target_name = $('#add_target_name').val();
            const type = $('#add_type').val();
            const year = $('#add_year').val();
            const year_target = $('#add_year_target').val();
            const january_target = $('#add_january_target').val();
            const february_target = $('#add_february_target').val();
            const march_target = $('#add_march_target').val();
            const april_target = $('#add_april_target').val();
            const may_target = $('#add_may_target').val();
            const june_target = $('#add_june_target').val();
            const july_target = $('#add_july_target').val();
            const august_target = $('#add_august_target').val();
            const september_target = $('#add_september_target').val();
            const october_target = $('#add_october_target').val();
            const november_target = $('#add_november_target').val();
            const december_target = $('#add_december_target').val();

            // Send AJAX request as a traditional POST request
            $.ajax({
                url: './pages/target/target_ajax.php',
                type: 'POST',
                data: {
                    target_name,
                    type,
                    year,
                    year_target,
                    january_target,
                    february_target,
                    march_target,
                    april_target,
                    may_target,
                    june_target,
                    july_target,
                    august_target,
                    september_target,
                    october_target,
                    november_target,
                    december_target
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success === true) {
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success',
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2500
                        });
                        $('#createModal').modal('hide'); // Close the modal
                        $('#createForm')[0].reset(); // Reset the form
                        $('#targetTable').DataTable().ajax.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while adding the target.');
                }
            });
        });

        // Initialize DataTable
        // Initialize DataTable
        $('#targetTable').DataTable({
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` + // Show entries and search in one row
                `<"row"<"col-sm-12 text-end"B>>` + // Buttons in a separate row
                `<"row dt-layout-row"<"col-sm-12"tr>>` + // Table content
                `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`, // Page length and pagination in one row
            keys: true,
            stateSave: true,
            lengthChange: true,
            responsive: true,
            pagingType: "full_numbers",
            processing: true, // Show the processing indicator
            serverSide: true, // Enable server-side processing
            ajax: {
                url: './pages/target/target_ajax.php', // Ensure this file is correctly handling the data
                type: 'GET',
                dataSrc: function(response) {
                    console.log(response); // Inspect the server response
                    return response.data; // Adjust based on your server's data structure
                }
            },
            order: [
                [0, 'asc'] // Default ordering by the first column (SL)
            ],
            columns: [{
                    data: 'sl',
                    orderable: true
                },
                {
                    data: 'target_name',
                    orderable: true
                },
                {
                    data: 'type',
                    orderable: true
                },
                {
                    data: 'year',
                    orderable: true
                },
                {
                    data: 'year_target',
                    orderable: true
                },
                {
                    data: 'january_target',
                    orderable: true
                },
                {
                    data: 'february_target',
                    orderable: true
                },
                {
                    data: 'march_target',
                    orderable: true
                },
                {
                    data: 'april_target',
                    orderable: true
                },
                {
                    data: 'may_target',
                    orderable: true
                },
                {
                    data: 'june_target',
                    orderable: true
                },
                {
                    data: 'july_target',
                    orderable: true
                },
                {
                    data: 'august_target',
                    orderable: true
                },
                {
                    data: 'september_target',
                    orderable: true
                },
                {
                    data: 'october_target',
                    orderable: true
                },
                {
                    data: 'november_target',
                    orderable: true
                },
                {
                    data: 'december_target',
                    orderable: true
                },
                {
                    data: 'FullName',
                    orderable: true
                },
                {
                    data: null,
                    render: function(data) {
                        return `
                    <a class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center editBtn" data-id="${data.id}" data-row='${JSON.stringify(data)}'>
                        <i class="fa-regular fa-pen-to-square"></i>
                    </a>
                    <a class="w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center deleteBtn" data-id="${data.id}">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                `;
                    }
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search business targets...",
                lengthMenu: "Show _MENU_ entries",
                emptyTable: "No data available in table"
            },
            buttons: [{
                    extend: "copy"
                },
                {
                    extend: "print"
                },
                {
                    extend: "pdf"
                }
            ]
        });





        // Handle Edit Button Click
        $('#targetTable tbody').on('click', '.editBtn', function() {
            const rowData = JSON.parse($(this).attr('data-row'))

            // Populate the modal fields
            $('#edit_id').val(rowData.id);
            $('#edit_target_name').val(rowData.target_name);
            $('#edit_type').val(rowData.type);
            $('#edit_year').val(rowData.year);
            $('#edit_year_target').val(rowData.year_target);

            const months = [
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ];

            months.forEach(month => {
                $(`#edit_${month}_target`).val(rowData[`${month}_target`]);
            });

            // Show the modal
            $('#editModal').modal('show');
        });

        // Handle Update via API
        $('#editForm').on('submit', function(e) {
            e.preventDefault();

            // const formData = {
            const id = $('#edit_id').val()
            const target_name = $('#edit_target_name').val()
            const type = $('#edit_type').val()
            const year = $('#edit_year').val()
            const year_target = $('#edit_year_target').val()
            const january_target = $('#edit_january_target').val()
            const february_target = $('#edit_february_target').val()
            const march_target = $('#edit_march_target').val()
            const april_target = $('#edit_april_target').val()
            const may_target = $('#edit_may_target').val()
            const june_target = $('#edit_june_target').val()
            const july_target = $('#edit_july_target').val()
            const august_target = $('#edit_august_target').val()
            const september_target = $('#edit_september_target').val()
            const october_target = $('#edit_october_target').val()
            const november_target = $('#edit_november_target').val()
            const december_target = $('#edit_december_target').val()
            // };
            // Send AJAX request
            alert(target_name);
            $.ajax({
                url: './pages/target/target_ajax.php',
                type: 'POST',
                data: {
                    id,
                    target_name,
                    type,
                    year,
                    year_target,
                    january_target,
                    february_target,
                    march_target,
                    april_target,
                    may_target,
                    june_target,
                    july_target,
                    august_target,
                    september_target,
                    october_target,
                    november_target,
                    december_target
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success === true) {
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success',
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2500
                        });
                        $('#editModal').modal('hide'); // $('#createModal').modal('hide'); // Close the modal
                        $('#editForm')[0].reset(); // Reset the form
                        $('#targetTable').DataTable().ajax.reload();
                        DataTable
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating the target.');
                }
            });
        });

        // Handle Delete Button Click
        $('#targetTable tbody').on('click', '.deleteBtn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: './pages/target/target_ajax.php',
                        method: 'POST',
                        data: {
                            id,
                            action: 'delete'
                        },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Your record has been deleted.',
                                    'success'
                                );
                                $('#targetTable').DataTable().ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            console.log(xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred');
                        }
                    });
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function() {
        // Add event listener for the button click
        $('#graphicalViewButton').click(function() {
            $('#loading-spinner').show()
            $('.graphicalChart').show()
            // Perform AJAX request when the button is clicked
            $.ajax({
                type: "GET",
                url: "./pages/target/target_column_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    target_view_column_chart(response.collectionData, response.targetData, response.collection, response.target, response.maxData);
                    console.log(response.collectionData); // To check the response
                    console.log(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/target/target_line_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    target_view_line_chart(response.collectionData, response.targetData, response.collection, response.target, response.maxData);
                    console.log(response.collectionData); // To check the response
                    console.log(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
            $.ajax({
                type: "GET",
                url: "./pages/target/target_view_mixed_chart_ajax.php",
                dataType: "json",
                success: function(response) {
                    $('#loading-spinner').hide();
                    // Call the function to render the line chart with the data from the server
                    target_view_mixed_chart(response.collectionData, response.targetData, response.collection, response.target, response.maxData);
                    console.log(response.collectionData); // To check the response
                    console.log(response); // To check the full response
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        });
    });

    function target_view_column_chart(collectionData, targetData, collection, target, maxData) {
        // Static month names for both years
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        // Convert the comma-separated strings to arrays of numbers
        const collectionDataArray = collectionData.split(',').map(Number);
        const targetDataArray = targetData.split(',').map(Number);
        var options = {
            series: [{
                name: collection,
                data: collectionDataArray
            }, {
                name: target,
                data: targetDataArray
            }],
            chart: {
                type: 'bar',
                height: 350
            },
            colors: ['#008000', '#FF0000'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: months,
            },
            yaxis: {
                title: {
                    text: "Target Bill Collection Compared To Current Year"
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return "৳ " + val + " BDT"
                    }
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#target_view_column_chart"), options);
        chart.render();
    }

    function target_view_line_chart(collectionData, targetData, collection, target, maxData) {
        // Static month names for both years
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let colors = ["#6658dd", "#1abc9c"];
        const dataColors = $("#apex-line-test").data("colors");
        if (dataColors) {
            colors = dataColors.split(",");
        }
        // Convert the comma-separated strings to arrays of numbers
        const collectionDataArray = collectionData.split(',').map(Number);
        const targetDataArray = targetData.split(',').map(Number);
        const tickAmount = Math.ceil(maxData / 1000);
        const options = {
            chart: {
                height: 380,
                type: "line",
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false
                }
            },
            colors: colors,
            dataLabels: {
                enabled: true
            },
            stroke: {
                width: [3, 3],
                curve: "smooth"
            },
            series: [{
                name: collection,
                data: collectionDataArray // Use the actual array of numbers
            }, {
                name: target,
                data: targetDataArray // Use the actual array of numbers
            }],
            grid: {
                row: {
                    colors: ["transparent", "transparent"],
                    opacity: 0.2
                },
                borderColor: "#f1f3fa"
            },
            markers: {
                style: "inverted",
                size: 6
            },
            xaxis: {
                categories: months, // Use static month names
                title: {
                    text: "Month"
                }
            },
            yaxis: {
                min: 0, // Start y-axis from 0 for better readability
                max: 200, // Use maxData to dynamically set the maximum value of the y-axis
                tickAmount: 10, // Calculate dynamic ticks based on maxData
            },
            legend: {
                position: "top",
                horizontalAlign: "right",
                floating: true,
                offsetY: -25,
                offsetX: -25
            },
            responsive: [{
                breakpoint: 600,
                options: {
                    chart: {
                        toolbar: {
                            show: false
                        }
                    },
                    legend: {
                        show: false
                    }
                }
            }]
        };
        // Render the chart
        const chart = new ApexCharts(document.querySelector("#target_view_line_chart"), options);
        chart.render();

    }

    function target_view_mixed_chart(collectionData, targetData, collection, target, maxData) {
        // Static month names for both years
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let colors = ["#ff0000", "#1abc9c"]; // Red color for collection and original color for target
        const dataColors = $("#apex-line-test").data("colors");
        if (dataColors) {
            colors = dataColors.split(",");
        }
        // Convert the comma-separated strings to arrays of numbers
        const collectionDataArray = collectionData.split(',').map(Number);
        const targetDataArray = targetData.split(',').map(Number);
        const tickAmount = Math.ceil(maxData / 1000);

        var options = {
            series: [{
                name: collection,
                data: collectionDataArray

            }, {
                name: target,
                data: targetDataArray

            }],
            legend: {
                show: false
            },
            chart: {
                type: 'area',
                width: '100%',
                height: 150,
                toolbar: {
                    show: false
                },
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3,
                colors: ['#CD20F9', '#6593FF'], // Use two colors for the lines
                lineCap: 'round'
            },
            grid: {
                show: true,
                borderColor: '#D1D5DB',
                strokeDashArray: 1,
                position: 'back',
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                row: {
                    colors: undefined,
                    opacity: 0.5
                },
                column: {
                    colors: undefined,
                    opacity: 0.5
                },
                padding: {
                    top: -20,
                    right: 0,
                    bottom: -10,
                    left: 0
                },
            },
            fill: {
                type: 'gradient',
                colors: ['#CD20F9', '#6593FF'],
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: [undefined, `${'#6593FF'}00`], // Apply transparency to both colors
                    inverseColors: false,
                    opacityFrom: [0.4, 0.6], // Starting opacity for both colors
                    opacityTo: [0.3, 0.3], // Ending opacity for both colors
                    stops: [0, 100],
                },
            },

            markers: {
                colors: ['#CD20F9', '#6593FF'],
                strokeWidth: 2,
                size: 0,
                hover: {
                    size: 8
                }
            },

            xaxis: {
                labels: {
                    show: false
                },
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                tooltip: {
                    enabled: false
                },
                labels: {
                    formatter: function(value) {
                        return value;
                    },
                    style: {
                        fontSize: "14px"
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        return value + "%";
                    },
                    style: {
                        fontSize: "12px"
                    }
                },
                min: 0,
                max: 100,
                tickAmount: 5,
            },
            tooltip: {
                x: {
                    format: 'dd/MM/yy HH:mm'
                }
            }
        };


        var chart = new ApexCharts(document.querySelector("#target_view_mixed_chart"), options);
        chart.render();
    }
</script>
<?php $obj->end_script(); ?>