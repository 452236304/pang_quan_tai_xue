<?php
namespace CApi\Controller;
use Think\Controller;
class OrderServiceHandleController extends BaseLoggedController {
	
	//服务订单评价
	public function servicecomment(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择要评价的订单");
		}
		$serviceid = $data["serviceid"];
		if(empty($serviceid)){
			E("请选择要评价的服务人员");
		}

		$comment1 = $data["comment1"];
		if(empty($comment1)){
			E("请对服务态度进行评价");
		}
		$comment2 = $data["comment2"];
		if(empty($comment2)){
			E("请对专业能力进行评价");
		}
		$comment3 = $data["comment3"];
		if(empty($comment3)){
			E("请对整体满意度进行评价");
		}
		$content = $data["content"];
		if(empty($content)){
			E("请输入服务人员的评价内容");
		}
		$images = $data["images"];

		$usermodel = D("user");
		$map = array("up.status"=>1, "u.status"=>200, "u.id"=>$serviceid);
		$serviceuser = $usermodel->alias("u")->join("left join sj_user_profile as up on u.id=up.userid")
			->field("u.id,u.nickname,u.avatar,up.realname,up.mobile,up.major_level,up.service_level")->where($map)->find();
		if(empty($serviceuser)){
			E("服务人员不存在");
		}

		$ordermodel = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，评价失败");
		}
		if($order["status"] != 4){
			E("订单未完成，评价失败");
		}
		if($order["commentid"] > 0){
			E("订单服务人员已经评价，请勿重复评价");
		}

		$entity = array(
			"status"=>1, "userid"=>$user["id"], "nickname"=>$user["nickname"], "avatar"=>$user["avatar"],
			"service_userid"=>$order["service_userid"], "service_realname"=>$order["service_realname"], "service_avatar"=>$order["service_avatar"],
			"comment1"=>$comment1, "comment2"=>$comment2, "comment3"=>$comment3,
			"images"=>$images, "content"=>$content, "orderid"=>$order["id"], "ordersn"=>$order["sn"],
			"createdate"=>date("Y-m-d H:i:s")
		);
		//计算评分
		$score1 = intval($comment1)/5*100; //服务态度
		$score2 = intval($comment2)/5*100; //专业能力
		$score3 = intval($comment3)/5*100; //整体满意度
        $score = ceil((($score1+$score2+$score3)/300)*100); //评分
		//计算评分
		$entity["score"] = $score;

		$commentmodel = D("service_comment");

		$map = array("orderid"=>$order["id"]);
		$check = $commentmodel->where($map)->find();
		if($check){
			$commentmodel->where($map)->save($entity);

			$commentid = $entity["id"] = $check["id"];
		} else{
			$commentid = $entity["id"] = $commentmodel->add($entity);
		}

		//设置订单为已评论
		$map = array("userid"=>$user["id"], "id"=>$order["id"]);
		$ordermodel->where($map)->save(array("commentid"=>$commentid));

		//计算服务等级升级逻辑处理
		$this->calcservicelevel($order);
		//计算服务人员的服务平均分
		$this->calccommentpercent($order["service_userid"]);

		return;
	}

	//计算服务人员的服务平均分
	private function calccommentpercent($serviceuserid){
		if(empty($serviceuserid)){
			return false;
		}

		$commentmodel = D("service_comment");

		$map = array(
            "sc.service_userid"=>$serviceuserid, "so.status"=>4, "so.commentid"=>array("gt", 0)
        );
        $comments = $commentmodel->alias("sc")->join("left join sj_service_order as so on so.id=sc.orderid")
            ->field("sc.*")->where($map)->select();
		$commentcount = count($comments);
		if($commentcount <= 0){
			return;
		}

		$total_score = 0;
		foreach ($comments as $k=>$v) {
			$total_score += $v["score"];
		}
		//平均分
		$score = ceil($total_score/$commentcount);

		$profilemodel = D("user_profile");

		$entity = array("comment_percent"=>$score);
		$map = array("userid"=>$serviceuserid);
		$profilemodel->where($map)->save($entity);
	}

	//计算服务等级升级逻辑处理
	private function calcservicelevel($order){
		$usermodel = D("user");
		$map = array("u.id"=>$order["service_userid"]);
		$serviceuser = $usermodel->alias("u")->join("left join sj_user_profile as up on u.id=up.userid")
			->field("u.id,u.avatar,up.realname,up.mobile,up.service_level,up.service_level_update_time")->where($map)->find();
		if(empty($serviceuser) || empty($serviceuser["service_level_update_time"])){
			return;
		}

		$ordermodel = D("service_order");

		//获取上次升降星时间后的已评论订单
		$begintime = $serviceuser["service_level_update_time"];
		$map = array("status"=>4, "service_userid"=>$serviceuser["id"], "execute_time"=>array("egt", $begintime), "commentid"=>array("gt", 0));
		$orders = $ordermodel->where($map)->select();
		$ordercount = count($orders);

		//验证接单数量是否满足升星订单数
		$service_level = $serviceuser["service_level"];
		
		//当前服务人员的服务等级高等于5级，无需进行升级验证
		if($service_level >= 5){
			return;
		}

		//1星升2星，累计要50单
		if($service_level == 1 && $ordercount < 50){
			return;
		}
		//2星升3星，累计要100单
		if($service_level == 2 && $ordercount < 100){
			return;
		}
		//3星升4星，累计要200单
		if($service_level == 3 && $ordercount < 200){
			return;
		}
		//4星升5星，累计要200单
		if($service_level == 4 && $ordercount < 200){
			return;
		}

		//评论id集合
		foreach($orders as $k=>$v){
			$commentids[] = $v["commentid"];
		}

		//评论集合
		$commentmodel = D("service_comment");
		$map = array("service_userid"=>$serviceuser["id"], "id"=>array("in", $commentids));
		$comments = $commentmodel->where($map)->select();
		$commentcount = count($comments);

		$total_score = 0;
		foreach ($comments as $k=>$v) {
			$total_score += $v["score"];
		}

		//平均分
		$score = $total_score/$commentcount;

		//检查是否符合升级服务星级的平均分
		$up_service_level = 0;
		switch($service_level){
			case 1:
				if($score >= 80){
					$up_service_level = 2;
				}
				break;
			case 2:
				if($score >= 80){
					$up_service_level = 3;
				}
				break;
			case 3:
				if($score >= 80){
					$up_service_level = 4;
				}
				break;
			case 4:
				if($score >= 90){
					$up_service_level = 5;
				}
				break;
		}
		if($up_service_level <= 0){
			return;
		}
		
		//更新服务人员服务等级
		$profilemodel = D("user_profile");
		$time = date("Y-m-d H:i:s");
		$entity = array(
			"service_level"=>$up_service_level, "service_level_update_time"=>$time, "service_level_check_time"=>$time
		);
		$map = array("userid"=>$serviceuser["id"]);
		$profilemodel->where($map)->save($entity);
	}

	//创建服务订单
	public function createserviceorder(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		//订单来源
		$hybrid = $this->GetHttpHeader("platform");
		if(!in_array($hybrid, ["app", "xcx"])){
			E("请提交订单来源");
		}
		
		if(empty($data['remark'])){
			$remark = '';
		}else{
			$remark = $data['remark'];
		}
		
		//服务项目
		$projectid = $data["projectid"];
		if(empty($projectid)){
			E("请选择要预约的服务项目");
		}
		
		$projectmodel = D("service_project");
		$map = array("sp.status"=>1, "sp.id"=>$projectid);
		$project = $projectmodel->alias("sp")->join("left join sj_service_category as sc on sp.categoryid=sc.id")
			->field("sc.title as categoryname,sc.role as service_role,sp.*")->where($map)->find();
		if(empty($project)){
			E("您预约的服务项目不存在");
		}
		
		if($project["assess"] == 1){ //线下评估
			$service_type = 2; //服务护理价格类型
		} else if($project["time_type"] == 4){
			$service_type = 3; //日间照护价格类型
		} else{
			$service_type = 1; //服务星级价格类型
		}

		//照护人
		$usercareid = $data["usercareid"];
		if(empty($usercareid)){
			E("请选择护理人");
		}
		$caremodel = D("user_care");
		$map = array("userid"=>$user["id"], "id"=>$usercareid);
		$usercare = $caremodel->where($map)->find();
		if(empty($usercare)){
			E("请添加被照护人");
		}
		if(empty($usercare["birth"])){
			E("护理人信息异常，预约失败");
		}
		$age = getAge($usercare["birth"]);
		if($age < 18){
			E("护理人年龄不足18岁，非服务对象，谢谢关注");
		}

		$time_length = $project["time"];
		//预约上门时间
		if($projectid == 72){ //居室清洁 - 特殊处理
			$begintime = $data["begintime"];
			if(empty($begintime)){
				E("请选择预约上门时间");
			}
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("请选择正确的时间格式");
			}

			//小时
			$days = intval($data["days"]);
			if(empty($days)){
				$days = 2;
			}
			$endtime = date("Y-m-d H:i", strtotime("+".$days." hour", strtotime($begintime)));

			$time_length = $days;

		} else if(in_array($project["time_type"], [0,1])){ // 时长类型为 分钟和小时 - 下午13点前可预约当天
			$timeid = $data["timeid"];
			if(empty($timeid)){
				E("请选择预约上门时间");
			}

			$timemodel = D("service_time");
			$map = array("status"=>1, "projectid"=>$project["id"], "id"=>$timeid);
			$servicetime = $timemodel->where($map)->find();
			if(empty($servicetime)){
				E("您选择的预约上门时间不存在");
			}

			//一天后
			$day = $servicetime["days"];
			
			//下午13点前可预约当天
			$current_hour = date("H", time());
			if($current_hour < 13){
				if($day == 1){
					$day = 0;
				} else{
					$day = $day - 1;
				}
			}

			$date = date("Y-m-d" ,strtotime("+".$day." day", time()));
			$begintime = $date." ".$servicetime["begintime"];
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("预约开始时间格式不正确");
			}

			$endtime = $date." ".$servicetime["endtime"];
			if(!checkDateTime($endtime, "Y-m-d H:i")){
				E("服务结束时间格式不正确");
			}
		} else if($service_type == 2){ // 服务护理价格类型 - 下午13点前可预约当天
			$begintime = $data["begintime"];
			if(empty($begintime)){
				E("请选择预约上门时间");
			}
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("请选择正确的时间格式");
			}

			$depositid = $data["depositid"];
			if(empty($depositid)){
				E("请选择照护周期");
			}

			$depositmodel = D("service_project_deposit_price");
			$map = array("projectid"=>$project["id"], "id"=>$depositid);
			$depositprice = $depositmodel->where($map)->find();
			if(empty($depositprice)){
				E("服务项目的照护周期不存在，请联系客服");
			}
			if($project["time_type"] == 3 && $depositprice["month"] > $project["time"]){
				E("服务项目的照护周期异常，请联系客服");
			}

			if($project["time_type"] == 3){ //服务时长类型 月
				$days = intval($depositprice["month"])*30 - 1;
				$endtime = date("Y-m-d H:i", strtotime("+".$days." day", strtotime($begintime)));

				$time_length = $depositprice["month"];
			} else if($project["time_type"] == 2){
				$days = intval($data["days"]);
				if(empty($days)){
					$days = 1;
				}

				$time_length = $days*$time_length;

				$endtime = date("Y-m-d H:i", strtotime("+".$time_length." day", strtotime($begintime)));
			}
		} else if($service_type == 3){ //日间照护价格类型
			$begintime = $data["begintime"];
			if(empty($begintime)){
				E("请选择预约上门时间");
			}
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("请选择正确的时间格式");
			}

			$hourid = $data["hourid"];
			if(empty($hourid)){
				E("请选择日间照护周期");
			}

			$hourmodel = D("service_project_hour_price");
			$map = array("projectid"=>$project["id"], "id"=>$hourid);
			$hourprice = $hourmodel->where($map)->find();
			if(empty($hourprice)){
				E("服务项目的日间照护周期不存在，请联系客服");
			}

			$days = intval($data["days"]);
			if(empty($days)){
				$days = 1;
			}

			$time_length = $days;

			$endtime = strtotime("+".$hourprice["hour"]." hour", strtotime($begintime));
			$endtime = date("Y-m-d H:i", strtotime("+".$time_length." day", $endtime));

		} else{ // 时长类型为 天/月 - 下午13点前可预约当天
			$begintime = $data["begintime"];
			if(empty($begintime)){
				E("请选择预约上门时间");
			}
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("请选择正确的时间格式");
			}

			$btime = strtotime($begintime);
			switch ($project["time_type"]) {
				// case 0: //分钟
				// 	$endtime = strtotime("+".$project["time"]." minute", $btime);
				// 	break;
				// case 1: //小时
				// 	$endtime = strtotime("+".$project["time"]." hour", $btime);
				// 	break;
				case 2: //天
					$days = 1;
					if($project["time"] == 1){
						if(empty($days)){
							$days = 1;
						}
						$time_length = $days;
					}else{
						$days = $project['time'];
					}
					$endtime = strtotime("+".$days." day", $btime);
					break;
				case 3: //月
					$endtime = strtotime("+".$project["time"]." month", $btime);
					break;
			}
			if(empty($endtime)){
				E("服务项目的服务时长异常，预约失败");
			}
			$endtime = date("Y-m-d H:i", $endtime);
		}

		//预约时间判断
		$time = time();
		$outtime_st = strtotime("+3 hours", $time);
		$begintime_st = strtotime($begintime);
		if ($begintime_st < $outtime_st) {
			E("预约时间必须在三小时以后");
		}
		//上下午预约时间验证
		$b = date("Y-m-d", $begintime_st);
		$c = date('Y-m-d', $time);
		if ($b == $c) {//判断是否同一天
			$present_hours = date('H', $time);//当前小时
			if($present_hours >= 13){
				E("下午只能预约明天的订单");
			}
		}
		
		//联系人
		$contact = $usercare["contact"];
		//联系电话
		$mobile = $usercare["contact_mobile"];
		//性别
		$gender = 0;
		/* $gender = $usercare["gender"];
		switch ($gender) {
			case "男": $gender = 1; break;
			case "女": $gender = 2; break;
			default: $gender = 0; break;
		} */
		//其他要求
		$other_remark = $data["remark"];
		if($project["doctor"] == 1){
			$doctor_image = $data["image"];
			if(empty($doctor_image)){
				if(empty($other_remark)){
					E("请输入医嘱");
				}
			}
		}
		//省份
		$province = $usercare["province"];
		//城市
		$city = $usercare["city"];
		//区县
		$region = $usercare["region"];
		//定位地址
		$region_detail = $usercare["region_detail"];
		if(empty($region_detail)){
			E("请定位服务地址");
		}
		//详细地址
		$address = $usercare["address"];
		if(empty($address)){
			E("请输入详细地址");
		}
		//经度
		$longitude = $usercare["longitude"];
		//纬度
		$latitude = $usercare["latitude"];
		if(empty($longitude) || empty($latitude)){
			E("请选择服务区域");
		}
		//地理类型
		$geo = $usercare["address_type"];
		if($geo == 0){
			$geo = 1;
		} else {
			$geo = 2;
		}
		if($geo == 2){
			//医院名称
			$hospital = $usercare["hospital"];
			//科室
			$department = $usercare["department"];
			//病房
			$room = $usercare["ward"];
		}

		if($service_type == 1){ // 服务星级价格类型
			//服务项目星级
			$projectlevelid = $data["projectlevelid"];
			if(empty($projectlevelid)){
				E("请选择要预约的服务项目星级");
			}
			$levelmodel = D("service_project_level_price");
			$map = array("status"=>1, "projectid"=>$projectid, "id"=>$projectlevelid);
			$projectlevelprice = $levelmodel->where($map)->find();
			if(empty($projectlevelprice)){
				E("您预约的服务项目星级不存在");
			}
		}

		$ordermodel = D("service_order");

		//服务人员
		$serviceid = $data["serviceid"];
		if($serviceid){
			$usermodel = D("user");
			$map = array("up.status"=>1, "u.status"=>200, "u.id"=>$serviceid);
			$serviceuser = $usermodel->alias("u")->join("left join sj_user_profile as up on u.id=up.userid")
				->field("u.id,u.nickname,u.avatar,up.realname,up.mobile,up.major_level,up.service_level,up.plane_time")->where($map)->find();
			if(empty($serviceuser)){
				E("您预约的服务人员不存在");
			}

			//验证服务人员的爽约状况
			$planetime = $serviceuser["plane_time"];
			if(checkDateTime($planetime, "Y-m-d H:i:s")){
				$time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
				if($planetime > $time){
					E("您预约的服务人员存在爽约记录，3个月内不能接单");
				}
			}

			//验证服务人员的专业等级是否符合服务项目的专业等级要求
			if($serviceuser["major_level"] < $project["major_level"]){
				E("您预约的服务人员专业等级不符合服务项目的专业等级要求");
			}

			//验证服务人员是否关联服务项目
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$serviceid, "projectid"=>$projectid);
			$checkproject = $relationmodel->where($map)->find();
			if(empty($checkproject)){
				E("您预约的服务人员无法服务您预约的服务项目");
			}

			if($service_type == 1){ //服务星级价格类型
				//验证当前服务星级是否符合服务人员的服务星级
				if($projectlevelprice["service_level"] < $serviceuser["service_level"]){
					E("您预约的服务项目星级不符合服务人员的服务星级");
				}
			}

			//验证服务订单预约时间是否与服务人员服务时间冲突
			$begincondition=date('Y-m-d H:i:s',strtotime($begintime)-10800);
			$endcondition=date('Y-m-d H:i:s',strtotime($endtime)+10800);
			$service_userid = $serviceuser["id"];
			$map = array(
				"service_userid"=>$service_userid, "status"=>1, "execute_status"=>array("in", [1,2,3]), "admin_status"=>array("in", [0,1]),
				"_complex"=>array(
					"begintime"=>array(
						array("egt", $begincondition), array("elt", $endcondition), "and"
					),
					"endtime"=>array(
						array("egt", $begincondition), array("elt", $endcondition), "and"
					),
					"_complex"=>array(
						"begintime"=>array("egt", $begincondition), "endtime"=>array("elt", $endcondition)
					),
					"_logic"=>"or"
				)
			);
			$checktimecount = $ordermodel->where($map)->count();
			if($checktimecount > 0){
				E("当前服务项目的预约时间与服务人员的服务订单时间冲突，预约失败");
			}
		}

		//优惠券
		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}

		//订单标题
		$ordertitle = $project["title"];

		if($service_type == 1){ //服务星级价格类型
			//订单总金额
			$totalamount = $projectlevelprice["price"];

			//时长类型为天，且时长为1时 或者 特殊处理 - 居室清洁
			if(($project["time_type"] == 2 && $project["time"] == 1) || $projectid == 72){
				$totalamount = $totalamount * $days;
			}

			//续费价格
			$again_price = $projectlevelprice["again_price"];

		} else if($service_type == 2){ //服务护理价格类型
			if(empty($depositprice)){
				E("服务项目护理订金不存在，请联系客服");
			}

			//订金
			$totalamount = $depositprice["price"]; 
			if(empty($totalamount)){
				E("服务订单护理金额异常，请联系客服");
			}

			//缴费价格 - 尾款
			$again_price = 0;
		} else if($service_type == 3){ //日间照护价格类型
			//订单总金额
			$totalamount = $hourprice["price"] * $days;
			//续费价格
			$again_price = 0;
		}

		//平台补贴金额
		$platform_money = $project["platform_money"];

		//优惠券金额
		$coupon_money = 0;

		//检查是否使用优惠券
		if($couponid > 0){
			$couponmodel = D("user_coupon");
			$map = array("userid"=>$user["id"], "id"=>$couponid);
			$coupon = $couponmodel->where($map)->find();
			if(empty($coupon)){
				E("您选择的优惠券不存在");
			}
			if($coupon["status"] != 0){
				E("您选择的优惠券已经被使用");
			}
			if($coupon["use_end_date"] < date("Y-m-d")){
				E("您选择的优惠券已失效");
			}

			if($coupon["min_amount"] > $totalamount){
				E("订单总金额小于优惠券的最低使用金额：".$coupon["min_amount"]."元");
			}
			if($coupon['coupon_type']==2){
				if($coupon['service_id']==$project["id"]){
					$coupon['money']=$totalamount;
				}
			}
			
			//优惠券金额
			$coupon_money = $coupon["money"];

			//平台补贴金额 - 使用优惠券，平台补贴金额设置为0
			$platform_money = 0;
		}

		//订单支付金额
		$amount = $totalamount - $coupon_money;
		if($amount < 0){
			$amount = 0;
		}

		//是否服务单价 - 遵照医嘱/日间照护/居室清洁（特殊处理）
		$single = 0; $single_price = 0;
		if($project["assess"] == 0 && ($project["doctor"] == 1 || $project["time_type"] == 4 || $project["id"] == 72)){
			$single = 1;

			if($project["doctor"] == 1 || $project["id"] == 72){ // 遵照医嘱/居室清洁（特殊处理）
				$single_price = $projectlevelprice["price"];
			} else if($project["time_type"] == 4){ //日间照护
				$single_price = $hourprice["price"];
			}
		}

		$order = array(
			"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>2, "service_role"=>$project["service_role"],
			"categoryid"=>$project["categoryid"], "category"=>$project["categoryname"], "projectid"=>$project["id"],
			"title"=>$ordertitle, "thumb"=>$project["thumb"], //"service_level"=>$projectlevelprice["service_level"],
			"time_type"=>$project["time_type"], "time"=>$time_length, "begintime"=>$begintime, "endtime"=>$endtime,
			"careid"=>$usercareid, "contact"=>$contact, "mobile"=>$mobile, "gender"=>$gender,
			"language"=>$usercare["language"], "care_remark"=>$usercare["remark"], "other_remark"=>$other_remark, "doctor_image"=>$doctor_image, "doctor"=>$project["doctor"],
			"province"=>$province, "city"=>$city, "region"=>$region, "address"=>$address, "longitude"=>$longitude, "latitude"=>$latitude,'region_detail'=>$region_detail,
			"geo"=>$geo, "hospital"=>$hospital, "department"=>$department, "room"=>$room,
			"status"=>1, "admin_status"=>0, "execute_status"=>0, "pay_status"=>0, "couponid"=>$couponid, "coupon_money"=>$coupon_money, 
			"total_amount"=>$totalamount, "amount"=>$amount, "platform_money"=>$platform_money,
			"again_price"=>$again_price, "single"=>$single, "single_price"=>$single_price,
			"remark"=>'', "createdate"=>date("Y-m-d H:i:s"), "keyword"=>$ordertitle, "hybrid"=>$hybrid,'brokerage'=>$project['brokerage']
		);
		
		if($service_type == 2){ //服务护理价格类型
			$order["assess"] = 1;
			//待评估状态
			$order["assess_status"] = 1;
			//默认3星服务
			$order["service_level"] = 3;
		} else if($service_type == 1){ // 服务星级价格类型
			//服务星级
			$order["service_level"] = $projectlevelprice["service_level"];
		} else if($service_type == 3){ // 日间照护价格类型
			//默认3星服务
			$order["service_level"] = 3;
		}

		//检查是否指定服务人员
		if($serviceuser){
			$order["service_userid"] = $serviceuser["id"];
			$order["service_realname"] = $serviceuser["realname"];
			$order["service_avatar"] = $serviceuser["avatar"];
		}

		//检查订单是否免费
		if($amount <= 0){
			$order["pay_status"] = 3;
			$order["pay_date"] = date("Y-m-d H:i:s");
            $order['brokerage'] = 0;
		}else{
            $order['brokerage'] = $project['brokerage'];
        }

		$orderid = $order["id"] = $ordermodel->add($order);

        //检查订单是否免费 分销体系
        if($amount <= 0){
            D('Brokerage', 'Service')->orderSettle(3, $orderid);
        }

		//更新优惠券信息
		if($coupon){
			$entity = array("orderid"=>$orderid, "status"=>1, "use_type"=>3);
			$map = array("userid"=>$user["id"], "id"=>$coupon["id"]);
			$couponmodel->where($map)->save($entity);
		}

        $content = D('Moor', 'Service')->orderMessage($order["id"], 2);
        D('Moor', 'Service')->createContext($user["id"]);
        D('Moor', 'Service')->sendRobotTextMessage($user["id"], $content);

		return array(
			"title"=>$ordertitle, "orderid"=>$order["id"], "ordersn"=>$order["sn"],
			"amount"=>$amount, "coupon_money"=>$coupon_money, "createdate"=>date("Y/m/d")
		);
	}

	//创建服务续费订单
	public function createserviceagainorder(){
		$user = $this->AuthUserInfo;

        $data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择要续费的服务订单");
		}

		$ordermodel = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("续费的服务订单不存在");
		}
		if($order["type"] != 2){
			E("服务订单类型异常，续费失败");
		}
		if($order["status"] == 4){
			E("服务订单已完成，续费失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3 && $order["admin_status"] == 1
			&& $order["service_userid"] > 0 && in_array($order["execute_status"], [1,2,3]))){
			E("服务订单状态异常，续费失败");
		}
		
		$again_price = $order["again_price"];
		if($again_price <= 0){
			E("当前服务项目未开启续费，续费失败");
		}

		//优惠券
		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}
		
		//当前订单结束两小时内 服务人员有其他服务订单则无法续费
		$starttime=date('Y-m-d H:i:s',strtotime($order['endtime'])+7200);//订单结束两小时后的时间
		$map = array();
		$map['service_userid']=$order['service_userid'];
		$map['id']=array('neq',$order['id']);
		$map['begintime']=array(array('lt',$starttime),array('gt',$order['begintime']));
		$checkorder=$ordermodel->where($map)->find();
		if($checkorder){
			F('checkorder',$checkorder);
			return array('info'=>'抱歉，因该服务人员已有另一单即将开始，暂时无法继续为您服务，您可以联系客服帮您协调，造成不便，请您谅解。','code'=>'33');
		}

		//订单总金额
		$totalamount = $again_price;

		//优惠券金额
		$coupon_money = 0;

		//检查是否使用优惠券
		if($couponid > 0){
			$couponmodel = D("user_coupon");
			$map = array("userid"=>$user["id"], "id"=>$couponid);
			$coupon = $couponmodel->where($map)->find();
			if(empty($coupon)){
				E("您选择的优惠券不存在");
			}
			if($coupon["status"] != 0){
				E("您选择的优惠券已经被使用");
			}
			if($coupon["use_end_date"] < date("Y-m-d")){
				E("您选择的优惠券已失效");
			}

			if($coupon["min_amount"] > $totalamount){
				E("订单总金额小于优惠券的最低使用金额：".$coupon["min_amount"]."元");
			}
			if($coupon['coupon_type']==2){
				if($coupon['service_id']==$project["id"]){
					$coupon['money']=$totalamount;
				}
			}
			//优惠券金额
			$coupon_money = $coupon["money"];
		}

		//订单支付金额
		$amount = $totalamount - $coupon_money;
		if($amount < 0){
			$amount = 0;
		}

		//续费记录
		$entity = array(
			"userid"=>$user["id"], "orderid"=>$order["id"], "projectid"=>$order["projectid"],
			"title"=>$order["title"], "thumb"=>$order["thumb"], "couponid"=>$couponid, "coupon_money"=>$coupon_money,
			"total_amount"=>$totalamount, "amount"=>$amount, "createdate"=>date("Y-m-d H:i:s"), "type"=>1
		);

		$orderagainmodel = D("service_order_again_record");

		$map = array("userid"=>$user["id"], "orderid"=>$order["id"], "pay_status"=>0, "type"=>1);
		$againrecord = $orderagainmodel->where($map)->find();
		if($againrecord){
			$map = array("id"=>$againrecord["id"]);
			$orderagainmodel->where($map)->save($entity);
		} else{
			$entity["id"] = $orderagainmodel->add($entity);
			$againrecord = $entity;
		}

        $entity = array(
			"again_status"=>1, "again_recordid"=>$againrecord["id"]
		);
		$map = array("userid"=>$user["id"], "id"=>$order["id"]);
		$ordermodel->where($map)->save($entity);

		//更新优惠券信息
		if($coupon){
			$entity = array("orderid"=>$orderid, "status"=>1, "use_type"=>4);
			$map = array("userid"=>$user["id"], "id"=>$coupon["id"]);
			$couponmodel->where($map)->save($entity);
		}
		
		//推送消息给服务人员
		$msgpush = D("Common/IGeTuiMessagePush");
		$usermodel = D("user");
		
		//服务人员
		$orderuser = $usermodel->find($order["service_userid"]);
		if($orderuser){
			$clientid = $orderuser["clientid"];
			$system = $orderuser["system"];
			$title = "服务订单提醒";
			$content = "您好，《".$order["title"]."》已发起续费1小时要求,请确认。";
			$msgpush->PushMessageToSingle($clientid, $system, $title, $content,$ext=array('type'=>1,'id'=>$order['id']));
		}
		
		$title = $order['title'];
		$messagemodel = D("user_message");
		//新增 - 服务人员订单消息
		$content = '<p>';
		$content .= "【订单内容】：".$title."<br/>";
		$content .= "您好，《".$title."》已发起续费1小时要求,请确认。";
		$content .= '</p>';
		$message_entity = array(
		    "userid"=>$order["service_userid"], "title"=>$title, "content"=>$content,
		    "hybrid"=>"service", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
		);
		$messagemodel->add($message_entity);
		
		return array(
			"orderid"=>$order["id"], "ordersn"=>$order["sn"], 'amount'=>$amount, 
			"recordid"=>$againrecord, 'code'=>32
			);
	}

	//创建服务评估缴费订单
	public function createserviceassessorder(){
		$user = $this->AuthUserInfo;

        $data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择要缴费的服务订单");
		}

		$ordermodel = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("缴费的服务订单不存在");
		}
		if($order["type"] != 2){
			E("服务订单类型异常，缴费失败");
		}
		if($order["status"] == 4){
			E("服务订单已完成，缴费失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3 && $order["assess_status"] == 2
			&& $order["service_userid"] > 0 && $order["execute_status"] == 1)){
			E("服务订单状态异常，缴费失败");
		}
		
		$again_price = $order["again_price"];
		if($again_price <= 0){
			E("当前服务项目未开启缴费，缴费失败");
		}

		//优惠券
		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}

		//订单总金额
		$totalamount = $again_price;

		//优惠券金额
		$coupon_money = 0;

		//检查是否使用优惠券
		if($couponid > 0){
			$couponmodel = D("user_coupon");
			$map = array("userid"=>$user["id"], "id"=>$couponid);
			$coupon = $couponmodel->where($map)->find();
			if(empty($coupon)){
				E("您选择的优惠券不存在");
			}
			if($coupon["status"] != 0){
				E("您选择的优惠券已经被使用");
			}
			if($coupon["use_end_date"] < date("Y-m-d")){
				E("您选择的优惠券已失效");
			}

			if($coupon["min_amount"] > $totalamount){
				E("订单总金额小于优惠券的最低使用金额：".$coupon["min_amount"]."元");
			}
			if($coupon['coupon_type']==2){
				if($coupon['service_id']==$project["id"]){
					$coupon['money']=$totalamount;
				}
			}
			//优惠券金额
			$coupon_money = $coupon["money"];
		}

		//订单支付金额
		$amount = $totalamount - $coupon_money;
		if($amount < 0){
			$amount = 0;
		}

		//缴费记录
		$entity = array(
			"userid"=>$user["id"], "orderid"=>$order["id"], "projectid"=>$order["projectid"],
			"title"=>$order["title"], "thumb"=>$order["thumb"], "couponid"=>$couponid, "coupon_money"=>$coupon_money,
			"total_amount"=>$totalamount, "amount"=>$amount, "createdate"=>date("Y-m-d H:i:s"), "type"=>2
		);

		$orderagainmodel = D("service_order_again_record");

		$map = array("userid"=>$user["id"], "orderid"=>$order["id"], "pay_status"=>0, "type"=>2);
		$againrecord = $orderagainmodel->where($map)->find();
		if($againrecord){
			$map = array("id"=>$againrecord["id"]);
			$orderagainmodel->where($map)->save($entity);
		} else{
			$entity["id"] = $orderagainmodel->add($entity);
			$againrecord = $entity;
		}

        $entity = array(
			"again_status"=>1, "again_recordid"=>$againrecord["id"]
		);
		$map = array("userid"=>$user["id"], "id"=>$order["id"]);
		$ordermodel->where($map)->save($entity);

		//更新优惠券信息
		if($coupon){
			$entity = array("orderid"=>$orderid, "status"=>1, "use_type"=>5);
			$map = array("userid"=>$user["id"], "id"=>$coupon["id"]);
			$couponmodel->where($map)->save($entity);
		}

		return array("orderid"=>$order["id"], "ordersn"=>$order["sn"], 'amount'=>$amount, "recordid"=>$againrecord,'info'=>'您好，您的续费要求已收到，请待服务人员确认后，再支付续费费用。温馨提示：请您知悉按次服务的项目仅可在订单服务时间结束前10分钟续单，续单服务时间为60分钟，一个订单仅可续费1次。','code'=>32);
	}

	//取消订单
	public function cancelorder(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要取消的订单");
		}

		$model = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["status"] == 4){
			E("订单已完成，无法取消");
		}
		if($order["status"] != 1 || $order["pay_status"] != 0){
			E("订单状态异常，操作失败");
		}

		$entity = array("status"=>2);
		$model->where($map)->save($entity);
		
		return;
	}

	//删除订单
	public function deleteorder(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要删除的订单");
		}

		$model = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["status"] == 4){
			E("订单已完成，无法删除");
		}
		if($order["pay_status"] == 3){
			E("订单已支付，无法删除");
		}

        //检查订单为已超时才可进行删除
        $time = time();
        $outtime = strtotime("+30 minute", strtotime($order["createdate"]));
        if($order["status"] == 1 && $order["pay_status"] == 0 && $outtime >= $time){
			E("订单状态异常，操作失败");
        }
		if(!in_array($order["status"], [1,2]) || $order["pay_status"] != 0){
			E("订单状态异常，操作失败");
		}

		$entity = array("status"=>-1);
		$model->where($map)->save($entity);

		return;
	}

	//申请退款
	public function refundorder(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择申请退款的订单");
		}

		$model = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if(!(in_array($order["status"], [1,4]) && $order["pay_status"] == 3)){
			E("订单状态异常，操作失败");
		}

		$reason = I("post.reason");
		if(empty($reason)){
			E("请输入申请退款的原因");
		}

		$images = I("post.images");

		//新增订单售后信息
		$refundmodel = D("service_order_refund");
		$entity = array(
			"userid"=>$user["id"], "orderid"=>$order["id"], "reason"=>$reason, "images"=>$images,
			"createdate"=>date("Y-m-d H:i:s"), "status"=>1
		);
		$refundmodel->add($entity);

		//更新订单的售后状态
		$entity = array("status"=>5);
		$model->where($map)->save($entity);

		//计算服务人员的服务平均分
		$this->calccommentpercent($order["service_userid"]);

		return;
	}

	//确认开始服务
	public function orderstart(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要操作的订单");
		}

		$model = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3 && $order["service_userid"] != 0)){
			E("订单状态异常，操作失败");
		}
		//1为服务人员开始服务，客户才可确认开始服务
		if($order["execute_status"] != 1){
			E("订单服务状态异常，操作失败");
		}
		
		//更新订单服务状态 - 2=开始服务
		$entity = array("execute_status"=>2, "execute_time"=>date("Y-m-d H:i:s"));
		$model->where($map)->save($entity);

		//服务交互记录
		$recordmodel = D("service_order_record");
		$record_entity = array(
			"orderid"=>$orderid, "userid"=>$user["id"], "title"=>"确认开始服务",
			"execute_status"=>2, "updatetime"=>date("Y-m-d H:i:s")
		);
		$recordmodel->add($record_entity);
		
		return;
	}

	//确认完成服务
	public function ordercompleted(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要操作的订单");
		}

		$model = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3 && $order["service_userid"] != 0)){
			E("订单状态异常，操作失败");
		}
		if($order["execute_status"] != 3){
			E("订单服务状态异常，操作失败");
		}
		
		//更新订单服务状态 - 4=确认服务完成
		$entity = array("execute_status"=>4, "execute_time"=>date("Y-m-d H:i:s"), "status"=>4);
		$model->where($map)->save($entity);

		//服务交互记录
		$recordmodel = D("service_order_record");
		$record_entity = array(
			"orderid"=>$orderid, "userid"=>$user["id"], "title"=>"确认服务完成",
			"execute_status"=>4, "updatetime"=>date("Y-m-d H:i:s")
		);
		
		//完成服务发放积分
		$user = D('user')->where(array('id'=>$user['id']))->find();
		if($user['level']>0){
			//购物发放积分 1元=2分
			$data=['remark'=>'会员购买服务获得积分','tag'=>'shopping'];
			$point = $order['amount']*2;
			D('PointLog','Service')->append($user['id'],$point,$data);
		}else{
			//购物发放积分 1元=1.5分
			$data=['remark'=>'购买服务获得积分','tag'=>'shopping'];
			$point = floor($order['amount']*1.5);
			D('PointLog','Service')->append($user['id'],$point,$data);
		}
		
		$recordmodel->add($record_entity);
		
		//记录到服务人员的佣金表
		$withdrawal = $order['amount']*0.8;
		$commission = array(
			'status'=>0,'user_id'=>$order['service_userid'],'achievement'=>$order['amount'],
			'withdrawal'=>$withdrawal,'order_id'=>$order['id'],'order_sn'=>$order['sn'],
			'title'=>$order['title'],'subsidy'=>$order['platform_money'],
			'createtime'=>date('Y-m-d H:i:s')
		);
		
		D('service_commission')->add($commission);
		
		$money = $withdrawal + $order['platform_money'];
		$map = array('userid'=>$order['service_userid']);
		D('user_profile')->where($map)->setInc('money',$money);
		
		return;
	}

    //取消售后订单
    public function cancelrefund(){
        $user = $this->AuthUserInfo;

        $orderid = I("post.orderid", 0);
        if(empty($orderid)){
            E("请选择要取消的售后订单");
        }
        $model = D("service_order");

        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $order = $model->where($map)->find();
        if(empty($order)){
            E("订单不存在，操作失败");
        }
        if(!($order["status"] == 5 && $order["pay_status"] == 3)){
            E("订单状态异常，操作失败");
        }
        //订单状态改变
        $entity = array('status'=>1);
        if ($order['execute_status'] == 4) {
            //服务完成
            $entity = array('status'=>4);
        }
        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $model->where($map)->save($entity);

		//计算服务人员的服务平均分
		$this->calccommentpercent($order["service_userid"]);

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

		$map = array("o.userid"=>$user["id"], "o.id"=>$orderid);
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
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$ordermodel->where($map)->save($entity);

		return;
	}
	
	//小程序创建服务订单
	public function xcxcreateserviceorder(){
		$user = $this->AuthUserInfo;
		
		$data = I("post.");
	
		//订单来源
		$hybrid = $this->GetHttpHeader("platform");
		
		if(empty($data['remark'])){
			$remark = '';
		}else{
			$remark = $data['remark'];
		}
		
		//其他要求
		$other_remark = $data["remark"];
		if($project["doctor"] == 1){
			$doctor_image = $data["image"];
			if(empty($doctor_image)){
				if(empty($other_remark)){
					E("请输入医嘱");
				}
			}
		}
		
		//服务项目
		$projectid = $data["projectid"];
		if(empty($projectid)){
			E("请选择要预约的服务项目");
		}
		
		$projectmodel = D("service_project");
		$map = array("sp.status"=>1, "sp.id"=>$projectid);
		$project = $projectmodel->alias("sp")->join("left join sj_service_category as sc on sp.categoryid=sc.id")
			->field("sc.title as categoryname,sc.role as service_role,sp.*")->where($map)->find();
		if(empty($project)){
			E("您预约的服务项目不存在");
		}
		if($project["service_role"] == 3){
			if($project["time_type"] == 3){
				$service_type = 2; //服务护理价格类型
			} else{
				$service_type = 1; //服务星级价格类型
			}
		} else{
			$service_type = 3; //服务项目价格类型
		}
	
		//照护人
		$usercareid = $data["usercareid"];
		if(empty($usercareid)){
			E("请选择护理人");
		}
		$caremodel = D("user_care");
		$map = array("userid"=>$user["id"], "id"=>$usercareid);
		$usercare = $caremodel->where($map)->find();
		if(empty($usercare)){
			E("请添加被照护人");
		}
		if(empty($usercare["birth"])){
			E("护理人信息异常，预约失败");
		}
		$age = getAge($usercare["birth"]);
		if($age < 18){
			E("护理人年龄不足18岁，非服务对象，谢谢关注");
		}
	
		$time_length = $project["time"];
		//预约上门时间 
		if(in_array($project["time_type"], [0,1])){ // 时长类型为 分钟和小时 - 一天后
			$timeid = $data["timeid"];
			if(empty($timeid)){
				E("请选择预约上门时间");
			}
	
			$timemodel = D("service_time");
			$map = array("status"=>1, "projectid"=>$project["id"], "id"=>$timeid);
			$servicetime = $timemodel->where($map)->find();
			if(empty($servicetime)){
				E("您选择的预约上门时间不存在");
			}
	
			//一天后
			$day = $servicetime["days"];
			$date = date("Y-m-d" ,strtotime("+".$day." day", time()));
			$begintime = $date." ".$servicetime["begintime"];
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("预约开始时间格式不正确");
			}
			$endtime = $date." ".$servicetime["endtime"];
			if(!checkDateTime($endtime, "Y-m-d H:i")){
				E("服务结束时间格式不正确");
			}
		} else if($service_type == 2){ // 服务护理价格类型 - 三天后 (service_role == 3 && time_type == 3)
			$begintime = $data["begintime"];
			if(empty($begintime)){
				E("请选择预约上门时间");
			}
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("请选择正确的时间格式");
			}
			$mintime = date("Y-m-d", strtotime("+12 hour", time()));
			if($begintime < $mintime){
				E("只能预约半天后");
			}
	
			$depositid = $data["depositid"];
			if(empty($depositid)){
				E("请选择照护周期月数");
			}
	
			$depositmodel = D("service_project_deposit_price");
			$map = array("projectid"=>$project["id"], "id"=>$depositid);
			$depositprice = $depositmodel->where($map)->find();
			if(empty($depositprice)){
				E("服务项目的照护周期月数不存在，请联系客服");
			}
			if($depositprice["month"] > $project["time"]){
				E("服务项目的照护周期月数异常，请联系客服");
			}
			$days = intval($depositprice["month"])*30 - 1;
			$endtime = date("Y-m-d H:i", strtotime("+".$days." day", strtotime($begintime)));
	
			$time_length = $depositprice["month"];
		} else{ // 时长类型为天和服务角色是家护师 或者 其它服务角色和 天/月 - 三天后
			$begintime = $data["begintime"];
			if(empty($begintime)){
				E("请选择预约上门时间");
			}
			if(!checkDateTime($begintime, "Y-m-d H:i")){
				E("请选择正确的时间格式");
			}
			$mintime = date("Y-m-d", strtotime("+12 hour", time()));
			if($begintime < $mintime){
				E("预约上门时间必须是 ".$mintime." 之后");
			}
			// $time = time();
			// $outtime_st = strtotime("+3 hours", $time);
			// $begintime_st = strtotime($begintime);
			// if ($begintime_st < $outtime_st) {
			// 	E("预约时间必须在三小时以后");
			// }
			// //上下午预约时间验证
			// $b = date("Y-m-d", $begintime_st);
			// $c = date('Y-m-d', $time);
			// if ($b == $c) {//判断是否同一天
			// 	$present_hours = date('H', $time);//当前小时
			// 	$begin_hours = date("H", $begintime_st);//预约小时
			// 	if ($present_hours < 12 && $begin_hours < 12) {
			// 		E("上午只能预约下午的订单");
			// 	}else if($present_hours > 12){
			// 		E("下午只能预约明天的订单");
			// 	}
			// }
			// $maxtime = strtotime("+3 day", strtotime(date("Y-m-d", $time)));
			// if ($begintime_st > $maxtime) {
			// 	E('预约时间不能大于三天');
			// }
	
			$btime = strtotime($begintime);
			switch ($project["time_type"]) {
				// case 0: //分钟
				// 	$endtime = strtotime("+".$project["time"]." minute", $btime);
				// 	break;
				// case 1: //小时
				// 	$endtime = strtotime("+".$project["time"]." hour", $btime);
				// 	break;
				case 2: //天
					$endtime = strtotime("+".$project["time"]." day", $btime);
					break;
				case 3: //月
					$endtime = strtotime("+".$project["time"]." month", $btime);
					break;
			}
			if(empty($endtime)){
				E("服务项目的服务时长异常，预约失败");
			}
			$endtime = date("Y-m-d H:i",$endtime);
		}
		
		//联系人
		$contact = $usercare["contact"];
		if(empty($contact)){
			E("请输入联系姓名");
		}
		//联系电话
		$mobile = $usercare["contact_mobile"];
		if(empty($mobile)){
			E("请输入联系手机号码");
		}
		if(!isMobile($mobile)){
			E("手机号码格式不正确");
		}
		$gender = 0;
		/* $gender = $data["gender"];
		if(!in_array($gender, [0,1,2])){
			E("请选择性别要求");
		} */
		//其他要求
		$other_remark = $data["other_remark"];
		//省份
		$province = $usercare["province"];
		//城市
		$city = $usercare["city"];
		//区县
		$region = $usercare["region"];
		//定位地址
		$region_detail = $usercare["region_detail"];
		//详细地址
		$address = $usercare["address"];
		//经度
		$longitude = $usercare["longitude"];
		//纬度
		$latitude = $usercare["latitude"];
		if(empty($longitude) || empty($latitude)){
			E("请选择服务区域");
		}
		//地理类型
		$geo = $usercare["address_type"];
		if($geo == 0){
			$geo = 1;
		} else {
			$geo = 2;
		}
		if($geo == 2){
			//医院名称
			$hospital = $usercare["hospital"];
			//科室
			$department = $usercare["department"];
			//病房
			$room = $usercare["ward"];
		}
	
		if($service_type == 1){ // 服务星级价格类型 （service_role == 3 && time_type != 3）
			//服务项目星级
			$projectlevelid = $data["projectlevelid"];
			if(empty($projectlevelid)){
				E("请选择要预约的服务项目星级");
			}
			$levelmodel = D("service_project_level_price");
			$map = array("status"=>1, "projectid"=>$projectid, "id"=>$projectlevelid);
			$projectlevelprice = $levelmodel->where($map)->find();
			if(empty($projectlevelprice)){
				E("您预约的服务项目星级不存在");
			}
		}
	
		$ordermodel = D("service_order");
	
		//服务人员
		$serviceid = $data["serviceid"];
		if($serviceid){
			$usermodel = D("user");
			$map = array("up.status"=>1, "u.status"=>200, "u.id"=>$serviceid);
			$serviceuser = $usermodel->alias("u")->join("left join sj_user_profile as up on u.id=up.userid")
				->field("u.id,u.nickname,u.avatar,up.realname,up.mobile,up.major_level,up.service_level,up.plane_time")->where($map)->find();
			if(empty($serviceuser)){
				E("您预约的服务人员不存在");
			}
	
			//验证服务人员的爽约状况
			$planetime = $serviceuser["plane_time"];
			if(checkDateTime($planetime, "Y-m-d H:i:s")){
				$time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
				if($planetime > $time){
					E("您预约的服务人员存在爽约记录，3个月内不能接单");
				}
			}
	
			//验证服务人员的专业等级是否符合服务项目的专业等级要求
			if($serviceuser["major_level"] < $project["major_level"]){
				E("您预约的服务人员专业等级不符合服务项目的专业等级要求");
			}
	
			//验证服务人员是否关联服务项目
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$serviceid, "projectid"=>$projectid);
			$checkproject = $relationmodel->where($map)->find();
			if(empty($checkproject)){
				E("您预约的服务人员无法服务您预约的服务项目");
			}
	
			if($service_type == 1){ //服务星级价格类型 （service_role == 3 && time_type != 3）
				//验证当前服务星级是否符合服务人员的服务星级
				if($projectlevelprice["service_level"] < $serviceuser["service_level"]){
					E("您预约的服务项目星级不符合服务人员的服务星级");
				}
			}
	
			//验证服务订单预约时间是否与服务人员服务时间冲突
			$service_userid = $serviceuser["id"];
			$begincondition=date('Y-m-d H:i:s',strtotime($begintime)-10800);
			$endcondition=date('Y-m-d H:i:s',strtotime($endtime)+10800);
			$map = array(
				"service_userid"=>$service_userid, "status"=>1, "execute_status"=>array("in", [0,1,2,3]), "admin_status"=>array("in", [0,1]),'pay_status'=>3,
				"_complex"=>array(
					"begintime"=>array(
						array("egt", $begincondition), array("elt", $endcondition), "and"
					),
					"endtime"=>array(
						array("egt", $begincondition), array("elt", $endcondition), "and"
					),
					"_complex"=>array(
						"begintime"=>array("egt", $begincondition), "endtime"=>array("elt", $endcondition)
					),
					"_logic"=>"or"
				)
			);
			$checktimecount = $ordermodel->where($map)->count();
			if($checktimecount > 0){
				E("当前服务项目的预约时间与服务人员的服务订单时间冲突，预约失败");
			}
		}
	
		//优惠券
		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}
	
		//订单标题
		$ordertitle = $project["title"];
	
		if($service_type == 1){ //服务星级价格类型
			//订单总金额
			$totalamount = $projectlevelprice["price"];
			//续费价格
			$again_price = $projectlevelprice["again_price"];
	
		} else if($service_type == 2){ //服务护理价格类型
			if(empty($depositprice)){
				E("服务项目护理订金不存在，请联系客服");
			}
	
			/*switch ($usercare["level"]) {
				case 1: //半护理价格
					$totalamount = $depositprice["one_price"];
					break;
				case 2: //全护理价格
					$totalamount = $depositprice["two_price"];
					break;
				case 3: //特重护理价格
					$totalamount = $depositprice["three_price"];
					break;
			}*/
			
			//固定的订金
			$totalamount = $depositprice["price"];
			
			if(empty($totalamount)){
				E("服务订单护理金额异常，请联系客服");
			}
	
			//缴费价格 - 尾款
			$again_price = 0;
	
		} else if($service_type == 3){ //服务项目价格类型
			//订单总金额
			$totalamount = $project["price"];
			//续费价格
			$again_price = 0;
		}
	
		//平台补贴金额
		$platform_money = $project["platform_money"];
	
		//优惠券金额
		$coupon_money = 0;
	
		//检查是否使用优惠券
		if($couponid > 0){
			$couponmodel = D("user_coupon");
			$map = array("userid"=>$user["id"], "id"=>$couponid);
			$coupon = $couponmodel->where($map)->find();
			if(empty($coupon)){
				E("您选择的优惠券不存在");
			}
			if($coupon["status"] != 0){
				E("您选择的优惠券已经被使用");
			}
			if($coupon["use_end_date"] < date("Y-m-d")){
				E("您选择的优惠券已失效");
			}
	
			if($coupon["min_amount"] > $totalamount){
				E("订单总金额小于优惠券的最低使用金额：".$coupon["min_amount"]."元");
			}
			if($coupon['coupon_type']==2){
				if($coupon['service_id']==$project["id"]){
					$coupon['money']=$totalamount;
				}
			}
			//优惠券金额
			$coupon_money = $coupon["money"];
	
			//平台补贴金额 - 使用优惠券，平台补贴金额设置为0
			$platform_money = 0;
		}
	
		//订单支付金额
		$amount = $totalamount - $coupon_money;
		if($amount < 0){
			$amount = 0;
		}
	
		$order = array(
			"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>2, "service_role"=>$project["service_role"],
			"categoryid"=>$project["categoryid"], "category"=>$project["categoryname"], "projectid"=>$project["id"],
			"title"=>$ordertitle, "thumb"=>$project["thumb"], //"service_level"=>$projectlevelprice["service_level"],
			"time_type"=>$project["time_type"], "time"=>$time_length, "begintime"=>$begintime, "endtime"=>$endtime,
			"careid"=>$usercareid, "contact"=>$contact, "mobile"=>$mobile, "gender"=>$gender,
			"language"=>$usercare["language"], "care_remark"=>$usercare["remark"], "other_remark"=>$other_remark,
			"province"=>$province, "city"=>$city, "region"=>$region, "address"=>$address, "longitude"=>$longitude, "latitude"=>$latitude,'region_detail'=>$region_detail,
			"geo"=>$geo, "hospital"=>$hospital, "department"=>$department, "room"=>$room,
			"status"=>1, "admin_status"=>0, "execute_status"=>0, "pay_status"=>0, "couponid"=>$couponid, "coupon_money"=>$coupon_money, 
			"total_amount"=>$totalamount, "amount"=>$amount, "platform_money"=>$platform_money,
			"remark"=>'', "createdate"=>date("Y-m-d H:i:s"), "keyword"=>$ordertitle, "again_price"=>$again_price, "hybrid"=>$hybrid
		);
		
		if($service_type == 2){ //服务护理价格类型
			//待评估状态
			$order["assess_status"] = 1;
			//默认3星服务
			$order["service_level"] = 3;
		} else if($service_type == 1){ // 服务星级价格类型
			//服务星级
			$order["service_level"] = $projectlevelprice["service_level"];
		}
	
		//检查是否指定服务人员
		if($serviceuser){
			$order["service_userid"] = $serviceuser["id"];
			$order["service_realname"] = $serviceuser["realname"];
			$order["service_avatar"] = $serviceuser["avatar"];
		}
	
		//检查订单是否免费
		if($amount <= 0){
			$order["pay_status"] = 3;
			$order["pay_date"] = date("Y-m-d H:i:s");
			
		}
	
		$orderid = $order["id"] = $ordermodel->add($order);
	
		//更新优惠券信息
		if($coupon){
			$entity = array("orderid"=>$orderid, "status"=>1, "use_type"=>3);
			$map = array("userid"=>$user["id"], "id"=>$coupon["id"]);
			$couponmodel->where($map)->save($entity);
		}
	
		return array(
			"title"=>$ordertitle, "orderid"=>$order["id"], "ordersn"=>$order["sn"],
			"amount"=>$amount, "coupon_money"=>$coupon_money, "createdate"=>date("Y/m/d")
		);
	}
}