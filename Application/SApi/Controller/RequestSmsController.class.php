<?php
namespace SApi\Controller;
use Think\Controller;
class RequestSmsController extends BaseController {
	
	//发送验证码 mobile,action（login:登录 reset:重置密码，forget:忘记密码）
	public function sendsms(){
        $data = I("post.");

        $action = $data["action"];
		if(empty($action)){
			E("请选择发送验证码的类型", 1003);
		}
		
		$mobile = $data["mobile"];
		if(empty($mobile)){
            E("请输入手机号码", 1001);
        }
        if(!isMobile($mobile)){
            E("手机号码格式不正确", 1002);
        }
        
        $map = array("account"=>$mobile, "mobile"=>$mobile, "_logic"=>"or");
        $checkuser = D("user")->where($map)->find();
		if($action == "login" || $action == "reset" || $action == "forget" || $action == "bind"){
			if(!$checkuser){
				E("手机号码还未注册", 1004);
			}
		}
		
		$code = rand(111111, 999999);
		//$code=123456;
		$sms = D("Common/RequestSms");
		$result = $sms->SendSms($mobile, $code, $action);
		if(is_string($result)){
			E($result);
		}

		$smscode = array("mobile"=>$mobile, "action"=>$action, "code"=>$code, "time"=>time());
		S("RequestSms-".$mobile, json_encode($smscode));
		
		unset($smscode["code"]);		
		return $smscode;
	}
}