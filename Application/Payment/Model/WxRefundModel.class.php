<?php
namespace Payment\Model;

use Think\Exception;

class WxRefundModel extends WxBaseModel{

    //微信退款
    public function WxPayRefund($data){
        if(empty($data)){
            return "参数为空，退款失败";
        }
        /*if(!preg_match("/^[0-9a-zA-Z]{10,64}$/i", $data["out_trade_no"])){
            return "微信商户订单号格式不正确，退款失败";
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
            $out_trade_no = $data["out_trade_no"];
            $total_fee = floatval($data["total_fee"])*100;; //floatval($data["total_fee"])*100;
            $refund_fee = floatval($data["refund_fee"])*100;
            $input = new \WxPayRefund();
            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);

            //退款参数
            \Log::INFO(json_encode($data));
    
            $config = new \WxPayConfig();
            $config->hybrid = $data["hybrid"];
            $input->SetOut_refund_no("refund".date("YmdHis"));
            $input->SetOp_user_id($config->GetMerchantId());
            $result = \WxPayApi::refund($config, $input);

            //退款结果
            \Log::INFO(json_encode($result));

            if($result["return_code"] == "FAIL" || $result["result_code"] == "FAIL"){
                return "微信退款失败，请检查相关参数";
            }

            return $result;
        } catch(Exception $e) {
            \Log::ERROR($e->getMessage());
        }
        return "微信退款失败，请检查相关参数";
    }
}
?>