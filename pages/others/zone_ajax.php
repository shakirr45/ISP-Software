<?php 
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$subzoneid=isset($_GET['subzone_id']) && !empty($_GET['subzone_id'])?$_GET['subzone_id']:0;
$zoneid=isset($_GET['zone_id']) && !empty($_GET['zone_id'])?$_GET['zone_id']:$subzoneid;
$subzones = $obj->getAllData("tbl_zone",['where'=>['parent_id','=',$zoneid]]);

$subz='';
if($subzones){
    foreach ($subzones as $subzone) {
        $subz.='<option value="'.$subzone['zone_id'].'">'.$subzone['zone_name'].'</option>';;
    }
if(isset($_GET['subzone_id']) && !empty($_GET['subzone_id'])){
    $selectdiv ='<div class="mb-3">
                    <label for="destination" class="form-label">Destination/Area</label>
                    <select id="destination" name="destination" class="form-control" >
                        <option value="">Select</option>
                        '.$subz.'
                    </select>                                                          
                </div>';
}else{
    $selectdiv ='<div class="mb-3">
                    <label for="sub_id" class="form-label">SubZone/SubArea*</label>
                    <select id="sub_id" name="sub_id" class="form-control" required>
                        <option value="">Select</option>
                        '.$subz.'
                    </select>                                                          
                </div>';
}
   


    echo $selectdiv;
}else{
    echo false;
}
exit();
?>
