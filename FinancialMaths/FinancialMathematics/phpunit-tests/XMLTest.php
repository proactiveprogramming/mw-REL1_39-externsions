<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Owen Kellie-Smith
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class FinMathXML_Test extends PHPUnit_Framework_TestCase{
  
	private $unused;

  public function setup(){}

  public function tearDown(){}
  
  public function test_valid_input()  {
	  $x = new FinMathXML("<parameters><something>A thing</something></parameters>");
		$temp = $x->get_values();
		$this->assertEquals( 
			$temp['xml'],
			"<parameters><something>A thing</something></parameters>" 
		);
  }  

  public function test_invalid_overwrite()  {
	  $x = new FinMathXML("<parameters><something>A thing</something></parameters>");
	  $x->set_xml("some junk");
		$temp = $x->get_values();
		$this->assertEquals( 
			$temp['xml'],
			"<parameters><something>A thing</something></parameters>" 
		);
  }

  public function test_valid_overwrite()  {
	  $x = new FinMathXML("<parameters><something>A thing</something></parameters>");
	  $x->set_xml("some junk");
	  $x->set_xml("<parameters><something>Something else</something></parameters>");
		$temp = $x->get_values();
		$this->assertEquals( 
			$temp['xml'],
			"<parameters><something>Something else</something></parameters>" 
		);
  }  

}
