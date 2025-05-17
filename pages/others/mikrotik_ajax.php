<?php 
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$mikrotikid=isset($_GET['mikrotikid']) && !empty($_GET['mikrotikid'])?$_GET['mikrotikid']:0;
$allData = $obj->profilelist($mikrotikid);

$package='';
if($allData){
    foreach ($allData as $data) {
        $package.='<option value="'.$data['name'].'">'.$data['name'].'</option>';;
    }
}


$selectdiv ='<div class="mb-3">
                    <label for="packagelist" class="form-label">Mikrotik Profile*</label>
                    <select id="packagelist" class="form-control" required>
                    <option value="">Select</option>
                    '.$package.'
                    </select>                                                          
            </div>';

if($allData){
    echo $selectdiv;
}else{
    echo false;
}
exit();
?>
