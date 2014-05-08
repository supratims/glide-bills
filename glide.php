<?php

class Glide {
	protected $glide_human_name=array("electricity"=>"Electricity","tv_license"=>"TV License");

	private $api_key;

	protected $url='https://www.glide.uk.com/api/4.0/';
	protected $methods=array('frontEnd'=>array(
	),'portal'=>array(
	),'signUp'=>array(
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
	protected $services=array('gas','electricity','water','telephone','broadband','tv');
	protected $html_error=array();

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
		return $this;
	}

	function get_quote(Array $services=array('electricity'),Array $extra=array(),$period=6,$tenants=1,$postcode='m1 1dz'){
		$quotes=$errors=array();
		foreach ($services as $service){
			try {
				$quotes[$service]=$this->get_service_quote($service,$extra[$service],$period,$tenants,$postcode);
			}
			catch (Exception $e){
				$errors[]=$e->getMessage();
			}
			// $quotes[$service]["human_name"]=$this->insert_human_name($service);
		}
		if (!empty($errors)){
			foreach ($this->html_error as $n => $html){
				if (!empty($html)){
					$this->_file_save($this->log_dir.'/glide_error'.$n.'.html',$html,true);
					$saved=true;
				}
			}
			throw new Exception('There was an error with the Glide API.'.($saved ? ' Log files have been saved at "'.$this->log_dir.'/"' : '').' More details: '.PHP_EOL.implode(PHP_EOL,$errors));
		}
		return $this->calculate_totals($quotes);
	}

	function set_postcode($postcode){
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
			throw new Exception('You must choose a valid broadband type.');
		}
		$this->broadbandType=$broadbandType;
		return $this;
	}

	function signUp_quote_allServices($no_water=false){
		$data=array(
			'postcode'=>$this->postcode,
			'capacity'=>$this->tenants,
			'minTerm'=>$this->term,
		);
		foreach ($this->services as $service){
			$data[$service]=true;
		}
		if ($no_water){
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
			if (!$no_water and strpos($res['message'],'We are unable to supply water')!==false){
				$res=$this->signUp_quote_allServices(true);
			}
		}
		return $res;
	}

	function signUp_quote_servicePrice($service){
		$data=array(
			'postcode'=>$this->postcode,
			'capacity'=>$this->tenants,
			'minTerm'=>$this->term,
		);
		if (!in_array($service,$this->services)){
			throw new Exception('The selected service ('.$service.') is not valid.');
		}
		$data[$service]=true;
		if ($service=='broadband'){
			if (empty($this->broadbandType)){
				throw new Exception('You must enter a broadband type to check broadband prices.');
			}
			$data['broadbandType']=$this->broadbandType;
		}
		$res=$this->send_request($data,'signUp/quote/allServices');
		if ($res['error']==1){

		}
		return $res;
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
			throw new Exception('The following data input errors have occured:'."<br/>\n".implode("<br/>\n",$errors));
		}
	}

	private function send_request($data,$route){
		if ($route[0]=='/'){
			$route=substr($route,1);
		}
		$check_route=explode("/",$route);
		$methods=$this->methods;
		foreach ($check_route as $step){
			if (!isset($methods[$step])){
				throw new Exception('The route '.$route.' could not be found. It failed looking for "'.$step.'"');
			}
			$methods=$methods[$step];
		}

		$data['key']=$this->api_key;
		$url=$this->url.$route.'.json';

		$res=$this->_json_post($url,$data);

		$res_arr=json_decode($res,true);
		if ($res_arr===false or $res_arr===null){
			if (empty($res)){
				throw new Exception('No data was returned from '.$url.'.');
			}
			else {
				$this->_file_save($this->log_dir.'/glide_error.html',$res,true);
				throw new Exception('The JSON data could not be parsed from '.$url.'.');
			}
		}
		return $res_arr;
	}

	private function insert_human_name($service){
		return $this->glide_human_name[$service] || ucfirst($service);
	}

	private function calculate_totals($quotes){
		$quotes["total"] = array();
		$build=array("tenant_week", "tenant_month", "monthly_fee");
		foreach ($build as $k){
			// $quotes["total"][$k] = "%.2f" % quotes.map { |e| e[1][k].to_f }.reduce(:+)
		}
		return $quotes;
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