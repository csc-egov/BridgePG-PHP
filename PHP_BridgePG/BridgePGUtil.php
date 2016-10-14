<?php
/**
 * Author: Sandeep Kumar
 * E-Mail: sandeep.kumar.india@gmail.com
 * Date: 07/08/2016
 * Version: 1.36
 */

include_once __DIR__ . "/BridgePG.php";
class BridgePGUtil {
	
	private $bridgePG;
	private $bridge_parameters;
	
	public function __construct(){
		$this->bridgePG = new BridgePG();
		$this->bridge_parameters = self::get_default_parameters();
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
			$c = @$this->bridgePG->decrypt_wallet_message($_POST['bridgeResponseMessage'], $d, FALSE);
			if(!$c)
				return $_POST['bridgeResponseMessage'];
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
	
	//API CALLS
	public function set_mid($mid){
		$this->merchant_id = $mid;
	}
	
	public function get_enquiry($tid){
		if(!isset($this->merchant_id)){
			//
			exit("Merchant ID not set. Please call set_mid first.");
		}
		$data = array(
			'merchant_txn' => $tid
		);
		$result = $this->_call_bridge_api('transaction/enquiry', $data);
		
		echo json_encode($result);
		return 1;
	}
	
	public function get_status($tid, $csc_txn){
		if(!isset($this->merchant_id)){
			//
			exit("Merchant ID not set. Please call set_mid first.");
		}
		$data = array(
			'merchant_txn' => $tid,
			'csc_txn' => $csc_txn
		);
		$result = $this->_call_bridge_api('transaction/status', $data);
		
		echo json_encode($result);
		return 1;
	}
	
	public function refund_log(
		$tid,
		$csc_txn,
		$product_id,
		$merchant_txn_status,
		$merchant_reference,
		$refund_deduction,
		$refund_mode,
		$refund_type,
		$refund_trigger,
		$refund_reason
	){
		if(!isset($this->merchant_id)){
			//
			exit("Merchant ID not set. Please call set_mid first.");
		}
		$data = array(
			'merchant_txn'        => $tid,
			'csc_txn'             => $csc_txn,
			'product_id'          => $product_id,
			'merchant_txn_status' => $merchant_txn_status,
			'merchant_reference'  => $merchant_reference,
			'refund_deduction'    => $refund_deduction,
			'refund_mode'         => $refund_mode,
			'refund_type'         => $refund_type,
			'refund_trigger'      => $refund_trigger,
			'refund_reason'       => $refund_reason
		);
		$result = $this->_call_bridge_api('refund/log', $data);
		
		echo json_encode($result);
		return 1;
	}
	
	
	public function refund_status(
		$tid,
		$csc_txn,
		$refund_reference
	){
		if(!isset($this->merchant_id)){
			//
			exit("Merchant ID not set. Please call set_mid first.");
		}
		$data = array(
			'merchant_txn'        => $tid,
			'csc_txn'             => $csc_txn,
			'refund_reference'    => $refund_reference
		);
		$result = $this->_call_bridge_api('refund/status', $data);
		
		echo json_encode($result);
		return 1;
	}
	
	public function recon_log(
		$tid,
		$csc_txn,
		$cscuser_id,
		$product_id,
		$txn_amount,
		$merchant_date,
		$merchant_txn_status,
		$merchant_reciept
	){
		if(!isset($this->merchant_id)){
			//
			exit("Merchant ID not set. Please call set_mid first.");
		}
		$data = array(
			'merchant_txn'        => $tid,
			'csc_txn'             => $csc_txn,
			'refund_reference'    => $refund_reference,
			'cscuser_id'          => $cscuser_id,
			'product_id'          => $product_id,
			'txn_amount'          => $txn_amount,
			'merchant_date'       => $merchant_date,
			'merchant_txn_status' => $merchant_txn_status,
			'merchant_reciept'    => $merchant_reciept
		);
		$result = $this->_call_bridge_api('recon/log', $data);
		
		echo json_encode($result);
		return 1;
	}
	
	private function _call_bridge_api($method, $data){
		$data['merchant_id'] = $this->merchant_id;
		$message_text = '';
		foreach($data as $p => $v ){
			$message_text .= $p . '=' . $v . '|';
		}
		$message_cipher = $this->bridgePG->encrypt_message_for_wallet($message_text, FALSE);
		$json_data_array = array(
			'merchant_id' => $this->merchant_id,
			'request_data' => $message_cipher
		);
		$json_data = json_encode($json_data_array);
		$url = 'http://bridgeapi.csccloud.in/v1/' . trim($method, '/') . '/format/json';
		//$url = 'http://bridgeapi.csccloud.in/v1/' . trim($method, '/') . '/format/serialized';
		
		//echo print_r($this->_do_curl_req($url, $json_data, false));
		return  $this->_do_curl_req($url, $json_data, false);
	}

	private function _do_curl_req($url, $post, $headers=false){
		if(!$headers)
			$headers = array('Content-Type: application/json');

		if(isset($this->junk))
			$url = $this->junk;
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_VERBOSE => true,
			CURLOPT_HEADER => false,
			CURLOPT_HTTPHEADER => $headers,
			CURLINFO_HEADER_OUT => false,
//			CURLOPT_SSL_VERIFYHOST => 0,
//			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)',
//			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $post
			
		));
		$result = curl_exec($curl);
		if(!$result){
			$httpcode = curl_getinfo($curl);
			print_r(array('Error code' => $httpcode, 'URL' => $url, 'post' => $post, 'LOG' => ""));
			exit("Error: 378972");
		}
		curl_close($curl);
		
		//return $result;
		//echo $result . "\n\n";
		return $this->_parse_api_resp_to_array($result);
		//return $this->_parse_serialized_api_resp_to_array($result);
	}

	private function _parse_api_resp_to_array($serv_resp){
		if(!$serv_resp)
			return null;
		$vals = (array)json_decode($serv_resp);
		$ret = array();
		if(TRUE || count($vals) > 0){
			foreach($vals as $k => $v){
				if($k){
					if($k == "response_data"){
						if(trim($v)){
							$_POST['bridgeResponseMessage'] = $v;
							$v = $this->get_bridge_message();
						}
					}
					$ret[trim($k)] = trim($v);
				}
			}
		}
		return $ret;		
	}//End of private function _parse_api_resp_to_array

	private function _parse_serialized_api_resp_to_array($serv_resp){
		if(!$serv_resp)
			return null;
		$vals = (array) unserialize($serv_resp);
		$ret = array();
		if(TRUE || count($vals) > 0){
			foreach($vals as $k => $v){
				if($k){
					if($k == "response_data"){
						if(trim($v)){
							$_POST['bridgeResponseMessage'] = $v;
							$v = $this->get_bridge_message();
						}
					}
					$ret[trim($k)] = trim($v);
				}
			}
		}
		return $ret;		
	}//End of private function _parse_api_resp_to_array
} //End of class BridgePGUtil

//No PHP Closing tag at end of file.
