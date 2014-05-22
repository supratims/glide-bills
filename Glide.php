<?php
class Glide {
	protected $url='https://www.glide.uk.com/api/4.0/';
	protected $methods=array('frontEnd'=>array(
	),'portal'=>array(
	),'signUp'=>array(
		'payment'=>array(
			'registerCard'=>false,
			'getClientAddresses'=>false,
		),
		'quote'=>array(
			'allServices'=>true,
			'servicePrice'=>true,
			'telephoneConnectionCharge'=>true,
			'broadbandActivationCharge'=>true,
			'broadbandAvailability'=>true,
			'telephoneConnectionType'=>true,
			'telephoneAddress'=>true,
			'email'=>false,
		),
	),);
	protected $service_names=array('gas'=>'Gas','electricity'=>'Electricity','water'=>'Water','telephone'=>'Phone','broadband'=>'Internet','tv'=>'TV license');

	protected $api_key;
	protected $services;

	public $log_dir='logs';
	public $broadbandTypes=array('llu24s','llu24p','bt24s');
	public $telOrderTypes=array('restart', 'takeover', 'transfer', 'convert','new');

	function __construct($api_key){
		if (empty($api_key)){
			throw new GlideException('You must set a valid Glide API key.');
		}
		$this->api_key=$api_key;
		$this->services=array_keys($this->service_names);
		return $this;
	}

	function get_services(){
		return $this->service_names;
	}

	function get_methods(){
		foreach ($this->methods as $master => $items){
			foreach ($items as $item => $functions){
				foreach ($functions as $function => $exists){
					if ($exists){
						$return[$master.'_'.$item.'_'.$function]=$master.'/'.$item.'/'.$function;
					}
				}
			}
		}
		return $return;
	}

	function __call($name,$args){
		$route=str_replace('_','/',$name);
		$data=$args[0];
		if (isset($args[1])){
			$extra=$args[1];
		}
		$valid_method='valid_'.$name;
		if (method_exists($this,$valid_method)){
			$data=$this->$valid_method($data,$extra);
		}
		$res=$this->send_request($data,$route);
		if ($res['error']==1){
			$error_method='error_'.$name;
			if (method_exists($this,$error_method)){
				return $this->$error_method($res,$data);
			}
			$this->exception_message($res,$data);
		}
		return $this->return_data($res,$name);
	}

	function error_signUp_quote_allServices($res,Array $data){
		if (isset($data['water']) and strpos($res['message'],'We are unable to supply water')!==false){
			unset($data['water']);
			return $res=$this->signUp_quote_allServices($data);
		}
		$this->exception_message($res);
	}

	function valid_signUp_quote(Array $data,&$errors=array()){
		if (empty($data['postcode'])){
			$errors['postcode']='No postcode.';
		}
		if (empty($data['tenants'])){
			$errors['tenants']='No tenants.';
		}
		if (empty($data['term'])){
			$errors['term']='No term.';
		}
		return $data;
	}

	function valid_signUp_quote_allServices(Array $data,Array $extra=null){
		$data=$this->valid_signUp_quote($data,$errors);
		if (isset($extra['all'])){
			$services=array_keys($this->get_services());
			foreach ($services as &$service){
				$data[$service]=true;
			}
		}
		$data['capacity']=$data['tenants'];
		$data['minTerm']=$data['term'];
		if (!isset($data['broadbandType'])){
			unset($data['broadband']);
		}
		$this->valid_check_errors($errors);
		return $data;
	}

	function valid_signUp_quote_servicePrice(Array $data){
		$data=$this->valid_signUp_quote($data,$errors);
		if (!isset($this->service_names[$data['service']])){
			$errors['service']='The selected service ('.$data['service'].') is not valid.';
		}
		$data['period']=$data['term'];
		if ($data['service']=='broadband'){
			if (empty($data['broadbandType'])){
				$errors['broadbandType']='You must enter a broadband type to check broadband prices.';
			}
		}
		$this->valid_check_errors($errors);
		return $data;
	}

	function valid_signUp_quote_broadbandActivationCharge(Array $data){
		if (empty($data['broadbandType'])){
			$errors['broadbandType']='You must enter a broadband type to check broadband prices.';
		}
		$data['minTerm']=$data['term'];
		$data['type']=$data['broadbandType'];
		$this->valid_check_errors($errors);
		return $data;
	}

	private function valid_check_errors($errors){
		if (!empty($errors)){
			throw new GlideException('There was a problem with the information submitted.',$errors);
		}
	}

	private function return_data($res){
		return $res;
	}

	private function exception_message($res,Array $data=array()){
		throw new GlideException('The Glide server reported an error: '.$res['message'].(!empty($data) ? echo_array($data,true) : ''));
	}

	private function send_request(Array $data,$route){
		if ($route[0]=='/'){
			$route=substr($route,1);
		}
		$check_route=explode("/",$route);
		$methods=$this->methods;
		foreach ($check_route as $step){
			if (!isset($methods[$step])){
				throw new GlideException('The route '.$route.' could not be found. It failed looking for "'.$step.'"');
			}
			$methods=$methods[$step];
		}

		$data['key']=$this->api_key;
		$url=$this->url.$route.'.json';

		$res=$this->_json_post($url,$data);

		$res_arr=json_decode($res,true);
		if ($res_arr===false or $res_arr===null){
			if (empty($res)){
				throw new GlideException('No data was returned from '.$url.'.');
			}
			else {
				$this->_file_save($this->log_dir.'/glide_error.html',$res,true);
				throw new GlideException('The JSON data could not be parsed from '.$url.'.');
			}
		}
		return $res_arr;
	}

	private function service_name($service){
		return $this->service_names[$service] || ucfirst($service);
	}

	// from http://www.lornajane.net/posts/2011/posting-json-data-with-php-curl
	private function _json_post($url,$data){                                                                   
		$data_string=json_encode($data);

		$ch=curl_init($url);
		curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"POST");
		curl_setopt($ch,CURLOPT_POSTFIELDS,$data_string);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array(
			'Content-Type: application/json',
			'Content-Length: '.strlen($data_string),
		));
		$result=curl_exec($ch);
		return $result;
	}

	private function _file_save($file,$string,$overwrite=false){
		$fh=@fopen($file,$overwrite ? 'w' : 'a');
		if (!empty($fh)){
			fwrite($fh,$string);
			fclose($fh);
			return true;
		}
		else {
			return false;
		}
	}
}

class GlideException extends Exception {
	private $errors=array();

	function __construct($message,$errors=null){
		$this->errors=$errors;
		parent::__construct($message);
	}

	function get_errors(){
		return $this->errors;
	}
}