<?php
namespace Payment\Controller;
use Think\Controller;

require_once "Application/Payment/Weixin/Base/WxPay.Api.php";
require_once 'Application/Payment/Weixin/Base/WxPay.Notify.php';
require_once "Application/Payment/Weixin/Extend/WxPay.Config.php";
require_once 'Application/Payment/Weixin/Extend/log.php';

class WeixinController extends Controller {

    //构造函数
    function __construct(){
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/weixin/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
	}
	
	//微信登录
	public function login(){

		return true;
	}
    
    //支付回调处理
	public function notify(){
        $config = new \WxPayConfig();
        \Log::DEBUG("begin notify");
        $notify = new PayNotifyCallBack();
        $notify->Handle($config, false);
	}

}

//微信支付回调
class PayNotifyCallBack extends \WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id, $hybrid = "mobile")
	{
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);

		$config = new \WxPayConfig();
		$config->hybrid = $hybrid;
		$result = \WxPayApi::orderQuery($config, $input);
		\Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}

	/**
	*
	* 回包前的回调方法
	* 业务可以继承该方法，打印日志方便定位
	* @param string $xmlData 返回的xml参数
	*
	**/
	public function LogAfterProcess($xmlData)
	{
		\Log::DEBUG("call back， return xml:" . $xmlData);
		return;
	}
	
	//重写回调处理函数
	/**
	 * @param WxPayNotifyResults $data 回调解释出的参数
	 * @param WxPayConfigInterface $config
	 * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
	 * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
	 */
	public function NotifyProcess($objData, $config, &$msg)
	{
		$data = $objData->GetValues();
		\Log::DEBUG("test:".json_encode($data));
		//TODO 1、进行参数校验
		if(!array_key_exists("return_code", $data) 
			||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
			//TODO失败,不是支付成功的通知
			//如果有需要可以做失败时候的一些清理处理，并且做一些监控
			$msg = "异常异常";\Log::DEBUG("异常异常:");
			return false;
		}
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";\Log::DEBUG("输入参数不正确:");
			return false;
		}

		//TODO 2、进行签名验证
		try {
			$checkResult = $objData->CheckSign($config);
			if($checkResult == false){
				//签名错误
				\Log::ERROR("签名错误...");
				return false;
			}
		} catch(Exception $e) {
			\Log::ERROR($e->getMessage());
		}
		
		//订单附加信息
		$attach = json_decode($data["attach"], true);
		
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"], $attach["hybrid"])){
			$msg = "订单查询失败";\Log::DEBUG("订单查询失败:");
			return false;
		}

		//TODO 3、处理业务逻辑
		\Log::DEBUG("call back:" . json_encode($data));
		
		//支付成功业务逻辑处理
		$model = D("CApi/OrderCallbackHandle");
		$model->OrderHandle($attach);

		return true;
	}
}

?>