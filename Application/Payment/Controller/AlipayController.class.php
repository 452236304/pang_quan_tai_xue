<?php
namespace Payment\Controller;
use Think\Controller;

require_once "Application/Payment/Alipay/service/AlipayTradeService.php";

class AlipayController extends Controller {

    //构造函数
    function __construct(){
		
    }

	//支付宝支付测试
	private function papepay(){
		$data = array(
			"subject"=>"支付宝支付测试", "body"=>"测试支付宝支付", "total_amount"=>"0.01", "out_trade_no"=>"type_paylogsn_".date("YmdHis")
		);

		$alimodel = D("Payment/AlipayNative");
		$result = $alimodel->AlipayUnifiedOrder($data);

		echo $result;
    }

    //支付宝退款
    private function refund(){
        $data = array(
			"refund_reason"=>"支付宝支付测试", "out_request_no"=>"", "refund_amount"=>"0.01", "out_trade_no"=>"type_paylogsn_20181214204054"
		);

		$alimodel = D("Payment/AlipayRefund");
		$result = $alimodel->AliPayRefund($data);

		print(json_encode($result));
    }
    
    //同步通知 - 关闭当前支付宝支付页面
    public function keep_notify(){
        echo "<script>window.close();</script>";
    }
    
    //支付回调处理
	public function notify(){

        require_once "Application/Payment/Alipay/config.php";

        $request_data = $_POST;
        $config = \AlipayConfig::getConfig();
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($request_data);

        
        $alipaySevice->writeLog(json_encode(array("result"=>$result)));

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代

            
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            
            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];

            if($trade_status == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序
                        
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($trade_status == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序			
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                //支付成功业务逻辑处理
                $model = D("CApi/OrderCallbackHandle");
                $arr = explode("_", $out_trade_no);
                $attach = array("type"=>$arr[0], "logsn"=>$arr[1]);
                $model->OrderHandle($attach);

            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success";	//请不要修改或删除
        }else {
            //验证失败
            echo "fail";
        }
        
	}

}
?>