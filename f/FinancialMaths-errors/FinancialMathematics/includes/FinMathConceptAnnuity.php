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

class FinMathConceptAnnuity extends FinMathForm{

public function __construct(FinMathObject $obj=null){
	if (null === $obj){
		$obj = new FinMathAnnuityEscalating();
	}
	parent::__construct($obj);
	$this->set_request( 'get_annuity_escalating' );
}

public function get_concept_label(){
	return array(
				'concept_annuity'=>self::myMessage(  'fm-annuity'), 
 );
} 

private function get_unrendered_solution(){
	return $this->obj->explain_annuity_certain();
} 

private function get_unrendered_interest_rate(){
	return $this->obj->explain_interest_rate_for_value();
}

	private function get_unrendered_summary( $_INPUT ){
		$ret=array();
			if (empty( $_INPUT['value'] )  ){
				$ret['sought']='value';
				$ret['result']=$this->obj->get_value();
		} else {
				$ret['sought']='i_effective';
				$ret['result']=exp($this->obj->get_delta_for_value()) - 1;
		}
		return $ret;
	}


	
protected function get_calculator($parameters){
	$p = array('exclude'=>$parameters,'request'=> $this->get_request(), 'submit'=>self::myMessage( 'fm-calculate'), 'introduction' => self::myMessage( 'fm-intro-annuity-certain') );
	$c = parent::get_calculator($p);
	$c['values']['value'] = NULL;
	return $c;
}

public function get_controller($_INPUT ){
  $return=array();
	if (isset($_INPUT['request'])){
		if ($this->get_request() == $_INPUT['request']){
			if ($this->set_annuity($_INPUT)){
				if (empty( $_INPUT['value'] ) ){
				  $return['output']['unrendered']['formulae'] = $this->get_unrendered_solution();
				  $return['output']['unrendered']['summary'] = $this->get_unrendered_summary($_INPUT);
					return $return;
				} else {
				  $return['output']['unrendered']['formulae'] = $this->get_unrendered_interest_rate();
				  $return['output']['unrendered']['summary'] = $this->get_unrendered_summary($_INPUT);
					return $return;
				}
			} else {
				$return['warning']=self::myMessage( 'fm-exception-setting-annuity');
				return $return;
			}
		}
	}
	else{
		$return['output']['unrendered']['forms'][] = array(
			'content'=>  $this->get_calculator(array(
					"delta", "escalation_delta","source_m","source_advance","source_rate"
					)),
			'type'=>  ''
		);
    return $return;
	}
  return $return;
}

private function set_annuity($_INPUT = array()){
	$this->set_received_input($_INPUT);
	$lobj = new FinMathAnnuity();
	if ($this->is_level_annuity($_INPUT)){
		$this->set_obj($lobj);
	}
	$this->obj->set_from_input($_INPUT);
	return ($this->obj->set_from_input($_INPUT));
}

private function is_level_annuity($_INPUT){
	$consider_increasing=0;
	$increasing=0;
  $escalation_rate_effective=0;
  if (isset($_INPUT['escalation_rate_effective'])){
  	$escalation_rate_effective=$_INPUT['escalation_rate_effective'];
	}
  if (isset($_INPUT['consider_increasing'])){
  	$consider_increasing=$_INPUT['consider_increasing'];
	}
  if (isset($_INPUT['increasing'])){
  	$increasing=$_INPUT['increasing'];
	}
	if (empty($escalation_rate_effective) && empty($increasing) && empty($consider_increasing) ){
				return true;
	}
	return false;
}

} // end of class


