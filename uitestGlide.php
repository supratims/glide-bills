<?php 
require 'vendor/autoload.php';
require 'Glide.php';
$api_key=shell_exec('cat api_key');
if (empty($api_key)){
	echo('Create a file called "api_key" which constains your Glide API key.');
}
$glide=new Glide($api_key);
$services=$glide->get_services();
?>
<?php if (!empty($_POST)):

	foreach($_POST as $param=>$value){
		$service_data[$param]=$value;	
	}
	
	$apiMethod=$_POST['apiMethod'];
	$api=$_POST['api'];
	foreach ($services as $name => $title){
		$arr[$name]=true;
	}

	$res=$glide->$apiMethod($service_data+$arr);
	//echo "Results for $api";
	foreach ($res as $key => $val){
		//echo $key . " " . $val . "<br>"; 
	}
	echo json_encode($res);
?>

<?php else: 
	$mustache=new Mustache_Engine(array(
   		'loader' => new Mustache_Loader_FilesystemLoader('templates')
	));
	$paramFactory=array(
		'signUp/quote/allServices'=>array(
			array('name'=>'postcode', 'type'=>'text'),
			array('name'=>'tenants', 'type'=>'number'),
			array('name'=>'term', 'type'=>'number'),
			array('name'=>'gas', 'type'=>'checkbox'),
			array('name'=>'electricity', 'type'=>'checkbox'),
			array('name'=>'water', 'type'=>'checkbox'),
			array('name'=>'telephone', 'type'=>'checkbox'),
			array('name'=>'broadband', 'type'=>'checkbox'),
			array('name'=>'tv', 'type'=>'checkbox'),
			array('name'=>'broadbandType', 'type'=>'text')
		),
		'signUp/quote/servicePrice'=>array(),
		'signUp/quote/telephoneConnectionCharge'=>array(),
		'signUp/quote/broadbandActivationCharge'=>array(),
		'signUp/quote/broadbandAvailability'=>array(),
		'signUp/quote/telephoneConnectionType'=>array(),
		'signUp/quote/telephoneAddress'=>array()
	);
	
	$methodTmpl=$mustache->loadTemplate('method');

	$methods=$glide->get_methods();
	$method=array();
	foreach ($methods as $key => $val){		
		array_push($method, array('key'=>$key, 'api'=>$val, 'params'=>$paramFactory[$val]));			
	}
	$methodArray['methods']=$method;
	echo $methodTmpl->render($methodArray);

?>
	<!--<input type="submit" id="">-->
<?php endif; ?>

