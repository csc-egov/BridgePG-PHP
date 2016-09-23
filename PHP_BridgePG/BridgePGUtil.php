<?php
/**
 * Author: Sandeep Kumar
 * Date: 07/08/2016
 * Version: 1.36
 */

include_once __DIR__ . "/BridgePG.php";
class BridgePGUtil {
	
	private $bridgePG;
	private $bridge_parameters;
	
	public function __construct(){
		$this->bridgePG = new BridgePG();
		$this->bridge_parameters = Bridge_connector::get_default_parameters();
	}
	
	private static function get_default_parameters(){
		$brj_params = array(
			'merchant_id'            => '11121',
			'merchant_txn'           => 'P121' . time() . rand(10,99),//'2016-06-21 18:11:58',
			'merchant_txn_date_time' => date('Y-m-d H:i:s'),//'2016-06-21 18:11:58',
			'product_id'             => '1112101',
			'product_name'           => 'my product',
			'txn_amount'             => '100',
			'amount_parameter'       => 'NA',
			'txn_mode'               => 'D',
			'txn_type'               => 'D',
			'merchant_receipt_no'    => '2016-06-21 18:11:58',
			'csc_share_amount'       => '0',
			'pay_to_email'           => 'a@abc.com',
			'return_url'             => '',
			'cancel_url'             => '',
			'Currency'               => 'INR',
			'Discount'               => '0',
			'param_1'                => 'NA',
			'param_2'                => 'NA',
			'param_3'                => 'NA',
			'param_4'                => 'NA'
		);
		return $brj_params;
	}
	
	public function set_params($params){
		foreach($params as $p => $v){
			$this->bridge_parameters[$p] = $v;
		}
	}

	public function get_parameter_string(){
		$message_text = '';
		foreach($this->bridge_parameters as $p => $v ){
			$message_text .= $p . '=' . $v . '|';
		}
		$message_cipher = $this->bridgePG->encrypt_message_for_wallet($message_text, FALSE);
		return $this->bridge_parameters['merchant_id'] . '|' . $message_cipher;
	}
	
	public function get_bridge_message(){
		$d = "Invalid Bridge message";
		if($_POST['bridgeResponseMessage']){
			$c = $this->bridgePG->decrypt_wallet_message($_POST['bridgeResponseMessage'], $d, FALSE);
		}
		return $d;
	}
	
	public function get_fraction($ddhhmm = ""){
		$time_format = "ymdHis";
		$algo_num    = "883";
		if(!$ddhhmm)
			$ddhhmm = date($time_format, time());
		$frac = $this->large_op1($ddhhmm, $algo_num );
		$frac = $this->large_op2($frac, "" . (1000 - $algo_num) );
		return $frac;
	}
	
	public function large_op1($n0, $x0){
		$n = '' . $n0;
		$x = '' . $x0;
		$sz = strlen('' . $n);
		$vals = array();
		$tens = 0;
		for($i = 0; $i < $sz; $i++ ){
			$d = $n[$sz - $i - 1];
			$res = $d * $x + $tens;
			$ones = $res % 10;
			$tens = (int)($res / 10);
			array_unshift($vals, $ones);
		}
		if($tens > 0)
			array_unshift($vals, $tens);
		return implode("", $vals);
	}
	
	public function large_op2($n0, $x0){
		$n = '' . $n0;
		$x = '' . $x0;
		$sz = strlen('' . $n);
		$vals = array();
		$tens = 0;
		for($i = 0; $i < $sz; $i++ ){
			$d = $n[$sz - $i - 1];
			if($i == 0)
				$res = $d + $x;
			else
				$res = $d + $tens;
			$ones = $res % 10;
			$tens = (int)($res / 10);
			array_unshift($vals, $ones);
		}
		if($tens > 0)
			array_unshift($vals, $tens);
		return implode("", $vals);
	}
}

//No PHP Closing tag at end of file.
