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

	try {
		$res=$glide->$apiMethod($service_data+$arr);
		echo json_encode($res);
	} catch(GlideException $e){
		if ($e->get_errors())
			echo json_encode(array('error'=>'exception')+$e->get_errors());
		else 
			echo json_encode(array('error'=>'exception'));
	}
?>

<?php else: 
	$mustache=new Mustache_Engine(array(
   		'loader' => new Mustache_Loader_FilesystemLoader('templates')
	));
	//consider moving this to Glide.php
	$paramFactory=array(
		'signUp/address/searchPremiseByPostcode'=>array(
			array('name'=>'postcode', 'type'=>'text', 'value'=>'m1 1dz')
		),					
		'signUp/address/searchPremiseByOrganisation'=>array(
			array('name'=>'organisation', 'type'=>'text', 'value'=>'THE LACAMANDA LTD')
		),					
		'signUp/address/searchPremiseByStreet'=>array(
			array('name'=>'street', 'type'=>'text', 'value'=>'Little Lever Street'),
			array('name'=>'town', 'type'=>'text', 'value'=>'Manchester')
		),	
		'signUp/address/getPremiseAddress'=>array(
			array('name'=>'udprn', 'type'=>'text', 'value'=>'14307716'),
			array('name'=>'simplified', 'type'=>'checkbox', 'value'=>'on')
		),						
		'signUp/address/validatePostcode'=>array(
			array('name'=>'postcode', 'type'=>'text', 'value'=>'m1 1dz')
		),			
		'signUp/quote/allServices'=>array(
			array('name'=>'postcode', 'type'=>'text', 'value'=>'m1 1dz'),
			array('name'=>'tenants', 'type'=>'number', 'value'=>'5'),
			array('name'=>'term', 'type'=>'number', 'value'=>'12'),
			array('name'=>'gas', 'type'=>'checkbox'),
			array('name'=>'electricity', 'type'=>'checkbox'),
			array('name'=>'water', 'type'=>'checkbox'),
			array('name'=>'telephone', 'type'=>'checkbox'),
			array('name'=>'tv', 'type'=>'checkbox'),
			array('name'=>'broadband', 'type'=>'checkbox'),			
			array('name'=>'broadbandType', 'type'=>'text', 'placeholder'=> 'llu24s, llu24p, bt24s')
			//array('name'=>'broadbandType', 'type'=>'select', 'options'=>array(array('key'=>'llu24s'), array('key'=>'llu24p'), array('key'=>'bt24s')))
		),
		'signUp/quote/servicePrice'=>array(
			array('name'=>'postcode', 'type'=>'text', 'value'=>'m1 1dz'),
			array('name'=>'service', 'type'=>'text', 'value'=>'gas'),
			array('name'=>'tenants', 'type'=>'number', 'value'=>'5'),
			array('name'=>'term', 'type'=>'number', 'value'=>'12'),
			array('name'=>'extra', 'type'=>'text', 'placeholder'=> 'llu24s, llu24p, bt24s')
		),
		'signUp/quote/telephoneConnectionCharge'=>array(			
			array('name'=>'tenants', 'type'=>'number', 'value'=>'5'),
			array('name'=>'term', 'type'=>'number', 'value'=>'12'),
			array('name'=>'orderType', 'type'=>'text', 'placeholder'=> 'restart, takeover, transfer, convert, new'),
			array('name'=>'broadband', 'type'=>'checkbox')
		),
		'signUp/quote/broadbandActivationCharge'=>array(
			array('name'=>'term', 'type'=>'number', 'value'=>'12'),
			array('name'=>'type', 'type'=>'text', 'placeholder'=> 'llu24s, llu24p, bt24s')
		),
		'signUp/quote/broadbandAvailability'=>array(
			array('name'=>'postcode', 'type'=>'text', 'value'=>'m1 1dz')
		),
		'signUp/quote/telephoneConnectionType'=>array(
			array('name'=>'addressReference', 'type'=>'text', 'value'=>'A14321522680')
		),
		'signUp/quote/telephoneAddress'=>array(
			array('name'=>'buildingNumber', 'type'=>'text', 'value'=>'26'),
			array('name'=>'thoroughFare', 'type'=>'text', 'value'=>'Lever Street'),
			array('name'=>'town', 'type'=>'text', 'value'=>'Manchester'),
			array('name'=>'postcode', 'type'=>'text', 'value'=>'m1 1dz')
		)
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
<?php endif; ?>

