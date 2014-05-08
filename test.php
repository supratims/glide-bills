<?php
include 'glide.php';

class testGlide extends PHPUnit_Framework_TestCase {
	private $glide;
	private $postcode='m1 1dz';
	private $tenants=5;
	private $term=12;

	function __construct(){
		$this->glide=$this->glide_new();
	}

	private function glide_new(){
		$api_key=shell_exec('cat api_key');
		if (empty($api_key)){
			throw new Exception('Create a file called "api_key" which constains your Glide API key.');
		}
		return new Glide($api_key);
	}

	private function quote_setup(){
		$this->glide->set_postcode($this->postcode)->set_tenants($this->tenants)->set_term($this->term);
	}

	function testGlideQuoteServicesReturnsArray(){
		$this->quote_setup();
		$res=$this->glide->signUp_quote_allServices();
		$this->assertTrue(is_array($res));
	}

	function testGlideQuoteServiceReturnsArray(){
		$this->quote_setup();
		$res=$this->glide->signUp_quote_servicePrice('electricity');
		$this->assertTrue(is_array($res));
	}

	function testGlideExceptionNoPostcode(){
		$this->setExpectedException('GlideValidException');
		$this->glide->set_tenants($this->tenants)->set_term($this->term)->signUp_quote_allServices();
	}

	function testGlideExceptionNoTerm(){
		$this->setExpectedException('GlideValidException');
		$this->glide->set_postcode($this->postcode)->set_tenants($this->tenants)->signUp_quote_allServices();
	}

	function testGlideExceptionNoTenants(){
		$this->setExpectedException('GlideValidException');
		$this->glide->set_postcode($this->postcode)->set_term($this->term)->signUp_quote_allServices();
	}

	function testGlideRouteFailure(){
        $method=new ReflectionMethod('Glide','send_request');
 
        $method->setAccessible(TRUE);
		$this->setExpectedException('GlideValidException');
 
        $method->invoke($this->glide_new(),array(),'made/up/route');
    }
}
