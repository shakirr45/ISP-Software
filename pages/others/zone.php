<?php
// insert 
if (isset($_POST['submit'])) {
    // Update zone
    if (isset($_POST['zone_id']) && isset($_POST['zone_name']) && !empty($_POST['zone_id'])) {
        $zoneCount = $obj->details_by_cond("tbl_zone", "zone_name='" . $_POST['zone_name'] . "' AND zone_id!='" . $_POST['zone_id'] . "'");
        if ($zoneCount > 0) {
            $obj->notificationStore("Zone Name Already Exist", 'danger');
            echo ' <script>window.location="?page=zone_view"; </script>';
            exit;
        }
        $obj->updateData('tbl_zone', ['zone_name' => $_POST['zone_name']], ['zone_id' => $_POST['zone_id']]);
        $obj->notificationStore(" Zone Update Successfull", 'success');
        echo ' <script>window.location="?page=zone_view"; </script>';
        exit;
    }
    // insert zone
    elseif (isset($_POST['zone_name'])) {
        $zoneCount = $obj->details_by_cond("tbl_zone", "zone_name='" . $_POST['zone_name'] . "'");
        if ($zoneCount > 0) {
            $obj->notificationStore("Zone Name Already Exist", 'danger');
            echo ' <script>window.location="?page=zone_view"; </script>';
            exit;
        }
        $obj->insertData('tbl_zone', ['zone_name' => $_POST['zone_name'], 'created_by' => $userId]);
        $obj->notificationStore("New Zone Add Successfull", 'success');
        echo ' <script>window.location="?page=zone_view"; </script>';
        exit;
    }
    // Update Subzone 
    elseif (isset($_POST['subzone_id']) && isset($_POST['parent_id']) && isset($_POST['subzone_name']) && !empty($_POST['subzone_id'])) {
        $obj->updateData('tbl_zone', ['zone_name' => $_POST['subzone_name'], 'parent_id' => $_POST['parent_id']], ['zone_id' => $_POST['subzone_id']]);
        $obj->notificationStore(" SubZone Update Successfull", 'success');
        echo ' <script>window.location="?page=subzone_view"; </script>';
        exit;
    }
    // insert Subzone 
    elseif (isset($_POST['subzone_name']) && isset($_POST['parent_id'])) {
        $obj->insertData('tbl_zone', ['zone_name' => $_POST['subzone_name'], 'parent_id' => $_POST['parent_id'], 'level' => 2, 'created_by' => $userId]);
        $obj->notificationStore("New SubZone Add Successfull", 'success');
        echo ' <script>window.location="?page=subzone_view"; </script>';
        exit;
    }
    // Update destinationzone 
    elseif (isset($_POST['destination_id']) && isset($_POST['sub_id']) && isset($_POST['destination_name']) && !empty($_POST['destination_id'])) {
        $obj->updateData('tbl_zone', ['zone_name' => $_POST['destination_name'], 'parent_id' => $_POST['sub_id']], ['zone_id' => $_POST['destination_id']]);
        $obj->notificationStore(" DestinationZone Update Successfull", 'success');
        echo ' <script>window.location="?page=destination_view"; </script>';
        exit;
    }
    // insert destinationzone  
    elseif (isset($_POST['destination_name']) && isset($_POST['sub_id'])) {
        $obj->insertData('tbl_zone', ['zone_name' => $_POST['destination_name'], 'parent_id' => $_POST['sub_id'], 'level' => 3, 'created_by' => $userId]);
        $obj->notificationStore("New DestinationZone Add Successfull", 'success');
        echo ' <script>window.location="?page=destination_view"; </script>';
        exit;
    } else {
        $obj->notificationStore("please Provide valid data", 'warning');
        if (isset($_POST['zone_name'])) {
            echo ' <script>window.location="?page=zone_view"; </script>';
        } elseif (isset($_POST['subzone_name'])) {
            echo ' <script>window.location="?page=subzone_view"; </script>';
        } elseif (isset($_POST['destination_name'])) {
            echo ' <script>window.location="?page=destination_view"; </script>';
        }
    }
    exit;
}

$obj->notificationShow();
$viewzone = $obj->getAllData("tbl_zone", ['where' => ['level', '=', '1']]);
$viewsubzone = $obj->getAllData("tbl_zone as subzone left join tbl_zone as zone on zone.zone_id = subzone.parent_id", ['where' => ['subzone.level', '=', '2']], 'subzone.*, zone.zone_name as zonename, zone.zone_id as zoneid');
$viewdestination = $obj->getAllData("tbl_zone as destination left join tbl_zone as subzone on subzone.zone_id=destination.parent_id left join tbl_zone as zone on zone.zone_id = subzone.parent_id", ['where' => ['destination.level', '=', '3']], 'destination.*, subzone.zone_name as subname, subzone.zone_id as subid, zone.zone_name as zonename, zone.zone_id as zoneid');
$selectsubzone = $obj->getAllData("tbl_zone as subzone left join tbl_zone as zone on zone.zone_id = subzone.parent_id", ['where' => ['subzone.level', '=', '2']], 'zone.zone_name as zonename, zone.zone_id as zoneid');
