<?php
require 'vendor/autoload.php';
require 'DBService.php';

$dbservice = new DBService();

$result=$dbservice->fetch('select test_id, result, creation from test_master', null);
echo json_encode($result);
?>


