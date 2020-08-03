<?php
namespace SApi\Controller;
use Think\Controller;
class OrderHandleController extends BaseLoggedController {
	
	//开始服务 / 开始配送
	public function orderstart(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要操作的订单");
		}

		//人脸识别
		$checkface = I("post.checkface");
		$this->BdFace($checkface);

		$model = D("service_order");

		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["admin_status"] == 0){
			E("订单正在审核，操作失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3)){
			E("订单状态异常，操作失败");
		}
		//0为待服务，服务人员才能开始服务
		if($order["execute_status"] != 0){
			E("服务已经开始，请勿重复操作");
		}

		$time = time();
        if ($order["type"] == 2) { //服务订单
            $btime = strtotime($order['begintime']);
            $zdtime = strtotime("-30 minute", $btime);
            if ($time < $zdtime) {
                E('至少要在预约时间的前半个小时才能开始服务');
            }
        }else if($order["type"] == 1){ //送餐订单
            //中晚餐，第二份为晚餐时
            if ($order['res_type'] == 3 && $order['service_time'] == 1) {
                $btime = date('Y-m-d',strtotime($order['begintime']));
                $btime .= ' 17:00:00';
                $btime = strtotime($btime);
                if ($time < $btime) {
                    E('还未到配送时间范围');
                }
            } else{
                $btime = strtotime($order['begintime']);
                if ($time < $btime) {
                    E('还未到配送时间范围');
                }
            }
		}

		//更新订单服务状态 - 1=开始服务
		$entity = array("execute_status"=>1, "execute_time"=>date("Y-m-d H:i:s"));
		$model->where($map)->save($entity);

		//服务交互记录
		$recordmodel = D("service_order_record");
		$title = "开始服务";
		if($order["type"] == 1){
			$title = "开始配送";
		}

		//获取经纬度
		$coordinatemodel = D("Common/Coordinate");
		$coordinate = $coordinatemodel->readnewcoordinate($order);

		$record_entity = array(
			"orderid"=>$orderid, "userid"=>$user["id"], "title"=>$title,
			"execute_status"=>1, "updatetime"=>date("Y-m-d H:i:s"),
			"longitude"=>$coordinate["longitude"], "latitude"=>$coordinate["latitude"]
		);
		$recordmodel->add($record_entity);
		
		return;
	}

	//完成服务 / 完成配送
	public function ordercompleted(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		$meal = I("post.meal", 0);
		if(empty($orderid)){
			E("请选择要操作的订单");
		}

		$model = D("service_order");

		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["admin_status"] == 0){
			E("订单正在审核，操作失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3)){
			E("订单状态异常，操作失败");
		}
		if($order["type"] == 2 && $order["execute_status"] == 1){
			E("客户未确认开始服务，操作失败");
		}
		if($order["execute_status"] == 3){
			E("您已完成服务，请耐心等候客户确认完成服务");
		}

		$time = time();
        if ($order['type'] == 2) { //服务订单
            $etime = strtotime($order['endtime']);
            $etime = strtotime("-30 minute", $etime);
            if ($time < $etime) {
                E('至少要在服务结束时间的前半个小时才能完成服务');
            }
		} else if($order["type"] == 1){ //送餐订单
            if ($order['res_type'] == 3) { //午晚餐
				$btime = date('Y-m-d', strtotime($order['begintime']));
				if($order["service_time"] == 0){ //第一份为午餐时
					$endtime = strtotime($btime." 13:00:00");
					$begintime = strtotime($btime.' 12:30:00');
					if(!($time >= $begintime && $time <= $endtime)) {
						E('至少要在送餐结束时间的前半个小时才能完成配送');
					}
				} else if($order["service_time"] == 1){ //第二份为晚餐时
					$endtime =  strtotime($btime.' 19:00:00');
					$begintime = strtotime($btime.' 18:30:00');
					if(!($time >= $begintime && $time <= $endtime)) {
						E('至少要在送餐结束时间的前半个小时才能完成配送');
					}
				}
            } else{ //午餐或者晚餐
				$endtime = date("Y-m-d H:i", strtotime($order["endtime"]));
				$begintime = strtotime("-30 minute", $endtime);
                if(!($time >= $begintime && $time <= $endtime)) {
                    E('至少要在送餐结束时间的前半个小时才能完成配送');
				}
            }
		}
		
		//更新订单服务状态 - 3=完成服务
		$entity = array("execute_status"=>3, "execute_time"=>date("Y-m-d H:i:s"));
		//送餐服务订单
		if($order["type"] == 1 && $order["service_time"] > 0){
			$entity["service_time"] = ($order["service_time"] - 1);
			if($entity["service_time"] > 0){ //服务次数大于0则变成待配送状态
				$entity["execute_status"] = 0;
			}
		}
		$model->where($map)->save($entity);

		//服务交互记录
		$recordmodel = D("service_order_record");
		$title = "完成服务";
		if($order["type"] == 1){
			$title = "完成配送";
		}

		//获取经纬度
		$coordinatemodel = D("Common/Coordinate");
		$coordinate = $coordinatemodel->readnewcoordinate($order);

		$record_entity = array(
			"orderid"=>$orderid, "userid"=>$user["id"], "title"=>$title,
			"execute_status"=>3, "updatetime"=>date("Y-m-d H:i:s"),
			"longitude"=>$coordinate["longitude"], "latitude"=>$coordinate["latitude"]
		);
        if($order["type"] == 1){
            $record_entity['meal'] = $meal;
        }
		$recordmodel->add($record_entity);
		
		return;
	}

	//录入照护信息
	public function recordcare(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择录入的服务订单");
		}

		$ordermodel = D("service_order");

		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}

		$entity = array("orderid"=>$orderid, "createdate"=>date("Y-m-d H:i:s"));

		$images = $data["images"];
		if($images){
			$entity["images"] = $images;
		}
		$video = $data["video"];
		if($video){
			$entity["video"] = $video;
		}
		$content = $data["content"];
		if($content){
			$entity["content"] = $content;
		}
		
		if(empty($images) && empty($video) && empty($content)){
			E("提交的数据不能为空");
		}

		$recordmodel = D("service_order_care_record");

		$map = array("orderid"=>$orderid);
		$record = $recordmodel->where($map)->find();
		if($record){
			if($record["video"]){
				$video = explode("|", $record["video"]);

				$entity["video"] = $video[0]."|".$entity["video"];
			}
			$recordmodel->where($map)->save($entity);
		} else{
			$recordmodel->add($entity);
		}

		return;
	}

	//录入表单信息 - 线下评估/日间照护/遵照医嘱 服务订单
	public function recordform(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择录入表单的服务订单");
		}
		$completedids = $data["completedids"];
		if(empty($completedids)){
			$completedids = [];
		} else{
			$completedids = explode(",", $completedids);
		}
		$date = $data["date"];
		if(empty($date)){
			E("请选择录入日期");
		}
		if(!checkDateTime($date, "Y-m-d")){
			E("录入日期的格式不正确");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1 && $order["time_type"] != 4 && $order["doctor"] != 1){
			E("当前服务订单必须为线下评估/日间照护/遵照医嘱");
		}
		$begintime = date("Y-m-d", strtotime($order["begintime"]));
		$endtime = date("Y-m-d", strtotime($order["endtime"]));
		if(!($begintime <= $date && $endtime >= $date)){
			E("录入日期必须是服务周期内");
		}
		if(empty($order["formids"])){
			E("服务订单未设置服务的表单");
		}
		$formids = explode(",", $order["formids"]);

		$recordmodel = D("service_order_form_record");

		//清除已录入的表单信息
		$map = array("orderid"=>$orderid, "record_date"=>$date, "formid"=>array("in", $formids));
		$recordmodel->where($map)->delete();

		foreach($formids as $k=>$formid){
			$entity = array(
				"orderid"=>$orderid, "formid"=>$formid, "completed"=>0, "remark"=>"",
				"record_date"=>$date, "createdate"=>date("Y-m-d H:i:s")
			);
			if(in_array($formid, $completedids)){
				$entity["completed"] = 1;
			}

			$recordmodel->add($entity);
		}

		return;
	}

	//申请退单
	public function orderreturned(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择申请的订单");
		}

		$model = D("service_order");

		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["status"] == 4){
			E("订单已完成，操作失败");
		}
		if(in_array($order["execete_status"], [1,2,3,4])){
			E("订单已开始服务，操作失败");
		}

		$reason = I("post.reason");
		if(empty($reason)){
			E("请输入申请退款的原因");
		}

		//更新订单的售后状态
		$entity = array("admin_status"=>5);
		$model->where($map)->save($entity);

		//服务交互记录
		$recordmodel = D("service_order_record");
		$record_entity = array(
			"orderid"=>$orderid, "userid"=>$user["id"], "title"=>"申请退单",
			"execute_status"=>5, "updatetime"=>date("Y-m-d H:i:s"), "remark"=>$reason
		);
		$recordmodel->add($record_entity);

		return;
	}
	
	//订单隐私号码解除绑定
	public function ordermobile(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid");
		if(empty($orderid)){
			E("请选择要操作的订单");
		}

		$ordermodel = D("service_order");

		$map = array("o.service_userid"=>$user["id"], "o.id"=>$orderid);
		$order = $ordermodel->alias("o")->join("left join sj_user_profile as p on o.service_userid=p.userid")
			->field("o.*,p.mobile as service_mobile")->where($map)->find();
		if(empty($order)){
			return;
		}
		//隐私号码已绑定
		if($order["pn_status"] != 1){
			return;
		}

		$pn_bind_id = $order["pn_bind_id"];

		//解除绑定隐私号码
		$axbmodel = D("Common/HwAXB");

		//三次解除绑定
		for($i=1;$i<=3;$i++){
			$result = $axbmodel->UnBind($pn_bind_id);
			if($result["result"] == "FAIL"){
				if($i == 3){ // 超过三次未解除绑定
					return;
				}
				continue;
			}
			break;
		}

		$entity = array(
			"pn_mobile"=>"", "pn_bind_id"=>"", "pn_status"=>0
		);
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$ordermodel->where($map)->save($entity);

		return;
	}

	//续费订单 同意续费/不同意续费
	public function again_agree(){
		$user = $this->AuthUserInfo;
		$orderid=I('post.orderid');
		$agree=I('post.agree',1);//1同意 2不同意
		
		//查询订单信息
		$order=D('service_order')->where(array('id'=>$orderid))->find();
		if(empty($order)){
			E('订单不存在');
		}
		switch($agree){
			case 1:
				//同意操作 发送消息推送通知用户
				//【您的压疮服务订单续费已确认，请在服务结束前10分钟续费，谢谢。去续费，取消】。同时服务端订单列表显示“待续费”，用户端订单列表也显示“待续费”，用户端完成续费后，两端列表都显示“已续费”
				//推送消息给服务人员
				$msgpush = D("Common/IGeTuiMessagePush");
				$usermodel = D("user");
				
				//服务人员
				$orderuser = $usermodel->find($order["userid"]);
				if($orderuser){
					$clientid = $orderuser["clientid"];
					$system = $orderuser["system"];
					$title = "服务订单续费提醒";
					$content = "您的《".$order["title"]."》订单续费已确认，请在服务结束前10分钟续费，谢谢。";
					$msgpush->PushMessageToSingle($clientid, $system, $title, $content);
				}
				
				$title = $order['title'];
				$messagemodel = D("user_message");
				//新增 - 服务人员订单消息
				$content = '<p>';
				$content .= "【订单内容】：".$title."<br/>";
				$content .= "您的《".$order["title"]."》订单续费已确认，请在服务结束前10分钟续费，谢谢。";
				$content .= '</p>';
				$message_entity = array(
				    "userid"=>$order["userid"], "title"=>$title, "content"=>$content,
				    "hybrid"=>"user", "type"=>1, "status"=>0, "createdate"=>date('Y-m-d H:i:s')
				);
				$messagemodel->add($message_entity);
				break;
			case 2:
				//不同意操作
				//【“服务人员繁忙，请联系客服进行协调，拨打电话4009916801”】用户订单列表显示：联系客服续费
				//推送消息给用户
				$msgpush = D("Common/IGeTuiMessagePush");
				$usermodel = D("user");
				
				//用户
				$orderuser = $usermodel->find($order["userid"]);
				if($orderuser){
					$clientid = $orderuser["clientid"];
					$system = $orderuser["system"];
					$title = "服务订单续费提醒";
					$content = "服务人员繁忙，请联系客服进行协调，拨打电话4009916801";
					$msgpush->PushMessageToSingle($clientid, $system, $title, $content);
				}
				
				$title = $order['title'];
				$messagemodel = D("user_message");
				//新增 - 用户订单消息
				$content = '<p>';
				$content .= "【订单内容】：".$title."<br/>";
				$content .= "服务人员繁忙，请联系客服进行协调，拨打电话4009916801";
				$content .= '</p>';
				$message_entity = array(
				    "userid"=>$order["userid"], "title"=>$title, "content"=>$content,
				    "hybrid"=>"user", "type"=>1, "status"=>0, "createdate"=>date('Y-m-d H:i:s')
				);
				$messagemodel->add($message_entity);
				break;
			default:
				E('请选择是否同意');
		}
		$map = array('orderid'=>$orderid);
		$entity = array('is_agree'=>$agree);
		D('service_order_again_record')->where($map)->save($entity);
		return ;
	}

	//录入线下评估表单 - 家护师
	public function recordassess3(){
		$user = $this->AuthUserInfo;

		$data = $_POST;

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择线下评估的服务订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("服务订单中的服务项目必须是线下评估服务订单");
		}
		if($order["service_role"] != 3){
			E("当前服务订单为非家护师线下评估，无法录入");
		}
		if($order["assess_status"] == 2 && $order["again_status"] == 2){
			E("服务订单已支付尾款，录入表单失败");
		}
		if(!($order["assess_status"] == 1 || ($order["assess_status"] == 2 && $order["again_status"] == 1))){
			E("服务订单状态异常，录入表单失败");
		}

		//服务项目的护理价格
		$depositmodel = D("service_project_deposit_price");
		$map = array("projectid"=>$order["projectid"]);
		$depositprice = $depositmodel->where($map)->find();
		if(empty($depositprice)){
			E("服务项目的护理周期不存在，请联系客服");
		}

		$type = $data["type"];
		if(!in_array($type, [1,2])){
			E("请选择评估类型");
		}
		
		//服务项目评估表单
		$assessmodel = D("service_order_assess");
		$map = array("type"=>$type);
		$assess = $assessmodel->where($map)->select();

		$answer_content = $data["answer"];
		if(empty($answer_content)){
			E("请提交评估内容");
		}
		$answer = json_decode($answer_content, true);

		//线下评估记录
		$recordmodel = D("service_order_assess_record");
		$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
		$record = $recordmodel->where($map)->find();

		$record_answer = array();
		if($record){
			if($record["answer_content"]){
				$record_answer = json_decode($record["answer_content"], true);
			}
		}

		$total_score = 0;

		$history_care_level = 0;
		
		foreach($assess as $k=>$v){
			$option_answer = $answer["answer_".$v["id"]];

			$option = $option_answer["answer"];
			if(empty($option)){
				E("请提交“".$v["question"]."”的答案");
			}
			if(!in_array($option, ["a", "b", "c", "d"])){
				E("“".$v["question"]."”的答案不符合");
			}

			if($type == 1){
				$score = intval($v["answer_".$option."_score"]);

				$total_score += $score;
			} else if($type == 2){
				if(in_array($v["id"], [22,23,24])){
					if($option == "a"){
						$history_care_level = 5;
					}
				} else if(in_array($v["id"], [25,26,27,28])){
					if($option == "b" && $history_care_level == 0){
						$history_care_level = 3;
					}
				}
			}

			$record_answer["answer_".$v["id"]] = $option_answer;
		}

		$care_level = 0;
		if($type == 1){
			if($total_score > 250 && $total_score <= 260){
				$care_level = 1;
			} else if($total_score > 235 && $total_score <= 250){
				$care_level = 2;
			} else if($total_score > 175 && $total_score <= 235){
				$care_level = 3;
			} else if($total_score > 170 && $total_score <= 175){
				$care_level = 4;
			} else if($total_score <= 170){
				$care_level = 5;
			}
		} else if($type == 2){
			if($record){
				$care_level = $record["care_level"];

				$total_score = $record["total_score"];
			}

			if($care_level < $history_care_level){
				$care_level = $history_care_level;
			}
		}

		if($type == 1){
			$image = $data["image"];
			if(empty($image)){
				E("请上传签名照片");
			}
		}

		$again_price = 0;
		switch($care_level){
			case 1: $again_price = $depositprice["one_price"]; break;
			case 2: $again_price = $depositprice["two_price"]; break;
			case 3: $again_price = $depositprice["three_price"]; break;
			case 4: $again_price = $depositprice["four_price"]; break;
			case 5: $again_price = $depositprice["five_price"]; break;
		}
		if(in_array($care_level, [1,2,3,4,5]) && $again_price <= 0){
			E("服务项目的护理等级价格异常，请联系客服");
		}

		$entity = array(
			"orderid"=>$order["id"], "careid"=>$order["careid"], "answer_content"=>json_encode($record_answer),
			"total_score"=>$total_score, "care_level"=>$care_level, "assess_care_level"=>$care_level,
			"image"=>$image, "updatetime"=>date("Y-m-d H:i:s")
		);
		if($record){
			$recordmodel->where($map)->save($entity);
		} else{
			$recordmodel->add($entity);
		}

		//更新订单为已评估待缴费
		if(in_array($care_level, [1,2,3,4,5])){
			//尾款 = 单价 x 时长
			$total_again_price = $again_price * $order["time"];

			$o_entity = array(
				"assess_status"=>2, "again_status"=>1, "again_price"=>$total_again_price
			);
			
			$map = array("id"=>$order["id"]);
			$ordermodel->where($map)->save($o_entity);
		}

		return;
	}

	//更新线下评估护理等级和服务结束时间 - 家护师
	public function orderupdateassess3(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择线下评估的服务订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("服务订单中的服务项目必须是线下评估服务订单");
		}
		if($order["service_role"] != 3){
			E("当前服务订单为非家护师线下评估，无法更新线下评估护理等级和服务结束时间");
		}
		if(!($order["assess_status"] == 1 || ($order["assess_status"] == 2 && $order["again_status"] == 1))){
			E("服务订单状态异常，更新线下评估护理等级和服务结束时间失败");
		}

		//服务项目的护理价格
		$depositmodel = D("service_project_deposit_price");
		$map = array("projectid"=>$order["projectid"]);
		$depositprice = $depositmodel->where($map)->find();
		if(empty($depositprice)){
			E("服务项目的护理周期不存在，请联系客服");
		}

		//照护人评估记录
		$recordmodel = D("service_order_assess_record");

		$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
		$record = $recordmodel->where($map)->find();
		if(empty($record)){
			E("当前订单暂未进行线下评估，无法更新订单护理等级信息");
		}

		$time = $data["time"];
		if(!is_numeric($time)){
			E("请提交服务周期");
		}

		$endtime = $data["endtime"];
		if(empty($endtime)){
			E("请提交订单结束时间");
		}
		if(!checkDateTime($endtime, "Y-m-d H:i")){
			E("结束时间格式不正确");
		}

		$care_level = $data["care_level"];
		if(!in_array($care_level, [1,2,3,4,5])){
			E("请选择护理等级");
		}

		if($care_level < $record["assess_care_level"]){
			E("当前所选护理等级不能低于线下评估的护理等级");
		}

		$again_price = 0;
		switch($care_level){
			case 1: $again_price = $depositprice["one_price"]; break;
			case 2: $again_price = $depositprice["two_price"]; break;
			case 3: $again_price = $depositprice["three_price"]; break;
			case 4: $again_price = $depositprice["four_price"]; break;
			case 5: $again_price = $depositprice["five_price"]; break;
		}

		if($again_price <= 0){
			E("服务项目的护理等级价格异常，请联系客服");
		}

		//更新订单评估记录
		$r_entity = array(
			"care_level"=>$care_level, "updatetime"=>date("Y-m-d H:i:s")
		);
		$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
		$recordmodel->where($map)->save($r_entity);

		//尾款 = 单价 x 时长
		$total_again_price = $again_price * $time - $order['amount'];

		//更新订单为已评估待缴费
		$o_entity = array(
			"endtime"=>$endtime, "time"=>$time, "assess_status"=>2, "again_status"=>1, "again_price"=>$total_again_price
		);
		
		$map = array("id"=>$order["id"]);
		$ordermodel->where($map)->save($o_entity);

		return;
	}

	//录入线下评估 - 康复师
	public function recordassess4(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择线下评估的服务订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("服务订单中的服务项目必须是线下评估服务订单");
		}
		if($order["service_role"] != 4){
			E("当前服务订单为非康复师线下评估，无法录入");
		}
		if($order["assess_status"] == 2 && $order["again_status"] == 2){
			E("服务订单已支付尾款，录入信息失败");
		}
		if(!($order["assess_status"] == 1 || ($order["assess_status"] == 2 && $order["again_status"] == 1))){
			E("服务订单状态异常，录入信息失败");
		}

		//诊断结果
		$doctor_1 = $data["doctor_1"];
		//治疗方案
		$doctor_2 = $data["doctor_2"];
		//诊疗方案
		$doctor_image = $data["doctor_image"];
		if(empty($doctor_image)){
			E("请上传诊疗方案图片");
		}
		$answer_content = array(
			"doctor_1"=>$doctor_1, "doctor_2"=>$doctor_2
		);

		//线下评估记录
		$recordmodel = D("service_order_assess_record");

		$entity = array(
			"orderid"=>$order["id"], "careid"=>$order["careid"], "answer_content"=>json_encode($answer_content),
			"total_score"=>0, "care_level"=>0, "assess_care_level"=>0,
			"image"=>$doctor_image, "updatetime"=>date("Y-m-d H:i:s")
		);

		$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
		$record = $recordmodel->where($map)->find();
		if($record){
			$recordmodel->where($map)->save($entity);
		} else{
			$recordmodel->add($entity);
		}

		return $data;
	}

	//更新线下评估服务结束时间和缴付尾款 - 康复师
	public function orderupdateassess4(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择线下评估的服务订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("服务订单中的服务项目必须是线下评估服务订单");
		}
		if($order["service_role"] != 4){
			E("当前服务订单为非康复师线下评估，无法更新线下评估服务结束时间和缴付尾款");
		}
		if(!($order["assess_status"] == 1 || ($order["assess_status"] == 2 && $order["again_status"] == 1))){
			E("服务订单状态异常，更新线下评估服务结束时间和缴付尾款失败");
		}

		//照护人评估记录
		$recordmodel = D("service_order_assess_record");

		$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
		$record = $recordmodel->where($map)->order("updatetime desc")->find();
		if(empty($record)){
			E("当前订单暂未进行线下评估，无法更新线下评估服务结束时间和缴付尾款");
		}

		$time = $data["time"];
		if(!is_numeric($time)){
			E("请提交服务周期");
		}

		$endtime = $data["endtime"];
		if(empty($endtime)){
			E("请提交订单结束时间");
		}
		if(!checkDateTime($endtime, "Y-m-d H:i")){
			E("结束时间格式不正确");
		}

		$amount = $data["amount"];
		if(!is_numeric($amount)){
			E("提交的订单余额格式不正确");
		}
		if($amount <= 0){
			E("订单余额必须大于0");
		}

		$o_entity = array(
			"endtime"=>$endtime, "time"=>$time, "assess_status"=>2, "again_status"=>1, "again_price"=>$amount
		);
		
		$map = array("id"=>$order["id"]);
		$ordermodel->where($map)->save($o_entity);

		return;
	}

	//关闭线下评估订单
	public function orderassessclose(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择关闭的线下评估服务订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("服务订单中的服务项目必须是线下评估服务订单");
		}
		if(!($order["assess_status"] == 1 || ($order["assess_status"] == 2 && $order["again_status"] == 1))){
			E("服务订单状态异常，关闭订单失败");
		}

		//更新服务订单的执行状态为已关闭
		$o_entity = array("status"=>4, "execute_status"=>8, "execute_time"=>date("Y-m-d H:i:s"));
		$map = array("id"=>$orderid);
		$ordermodel->where($map)->save($o_entity);

        //服务交互记录
        $recordmodel = D("service_order_record");
		$r_entity = array(
			"orderid"=>$orderid, "userid"=>$order["service_userid"], "title"=>"线下评估关闭订单",
			"execute_status"=>8, "updatetime"=>date("Y-m-d H:i:s")
		);
		$recordmodel->add($r_entity);

		return;
	}
}