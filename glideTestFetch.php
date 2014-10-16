<?php
require 'vendor/autoload.php';
require 'DBService.php';
$mustache=new Mustache_Engine(array(
	'loader' => new Mustache_Loader_FilesystemLoader('templates')
));
$dbservice = new DBService();

$result=$dbservice->fetch('select test_id, result, creation from test_master order by creation desc ', null);

if (count($result) == 0){
	echo "No tests run yet";
}else{
	$tableData=array();
	foreach($result as $row) {
		array_push($tableData, array('cells'=>array('testid'=>$row['test_id'], 'result'=>$row['result'], 'creation'=>date_format(date_create($row['creation']),'d M Y, H:i:s A') )));
	}
	$tableTmpl=$mustache->loadTemplate('table');
	$tableArray['rows']=$tableData;
	echo $tableTmpl->render($tableArray);
}

?>

