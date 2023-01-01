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
 *
 * @file
 */

/**
 *
 * FinMathAnnuityEscalating calculates present values of 
 * annuities certain, i.e. discrete regular payments (or continuous streams
 * of payments).
 *
 * The payment rate is always 1 per year initially.  The payment rate may
 * escalate at any rate, provided that the escalation points coincide
 * meaningfully with the payment points.
 *
 * An example of a meaningless escalation frequency (which would throw an error)
 * would be if escalations of say 10% per escalation occured every 11 months, 
 * but payments were made every 12 months.
 * If the first payment were 1, how much should the second payment be for?
 * 1.1? Some combination of 1.1 and 1.21 (to reflect the next escalation)?
 */
class FinMathAnnuityEscalating extends FinMathAnnuity{

	protected $escalation_delta;
	protected $escalation_frequency;

	public function get_valid_options(){ 
		$r = parent::get_valid_options();
		$r['escalation_delta'] = $r['delta'];
		$r['escalation_rate_effective'] = $r['i_effective'];
		$r['escalation_frequency'] = $r['m'];
		return $r; 
	}

	public function get_parameters(){ 
		$r = parent::get_parameters();
		$r['escalation_delta'] = array(
			'name'=>'escalation_delta',
			'label'=>self::myMessage( 'fm-label-escalation-delta') ,
		);
		$r['escalation_rate_effective'] = array(
			'name'=>'escalation_rate_effective',
			'label'=>self::myMessage( 'fm-escalation-rate-effective') ,
		);
		$r['escalation_frequency'] = array(
			'name'=>'escalation_frequency',
			'label'=>self::myMessage( 'fm-escalation-frequency') ,
		);
		return $r; 
	}

	public function get_values(){ 
		$r = parent::get_values();
		$r['escalation_delta'] = $this->get_escalation_delta();
		$r['escalation_rate_effective'] = $this->get_escalation_rate_effective();
		$r['escalation_frequency'] = $this->get_escalation_frequency();
		return $r; 
	}

	public function __construct( $m = 1, $advance = false, 
		$delta = 0, $term = 1, 
		$escalation_frequency = 1, $escalation_delta = 0 ){
		parent::__construct( $m, $advance, $delta, $term );
		$this->set_escalation_delta( $escalation_delta );
		$this->set_escalation_frequency( $escalation_frequency );
	}

	protected function get_clone_this(){
		$a_calc = new FinMathAnnuityEscalating( 
			$this->get_m(), $this->get_advance(), 
			$this->get_delta(), $this->get_term(), 
			$this->get_escalation_frequency(), $this->get_escalation_delta() 
		);
		return $a_calc;
	}

	public function set_escalation_delta( $r ){
		$candidate = array( 'escalation_delta'=>$r );
		$valid = $this->get_validation( $candidate );
		if ( $valid['escalation_delta'] ){
			 $this->escalation_delta = $r;
		}
	}

	public function get_escalation_delta(){
		return $this->escalation_delta;
	}

	public function set_escalation_rate_effective( $r ){
		$candidate = array( 'escalation_rate_effective'=>$r );
		$valid = $this->get_validation( $candidate );
		if ( $valid['escalation_rate_effective'] ){ 
			$this->set_escalation_delta( log( 1 + $r) );
		}
	}

	public function get_escalation_rate_effective(){
		return exp( $this->escalation_delta ) - 1;
	}

	public function set_escalation_frequency( $r ){
		if ( NULL == $r ){
			$r=1;
		}
		$candidate = array( 'escalation_frequency'=>$r );
		$valid = $this->get_validation( $candidate );
		if ( $valid['escalation_frequency'] && 
			$this->is_valid_escalation_frequency( $r ) 
		) { 
			if ( $this->is_valid_escalation_frequency( $r ) ){ 
				$this->escalation_frequency = $r;
			}
		} else {
			throw new Exception(
				self::myMessage('fm-exception-escalation-frequency')  . "  " . 
				self::myMessage( 'fm-attempted-frequency')  . $this->get_m() . "." . 
				self::myMessage( 'fm-attempted-escalation-frequency')  . $r . "."
			);
		}
	}

	private function is_valid_escalation_frequency( $f ){
		// valid if continuous or $f/m integer or m/$f integer
		$escalation_format = new FinMathInterestFormat( $f );
		if ( $escalation_format->is_continuous() ){ 
			return true;
		}
		$close_enough = 0.00001;
		$trial = $f / $this->get_m();
		if ( $close_enough > abs( intval( $trial ) - $trial ) ){ 
			return true;
		}
		$trial = $this->get_m() / $f;
		if ( $close_enough > abs( intval( $trial ) - $trial ) ){ 
			return true;
		}
		return false;
	}

	public function get_escalation_frequency(){
		return $this->escalation_frequency;
	}

	public function get_annuity_certain(){
		$escalation_format = new FinMathInterestFormat( 
			$this->get_escalation_frequency() 
		);
		if ( $escalation_format->is_continuous() || 
				$this->get_escalation_frequency() >= $this->get_m() 
		){ 
			return $this->get_annuity_certain_escalation_continual();
		} else {
			return $this->get_annuity_certain_escalation_stepped();
		}
	}

	private function explain_net_interest_rate(){
		$return[0]['left'] = "\\mbox{" . 
			self::myMessage( 'fm-net-interest-rate')  . " } \\delta";
		$return[0]['right'] = "\\log \\left( \\frac{1 + \\mbox{ " . 
			self::myMessage( 'fm-effective-interest-rate')  .  "}}{ 1 + \\mbox{ " . 
			self::myMessage( 'fm-effective-escalation-rate')   . "} } \\right) ";
		$return[1]['right'] = "\\log \\left( \\frac{ " . 
			(1 + $this->get_i_effective()) . "}{ " . 
			(1 + $this->get_escalation_rate_effective()) . " } \\right) ";
		$return[2]['right'] = $this->explain_format( $this->get_delta_net()  ) ;
		return $return;
	}

	private function explain_annuity_certain_escalation_stepped(){
		$a_flat = $this->a_flat();
		$a_inc = $this->a_inc();
		$sub[0]['left'] = "\\mbox{" . self::myMessage( 'fm-annuity-value')  . "}";
		$sub[0]['right'] = 
			$a_flat->label_annuity() . " (i_{" . self::myMessage( 'fm-gross')   . "})" . 
			" \\times  m_{" . self::myMessage( 'fm-esc')   . "} " . 
			" \\times " . $a_inc->label_annuity() . 
			"(\\delta_{" . self::myMessage( 'fm-net')  . "})";
		$sub[1]['right']['summary'] = 
			$this->explain_format( $a_flat->get_annuity_certain() ) . 
			" \\times " . $this->get_escalation_frequency() . 
			" \\times " . $this->explain_format( $a_inc->get_annuity_certain() );
		$sub[2]['right'] = $this->explain_format( $this->get_annuity_certain() );
		$sub[1]['right']['detail'][] = $a_flat->explain_annuity_certain();
		$sub[1]['right']['detail'][] = $a_inc->explain_annuity_certain();
		return array_merge( $sub, $this->explain_net_interest_rate() );
	}

	private function explain_annuity_certain_escalation_continual(){
		$a = new FinMathAnnuity(
			$this->get_m(), $this->get_advance(), 
			$this->get_delta_net(), $this->get_term()
		);
		if ( $a->is_continuous() || $a->get_advance() ){
			return array_merge( $this->explain_net_interest_rate(), 
				$a->explain_annuity_certain() 
			);
		} else {
			// deflate by implied escalation to first payment
			$de[0]['left'] = "\\delta_{\mbox{" . self::myMessage( 'fm-esc')  . "}}";
			$de[0]['right'] = "\\log \\left( 1 + \\mbox{ " . 
				self::myMessage( 'fm-effective-escalation-rate')  . "} \\right)";
			$de[1]['right'] = "\\log \\left( " . 
				(1 + $this->get_escalation_rate_effective() ) . " \\right)";
			$de[2]['right'] = $this->explain_format( $this->get_escalation_delta() );
			$sub[0]['left'] = "\\mbox{" . self::myMessage( 'fm-annuity-value')   . "}";
			$sub[0]['right']['summary'] = 
				"\\exp \\left( - \\delta_{\mbox{" . self::myMessage( 'fm-esc')   .
				"}} / m \\right) \\times " . $this->label_annuity();
			$sub[1]['right']['summary'] = "\\exp \\left( - " . 
				$this->explain_format( $this->get_escalation_delta() ) . 
				" / " . $this->get_m() . " \\right) \\times " . 
				$this->explain_format( $a->get_annuity_certain() );
			$sub[0]['right']['detail'][] = $de;
			$sub[1]['right']['detail'][] = $a->explain_annuity_certain();
			$sub[2]['right'] = $this->explain_format( $this->get_annuity_certain() );
			return array_merge( $sub, $this->explain_net_interest_rate() );
		}
	}

	private function get_annuity_certain_escalation_continual(){
		$a = new FinMathAnnuity( $this->get_m(), $this->get_advance(), 
			$this->get_delta_net(), $this->get_term()
		);
		$raw = $a->get_annuity_certain();
		if ( $a->is_continuous() || $a->get_advance() ){
			return $raw;
		} else {
			// deflate by implied escalation to first payment
			return $raw * exp( -$this->get_escalation_delta() / $this->get_m() );
		}	
	}

	private function a_flat(){
		return  new FinMathAnnuity($this->get_m(), $this->get_advance(), 
			$this->get_delta(), 1.0 / $this->get_escalation_frequency()
		);
	}

	private function a_inc(){
		return  new FinMathAnnuity($this->get_escalation_frequency(), true, 
			$this->get_delta_net(), $this->get_term()
		);
	}

	private function get_annuity_certain_escalation_stepped(){
		$a_flat = $this->a_flat();
		$a_inc = $this->a_inc();
		return $a_inc->get_annuity_certain() * $this->get_escalation_frequency() 
			* $a_flat->get_annuity_certain();
	}

	private function get_delta_net(){
		return $this->get_delta() - $this->get_escalation_delta();
	}

	public function explain_annuity_certain(){
		$escalation_format = new FinMathInterestFormat( $this->get_escalation_frequency() );
		if ( $escalation_format->is_continuous() || 
			$this->get_escalation_frequency() >= $this->get_m() 
		){ 
			return $this->explain_annuity_certain_escalation_continual();
		} else {
			return $this->explain_annuity_certain_escalation_stepped();
		}
	}

	public function set_from_input($_INPUT = array(), $pre = ''){
		try{
			if (parent::set_from_input($_INPUT, $pre)){
				if ( isset( $_INPUT[$pre. 'escalation_delta'] ) ){
					$this->set_escalation_delta(	$_INPUT[$pre. 'escalation_delta'] );
				}
				if ( isset( $_INPUT[$pre. 'escalation_rate_effective'] ) ){
					$this->set_escalation_rate_effective(	
						$_INPUT[$pre. 'escalation_rate_effective'] 
					);
				}
				if ( isset( $_INPUT[$pre. 'escalation_frequency'])){
					$this->set_escalation_frequency(	
						$_INPUT[$pre. 'escalation_frequency'] 
					);
				}
				return true;
			} else {
				return false;
			}
		}
		catch( Exception $e ){ 
			throw new Exception( self::myMessage( 'fm-exception-in')  . 
				__FILE__ . ": " . $e->getMessage() 
			);
		}
	}

	public function get_label(){
		return $this->label_annuity();
	}

	public function get_labels(){
		$labels = parent::get_labels();
		$labels['FinMathAnnuity'] = $this->label_annuity();
		return $labels;
	}

}

