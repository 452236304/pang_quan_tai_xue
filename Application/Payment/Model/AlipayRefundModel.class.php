<?php
namespace Payment\Model;
use Think\Exception;

class AlipayRefundModel extends AlipayBaseModel{

    //支付宝支付统一订单
    public function AliPayRefund($data){

        if(empty($data)){
            return "参数为空，退款失败";
        }
        /*if(!preg_match("/^[0-9a-zA-Z]{10,64}$/i", $data["out_trade_no"])){
            return "支付宝商户订单号格式不正确，退款失败";
        }
        if(!preg_match("/^[0-9]{0,10}$/i", $data["total_fee"])){
            return "订单金额格式不正确，退款失败";
        }
        if(!preg_match("/^[0-9]{0,10}$/i", $data["refund_fee"])){
            return "退款金额格式不正确，退款失败";
        }*/

        if($data["total_fee"] < $data["refund_fee"]){
            return "退款金额必须小于等于订单金额，退款失败";
        }

        try{
            //构造参数
            $RequestBuilder = new \AlipayTradeRefundContentBuilder();
            $RequestBuilder->setOutTradeNo($data["out_trade_no"]);
            $RequestBuilder->setRefundAmount($data["refund_fee"]);
            $RequestBuilder->setOutRequestNo($data["out_request_no"]);
            $RequestBuilder->setRefundReason($data["refund_reason"]);

            $config = \AlipayConfig::getConfig();

            $aop = new \AlipayTradeService($config);

            //退款参数
            $aop->writeLog(json_encode($data));
            
            /**
             * alipay.trade.refund (统一收单交易退款接口)
             * @param $builder 业务参数，使用buildmodel中的对象生成。
             * @return $response 支付宝返回的信息
             */
            $response = $aop->Refund($RequestBuilder);

            //退款结果
            $aop->writeLog(json_encode($response));

            if($response->code != "10000"){
                return "支付宝退款失败，请检查相关参数";
            }

            return $response;
        } catch(Exception $e) {
            $aop->writeLog($e->getMessage());
        }
        return "支付宝退款失败，请检查相关参数";
    }
    
}
?>