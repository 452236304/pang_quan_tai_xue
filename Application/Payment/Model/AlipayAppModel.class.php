<?php
namespace Payment\Model;
use Think\Exception;

class AlipayAppModel extends AlipayBaseModel{

    //支付宝支付统一订单
    public function AlipayUnifiedOrder($data){
        $config = \AlipayConfig::getConfig();

        $aop = new \AlipayTradeService($config);

        try{
            //构造参数
            $payRequestBuilder = new \AlipayTradeAppPayContentBuilder();
            $payRequestBuilder->setBody($data["body"]);
            $payRequestBuilder->setSubject($data["subject"]);
            $payRequestBuilder->setOutTradeNo($data["out_trade_no"]);
            $payRequestBuilder->setTotalAmount($data["amount"]); //$data["amount"]
            $payRequestBuilder->setTimeExpress("10m");
            
            /**
             * appPay APP支付请求
             * @param $builder 业务参数，使用buildmodel中的对象生成。
             * @param $notify_url 异步通知地址，公网可以访问
             * @return $response 支付宝返回的信息
            */
		   
            $response = $aop->appPay($payRequestBuilder, $config["notify_url"]);
			return $response;
        }catch(Exception $e){
            $aop->writeLog($e->getMessage());
        }

        return "支付宝支付异常，请稍后尝试";
    }
}
?>