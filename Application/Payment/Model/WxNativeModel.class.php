<?php
namespace Payment\Model;

use Think\Exception;

class WxNativeModel extends WxBaseModel{

    //微信支付统一订单
    public function WxPayUnifiedOrder($data){
        if(empty($data) || count($data) <= 0){
            \Log::INFO("data empty or count eq 0");
            return false;
        }

        $notify = new \NativePay();

        $input = new \WxPayUnifiedOrder();
        $input->SetBody($data["title"]);
        $input->SetAttach(json_encode($data["attach"]));
        $input->SetOut_trade_no($data["ordersn"]);
        $input->SetTotal_fee(floatval($data["amount"]) * 100); //floatval($data["amount"]) * 100
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url("http://".$_SERVER['HTTP_HOST']."/payment.php/Weixin/notify");
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($data["ordersn"].'_'.$data["id"]);

        $result = $notify->GetPayUrl($input);
        \Log::INFO(json_encode($result));

        if($result["result_code"] == "FAIL"){
            return false;
        }

        $url = $result["code_url"];
        if($url !== false){

            $level = 'L';// 纠错级别：L、M、Q、H
            $size = 5;// 点的大小：1到10,用于手机端4就可以了
            $QRcode = new \QRcode();
            ob_start();
            $QRcode->png($url,false,$level,$size,2);
            $imageString = base64_encode(ob_get_contents());
            ob_end_clean();

            $url = "data:image/jpg;base64,".$imageString;
        }

        return $url;
    }
}
?>