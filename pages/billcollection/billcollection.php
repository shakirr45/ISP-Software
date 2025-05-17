<?php 

if ($page == 'billcollection_view') {
    $bzones=$obj->rawSql('SELECT * FROM tbl_zone WHERE EXISTS ( SELECT 1 FROM tbl_agent WHERE tbl_agent.zone = tbl_zone.zone_id AND tbl_agent.ag_status=1)') ;
    $bbillingperson=$obj->rawSql('SELECT * FROM _createuser WHERE EXISTS ( SELECT 1 FROM tbl_agent WHERE tbl_agent.billing_person_id = _createuser.UserId AND tbl_agent.ag_status=1)');
}

if ($page == 'all_paid') {
    $pzones=$obj->rawSql('SELECT * FROM tbl_zone WHERE EXISTS ( SELECT 1 FROM tbl_agent WHERE tbl_agent.zone = tbl_zone.zone_id AND tbl_agent.ag_status=1)');
    $pbillingPerson=$obj->rawSql('SELECT * FROM _createuser WHERE EXISTS ( SELECT 1 FROM tbl_agent WHERE tbl_agent.pay_status=0 AND tbl_agent.billing_person_id = _createuser.UserId AND tbl_agent.ag_status=1)');
}

if ($page == 'previous_due') {
    $przones=$obj->rawSql('SELECT * FROM tbl_zone WHERE EXISTS ( SELECT 1 FROM tbl_agent WHERE tbl_agent.zone = tbl_zone.zone_id AND tbl_agent.ag_status=1)');
    $prBillingperson =$obj->rawSql('SELECT * FROM _createuser WHERE EXISTS ( SELECT 1 FROM tbl_agent WHERE tbl_agent.billing_person_id = _createuser.UserId AND tbl_agent.ag_status=1)');
}
?>