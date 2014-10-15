<?php
require 'vendor/autoload.php';
require 'DBService.php';

$dbservice = new DBService();

$result=$dbservice->insert('insert into test_master(creation) values(now())', null);
echo json_encode($result);
?>