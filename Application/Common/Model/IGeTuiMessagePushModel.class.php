<?php
namespace Common\Model;

Vendor('IGeTui.IGeTui');

class IGeTuiMessagePushModel{

    private $APPKEY = "s6Tp2aS7kT7eci8vtAJYZ1";
    private $APPID = "aKBJxqmSqp5gKMTp6pk2w";
    private $MASTERSECRET = "83kkhBLr8r7NtuQ7Dv1Sg1";
    private $HOST = "http://sdk.open.api.igexin.com/apiex.htm";

    public function setHybrid($hybrid = "client"){
        if($hybrid == "service"){
            $this->APPKEY = "uhFya4KYgSALhcM1rS6sY2";
            $this->APPID = "4JBlaVl4ky5YfuSGzGlxP9";
            $this->MASTERSECRET = "5uReBuj1rX7xhp1EkddHo1";
        }
    }

    //消息推送-单个，$clientid=个推标识，$system=手机系统，$title=标题，$text=内容，$ext=扩展参数，$duration=推送开始时间
    public function	PushMessageToSingle($clientid, $system, $title, $text, $ext = null, $duration = null){
        if(empty($clientid) || empty($system)){
            return false;
        }
    
        $igt = new \IGeTui($this->HOST, $this->APPKEY, $this->MASTERSECRET);
        
        if(empty($ext)){
            $ext = array("baochun"=>1);
        }
        
        //消息模版：
        if( $system == "android" ){
            $template = $this->IGtNotificationTemplate($title,$text,$ext,$duration);
        }else if( $system == "ios" ){
            $template = $this->IGtTransmissionTemplate($title,$text,$ext,$duration);
        }
    
        //定义"SingleMessage"
        $message = new \IGtSingleMessage();
    
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600*12*1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        $target = new \IGtTarget();
        $target->set_appId($this->APPID);
        $target->set_clientId($clientid);
    
        try {
            $rep = $igt->pushMessageToSingle($message, $target);
        }catch(RequestException $e){
            $requstId = e.getRequestId();
            //失败时重发
            $rep = $igt->pushMessageToSingle($message, $target, $requstId);
        }
        
        return $rep;
    }


    //消息推送-群推，$title=标题，$text=内容，$ext=扩展参数，$list=[{clientid:111,system:android}]
    public function PushMessageToList($text, $title, $ext=null, $list){
        if(empty($list) || count($list) <= 0){
            return false;
        }

        putenv("gexin_pushList_needDetails=true");
        putenv("gexin_pushList_needAsync=true");
    
        $igt = new \IGeTui($this->HOST, $this->APPKEY, $this->MASTERSECRET);
    
        $template1 = $this->IGtNotificationTemplate($title,$text,$ext);
        $template2 = $this->IGtTransmissionTemplate($title,$text,$ext);
        //个推信息体
        $message1 = new \IGtListMessage();
        $message1->set_isOffline(true);//是否离线
        $message1->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message1->set_data($template1);//设置推送消息类型

        $message2 = new \IGtListMessage();
        $message2->set_isOffline(true);//是否离线
        $message2->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message2->set_data($template2);//设置推送消息类型
    //    $message->set_PushNetWorkType(1); //设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
    //    $contentId = $igt->getContentId($message);
        $contentId1 = $igt->getContentId($message1,"toList任务别名功能");   //根据TaskId设置组名，支持下划线，中文，英文，数字
        $contentId2 = $igt->getContentId($message2,"toList任务别名功能");   //根据TaskId设置组名，支持下划线，中文，英文，数字

        //接收方1
        foreach ($list as $key => $value) {
            if($value["clientid"]){
                if($value["system"]=="android"){
                    $target1 = new \IGtTarget();
                    $target1->set_appId($this->APPID);
                    $target1->set_clientId($value["clientid"]);
                    $targetList1[] = $target1;
                }elseif($value["system"]=="ios"){
                    $target2 = new \IGtTarget();
                    $target2->set_appId($this->APPID);
                    $target2->set_clientId($value["clientid"]);
                    $targetList2[] = $target2;
                }
                
            }
        }
        
        try {
            $rep = $igt->pushMessageToList($contentId1, $targetList1);
            $rep = $igt->pushMessageToList($contentId2, $targetList2);
        }catch(RequestException $e){
            //失败时重发
            $rep = $igt->pushMessageToList($contentId1, $targetList1);
            $rep = $igt->pushMessageToList($contentId2, $targetList2);
        }

        return $rep;
    }


    private function IGtNotificationTemplate($title,$text,$ext,$duration){
        $template =  new \IGtNotificationTemplate();
        $template->set_appId($this->APPID);                   //应用appid
        $template->set_appkey($this->APPKEY);                 //应用appkey
        $template->set_transmissionType(1);            //透传消息类型
        $template->set_transmissionContent(json_encode($ext));//透传内容
        $template->set_title($title);                  //通知栏标题
        $template->set_text($text);     //通知栏内容
        $template->set_logo("");                       //通知栏logo
        $template->set_logoURL("");                    //通知栏logo链接
        $template->set_isRing(true);                   //是否响铃
        $template->set_isVibrate(true);                //是否震动
        $template->set_isClearable(true);              //通知栏是否可清除

		//定时推送 最小间隔六分钟
		if($duration){
			$begin = date('Y-m-d H:i:s',strtotime($duration)-600);
			$end = $duration;
			$template->set_duration($begin,$end);
		}
		
        return $template;
    }

    private function IGtTransmissionTemplate($title,$text,$ext,$duration){
        $template =  new \IGtTransmissionTemplate();
        $template->set_appId($this->APPID);//应用appid
        $template->set_appkey($this->APPKEY);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent(json_encode($ext));//透传内容
		
		//定时推送 最小间隔六分钟
		if($duration){
			$begin = date('Y-m-d H:i:s',strtotime($duration)-600);
			$end = $duration;
			$template->set_duration($begin,$end);
		}
		
		
        //       APN高级推送
        $apn = new \IGtAPNPayload();
        $alertmsg=new \DictionaryAlertMsg();
        $alertmsg->body=$text;
        $alertmsg->actionLocKey="ActionLockey";
        $alertmsg->locKey="LocKey";
        $alertmsg->locArgs=array("locargs");
        $alertmsg->launchImage="launchimage";
        //iOS8.2 支持
        $alertmsg->title=$title;
        $alertmsg->titleLocKey="TitleLocKey";
        $alertmsg->titleLocArgs=array("TitleLocArg");

        $apn->alertMsg=$alertmsg;
        $apn->badge=0;
        $apn->sound="";
        $apn->add_customMsg("payload", json_encode($ext));
        //$apn->contentAvailable=1;
        $apn->category="ACTIONABLE";
        $template->set_apnInfo($apn);
        return $template;
    }
	
	
}
?>