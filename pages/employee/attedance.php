<?php

include('store_attendace.php');
$currentYear = date('Y');
$currentMonth = date('m');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear); // Total days in the month
// Get years for the year selection dropdown (you can customize this range)
$years = range(2020, 2030);
$employees = $obj->getAllData("tbl_employee");
?>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 10px;
        border: 1px solid #ddd;
    }

    select,
    input {
        width: 100%;
        padding: 5px;
    }
</style>

<div class="container mt-5">
    <h2 class="text-center mb-4">Employee Attendance for the Month</h2>

    <!-- Attendance Form -->
    <form action="" method="POST">

        <div class="row">
            <div class="col-md-3 mb-3">

                <select name="employee_search" class="form-control" data-toggle="select2" data-width="100%">

                    <option>Select Employee Name</option>
                    <?php

                    foreach ($employees as $employee) { ?>

                        <option value="<?= htmlspecialchars($employee['id']) ?>"><?= htmlspecialchars($employee['employee_name']) ?></option>

                    <?php } ?>

                </select>
            </div>

            <div class="col-md-3">
                <input type="submit" name="search" class="btn btn-primary" value="Search">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Employee Id</th>
                        <th>In time</th>
                        <th>Out time</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    if (!empty($employee_search_data)) {


                        foreach ($employee_search_data as $employee): ?>
                            <tr>
                                <td class="align-middle"><?= htmlspecialchars($employee['employee_name']) ?></td>
                                <input type="hidden" value="<?= htmlspecialchars($employee['id']) ?>" name="hiden_em_id">

                                <td>


                                    <div class="mb-2">

                                        <?= htmlspecialchars($employee['employee_id']) ?>
                                    </div>
                                </td>
                                <td>


                                    <div class="mb-2">

                                        <input value="" type="time" class="form-control" name="inTime">
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-2">

                                        <input type="time" class="form-control" name="outTime">
                                    </div>
                                </td>

                                <td>

                                    <select class="form-select" name="status" id="">
                                        <option value="Present">Present</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Leave">Leave</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="mb-2">

                                        <input type="date" class="form-control" name="attendance_date">
                                    </div>
                                </td>

                            </tr>
                    <?php endforeach;
                    } ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-4">
            <button type="submit" name="attendance" class="btn btn-primary btn-lg">Submit Attendance</button>
        </div>
    </form>
</div>