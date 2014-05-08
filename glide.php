<?php
class Glide {
	protected $url='https://www.glide.uk.com/api/4.0/';
	protected $methods=array('frontEnd'=>array(
	),'portal'=>array(
	),'signUp'=>array(
		'payment'=>array(
			'registerCard',
			'getClientAddresses',
		),
		'quote'=>array(
			'allServices'=>true,
			'servicePrice'=>true,
			'telephoneConnectionCharge'=>true,
			'broadbandActivationCharge'=>true,
			'broadbandAvailability'=>true,
			'telephoneConnectionType'=>true,
			'telephoneAddress'=>true,
			'email'=>true,
		),
	),);
	protected $service_names=array('gas'=>'Gas','electricity'=>'Electricity','water'=>'Water','telephone'=>'Phone','broadband'=>'Internet','tv'=>'TV license');
	protected $html_error=array();

	private $api_key;
	private $services;
	private $postcode_no_water=false;

	public $log_html=true;
	public $log_dir='logs';
	public $postcode='';
	public $tenants=0;
	public $term=0;
	public $broadbandTypes=array('llu24s','llu24p','bt24s');
	public $broadbandType='';

	function __construct($api_key){
		if (empty($api_key)){
			throw Exception('You must set a valid Glide API key.');
		}
		$this->api_key=$api_key;
		$this->services=array_keys($this->service_names);
		return $this;
	}

	function set_postcode($postcode){
		$this->postcode_no_water=false;
		$this->postcode=$postcode;
		return $this;
	}

	function set_term($term){
		$this->term=$term;
		return $this;
	}

	function set_tenants($tenants){
		$this->tenants=$tenants;
		return $this;
	}

	function set_broadbandType($broadbandType){
		if (!in_array($broadbandType,$this->broadbandTypes)){
			throw new GlideValidException('You must choose a valid broadband type.');
		}
		$this->broadbandType=$broadbandType;
		return $this;
	}

	function signUp_quote_allServices(){
		$this->valid_signUp_quote();
		$data=array(
			'postcode'=>$this->postcode,
			'capacity'=>$this->tenants,
			'minTerm'=>$this->term,
		);
		foreach ($this->services as $service){
			$data[$service]=true;
		}
		if ($this->postcode_no_water){
			unset($data['water']);
		}
		if (!empty($this->broadbandType)){
			$data['broadband']=true;
			$data['broadbandType']=$this->broadbandType;
		}
		else {
			$data['broadband']=false;
		}
		$res=$this->send_request($data,'signUp/quote/allServices');
		if ($res['error']==1){
			// this hack is needed until Glide return a simple "0" or similar for unable to provide water reponse
			if (!$this->postcode_no_water and strpos($res['message'],'We are unable to supply water')!==false){
				$this->postcode_no_water=true;
				$res=$this->signUp_quote_allServices();
			}
			else {
				$this->exception_message($res);
			}
		}
		return $res;
	}

	function signUp_quote_servicePrice($service){
		$this->valid_signUp_quote();
		$data=array(
			'postcode'=>$this->postcode,
			'capacity'=>$this->tenants,
			'minTerm'=>$this->term,
		);
		if (!in_array($service,$this->services)){
			throw new GlideValidException('The selected service ('.$service.') is not valid.');
		}
		$data[$service]=true;
		if ($service=='water' and $this->postcode_no_water){
			$this->exception_no_water();
		}
		if ($service=='broadband'){
			if (empty($this->broadbandType)){
				throw new GlideValidException('You must enter a broadband type to check broadband prices.');
			}
			$data['broadbandType']=$this->broadbandType;
		}
		$res=$this->send_request($data,'signUp/quote/allServices');
		if ($res['error']==1){
			if (!$this->postcode_no_water and strpos($res['message'],'We are unable to supply water')!==false){
				$this->exception_no_water();
			}
			$this->exception_message($res);
		}
		return $res;
	}

	private function exception_no_water(){
		throw new GlideException('It is not possible to get a water quote for this postcode.');
	}

	private function exception_message($res){
		throw new GlideException('The Glide server reported an error: '.$res['message']);
	}

	private function valid_signUp_quote(){
		if (empty($this->postcode)){
			$errors[]='No postcode.';
		}
		if (empty($this->tenants)){
			$errors[]='No tenants.';
		}
		if (empty($this->term)){
			$errors[]='No term.';
		}
		if (!empty($errors)){
			throw new GlideValidException('The following data input errors have occured:'."<br/>\n".implode("<br/>\n",$errors));
		}
	}

	private function send_request(Array $data,$route){
		if ($route[0]=='/'){
			$route=substr($route,1);
		}
		$check_route=explode("/",$route);
		$methods=$this->methods;
		foreach ($check_route as $step){
			if (!isset($methods[$step])){
				throw new GlideValidException('The route '.$route.' could not be found. It failed looking for "'.$step.'"');
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

class GlideValidException extends Exception {
	
}

class GlideException extends Exception {

}