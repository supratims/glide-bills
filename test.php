<?php
include 'glide.php';

class testGlide extends PHPUnit_Framework_TestCase {
	private $glide;

	function __construct(){
		$this->glide=new Glide('PoinbidukyiksUkJedeivtaghaHikmot');
	}

	function testGlideResponse(){
		var_dump($this->glide->get_quote());
		$this->assertTrue(true);
	}
}
