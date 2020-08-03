<?php
namespace SApi\Controller;
use Think\Controller;
class OrderPlatformHandleController extends BaseLoggedController {
	
	//抢单
	public function orderbattle(){
		$user = $this->AuthUserInfo;
		
		if($user['profile']['status'] != 1){
			E('尚未通过审核,无法接单');
		}
		
		//验证服务人员的爽约状况
		$planetime = $user["profile"]["plane_time"];
		if(checkDateTime($planetime, "Y-m-d H:i:s")){
			$time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
			if($planetime > $time){
				E("你存在爽约记录，3个月内不能接单");
			}
		}

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要抢的订单");
		}

		//人脸识别
		$checkface = I("post.checkface");
		$this->BdFace($checkface);

		$ordermodel = D("service_order");

		$order = $ordermodel->find($orderid);
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["service_userid"] != 0){
			E("订单已经被其它服务人员抢单，操作失败");
		}
		$time = date("Y-m-d H:i:s");
		if($time >= $order["begintime"]){
			E("当前时间已经超过服务预约时间，抢单失败");
		}
		/*if($order["userid"] == $user["id"]){
			E("不可自发自抢，操作失败");
		}*/

		//验证订单的服务项目角色是否匹配当前服务人员的角色
		$projectmodel = D("service_project");
		$map = array("p.id"=>$order["projectid"]);
		$project = $projectmodel->alias("p")->join("left join sj_service_category as c on p.categoryid=c.id")
			->field("p.*,c.role as service_role")->where($map)->find();
		if(empty($project)){
			E("服务项目不存在，抢单失败");
		}
		$roles = $user["role"];
		if(!in_array($project["service_role"], $roles)){
			E("您的角色与服务项目的角色不匹配，抢单失败");
		}
		$service_level = $user["profile"]["service_level"];
		if($order["service_level"] > $service_level){
			E("您的服务星级不符合订单服务星级");
		}
		$major_level = $user["profile"]["major_level"];
		if($project["major_level"] > $major_level){
			E("您的专业等级不符合订单专业等级");
		}

		//验证服务订单预约时间是否与服务人员服务时间冲突
		$service_userid = $user["id"];
		$begintime = $order["begintime"];
		$endtime = $order["endtime"];
		$map = array(
			"service_userid"=>$service_userid, "status"=>1, "execute_status"=>array("in", [0,1,2,3]), "admin_status"=>array("in", [0,1]),
			"_complex"=>array(
				"begintime"=>array(
					array("egt", $begintime), array("elt", $endtime), "and"
				),
				"endtime"=>array(
					array("egt", $begintime), array("elt", $endtime), "and"
				),
				"_complex"=>array(
					"begintime"=>array("elt", $begintime), "endtime"=>array("egt", $endtime)
				),
				"_logic"=>"or"
			)
		);
		$checktimecount = $ordermodel->where($map)->count();
		if($checktimecount > 0){
			E("当前服务订单的预约时间与您的服务订单时间冲突，抢单失败");
		}

		//服务人员关联服务项目
		if($order["type"] == 2){
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$user["id"], "projectid"=>$order["projectid"]);
			$checkproject = $relationmodel->where($map)->find();
			if(empty($checkproject)){
				E("您还未关联订单的服务项目，操作失败");
			}
		}

		//验证区域
		$profile = $user["profile"];
		if(!($order["province"] == $profile["province"] && $order["city"] == $profile["city"] && $order["region"] == $profile["region"])){
			E("您的服务区域与订单服务区域不一致，操作失败");
		}

		$order = $ordermodel->find($orderid);
		if($order["service_userid"] > 0){
			E("您下手慢了，订单已经被其它服务人员接单");
		}

		//更新订单为当前服务人员
		$entity = array(
			"service_userid"=>$user["id"], "service_realname"=>$profile["realname"], "service_avatar"=>$user["avatar"],
			"resid"=>$profile["resid"]
		);
		$map = array("id"=>$orderid);
		$ordermodel->where($map)->save($entity);

		//消息推送
		# code...
        $title = $order['title'];
		$messagemodel = D("user_message");
		//新增 - 服务人员订单消息
        $content = '<p>';
        $content .= "【订单内容】：".$title."<br/>";
		$content .= "【服务地址】：".$order["address"]."<br/>";
		$content .= "【服务时间】：".$order["begintime"].' / '.$order["endtime"]."<br/>";
		if($order["other_remark"]){
			$content .= "【用户备注】：".$order["other_remark"]."<br/>";
		}
		if($order["platform_money"] > 0){
			$content .= "【平台补贴】：".$order["platform_money"]."元<br/>";
		}
        $content .= "恭喜您抢单成功，请您准时上门服务，如出现不能按时服务情况请提前至少3小时联系一点椿客服，电话：4009916801。";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$user["id"], "title"=>$title, "content"=>$content,
            "hybrid"=>"service", "type"=>1, "status"=>0, "createdate"=>$time
        );
		$messagemodel->add($message_entity);

		//短信通知抢单成功
		$user_info=D('user_profile')->where(array('userid'=>$user['id']))->find();
		$info=array(
			"mobile"=>$user_info['mobile'],"name"=>$user_info['realname'],"title"=>$title,
			"address"=>$order["address"],'time'=>$order["begintime"]
		);
		D('Common/RequestSms')->SendServiceBattleSms($info);
		
		
		//发送短信消息
		$sms = D("Common/RequestSms");
		$sms->SendServiceSms($order['mobile'], $order['title'] ,$order['sn'] ,$order['begintime']);
		
		
		//新增 - 用户订单消息
		$realname = $profile["realname"];
		if(empty($realname)){
			$realname = $user["realname"];
		}
		$mobile = $profile["mobile"];
		if(empty($mobile)){
			$mobile = $user["mobile"];
		}
        $content = '<p>';
        $content .= "【订单内容】：".$title."<br/>";
		$content .= "【服务人员】：".$realname."<br/>";
		$content .= "【联系方式】：".substr_replace($mobile,'****',3,4)."<br/>";
		$content .= "【订单号】：".$order["sn"]."<br/>";
		if($order["other_remark"]){
			$content .= "【用户备注】：".$order["other_remark"]."<br/>";
		}
        if($order["platform_money"] > 0){
            $content .= "【平台补贴】：".$order["platform_money"]."元<br/>";
        }
        $content .= "您的订单有人接单了，服务人员：".$realname."，联系电话：".substr_replace($mobile,'****',3,4)."，将准时上门服务，请耐心等候。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$order["userid"], "title"=>$order["title"], "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>$time
        );
        $messagemodel->add($message_entity);

        //发送短信消息
		$sms = D("Common/RequestSms");
		
		//用户
        $sms->SendServiceSms($order['mobile'], $order['title'], $order['sn'], $order['begintime']);

		//服务人员
        $info = array('name'=>$realname, 'mobile'=>$mobile, 'title'=>$order['title'],  'sn'=>$order['title']);
		$sms->SendServiceBattleSms($info);
		
		//消息推送
		$msgpush = D("Common/IGeTuiMessagePush");
		$usermodel = D("user");

		//用户
		$orderuser = $usermodel->find($order["userid"]);
		if($orderuser){
			$clientid = $orderuser["clientid"];
			$system = $orderuser["system"];
			$title = "服务订单提醒";
			$content = "您购买的服务《".$order["title"]."》，已经有服务人员接单了，请耐心等候上门服务。";
			$msgpush->PushMessageToSingle($clientid, $system, $title, $content);
		}

		//服务人员
		$serviceuser = $usermodel->find($user["id"]);
		if($serviceuser){
			$clientid = $serviceuser["sclientid"];
			$system = $serviceuser["ssystem"];
			$title = "服务订单提醒";
			$content = "《".$order["title"]."》服务订单，您已经成功接单，请准时上门服务。";
			$msgpush->PushMessageToSingle($clientid, $system, $title, $content);
			
			//定时发送信息提醒服务人员
			$msgpush->setHybrid("service");
			$content="【一点椿】尊敬的".$realname."，您的".$order["title"]."（服务项目名单）服务订单将于一小时后开始，请确保您可以在预订时间到达客户指定地点。如有疑问，请联系客服热线4009916801。";
			$msgpush->PushMessageToSingle($clientid, $system, $title, $content,null,date('Y-m-d H:i:s',strtotime($order['begintime'])-3600));
		}
		return;
	}
	//抢单验证
	public function check_orderbattle(){
		$user = $this->AuthUserInfo;
		
		//验证服务人员的爽约状况
		$planetime = $user["profile"]["plane_time"];
		if(checkDateTime($planetime, "Y-m-d H:i:s")){
			$time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
			if($planetime > $time){
				E("你存在爽约记录，3个月内不能接单");
			}
		}
		
		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要抢的订单");
		}
		
		
		$ordermodel = D("service_order");
		
		$order = $ordermodel->find($orderid);
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["service_userid"] != 0){
			E("订单已经被其它服务人员抢单，操作失败");
		}
		$time = date("Y-m-d H:i:s");
		if($time >= $order["begintime"]){
			E("当前时间已经超过服务预约时间，抢单失败");
		}
		/*if($order["userid"] == $user["id"]){
			E("不可自发自抢，操作失败");
		}*/
		
		//验证订单的服务项目角色是否匹配当前服务人员的角色
		$projectmodel = D("service_project");
		$map = array("p.id"=>$order["projectid"]);
		$project = $projectmodel->alias("p")->join("left join sj_service_category as c on p.categoryid=c.id")
			->field("p.*,c.role as service_role")->where($map)->find();
		if(empty($project)){
			E("服务项目不存在，抢单失败");
		}
		$roles = $user["role"];
		if(!in_array($project["service_role"], $roles)){
			E("您的角色与服务项目的角色不匹配，抢单失败");
		}
		$service_level = $user["profile"]["service_level"];
		if($order["service_level"] > $service_level){
			E("您的服务星级不符合订单服务星级");
		}
		$major_level = $user["profile"]["major_level"];
		if($project["major_level"] > $major_level){
			E("您的专业等级不符合订单专业等级");
		}
		
		//验证服务订单预约时间是否与服务人员服务时间冲突
		$service_userid = $user["id"];
		$begintime = $order["begintime"];
		$endtime = $order["endtime"];
		$map = array(
			"service_userid"=>$service_userid, "status"=>1, "execute_status"=>array("in", [0,1,2,3]), "admin_status"=>array("in", [0,1]),
			"_complex"=>array(
				"begintime"=>array(
					array("egt", $begintime), array("elt", $endtime), "and"
				),
				"endtime"=>array(
					array("egt", $begintime), array("elt", $endtime), "and"
				),
				"_complex"=>array(
					"begintime"=>array("elt", $begintime), "endtime"=>array("egt", $endtime)
				),
				"_logic"=>"or"
			)
		);
		$checktimecount = $ordermodel->where($map)->count();
		if($checktimecount > 0){
			E("当前服务订单的预约时间与您的服务订单时间冲突，抢单失败");
		}
		
		//服务人员关联服务项目
		if($order["type"] == 2){
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$user["id"], "projectid"=>$order["projectid"]);
			$checkproject = $relationmodel->where($map)->find();
			if(empty($checkproject)){
				E("您还未关联订单的服务项目，操作失败");
			}
		}
		
		//验证区域
		$profile = $user["profile"];
		if(!($order["province"] == $profile["province"] && $order["city"] == $profile["city"] && $order["region"] == $profile["region"])){
			E("您的服务区域与订单服务区域不一致，操作失败");
		}
	}
}