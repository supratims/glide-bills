<?php

class Glide {
	protected $glide_human_name=array("elec"=>"Electricity","tv_license"=>"TV License");
	private $api_key;
	protected $url='https://www.glide.uk.com/api/4.0/signUp/quote';
	protected $html_error=array();
	public $log_html=true;
	public $log_dir='logs';

	function __construct($api_key){
		if (empty($api_key)){
			throw Exception('You must set a valid Glide API key.');
		}
		$this->api_key=$api_key;
		return $this;
	}

	function get_quote(Array $services=array('elec'),Array $extra=array(),$period=6,$tenants=1){
		$quotes=$errors=array();
		foreach ($services as $service){
			try {
				$quotes[$service]=$this->get_service_quote($service,$extra[$service],$period,$tenants);
				$quotes[$service]["human_name"]=$this->insert_human_name($service);
			}
			catch (Exception $e){
				$errors[]=$e->getMessage();
			}
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

	private function get_service_quote($service,$extra,$period,$tenants){
		$query=array('service'=>$service,'period'=>$period,'extra'=>$extra,'tenants'=>$tenants,'key'=>$this->api_key);
		$res=$this->_json_post($this->url,$query);
		$res_arr=json_decode($res);
		if ($res_arr===false or $res_arr===null){
			if (empty($res)){
				throw new Exception('No data was returned.');
			}
			else {
				$this->html_error[]=$res;
				throw new Exception('The JSON data could not be parsed.');
			}
		}
		return is_array($res_arr) ? $res_arr : $res;
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