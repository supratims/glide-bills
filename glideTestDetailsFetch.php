<?php
require 'vendor/autoload.php';
require 'DBService.php';

$mustache=new Mustache_Engine(array(
	'loader' => new Mustache_Loader_FilesystemLoader('templates')
));
$dbservice = new DBService();

$result=$dbservice->fetch('select api, api_param, output_json from test_api_details where fk_test_id = ? ', array($_GET['testid']));

if (count($result) == 0){
	echo "No details found ...";
}else{
	$tableData=array();
	foreach($result as $row) {
		//todo: find out best way to prettify the json
		//$param=json_encode(json_decode($row['api_param']), JSON_PRETTY_PRINT));
		$param=$row['api_param'];
		$resObj=json_decode($row['output_json']);
		$errClass=($resObj->error=='exception' ? 'alert-danger': 'alert-success');

		array_push($tableData, 
			array('key'=>$row['api'],'params'=>array('param'=>$param), 'output'=>$row['output_json'], 'error-class'=>$errClass)
		);
	}
	$tableTmpl=$mustache->loadTemplate('methodoutput');
	$tableArray['methods']=$tableData;
	echo $tableTmpl->render($tableArray);
}

?>

