<?php
namespace Payment\Model;

use Think\Exception;

class WxJsApiModel extends WxBaseModel{

    //微信支付统一订单
    public function WxPayUnifiedOrder($data){
        if(empty($data) || count($data) <= 0 || !in_array($data["hybrid"], ["mobile", "h5", "xcx", "app"])){
            \Log::INFO("data empty or count eq 0");
            return array("result_code"=>"FAIL", "return_msg"=>"参数不能为空");
        }
    
        \Log::INFO("data info：".json_encode($data));
    
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($data["title"]);
        $input->SetAttach(json_encode($data["attach"]));
        $input->SetOut_trade_no($data["ordersn"]);
        $input->SetTotal_fee(floatval($data["amount"]) * 100); //
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url("http://".$_SERVER['HTTP_HOST']."/payment.php/Weixin/notify");
        if ($data["hybrid"] == "h5") {
            $input->SetTrade_type("MWEB");
        }else if($data["hybrid"] == "mobile" || $data["hybrid"] == "xcx"){
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($data["openid"]);
        } else if($data["hybrid"] == "app"){
            $input->SetTrade_type("APP");
        }
    
        $config = new \WxPayConfig();
        $config->hybrid = $data["hybrid"];
        $order = \WxPayApi::unifiedOrder($config, $input);
    
        \Log::INFO("order info：".json_encode($order));
        
        if($order["result_code"] == "FAIL" || $order["return_code"] == "FAIL"){
            return array(
                "err_code"=>$order["err_code"], "err_code_des"=>$order["err_code_des"],
                "result_code"=>$order["result_code"], "return_code"=>$order["return_code"], "return_msg"=>$order["return_msg"]
            );
        }
    
        if ($data["hybrid"] == "h5") {
            return array(
                "mweb_url"=>$order["mweb_url"], "prepay_id"=>$order["prepay_id"]
            );
        }
    
        $tools = new \JsApiPay();
    
        if($data["hybrid"] == "mobile" || $data["hybrid"] == "xcx"){
            $jsApiParameters = $tools->GetJsApiParameters($order, $data["hybrid"]);
        } else if($data["hybrid"] == "app"){
            $jsApiParameters = $tools->GetAppParameters($order);
        }
    
        \Log::INFO(json_encode($jsApiParameters));
    	
        return $jsApiParameters;
    }
	
	
	//微信提现
	public function WxPayWithdraw($data){
	    if(empty($data) || count($data) <= 0 ){
	        \Log::INFO("data empty or count eq 0");
	        return array("result_code"=>"FAIL", "return_msg"=>"参数不能为空");
	    }
	
	    \Log::INFO("data info：".json_encode($data));
	
	    $input = new \WxPayWithdraw();
	    $input->SetPartner_trade_no($data["ordersn"]);
		$input->SetOpenid($data["openid"]);
		$input->SetCheck_name('NO_CHECK');
		$input->SetDesc($data['desc']);
	    $input->SetAmount(1); //floatval($data["amount"]) * 100
	
	    $config = new \WxPayConfig();
	    $config->hybrid = 'app';
	    $order = \WxPayApi::withdraw($config, $input);
	
	    \Log::INFO("Withdraw info：".json_encode($order));
	    
	    if($order["result_code"] == "FAIL" || $order["return_code"] == "FAIL"){
	        return array(
	            "err_code"=>$order["err_code"], "err_code_des"=>$order["err_code_des"],
	            "result_code"=>$order["result_code"], "return_code"=>$order["return_code"], "return_msg"=>$order["return_msg"]
	        );
	    }
		
	    return $order;
	}
}
?>