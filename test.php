<?php
include 'glide.php';

class testGlide extends PHPUnit_Framework_TestCase {
	private $glide;
	private $postcode='m1 1dz';
	private $tenants=5;
	private $term=12;

	function __construct(){
		$api_key=shell_exec('cat api_key');
		if (empty($api_key)){
			throw new Exception('Create a file called "api_key" which constains your Glide API key.');
		}
		$glide=new Glide($api_key);
		$this->glide=$glide->set_postcode($this->postcode)->set_tenants($this->tenants)->set_term($this->term);
	}

	function testGlideQuoteServices(){
		print_r($this->glide->signUp_quote_allServices());
		$this->assertTrue(true);
	}

	function testGlideQuoteService(){
		print_r($this->glide->signUp_quote_servicePrice('electricity'));
		$this->assertTrue(true);
	}
}
