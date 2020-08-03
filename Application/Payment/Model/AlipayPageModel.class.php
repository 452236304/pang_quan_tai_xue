<?php
namespace Payment\Model;
use Think\Exception;

class AlipayPageModel extends AlipayBaseModel{

    //支付宝支付统一订单
    public function AlipayUnifiedOrder($data){

        $config = \AlipayConfig::getConfig();

        $aop = new \AlipayTradeService($config);

        try{
            //构造参数
            $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
            $payRequestBuilder->setBody($data["body"]);
            $payRequestBuilder->setSubject($data["subject"]);
            $payRequestBuilder->setTotalAmount($data["amount"]); //$data["amount"]
            $payRequestBuilder->setOutTradeNo($data["out_trade_no"]);
            $payRequestBuilder->setTimeExpress("10m");
            
            /**
             * pagePay 电脑网站支付请求
             * @param $builder 业务参数，使用buildmodel中的对象生成。
             * @param $return_url 同步跳转地址，公网可以访问
             * @param $notify_url 异步通知地址，公网可以访问
             * @return $response 支付宝返回的信息
            */
            $response = $aop->pagePay($payRequestBuilder, $config["return_url"], $config["notify_url"]);

            return $response;
        }catch(Exception $e){
            $aop->writeLog($e->getMessage());
        }

        return "支付宝支付异常，请稍后尝试";
    }
}
?>