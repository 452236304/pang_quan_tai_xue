<?php
namespace Payment\Model;
use Think\Exception;

require_once "Application/Payment/Alipay/config.php";
require_once "Application/Payment/Alipay/service/AlipayTradeService.php";
require_once "Application/Payment/Alipay/buildermodel/AlipayTradePagePayContentBuilder.php";
require_once "Application/Payment/Alipay/buildermodel/AlipayTradeWapPayContentBuilder.php";
require_once "Application/Payment/Alipay/buildermodel/AlipayTradeAppPayContentBuilder.php";
require_once "Application/Payment/Alipay/buildermodel/AlipayTradeRefundContentBuilder.php";
require_once "Application/Payment/Alipay/buildermodel/AlipayTradeCloseContentBuilder.php";
require_once "Application/Payment/Alipay/buildermodel/AlipayTradeTransferContentBuilder.php";

class AlipayBaseModel{

    
//    //支付宝关闭订单
    public function AlipayCloseOrder($data){

        $config = \AlipayConfig::getConfig();

        $aop = new \AlipayTradeService($config);

        try{
            //构造参数
            $RequestBuilder = new \AlipayTradeCloseContentBuilder();
            $RequestBuilder->setOutTradeNo($data["ordersn"]);

            /**
             * alipay.trade.close (统一收单交易关闭接口)
             * @param $builder 业务参数，使用buildmodel中的对象生成。
             * @return $response 支付宝返回的信息
             */
            $response = $aop->Close($RequestBuilder);

            if($response->code != "10000"){
                return false;
            }

            return true;
        }catch(Exception $e){
            $aop->writeLog($e->getMessage());
        }
        return false;
    }

}
?>