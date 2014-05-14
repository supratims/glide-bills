<?php
include 'Glide.php';

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
		try {
			$this->glide->set_tenants($this->tenants)->set_term($this->term)->signUp_quote_allServices();
		}
		catch (GlideException $e){
			$errors=$e->get_errors();
		}
		$this->assertEquals(array_keys($errors),array('postcode'));
	}

	function testGlideExceptionNoTerm(){
		try {
			$this->glide->set_postcode($this->postcode)->set_tenants($this->tenants)->signUp_quote_allServices();
		}
		catch (GlideException $e){
			$errors=$e->get_errors();
		}
		$this->assertEquals(array_keys($errors),array('term'));
	}

	function testGlideExceptionNoTenants(){
		try {
			$this->glide->set_postcode($this->postcode)->set_term($this->term)->signUp_quote_allServices();
		}
		catch (GlideException $e){
			$errors=$e->get_errors();
		}
		$this->assertEquals(array_keys($errors),array('tenants'));
	}

	function testGlideRouteFailure(){
        $method=new ReflectionMethod('Glide','send_request');
 
        $method->setAccessible(TRUE);
		$this->setExpectedException('GlideException');
 
        $method->invoke($this->glide_new(),array(),'made/up/route');
    }
}
