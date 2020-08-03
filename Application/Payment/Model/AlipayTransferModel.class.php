<?php
namespace Payment\Model;
use Think\Exception;

class AlipayTransferModel extends AlipayBaseModel{

    //单笔转账到支付宝账户接口
    public function AlipayTransfer($data){
        $config = \AlipayConfig::getConfig();

        $aop = new \AlipayTradeService($config);

        try{
            //构造参数
            $payRequestBuilder = new \AlipayTradeTransferContentBuilder();
            $payRequestBuilder->setOut_biz_no($data["ordersn"]);
			$payRequestBuilder->setPayee_type($data["pay_type"]);
			$payRequestBuilder->setPayee_account($data["account"]);
			$payRequestBuilder->setAmount($data["amount"]);
			$payRequestBuilder->setPayer_show_name($data["show_name"]);
			$payRequestBuilder->setPayee_real_name($data["real_name"]);
			$payRequestBuilder->setRemark($data["remark"]);
            
            /**
             * appPay 转账请求
             * @param $builder 业务参数，使用buildmodel中的对象生成。
             * @return $response 支付宝返回的信息
            */
		   
            $response = $aop->transfer($payRequestBuilder);
			
			return $response;
        }catch(Exception $e){
            $aop->writeLog($e->getMessage());
        }

        return "支付宝转账异常，请稍后尝试";
    }
}
?>