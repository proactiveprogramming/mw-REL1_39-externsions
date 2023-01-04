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

class FinMathCashflows_Test extends PHPUnit_Framework_TestCase{
  private $debug = false;
  private $ccalc;
  private $neg = 0.00001;
  
  public function setup(){
    $this->ccalc = new FinMathCashflows();
  }

  public function tearDown(){
	}
  
  public function test_setup(){
    $this->assertEquals( $this->ccalc->get_values(), 
			array('cashflows_value'=>0)
		);
  }  
 
  public function test_add_cashflow(){
		$a = new FinMathAnnuity();
    $c = new FinMathCashflow( 100, 0, $a);
		$this->ccalc->add_cashflow( $c );
    $this->ccalc->set_value( -1 );
		try{
			$expl = $this->ccalc->get_delta_for_value( -123 );
		} catch ( Exception $e ) { 
    	$error = $e->getMessage(); 
		}
    $this->assertEquals( 
			'fm-exception-max-iterations-exceeded-123. fm-exception-query-cashflows-possible', 
			$error 
		); 		
  }  

  public function test_concepts()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'concept'=>'concept_cashflows' ) );
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
  }  

  public function test_input_returns_expected_add_cashflow()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 
			'request' => 'add_cashflow',
			'rate_per_year' => 0,
			'effective_time' => 0, 
			'm' => 1, 
			'term' => 1,
			'escalation_rate_effective' => 0,
			'escalation_frequency' => 1)
		);
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
	  $this->assertTrue( isset($c['output']['unrendered']['formulae']) ) ;
	  $this->assertFalse( isset($c['output']['unrendered']['xml-form']) ) ;
  }  

  public function test_input_cashflow_frequency()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 
			'request'=>'add_cashflow', 'm'=>200, 'term'=>0.75)
		);
	  $this->assertFalse( isset($c['warning']));
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
		$c = $x->get_controller( array( 
			'request'=>'add_cashflow', 'm'=>100, 'term'=>0.75)
		);
	  $this->assertFalse( isset($c['warning']));
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
  }  

  public function test_input_cashflow_escalation_frequency()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 
			'request'=>'add_cashflow', 'm'=>4, 'term'=>0.75 )
		);
	  $this->assertFalse( isset($c['warning']));
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
		$c = $x->get_controller( array( 
			'request'=>'add_cashflow', 'm'=>100, 
			'term'=>0.75, 'escalation_frequency'=>1)
		);
	  $this->assertFalse( isset($c['warning']));
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
  }  

  public function test_input_add_cashflow()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array(
		    'request' => 'add_cashflow',
				'rate_per_year'=>21,
				'effective_time'=>0,
				'm'=>1000,
				'source_m'=>1,
				'source_rate'=>0.09,
				'term'=>0.75,
				'escalation_rate_effective'=>0.035,
				'escalation_frequency'=>1000,
		));
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
		$r = new FinMathRender();
		$out = $r->get_rendered_result( 
			$c['output']['unrendered'], 'dummyPageTitle' 
		);
    $this->assertTrue( isset($out['forms']) ) ;		
}

  public function test_input_add_cashflow2()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array(
		    'request' => 'add_cashflow',
				'rate_per_year'=>21,
				'effective_time'=>0,
				'm'=>1,
				'source_m'=>1,
				'source_rate'=>0.09,
				'term'=>0.75,
				'escalation_rate_effective'=>0.035,
				'escalation_frequency'=>1000,
		));
    $this->assertTrue( isset($c['warning'])) ;  
}

  public function test_input_returns_expected_VIEW_cashflowS()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array(
    'FinMathCashflows' => Array
        (
            0 => Array
                (
                    'm' => 1,
                    'term' => 10,
                    'rate_per_year' => 123,
                    'effective_time' => 0,
                ),
        ),
    	'request' => 'view_cashflows',
			)
		);
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
	  $this->assertTrue( isset($c['output']['unrendered']['formulae']) ) ;
	  $this->assertFalse( isset($c['output']['unrendered']['xml-form']) ) ; 
		//we don't want XML-form until we have a value or i_effective
  }  

  public function test_input_XML_cashflows(){
	  $x = new FinMathFormXML();
		$x->set_text( array( 
			'request'=>'value_cashflows',  
			'FinMathCashflows'=>array(
				'item0'=>array(
					'm'=>1, 'advance'=>1, 'delta'=>0, 
					'i_effective'=>0, 'term'=>1,'value'=>1, 
					'rate_per_year'=>999,'effective_time'=>1) 
				) 
			) 
		);
		$c = $x->get_calculator( array());
		$expected="\n<dummy_tag_set_in_FinMathFormXML><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>999</rate_per_year><effective_time>1</effective_time></item0></FinMathCashflows></parameters></dummy_tag_set_in_FinMathFormXML>\n";
		$this->assertEquals( $expected, $c['values']['xml'] ) ;
  }  

  public function test_xml_GIVES_SAME_XML(){
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array(
    	'FinMathCashflows' => Array(	0 => Array(
					'm' => 1,
					'term' => 10,
					'rate_per_year' => 123,
					'effective_time' => 0,
       		),
      	),
			'i_effective' => 0,
    	'request' => 'value_cashflows',
			)
		);
	  $this->assertTrue( isset($c['output']['unrendered']['forms']) ) ;
	  $this->assertTrue( isset($c['output']['unrendered']['formulae']) ) ;
	  $this->assertTrue( isset($c['output']['unrendered']['xml-form']) ) ; 
	  $this->assertTrue( isset($c['output']['unrendered']['summary']) ) ; 
		$c_forms = $c['output']['unrendered']['xml-form']['forms'];
		$produced_xml ='';
		foreach ($c_forms as $f){
			if ('process_xml'==$f['content']['request']){
				$produced_xml = $f['content']['values']['xml'];
			}
		}
		$XML = $produced_xml;
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$XML ));
		$c_forms = $c['output']['unrendered']['forms'];
		$candidate_xml ='';
		foreach ($c_forms as $f){
			if ('process_xml'==$f['content']['request']){
				$candidate_xml = $f['content']['values']['xml'];
			}
		}
	  $this->assertEquals( urldecode($XML), $candidate_xml) ;
  }  

  public function test_xml_GIVES_SAME_result()  {
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array(
    'FinMathCashflows' => Array
			(	0 => Array
				(
					'm' => 1,
					'term' => 10,
					'rate_per_year' => 123,
					'effective_time' => 0,
        ),
      ),
		'i_effective' => 0,
    'request' => 'value_cashflows',
		));
	  $original_formulae = $c['output']['unrendered']['formulae'];
		$c_forms = $c['output']['unrendered']['xml-form']['forms'];
		$produced_xml ='';
		foreach ($c_forms as $f){
			if ('process_xml'==$f['content']['request']){
				$produced_xml = $f['content']['values']['xml'];
			}
		}
		$XML = $produced_xml;
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$XML ));
	  $processed_formulae = $c['output']['unrendered']['formulae'];
	  $this->assertEquals( $original_formulae, $processed_formulae) ;
  }  

  public function test_single_cashflow()  {
	  $x = new FinMathConceptAll();
  	$c = $x->get_controller( array(
    'request' => 'add_cashflow',
		'single_payment'=>1,
		'effective_time'=>0,
		'rate_per_year'=>999,
		'm'=>123,
		'advance'=>1,
		'term'=>11,
		));
	  $processed_formulae = $c['output']['unrendered']['formulae'];
	  $this->assertEquals( ' + 999', $processed_formulae[0]['right']) ;
	}

  public function test_single_cashflow2()  {
	  $x = new FinMathConceptAll();
  	$c = $x->get_controller( array(
    'request' => 'add_cashflow',
		'single_payment'=>1,
		'effective_time'=>0,
		'rate_per_year'=>-123,
		'm'=>123,
		'term'=>11,
		));
	  $processed_formulae = $c['output']['unrendered']['formulae'];
	  $this->assertEquals( ' - 123', $processed_formulae[0]['right']) ;
	}


  public function test_CT1_S2014_Q6ii() {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0.039220713153281</delta><i_effective>0.04</i_effective><term>1</term><value>1</value><rate_per_year>1000</rate_per_year><effective_time>10</effective_time><cashflow_value>6755.641688258</cashflow_value></item0><item1><m>1</m><advance/><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>10</term><value></value><increasing>1</increasing><rate_per_year>5</rate_per_year><effective_time>0</effective_time><cashflow_value>22000</cashflow_value></item1></FinMathCashflows><i_effective>0.04</i_effective><value/></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 885.53, 
			number_format($c['output']['unrendered']['summary']['result'],2)
		) ;
  }  

/**
 * @medium
 */
  public function test_CT1_A2015_Q6()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1000</term><value>3.3706527598447E 26</value><escalation_delta>0.058268908123976</escalation_delta><escalation_rate_effective>0.06</escalation_rate_effective><escalation_frequency>1</escalation_frequency><rate_per_year>6</rate_per_year><effective_time>0.5</effective_time><cashflow_value>2.0223916559068E 27</cashflow_value></item0></FinMathCashflows><i_effective/><value>175</value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 0.09589, 
			number_format($c['output']['unrendered']['summary']['result'],5)
		) ;
}

  public function test_CT1_A2015_Q8i()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>1000</rate_per_year><effective_time>10</effective_time><cashflow_value>1000</cashflow_value></item0><item1><m>1</m><advance/><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>10</term><value>55</value><increasing>1</increasing><rate_per_year>9</rate_per_year><effective_time>0</effective_time><cashflow_value>495</cashflow_value></item1></FinMathCashflows><i_effective>0.07</i_effective><value/></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 821, 
			number_format($c['output']['unrendered']['summary']['result'],0)
		) ;
}

  public function test_CT1_A2015_Q8iagain()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance/><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>10</term><value>10</value><rate_per_year>9</rate_per_year><effective_time>0</effective_time><cashflow_value>90</cashflow_value></item0><item1><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>100</rate_per_year><effective_time>10</effective_time><cashflow_value>100</cashflow_value></item1></FinMathCashflows><i_effective>0.07</i_effective><value/></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( number_format(114.05,2), 
			number_format($c['output']['unrendered']['summary']['result'],2)
		) ;
}

  public function test_CT1_A2015_Q9()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0.113328685307</delta><i_effective>0.12</i_effective><term>1</term><value>1</value><rate_per_year>4000000</rate_per_year><effective_time>0</effective_time><cashflow_value>4000000</cashflow_value></item0><item1><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0.113328685307</delta><i_effective>0.12</i_effective><term>1</term><value>1</value><rate_per_year>900000</rate_per_year><effective_time>0.5</effective_time><cashflow_value>850420.06427076</cashflow_value></item1><item2><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0.113328685307</delta><i_effective>0.12</i_effective><term>1</term><value>1</value><rate_per_year>-6800000</rate_per_year><effective_time>6</effective_time><cashflow_value>-3445091.6240058</cashflow_value></item2><item3><m>4</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>5</term><value>5.75073901</value><escalation_delta>0.067658648473815</escalation_delta><escalation_rate_effective>0.0683544</escalation_rate_effective><escalation_frequency>1</escalation_frequency><rate_per_year>-360000</rate_per_year><effective_time>1</effective_time><cashflow_value>-2070266.0436</cashflow_value></item3></FinMathCashflows><i_effective>0.12</i_effective><value/></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
		// test that value is near nil i.e. input rate gives 0 NPV
	  $this->assertTrue( abs($c['output']['unrendered']['summary']['result']) < 1) ;
}

  public function test_CT1_A2015_Q11()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>30000</rate_per_year><effective_time>0</effective_time><cashflow_value>30000</cashflow_value></item0><item1><m>12</m><advance/><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>5</term><value>5</value><rate_per_year>-11499.84</rate_per_year><effective_time>3</effective_time><cashflow_value>4791.6</cashflow_value></item1></FinMathCashflows><value>0</value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(0.128,3), 
			number_format($c['output']['unrendered']['summary']['result'],3)
		) ;
}

  public function test_CT1_S2013_Q6()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1000</m><advance/><source_rate/><source_format/><delta>0.076961041136128</delta><i_effective>0.08</i_effective><term>1</term><value>0.96248794169835</value><rate_per_year>146000</rate_per_year><effective_time>0</effective_time><cashflow_value>140523.23948796</cashflow_value></item0><item1><m>1000</m><advance/><source_rate/><source_format/><delta>0.076961041136128</delta><i_effective>0.08</i_effective><term>19</term><value>10.692854773277</value><escalation_delta>0.0099503308531681</escalation_delta><escalation_rate_effective>0.0201</escalation_rate_effective><escalation_frequency>1</escalation_frequency><rate_per_year>200750</rate_per_year><effective_time>1</effective_time><cashflow_value>1987583.8849401</cashflow_value></item1><item2><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0.076961041136128</delta><i_effective>0.08</i_effective><term>1</term><value>1</value><rate_per_year>-2000000</rate_per_year><effective_time>0</effective_time><cashflow_value>-2000000</cashflow_value></item2></FinMathCashflows><i_effective>0.08</i_effective><value/></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(275324,0), 
			number_format($c['output']['unrendered']['summary']['result'],0)
		) ;
}

  public function test_CT1_S2013_Q9()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance></advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>15</term><value>15</value><rate_per_year>420</rate_per_year><effective_time>0</effective_time><cashflow_value>6300</cashflow_value></item0><item1><m>1</m><advance></advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>15</term><value>120</value><increasing>1</increasing><rate_per_year>-20</rate_per_year><effective_time>0</effective_time><cashflow_value>-2400</cashflow_value></item1></FinMathCashflows><i_effective>0.04</i_effective><value></value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(3052.65,2), 
			number_format($c['output']['unrendered']['summary']['result'],2)
		) ;
}

  public function test_CT1_A2013_Q1()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>1.3</rate_per_year><effective_time>0</effective_time><cashflow_value>1.3</cashflow_value></item0><item1><m>1</m><advance>1</advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>-0.9</rate_per_year><effective_time>0.75</effective_time><cashflow_value>-0.9</cashflow_value></item1><item2><m>1</m><advance>1</advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>-0.8</rate_per_year><effective_time>1</effective_time><cashflow_value>-0.8</cashflow_value></item2></FinMathCashflows><i_effective></i_effective><value>0</value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(0.36,2), 
			number_format($c['output']['unrendered']['summary']['result'],2)
		) ;
}

  public function test_perpetuity_20()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance></advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>1000</term><rate_per_year>1</rate_per_year><effective_time>0</effective_time></item0></FinMathCashflows><value>20</value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(0.05,2), 
			number_format($c['output']['unrendered']['summary']['result'],2)
		) ;
	}

  public function test_perpetuity_5pc()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance></advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>1000</term><rate_per_year>1</rate_per_year><effective_time>0</effective_time></item0></FinMathCashflows><i_effective>0.05</i_effective><value></value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(20,2), 
			number_format($c['output']['unrendered']['summary']['result'],2)
		) ;
	}

  public function test_single_cashflows_short()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>99</rate_per_year><effective_time>99</effective_time><cashflow_value>99</cashflow_value></item0><item1><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>12</rate_per_year><effective_time>12</effective_time><cashflow_value>12</cashflow_value></item1></FinMathCashflows><i_effective>0.1</i_effective><value/></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(3.83,2), 
			number_format($c['output']['unrendered']['summary']['result'],2)
		) ;
	  if ( isset($c['output']['unrendered']['formulae'][1]['right']) ){
	  	if ( is_array($c['output']['unrendered']['formulae'][1]['right'])){
	  		if ( isset($c['output']['unrendered']['formulae'][1]['right']['detail'])){
					$_content = $c['output']['unrendered']['formulae'][1]['right']['detail'];
					if (isset($_content)){
	  				if ( !( empty($_content) )){
	  					$this->assertTrue( false );
						}
					}
				}
			}
		}
	}

  public function test_single_cashflows_show_v()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>100</rate_per_year><effective_time>3</effective_time><cashflow_value>100</cashflow_value></item0><item1><m>1</m><advance/><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>3</term><value>3</value><rate_per_year>3</rate_per_year><effective_time>0</effective_time></item1></FinMathCashflows><i_effective/><value>101</value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 6, count($c['output']['unrendered']['formulae']) );
	}

  public function test_single_cashflows_show_v_four_rows()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>870000</rate_per_year><effective_time>0</effective_time><cashflow_value>870000</cashflow_value></item0><item1><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>26000</rate_per_year><effective_time>0.5</effective_time><cashflow_value>26000</cashflow_value></item1><item2><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>27000</rate_per_year><effective_time>1.5</effective_time><cashflow_value>27000</cashflow_value></item2><item3><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>33000</rate_per_year><effective_time>2.5</effective_time><cashflow_value>33000</cashflow_value></item3><item4><m>1</m><advance>1</advance><source_rate/><source_format/><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>-990000</rate_per_year><effective_time>3</effective_time><cashflow_value>-990000</cashflow_value></item4></FinMathCashflows><i_effective/><value>0</value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(0.012,3), 
			number_format($c['output']['unrendered']['summary']['result'],3)
		) ;
	  $this->assertEquals( 9, count($c['output']['unrendered']['formulae']) );
}

  public function test_CT1_A2013_Q3()  {
		$xml="<fin-math><parameters><request>value_cashflows</request><FinMathCashflows><item0><m>1</m><advance></advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>3</term><value>3</value><rate_per_year>6</rate_per_year><effective_time>0</effective_time><cashflow_value>18</cashflow_value></item0><item1><m>1</m><advance>1</advance><source_rate></source_rate><source_format></source_format><delta>0</delta><i_effective>0</i_effective><term>1</term><value>1</value><rate_per_year>103</rate_per_year><effective_time>3</effective_time><cashflow_value>103</cashflow_value></item1></FinMathCashflows><i_effective></i_effective><value>97</value></parameters></fin-math>";
	  $x = new FinMathConceptAll();
		$c = $x->get_controller( array( 'request'=>'process_xml', 'xml'=>$xml ));
	  $this->assertEquals( 
			number_format(0.08089,5), 
			number_format($c['output']['unrendered']['summary']['result'],5)
		) ;
	  $this->assertFalse( isset($c['output']['unrendered']['formulae'][3]['right']['detail'])); 
		// 3rd line is simple, needs no explanation
	}

}
