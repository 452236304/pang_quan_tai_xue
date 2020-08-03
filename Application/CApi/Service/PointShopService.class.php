<?php
namespace CApi\Service;

class PointShopService{
    //创建商品订单
    public function create_product_order($user,$product_id,$attribute_id,$quantity,$addressid,$point_shop_id){
    	//积分商品ID
    	$point_shop = D('point_shop')->where(array('id'=>$point_shop_id))->find();
    	
    	//收货地址
    	$addressmodel = D("user_address");
    	$map = array("userid"=>$user["id"], "id"=>$addressid);
    	$address = $addressmodel->where($map)->find();
    	if(empty($address)){
    		E("收货地址不存在");
    	}
    	
    	$productmodel = D("product");
    	$map = array("p.status"=>1, "p.id"=>$product_id, "a.id"=>$attribute_id);
    	$products = $productmodel->alias("p")->join("left join sj_product_attribute as a on a.productid=p.id")->field("p.id,p.company_id,p.status,p.title,p.subtitle,p.thumb,a.id as attributeid,a.title as attributetitle,a.price,a.stock,p.freightid,p.brokerage")->where($map)->select();
    	
		if($point_shop['price'] == 0){
			$remark['remark'] = '购买积分商城商品'.$point_shop['title'].'-'.$point_shop['subtitle'];
			$result = D('PointLog','Service')->append($user['id'],-$point_shop['point'],$remark);
			E('积分不足');
			$pay_date = date('Y-m-d H:i:s');
			$pay_status = 3;
		}else{
			$pay_status = 0;
		}
		
    	//运费模版集合
    	$freight_temp = array();
    	$company_id = 0;
    	$totalamount = 0;
    	foreach($products as $k=>$v){
    		$productids[]=$v;
    		if($now == 1){ //立即购买，默认购买数量为1
    			$v["quantity"] = $quantity;
    		}
    		
    		$amount = floatval($point_shop["price"]) * floatval($quantity);
    		
    		//订单总金额
    		$totalamount = $totalamount + $amount;
    		$totalpoint = floatval($point_shop["point"]) * floatval($quantity);
    		//订单标题
    		$ordertitle[] = $v["title"];
    	    
    		//订单商品
    		$orderproduct[] = array(
    			"userid"=>$user["id"], "productid"=>$v["id"], "title"=>$v["title"], "thumb"=>$v["thumb"],
    			"attributeid"=>$v["attributeid"], "attributetitle"=>$v["attributetitle"],"price"=>$amount, "quantity"=>$quantity, 'brokerage' => $v['brokerage'],'point'=>$totalpoint
    		);
    	    
    		$products[$k] = $v;
    	    
    		$temp = $freight_temp["temp_".$v["freightid"]];
    		if($temp){
    			$temp["amount"] += $amount;
    		} else{
    			$temp = array("id"=>$v["freightid"], "amount"=>$amount);
    		}
    		$freight_temp["temp_".$v["freightid"]] = $temp;
    		$company_id = $v['company_id'];
    	}
    	    
    	//订单标题
    	$ordertitle = join(",", $ordertitle);
    	    
    	//优惠券金额
    	$coupon_money = 0;
    	    
    	//运费
    	$freight = 0;
    	$freightmodel = D("product_order_freight");
    	foreach($freight_temp as $k=>$v){
    		if($v["id"] == 0){
    			continue;
    		}
    		$temp = $freightmodel->find($v["id"]);
    		if($temp && $temp["full_amount"] > $v["amount"]){
    			$freight += floatval($temp["money"]);
    		}
    	}
    	
    	//订单总金额
    	$totalamount += $freight;
    	    
    	//订单支付金额
    	$amount = $totalamount - $coupon_money;
    	$createdate=date("Y-m-d H:i:s");
    	$order = array(
    		"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>0, "title"=>$ordertitle, "status"=>1, "pay_status"=>$pay_status,'pay_date'=>$pay_date,'company_id'=>$company_id,
    		"consignee"=>$address["consignee"], "mobile"=>$address["mobile"], "province"=>$address["province"], "city"=>$address["city"], "region"=>$address["region"],
    		"address"=>$address["address"], "couponid"=>0, "coupon_money"=>0, "freight"=>$freight, "total_amount"=>$totalamount, "amount"=>$amount,
    		"remark"=>"", "createdate"=>$createdate, "keyword"=>$ordertitle,"hybrid"=>$hybrid,'is_point_shop'=>1,'point'=>$totalpoint
    	);
    	
    	$ordermodel = D("product_order");
    	    
    	$orderid = $order["id"] = $ordermodel->add($order);
    	
    	$orderproductmodel = D("product_order_product");
    	//新增订单商品
    	foreach($orderproduct as $k=>$v){
    		$v["orderid"] = $orderid;
    	    
    		$orderproductmodel->add($v);
    	    
    		//$this->updateproductstock($v["attributeid"],$v['quantity']);
    	    
    	}
		
		
		
    	return array("orderid"=>$orderid, "ordersn"=>$order["sn"], "amount"=>$amount,'coupon_money'=>$coupon_money,'freight'=>$freight,'point'=>$totalpoint,'createdate'=>date('Y/m/d'),"title"=>$ordertitle);
    }
	
	//检查商品订单
	public function check_product_order($user,$product_id,$attribute_id,$quantity,$addressid,$point_shop_id){
		//积分商品ID
		$point_shop = D('point_shop')->where(array('id'=>$point_shop_id))->find();
		
		//收货地址
		$addressmodel = D("user_address");
		$map = array("userid"=>$user["id"], "id"=>$addressid);
		$address = $addressmodel->where($map)->find();
		
		$productmodel = D("product");
		$map = array("p.status"=>1, "p.id"=>$product_id, "a.id"=>$attribute_id);
		$products = $productmodel->alias("p")->join("left join sj_product_attribute as a on a.productid=p.id")->field("p.id,p.company_id,p.status,p.title,p.subtitle,p.thumb,a.id as attributeid,a.title as attributetitle,a.price,a.stock,p.freightid,p.brokerage")->where($map)->select();
		
		//运费模版集合
		$freight_temp = array();
		$company_id = 0;
		$totalamount = 0;
		foreach($products as $k=>$v){
			$productids[]=$v;
			$v['quantity'] = $quantity;
			$v['price'] = $point_shop['price'];
			$v['point'] = $point_shop['point'];
			$amount = floatval($point_shop["price"]) * floatval($quantity);
			
			//订单总金额
			$totalamount = $totalamount + $amount;
			$totalpoint = floatval($point_shop["point"]) * floatval($quantity);
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			
			$products[$k] = $v;
		    
			$temp = $freight_temp["temp_".$v["freightid"]];
			if($temp){
				$temp["amount"] += $amount;
			} else{
				$temp = array("id"=>$v["freightid"], "amount"=>$amount);
			}
			$freight_temp["temp_".$v["freightid"]] = $temp;
			$company_id = $v['company_id'];
			
		}
		    
		//订单标题
		$ordertitle = join(",", $ordertitle);

		    
		//运费
		$freight = 0;
		$freightmodel = D("product_order_freight");
		foreach($freight_temp as $k=>$v){
			if($v["id"] == 0){
				continue;
			}
			$temp = $freightmodel->find($v["id"]);
			if($temp && $temp["full_amount"] > $v["amount"]){
				$freight += floatval($temp["money"]);
			}
		}
		
		//订单总金额
		$totalamount += $freight;
		    
		//订单支付金额
		$amount = $totalamount;
		
		return array('products'=>$products,"amount"=>$amount,'freight'=>$freight,'point'=>$totalpoint,'address'=>$address,'surplus'=>$user['point']);
	}
	
	public function create_service_order($user,$data,$hybrid,$point_shop_id){
		//积分商品
		$point_shop = D('point_shop')->where(array('id'=>$point_shop_id))->find();
		
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
		//积分商城金额
		$totalamount = $point_shop['price'];
		//积分商城不设置续费
		$again_price = 0;
		//平台补贴金额
		$platform_money = 0;
		
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
			"remark"=>'', "createdate"=>date("Y-m-d H:i:s"), "keyword"=>$ordertitle, "hybrid"=>$hybrid,'brokerage'=>$project['brokerage'],'point'=>$point_shop['point'],'is_point_shop'=>1
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
			$remark['remark'] = '购买积分商城商品'.$point_shop['title'].'-'.$point_shop['subtitle'];
			$result = D('PointLog','Service')->append($user['id'],-$point_shop['point'],$remark);
			E('积分不足');
			$order["pay_status"] = 3;
			$order["pay_date"] = date("Y-m-d H:i:s");
		    $order['brokerage'] = 0;
		}else{
		    $order['brokerage'] = $project['brokerage'];
		}
		
		$orderid = $order["id"] = $ordermodel->add($order);
		
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
	
	//检查积分服务订单
	public function check_service_order($user,$projectid,$point_shop_id){
		//积分商品
		$point_shop = D('point_shop')->where(array('id'=>$point_shop_id))->find();
		
		if(empty($projectid)){
			E("请选择要预约的服务项目");
		}
		
		//服务项目
		$model = D("service_project");
		$map = array("p.status"=>1, "p.id"=>$projectid);
		$project = $model->alias("p")->join("left join sj_service_category as c on p.categoryid=c.id")
			->field("p.*,c.role as service_role")->where($map)->find();
		if(empty($project)){
			E("您预约的服务项目不存在");
		}
		
		if($project["assess"] == 1){ //线下评估
			$depositmodel = D("service_project_deposit_price");
			$map = array("projectid"=>$project["id"]);
			$depositprices = $depositmodel->where($map)->order("month asc")->select();
		
			$service_type = 2; //服务护理价格类型
		} else if($project["time_type"] == 4){ 
			$service_type = 3; //日间照护价格类型
		} else{
			$service_type = 1; //服务星级价格类型
		}
		
		if($service_type == 1){ //服务星级价格类型
			//服务项目星级价格 - 时长类型为 分/时
			$levelmodel = D("service_project_level_price");
			$map = array("status"=>1, "projectid"=>$projectid);
			$levelprices = $levelmodel->where($map)->order("service_level asc")->select();
			if(count($levelprices) <= 0){
				E("您预约的服务项目暂未开通服务");
			}
		} else if($service_type == 3){ //日间照护价格类型
			$hourmodel = D("service_project_hour_price");
			$map = array("status"=>1, "projectid"=>$projectid);
			$hourprices = $hourmodel->where($map)->order("hour asc")->select();
			if(count($hourprices) <= 0){
				E("您预约的服务项目暂未开通服务");
			}
		}
		
		if(in_array($project["time_type"], [0,1])){ //服务时间 - 分钟、小时
			$timemodel = D("service_time");
		
			$map = array("status"=>1, "projectid"=>$project["id"]);
			$time_list = $timemodel->where($map)->order("days asc, begintime asc")->select();
		
			$current_hour = date("H", time());
			for($i=0;$i<=15;$i++){
				if(($i == 0 && $current_hour >= 13) || ($current_hour < 13 && $i == 15)){
					continue;
				}
		
				$date = date("Y-m-d", strtotime("+".$i." day", time()));
				$week = getWeek($date);
				$item = array("id"=>$i+1, "title"=>"第".($i+1)."天", "date"=>$date, "week"=>$week, "list"=>[], "count"=>0);
				foreach($time_list as $k=>$v){
					if($v["days"] == $i+1){
						$v["date"] = $date;
						if($i == 0 && $current_hour < 13){
							if($v["begintime"] >= ($current_hour+4).":00"){
								$item["list"][] = $v;
							}
						} else{
							$item["list"][] = $v;
						}
					}
				}
				$item["count"] = count($item["list"]);
				
				$servicetime[] = $item;
			}
		} else{ //服务时间 - 天、月、日间
			for($i=0;$i<=15;$i++){
				$date = date("Y-m-d", strtotime("+".$i." day", time()));
				$week = getWeek($date);
				$item = array("id"=>$i+1, "title"=>"第".($i+1)."天", "date"=>$date, "week"=>$week, "list"=>[], "count"=>0);
		
				$current_hour = date("H", time());
				if(($i == 0 && $current_hour >= 13) || ($current_hour < 13 && $i == 15)){
					continue;
				}
		
				$begin_hour = $project["begin_hour"];
				if($i == 0 && $current_hour > $begin_hour){
					$begin_hour = $current_hour + 4;
				}
				$end_hour = $project["end_hour"];
		
				for($j=$begin_hour; $j<=$end_hour; $j++){
					$item["list"][] = array("hour"=>($j.":00"), "time"=>($date." ".$j.":00"), "number"=>$j);
				}
				$item["count"] = count($item["list"]);
		
				$servicetime[] = $item;
			}
		}
		
		//特殊处理
		if($projectid == 70){ //跑腿代办
			$project["options"] = [
				"跑腿送货", "代缴水电费", "话费", "网费", "代办公积金", "退休金", "社保", "代购买药", "生活用品", "其它"
			];
		} else if($projectid == 72){ //居室清洁
			$project["hours"] = [
				2,3,4,5,6,7,8
			];
		}
		
		//前端要求以数组的形式传递
		$serviceuser_array = array();
		if ($serviceuser) {
		    $serviceuser_array[] = $serviceuser;
		}
		
		//服务星级价格类型
		if($service_type == 1){
			//默认星级价格
			$currentlevel = $levelprices[0];
			if($serviceuser){
				//服务项目的星级价格匹配服务人员的服务星级，剔除低于服务人员的服务星级的星级价格
				foreach($levelprices as $k=>$v){
					if($v["service_level"] >= $serviceuser["service_level"]){
						if(empty($level)){
							$level = $v;
						}
		
						$levels[] = $v;
					}
				}
				if(empty($level)){
					E("你预约的服务人员服务星级高于服务项目的最高星级，预约失败");
				}
		
				//服务项目的星级价格匹配服务人员的服务星级
				$currentlevel = $level;
				
				//剔除低于服务人员的服务星级的星级价格
				$levelprices = $levels;
			}
		}
		
		//用户照护人
		$caremodel = D("user_care");
		$map = array("userid"=>$user["id"]);
		$usercares = $caremodel->where($map)->select();
		
		//支付金额
		$amount = 0;
		
		//优惠券 - 服务星级价格类型
		if($service_type == 1){
			$amount = $currentlevel["price"];
		} else if($service_type == 2){ //服务护理价格类型 - 获取服务护理订金
			if(count($depositprices) > 0){
				$amount = $depositprices[0]["price"];
			}
		}
		
		if($amount > 0){
			//优惠券
			$counponmodel = D("user_coupon");
			$time = date("Y-m-d");
			$map = array(
				array("userid"=>$user['id'], "status"=>0, "min_amount"=>array("elt", $amount), "use_end_date"=>array("egt", $time),'coupon_type'=>0),
				array("userid"=>$user['id'], "status"=>0, "use_end_date"=>array("egt", $time),'coupon_type'=>2,'service_id'=>$projectid),
				'_logic'=>'or'
			);
			$counpons = $counponmodel->where($map)->select();
			foreach($counpons as $k=>$v){
				if($v['service_id']==$projectid){
					$v['money']=$amount;
				}
				$v['title']=$v['title'].'(￥'.$v['money'].')';
				$counpons[$k]=$v;
			}
		}
		
		//服务地址
		$addressmodel = D("user_address");
		$map = array("type"=>1, "userid"=>$user["id"]);
		$address = $addressmodel->where($map)->order("is_default desc")->find();
		
		//店铺
		$company = array('title'=>'一点椿旗舰店', 'image'=>$this->DoUrlHandle('/Public/Home/img/company.png'));
		
		//服务单价 - 遵照医嘱/日间照护/居室清洁（特殊处理）
		$single_price = "";
		if($project["assess"] == 0 && ($project["doctor"] == 1 || $project["time_type"] == 4 || $project["id"] == 72)){
			if($project["doctor"] == 1 || $project["id"] == 72){ // 遵照医嘱/居室清洁（特殊处理）
				$single_price = $currentlevel["price"];
			}  else if($project["time_type"] == 4){ //日间照护
				$single_price = $hourprices[0]["price"];
			}
		
			$single_price .= "/";
		
			switch ($project["time_type"]) {
				case 1: $single_price .= "时"; break;
				case 2:	case 4: $single_price .= "天"; break;
				case 3: $single_price .= "月"; break;
			}
		}
		
		$data = array(
			"project"=>$project, "levelprices"=>$levelprices, "currentlevel"=>$currentlevel,
			"depositprices"=>$depositprices, "hourprices"=>$hourprices, "amount"=>$point_shop['price'],'point'=>$point_shop['point'], "service_type"=>$service_type,
			"serviceuser"=>$serviceuser_array, "usercares"=>$usercares,'surplus'=>$user['point'],
			"address"=>$address, "servicetime"=>$servicetime,'company'=>$company, "single_price"=>$single_price
		);
		
		return $data;
	}
	
	//补全访问链接地址
	protected function DoUrlHandle($thumb){
		if(!empty($thumb) && (strpos(strtolower($thumb), 'http://') === false && strpos(strtolower($thumb), 'https://') === false)){
			$http_type = "http://";
			if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
				$http_type = "https://";
			}
			return $http_type.$_SERVER['HTTP_HOST'].$thumb;
		}else{
			return $thumb;
		}
	}
	//生成订单流水号
	protected function BuildOrderSN(){
	    
	    list($msec, $sec) = explode(' ', microtime());
	    $time =  ((float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000)) * 0.001;
	    
	    if(strstr($time,'.')){
	        sprintf("%01.3f",$time); //小数点。不足三位补0
	        list($usec, $sec) = explode(".",$time);
	        $sec = str_pad($sec,3,"0",STR_PAD_RIGHT); //不足3位。右边补0
	    }else{
	        $usec = $time;
	        $sec = "000"; 
	    }
	    $date = date("YmdHisx",$usec);
	
	    $sn = str_replace('x', $sec, $date);
	    $sn .= rand(100, 999);
	
	    return $sn;
	}
}