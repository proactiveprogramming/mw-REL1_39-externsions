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
 * @file
 */

/**
 *
 * FinMathForm is an abstract class for the "forms" used to input and process
 * requests about FinMath objects.
 *
 * It might turn out that the forms and their related classes should be merged 
 * into one class, but for now they are separate.
 * Questions (inputs) go into a FinMathForm.  The inputs are used to populate a
 * relevant FinMathObject.  Answers (results) come out of the FinMathForm.
 */
abstract class FinMathForm implements FinMathConcept {

	protected $obj;
	protected $request;

	protected static function myMessage( $messageKey){
		$m = $messageKey;
		if ( function_exists('wfMessage') ){
			if (null == wfMessage( $messageKey)->text()){
				return $messageKey;
			} else {
				$m=wfMessage( $messageKey)->text();
			}
		}
		return $m;
	}

	protected function candidate_concepts(){
		return array( 
			new FinMathConceptInterest(),
			new FinMathConceptAnnuity(), 
			new FinMathConceptMortgage(), 
			new FinMathConceptAnnuityIncreasing(), 
			new FinMathConceptCashflows(),
			new FinMathConceptSpotRates(),
			new FinMathFormXML(),
	 );
	}

	protected function get_unrendered_delete_buttons( $request = ""){
		$out = array();
		if ( $this->obj instanceof FinMathCollection ){
			if ( $this->obj->get_count() > 0 ){
				$cfs = $this->obj->get_objects();
				foreach ( $this->obj->get_objects() as $o ) {
					if (!method_exists( $this->obj, 'get_clone_this' )){
						throw new Exception('get_clone_this ' . 
							self::myMessage( 'fm-error-clone') . get_class( $this->obj ) . 
							self::myMessage( 'fm-error-in')  . __FILE__ 
						);
					}
					$clone = $this->obj->get_clone_this();
					$label = "";
					$clone->remove_object($o);
					$button = array( 
						'type'=>'get_form_collection',
						'content'=> array(
							'collection' =>$clone,
							'submit'=> self::myMessage( 'fm-button-delete') . " " .  $o->get_label(),
							'intro' =>'',
							'request' => $request,
						),
					);
					$out[] = $button;
					$clone = null;
					$button = null;
				}
			}
		}
		return $out;
	}

	public function __construct(FinMathObject $obj){
		$this->set_obj($obj);
	}

	protected function set_obj(FinMathObject $obj){	
		$this->obj = $obj;
	}

	public function get_obj(){
		return $this->obj;
	}

	protected function get_solution(){
		return;
	}

	protected function get_request(){
		return $this->request;
	}

	protected function get_possible_requests(){
		return array( $this->request );
	}

	protected function set_request($s){
		$this->request = $s;
	}

	protected function get_calculator( $parameters) {
		// returns associative array which can be passed to a form renderer e.g. QuickForm2
		$p = $parameters;
		$this->get_form_parameters($p);
		$return = array();
		$return['method'] = $p['method'];
		$return['parameters'] = $this->obj->get_parameters();
		$return['valid_options'] = $this->obj->get_valid_options();
		$return['values'] = $this->obj->get_values();
		$return['request'] = $p['request'];
		$return['submit'] = $p['submit'];
		$return['type'] = $p['type'];
		$return['special_input'] = $p['special_input'];
		$return['action'] = $p['action'];
		$return['exclude'] = $p['exclude'];
		$return['render'] = $p['render'];
		$return['introduction'] = $p['introduction'];
		return $return;			
	}

	protected function set_received_input(&$_INPUT = array()){
		$c = 0;
		$c++;
		foreach (array_keys($this->obj->get_parameters()) as $p){
			if (!isset($_INPUT[$p])){	
				$_INPUT[$p] = NULL;
			}
		}	
	}

	protected function get_form_parameters(&$_parameters = array()){
		$def = $this->get_form_parameters_default();
		foreach (array_keys($def) as $p){
			if (!array_key_exists($p, $_parameters)){
				$_parameters[ $p] = $def[$p];
			}
		}
	}

	protected function get_form_parameters_default(){
		return array( 'exclude' =>array(), 
			'request'=> '',
			'submit'=>"Submit",
			'type'=>'', 
			'special_input'=>'',
			'action'=>'', 
			'method'=>'GET', 
			'render'=>'HTML', 
			'introduction'=>'', 
		);
	}

}


