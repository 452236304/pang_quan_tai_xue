<?php
namespace Payment\Model;

use Think\Exception;

require_once "Application/Payment/Weixin/Base/WxPay.Api.php";
require_once "Application/Payment/Weixin/Extend/WxPay.NativePay.php";
require_once "Application/Payment/Weixin/Extend/WxPay.JsApiPay.php";
require_once "Application/Payment/Weixin/Extend/WxPay.CloseWxOrder.php";
require_once "Application/Payment/Weixin/Extend/log.php";
require_once "Application/Payment/Weixin/Extend/phpqrcode.php";

class WxBaseModel{

    //构造函数
    function __construct(){
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/weixin/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }

    //微信关闭订单
    public function WxCloseOrder($data){
        if(empty($data) || count($data) <= 0){
            \Log::INFO("data empty or count eq 0");
            return false;
        }

        $notify = new \CloseWxOrder();

        $input = new \WxPayUnifiedOrder();
        $input->SetOut_trade_no($data["ordersn"]);

        $result = $notify->CloseOrder($input);
        \Log::INFO(json_encode($result));

        if($result["result_code"] == "FAIL"){
            return false;
        }

        return true;
    }
    
}
?>