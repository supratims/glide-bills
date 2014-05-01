<?php
include 'glide.php';

class testGlide extends PHPUnit_Framework_TestCase {
	private $glide;

	function __construct(){
		$api_key=shell_exec('cat api_key');
		if (empty($api_key)){
			throw new Exception('Create a file called "api_key" which constains your Glide API key.');
		}
		$this->glide=new Glide($api_key);
	}

	function testGlideResponse(){
		var_dump($this->glide->get_quote());
		$this->assertTrue(true);
	}
}
