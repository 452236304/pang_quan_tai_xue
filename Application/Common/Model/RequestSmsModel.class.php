<?php
namespace Common\Model;

class RequestSmsModel{
    private $sms;
    function __construct(){
        Vendor("dysms.api_demo.Sms");

        $this->sms = new \Sms(
            "LTAIYkYdPZY1LhFd",
            "ZGVYFRbpiI1T2BsmE1ratqpuClX9Kb"
        );
    }

    //消息通知 - 验证码 - action：login=登录,register=注册,reset=更换密码,update=信息变更
    public function SendSms($mobile, $code, $action){
        //手机号码发送频率验证
        $result = $this->SendTimeCheck($mobile);
        if($result){
            return $result;
        }

        switch($action){
            case "login":
            case "loginbing":
				//登录 绑定
                return $this->SendCodeSms($mobile, $code, "SMS_160220064");
            case "register":
				//注册
                return $this->SendCodeSms($mobile, $code, "SMS_160220062");
            case "reset":
            case "forget":
				//修改密码
                return $this->SendCodeSms($mobile, $code, "SMS_160220061");
            case "update":
				//信息变更
                return $this->SendCodeSms($mobile, $code, "SMS_160220060");
        }
        
        return "请选择发送验证码的方式";
    }

    //消息通知 - 发送验证码
    private function SendCodeSms($mobile, $code, $template){
        $time = date("Y-m-d H:i:s", time());

        $response = $this->sms->sendSms(
            "一点椿", // 短信签名
            $template, // 短信模板编号
            $mobile, // 短信接收者
            Array(  // 短信模板中字段的值
                "code"=>$code,
            )
        );
        $response = json_encode($response);
        $request = json_decode($response, true);

        //短信日志记录
        $entity = array(
            "action"=>$action, "mobile"=>$mobile, "content"=>$code, "status"=>1,
            "createdate"=>$time, "resulttext"=>json_encode($response)
        );
        
        if(strtolower($request["Code"]) != "ok"){
            $entity["status"] = 0;
        }

        D("sms_log")->add($entity);

        if($entity["status"] == 0){
            return "短信发送失败，请稍候尝试";
        }
        
        return $request;
    }

    //消息通知 - 购买机构订单
    public function SendOrgSms($mobile, $title, $sn){

    }

    //消息通知 - 购买商品/定制/改造订单
    public function SendProductSms($mobile, $title, $sn){
        $title = mb_substr($title, 0, 20, 'utf-8');
        $sn = substr($sn, 0, 20);
        $response = $this->sms->sendSms(
            "一点椿", // 短信签名
            'SMS_174987168', // 短信模板编号 SMS_174987168
            $mobile, // 短信接收者
            Array(  // 短信模板中字段的值
                "title"=>$title,
                "sn"=>$sn,
            )
        );
        $response = json_encode($response);
        $request = json_decode($response, true);

        if(strtolower($request["Code"]) != "ok"){
            return "短信发送失败，请稍候尝试";
        }

        return $request;
    }

    //消息通知 - 商品发货
    public function SendProductShippingSms($mobile, $title, $sn){

    }

    //消息通知 - 购买服务订单
    public function SendServiceSms($mobile, $title, $sn, $begintime){
        $title = mb_substr($title, 0, 20, 'utf-8');
        $sn = substr($sn, 0, 20);
        $response = $this->sms->sendSms(
            "一点椿", // 短信签名
            'SMS_174992098', // 短信模板编号 SMS_174992098
            $mobile, // 短信接收者
            Array(  // 短信模板中字段的值
                "title"=>$title,
                "sn"=>$sn,
                "time"=>$begintime
            )
        );
        $response = json_encode($response);
        $request = json_decode($response, true);

        if(strtolower($request["Code"]) != "ok"){
            return "短信发送失败，请稍候尝试";
        }

        return $request;
    }

    //消息通知 - 服务订单抢单成功
    public function SendServiceBattleSms($info){
        if (empty($info['mobile'])) {
            return "缺少手机号吗";
        }
        $title = mb_substr($info['title'], 0, 20, 'utf-8');
		$address = mb_substr($info['address'], 0, 20, 'utf-8');
        $response = $this->sms->sendSms(
            "一点椿", // 短信签名
            'SMS_174988448', // 短信模板编号 SMS_174988448
            $info['mobile'], // 短信接收者
            Array(  // 短信模板中字段的值
                "name"=>$info['name'],
                "title"=>$title,
                "Adress"=>$address,
                "time"=>$info['time']
            )
        );
        $response = json_encode($response);
        $request = json_decode($response, true);
		
        if(strtolower($request["Code"]) != "ok"){
            return "短信发送失败，请稍候尝试";
        }
        return $request;
    }
	
	//在服务时间开始前的一个小时	SMS_174988447
	public function SendLastHour($info){
		if (empty($info['mobile'])) {
		    return "缺少手机号吗";
		}
		$response = $this->sms->sendSms(
		    "一点椿", // 短信签名
		    'SMS_174988447', // 短信模板编号 SMS_174988447
		    $info['mobile'], // 短信接收者
		    Array(  // 短信模板中字段的值
		        "title"=>$info['title']
		    )
		);
		$response = json_encode($response);
		$request = json_decode($response, true);
		
		if(strtolower($request["Code"]) != "ok"){
			return $request;
		    return "短信发送失败，请稍候尝试";
		}
		return $request;
	}
	//提醒服务人员有订单可抢		SMS_174993316
	public function SendRemindOrder($info){
		if (empty($info['mobile'])) {
		    return "缺少手机号吗";
		}
		$response = $this->sms->sendSms(
		    "一点椿", // 短信签名
		    'SMS_174993316', // 短信模板编号 SMS_174993316
		    $info['mobile'], // 短信接收者
		    Array(  // 短信模板中字段的值
		        
		    )
		);
		$response = json_encode($response);
		$request = json_decode($response, true);
		
		if(strtolower($request["Code"]) != "ok"){
		    return "短信发送失败，请稍候尝试";
		}
		return $request;
	}
	
	//成功续费					SMS_174993314
	public function SendRenew($info){
		if (empty($info['mobile'])) {
		    return "缺少手机号吗";
		}
		$title = mb_substr($info['title'], 0, 20, 'utf-8');
		$response = $this->sms->sendSms(
		    "一点椿", // 短信签名
		    'SMS_174993314', // 短信模板编号 SMS_174993314
		    $info['mobile'], // 短信接收者
		    Array(  // 短信模板中字段的值
		        "title"=>$info['title']
		    )
		);
		$response = json_encode($response);
		$request = json_decode($response, true);
		
		if(strtolower($request["Code"]) != "ok"){
		    return "短信发送失败，请稍候尝试";
		}
		return $request;
	}
	//成功续单					SMS_174988449
	public function SendRenewal($info){
		if (empty($info['mobile'])) {
		    return "缺少手机号吗";
		}
		$name = mb_substr($info['name'], 0, 20, 'utf-8');
		$response = $this->sms->sendSms(
		    "一点椿", // 短信签名
		    'SMS_174988449', // 短信模板编号 SMS_174988449
		    $info['mobile'], // 短信接收者
		    Array(  // 短信模板中字段的值
		        "name"=>$name
		    )
		);
		$response = json_encode($response);
		$request = json_decode($response, true);
		
		if(strtolower($request["Code"]) != "ok"){
		    return "短信发送失败，请稍候尝试";
		}
		return $request;
	}
	
	//成功退款推送				SMS_174988441
	public function SendRefund($info){
	    if (empty($info['mobile'])) {
	        return "缺少手机号吗";
	    }
	    $response = $this->sms->sendSms(
	        "一点椿", // 短信签名
	        'SMS_174988441', // 短信模板编号 SMS_174988441
	        $info['mobile'], // 短信接收者
	        Array(  // 短信模板中字段的值
				
	        )
	    );
	    $response = json_encode($response);
	    $request = json_decode($response, true);
	
	    if(strtolower($request["Code"]) != "ok"){
	        return "短信发送失败，请稍候尝试";
	    }
	    return $request;
	}
	
	//成功预约折扣长住机构		SMS_174988440
	public function SendLongStay($info){
	    if (empty($info['mobile'])) {
	        return "缺少手机号吗";
	    }
	    $title = mb_substr($info['title'], 0, 20, 'utf-8');
	    $response = $this->sms->sendSms(
	        "一点椿", // 短信签名
	        'SMS_174988440', // 短信模板编号 SMS_174988440
	        $info['mobile'], // 短信接收者
	        Array(  // 短信模板中字段的值
	            "title"=>$title,
	        )
	    );
	    $response = json_encode($response);
	    $request = json_decode($response, true);
	
	    if(strtolower($request["Code"]) != "ok"){
	        return "短信发送失败，请稍候尝试";
	    }
	    return $request;
	}
	
	//成功预约短期入住机构		SMS_174988438
	public function SendShortStay($info){
	    if (empty($info['mobile'])) {
	        return "缺少手机号吗";
	    }
	    //$title = mb_substr($info['title'], 0, 20, 'utf-8');
		$name = mb_substr($info['name'], 0, 20, 'utf-8');
	    $response = $this->sms->sendSms(
	        "一点椿", // 短信签名
	        'SMS_174988438', // 短信模板编号 SMS_174988438
	        $info['mobile'], // 短信接收者
	        Array(  // 短信模板中字段的值
				"name"=>$name,
	            "time"=>$time,
	        )
	    );
	    $response = json_encode($response);
	    $request = json_decode($response, true);
	
	    if(strtolower($request["Code"]) != "ok"){
	        return "短信发送失败，请稍候尝试";
	    }
	    return $request;
	}
	
	//成功支付一元预约参观		SMS_174988428
	public function SendVisit($info){
	    if (empty($info['mobile'])) {
	        return "缺少手机号吗";
	    }
	    $response = $this->sms->sendSms(
	        "一点椿", // 短信签名
	        'SMS_174988428', // 短信模板编号 SMS_174988428
	        $info['mobile'], // 短信接收者
	        Array(  // 短信模板中字段的值
				
	        )
	    );
	    $response = json_encode($response);
	    $request = json_decode($response, true);
	
	    if(strtolower($request["Code"]) != "ok"){
	        return "短信发送失败，请稍候尝试";
	    }
	    return $request;
	}
	
    //手机号码发送频率验证
    private function SendTimeCheck($mobile){
        $model = D("sms_log");

        $begin = date("Y-m-d");
        $end = date("Y-m-d", strtotime("+1 day", time()));
        $map = array(
            "mobile"=>$mobile, "status"=>1,
            "createdate"=>array(array("egt", $begin), array("lt", $end), "and")
        );
        $list = $model->where($map)->select();

        if(count($list) >= 10){
            return "当前手机号码发送验证码数量已超过上限，请明天再尝试！";
        }

        $end30S = strtotime("-30 second", time());
        $end1H = strtotime("-1 hour", time());
        foreach($list as $k=>$v){
            if(strtotime($v["createdate"]) >= $end30S){
                return "当前手机号码30秒内已经发送过验证码，请稍后尝试！";
            }

            if(strtotime($v["createdate"]) >= $end1H){
                $list1H[] = $v;
            }
        }
        if(count($list1H) >= 5){
            return "当前手机号码发送验证码过于频繁，请稍后尝试！";
        }
    }
}
