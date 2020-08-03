<?php
namespace CApi\Controller;
use Think\Controller;
class OrderServiceController extends BaseLoggedController {
	
	//服务订单列表
	public function serviceorder(){
		$user = $this->AuthUserInfo;

		$ordermodel = D("service_order");

		//orderstatus：0=全部,1=待评价,2=待确认完成,3=已取消,4=已完成,5=服务中,6=售后,7=审核不通过,8=待付款,9=待审核,10=待开始服务,11=待接单,12=待确认开始服务,13=待缴付尾款
		$orderstatus = I("get.orderstatus", 0);

		//剔除已删除的订单
		$map = array("o.userid"=>$user["id"], "o.status"=>array("neq", -1));
		switch ($orderstatus) {
			case 1:
				$map["o.status"] = 4;
				$map["o.execute_status"] = 4;
				$map["o.pay_status"] = 3;
				$map["o.commentid"] = 0;
				break;
			case 2:
				$map["o.status"] = 1;
				$map["o.execute_status"] = 3;
				$map["o.pay_status"] = 3;
				break;
			case 3:
				$map["o.status"] = 2;
				break;
			case 4:
				$map["o.status"] = 4;
                $map["o.pay_status"] = 3;
				$map["o.execute_status"] = 4;
				break;
			case 5:
				$map["o.status"] = 1;
                $map["o.pay_status"] = 3;
				$map["o.execute_status"] = array("not in", [0,3,4]);
				break;
			case 6:
				$map["o.status"] = array('in',[5,6]);
                $map["o.pay_status"] = 3;
				break;
			case 7:
				$map["o.admin_status"] = 2;
				$map["o.status"] = 1;
				$map["o.pay_status"] = 3;
				break;
			case 8:
				$map["o.status"] = 1;
				$map["o.pay_status"] = 0;
				break;
			case 9:
				$map["o.admin_status"] = 0;
				$map["o.status"] = 1;
				$map["o.pay_status"] = 3;
				break;
			case 10:
				$map["o.admin_status"] = 1;
				$map["o.status"] = 1;
				$map["o.pay_status"] = 3;
				$map["o.execute_status"] = 0;
				$map["o.service_userid"] = array('gt',0);
				break;
			case 11:
				$map["o.admin_status"] = 1;
				$map["o.status"] = 1;
				$map["o.pay_status"] = 3;
				$map["o.execute_status"] = 0;
				break;
			case 12:
				$map["o.admin_status"] = 1;
				$map["o.status"] = 1;
				$map["o.pay_status"] = 3;
				$map["o.execute_status"] = 1;
				$map["o.assess"] = 0;
				break;
			case 13:
				$map["o.execute_status"] = 1;
				$map["o.assess"] = 1;
				$map["o.assess_status"] = 2;
				$map["o.again_status"] = 1;
				break;
		}

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        
        $order = "o.createdate desc";
        $count = $ordermodel->alias("o")->where($map)->count();
        $totalpage = ceil($count/$row);
		$list = $ordermodel->alias("o")->join("left join sj_user_care as c on o.careid=c.id")
			->field("o.*,c.level as care_level")->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		$recordmodel = D("service_order_record");
		$caremodel = D("user_care");
		foreach($list as $k=>$v){

			//订单综合状态
			$v["com_status"] = $this->GetServiceOrderStatus($v);
			
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
			$v["doctor_image"] = $this->DoUrlListHandle($v["doctor_image"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);
			$v["again_price"] = getNumberFormat($v["again_price"]);

			$begintime = strtotime($v["begintime"]);
            $v["begintime"] = date("Y/m/d H:i", $begintime);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                $v["endtime"] = date("H:i", $endtime);
            } else{
                $v["endtime"] = date("Y/m/d H:i", $endtime);
			}
			
			//照护人详情
			$map = array("userid"=>$v["userid"], "id"=>$v["careid"]);
			$usercare = $caremodel->where($map)->find();
			if ($usercare) {
				$usercare["age"] = getAgeMonth($usercare["birth"]);
				if(empty($usercare["height"])){
					$usercare["height"] = "";
				}
				if(empty($usercare["weight"])){
					$usercare["weight"] = "";
				}
				$usercare["avatar"] = $this->DoUrlHandle($usercare["avatar"]);
			}
			$v["user_care"] = $usercare;
			
			if($v["assess"] == 1 && $v["service_role"] == 3){
				//照护人评估记录
				$recordmodel = D("service_order_assess_record");
				$map = array("orderid"=>$v["id"], "careid"=>$v["careid"]);
				$record = $recordmodel->where($map)->find();
				if($record){
					$v["assess_care_level"] = $record["assess_care_level"]; //专业评估等级
					$v["care_level"] = $record["care_level"]; //实际照护等级
				}else{
					$v["assess_care_level"] = 0; //专业评估等级
					$v["care_level"] = 0; //实际照护等级
				}
			}
			
			//是否评论
			$v["is_comment"] = 0;
			if($v["commentid"] > 0){
				$v["is_comment"] = 1;
			}
			
			if($v["type"] == 1){			
				//服务交互记录 - 配餐餐次
				$map = array("orderid"=>$v["id"], "userid"=>$v["service_userid"], "execute_status"=>3);
				$record = $recordmodel->where($map)->select();

				$v["record"] = $record;
			}

			$list[$k] = $v;
		}

		return $list;
	}

    //服务订单详情
    public function serviceorderdetail(){
        $user = $this->AuthUserInfo;

        $orderid = I("get.orderid", 0);
        if(empty($orderid)){
            E("请选择要查看的订单");
        }

        //订单详情
        $ordermodel = D("service_order");
        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $order = $ordermodel->where($map)->find();
        if(empty($order)){
            E("订单不存在");
        }
        $order["thumb"] = $this->DoUrlHandle($order["thumb"]);
		$order["service_avatar"] = $this->DoUrlHandle($order["service_avatar"]);
		$order["doctor_image"] = $this->DoUrlListHandle($order["doctor_image"]);
		$order["coupon_money"] = getNumberFormat($order["coupon_money"]);
		$order["total_amount"] = getNumberFormat($order["total_amount"]);
		$order["amount"] = getNumberFormat($order["amount"]);
		$order["again_price"] = getNumberFormat($order["again_price"]);
		//平台补贴（优惠券金额）
		$order["platform_money"] = getNumberFormat($order["platform_money"]);

        //订单综合状态
        $order["com_status"] = $this->GetServiceOrderStatus($order);
		
		//订单进度条
		$order['progress'] = $this->GetProgress($order);
		
		$begintime = strtotime($order["begintime"]);
		$order["begintime"] = date("Y/m/d H:i", $begintime);
		$endtime = strtotime($order["endtime"]);
		if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
			$order["endtime"] = date("H:i", $endtime);
		} else{
			$order["endtime"] = date("Y/m/d H:i", $endtime);
		}

		//是否评论
		$order["is_comment"] = 0;
		if($order["commentid"] > 0){
			$order["is_comment"] = 1;
		}

		//订单线下评估信息 - 家护师
		if($order["assess"] == 1 && $order["service_role"] == 3){
			//照护人评估记录
			$recordmodel = D("service_order_assess_record");
			$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
			$record = $recordmodel->where($map)->find();
			if($record){
				$order["assess_care_level"] = $record["assess_care_level"]; //专业评估等级
				$order["care_level"] = $record["care_level"]; //实际照护等级
			}else{
				$order["assess_care_level"] = 0; //专业评估等级
				$order["care_level"] = 0; //实际照护等级
			}
		}

		//订单售后信息
		if(in_array($order["status"], [5,6])){
			$refundmodel = D("service_order_refund");
			$map = array("userid"=>$user["id"], "orderid"=>$orderid);
			$order["refund_record"] = $refundmodel->where($map)->find();
			if($order["refund_record"]){
				$order["refund_record"]["images"] = $this->DoUrlListHandle($order["refund_record"]["images"]);
				unset($order["refund_record"]['feedback_date']);
			}
		}

        //角色类型
        $scmodel = D("service_category");//服务项目栏目
        $sc= $scmodel->where('id='.$order['categoryid'])->find();
        $order['service_role'] = $sc['role'];
        //专业等级
        $spmodel = D("service_project");//服务项目
        $sp = $spmodel->where('id='.$order['projectid'])->find();
        $order['service_major_level'] = $sp['major_level'];

        //照护人详情
        $caremodel = D("user_care");
        $map = array("userid"=>$order["userid"], "id"=>$order["careid"]);
        $usercare = $caremodel->where($map)->find();
        if ($usercare) {
            $usercare['age'] = getAgeMonth($usercare['birth']);
			switch($usercare['level']){
				case 1:
					$usercare['level']='半护理';
					break;
				case 2:
					$usercare['level']='全护理';
					break;
				case 3:
					$usercare['level']='特重护理';
					break;
			}
        }

        //服务人员详情
        if($order["service_userid"] > 0){
            $usermodel = D("user");
            $map = array("u.status"=>200, "up.status"=>1,"u.id"=>$order["service_userid"]);
            $serviceuser = $usermodel->alias("u")->join("left join sj_user_role as ur on u.id=ur.userid")->join("left join sj_user_profile as up on ur.userid=up.userid")
                ->field("u.id,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent,ur.role as service_role")->order('service_role desc')->where($map)->find();
            if ($serviceuser){
                $serviceuser['age'] = getAgeMonth($serviceuser['birth']);
                $serviceuser['avatar'] = $this->DoUrlHandle($serviceuser["avatar"]);
				$serviceuser['en_mobile']=substr($serviceuser['mobile'], 0, 3).'****'.substr($serviceuser['mobile'], 7);
            }
			switch($serviceuser['gender']){
				case 1:
					$serviceuser['gender']='男';
					break;
				case 2:
					$serviceuser['gender']='女';
					break;
				case 0:
					$serviceuser['gender']='保密';
					break;
			}
			if($order['execute_status']==1 || $order['execute_status']==2 || $order['execute_status']==3){
				$order['is_location']=1;
			}elseif($order['execute_status']==4 && $order['execute_time'] > date('Y-m-d H:i:s',time()-3600)){
				$order['is_location']=1;
			}else{
				$order['is_location']=0;
			}
		}
		
		//服务人员坐标
		$coordinatemodel = D("Common/Coordinate");
		$coordinate = $coordinatemodel->readcoordinate($order);

        $data = array(
			"order"=>$order, "usercare"=>$usercare, "serviceuser"=>$serviceuser, "coordinate"=>$coordinate
        );

        return $data;
    }

	//送餐服务订单详情
	public function mealserviceorderdetail(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择要查看的订单");
		}

		//订单详情
		$ordermodel = D("service_order");
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在");
		}
		$order["thumb"] = $this->DoUrlHandle($order["thumb"]);
		$order["service_avatar"] = $this->DoUrlHandle($order["service_avatar"]);
		$order["coupon_money"] = getNumberFormat($order["coupon_money"]);
		$order["total_amount"] = getNumberFormat($order["total_amount"]);
		$order["amount"] = getNumberFormat($order["amount"]);
		$order["again_price"] = getNumberFormat($order["again_price"]);
		//平台补贴（优惠券金额）
		$order["platform_money"] = getNumberFormat($order["platform_money"]);
		//是否评论
		$order["is_comment"] = 0;
		if($order["commentid"] > 0){
			$order["is_comment"] = 1;
		}
		//订单综合状态
		$order["com_status"] = $this->GetServiceOrderStatus($order);

		$begintime = strtotime($order["begintime"]);
		$order["begintime"] = date("Y/m/d H:i", $begintime);
		$endtime = strtotime($order["endtime"]);
		if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
			$order["endtime"] = date("H:i", $endtime);
		} else{
			$order["endtime"] = date("Y/m/d H:i", $endtime);
		}

		//服务人员详情
		if($order["service_userid"] > 0){
			$usermodel = D("user");
			$map = array("u.status"=>200, "up.status"=>1,"u.id"=>$order["service_userid"]);
			$serviceuser = $usermodel->alias("u")->join("left join sj_user_role as ur on u.id=ur.userid")->join("left join sj_user_profile as up on ur.userid=up.userid")
				->field("u.id,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent,ur.role as service_role")->where($map)->find();
			if ($serviceuser){
                $serviceuser['age'] = getAgeMonth($serviceuser['birth']);
                $serviceuser['avatar'] = $this->DoUrlHandle($serviceuser["avatar"]);
            }

            //服务交互记录
            $recordmodel = D("service_order_record");
            $map = array("orderid"=>$orderid, "userid"=>$order["service_userid"],
                "execute_status"=>3);
            $record = $recordmodel->where($map)->select();

		}

		$data = array(
			"order"=>$order, "serviceuser"=>$serviceuser, 'record'=>$record
		);

		return $data;
	}

	//服务订单服务人员坐标
	public function ordercoordinate(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择查看的订单");
		}

		//订单详情
        $ordermodel = D("service_order");
        $map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
            E("订单不存在");
        }
		
		//服务人员坐标
		$coordinatemodel = D("Common/Coordinate");
		$coordinate = $coordinatemodel->readcoordinate($order);

		return $coordinate;
	}
	
	//服务订单评价列表
	public function servicecomment(){
		$user = $this->AuthUserInfo;

		$model = D("service_order");

		//类型：0=待评价，1=已评价
		$type = I("get.type", 0);

		$map = array("userid"=>$user["id"]);
		if($type == 0){
			$map["commentid"] = 0;
		} else{
			$map["commentid"] = array("gt", 0);
		}
        $map["status"] = 4;
        $map["pay_status"] = 3;
		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        
        $order = "createdate desc";
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
		$list = $model->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
            $v['star'] = $this->calcstar($v['score']);

			$list[$k] = $v;
		}
		
		return $list;
	}

	//服务订单评价详情
	public function servicecommentdetail(){
		$user = $this->AuthUserInfo;

		$model = D("service_comment");

		$commentid = I("get.commentid", 0);
		if(empty($commentid)){
			E("请选择要查看的服务订单评价");
		}

		$map = array("userid"=>$user["id"], "id"=>$commentid);
		$detail = $model->where($map)->find();
		if(empty($detail)){
			E("当前订单还未评价，无法查看");
		}

		$detail["service_avatar"] = $this->DoUrlHandle($detail["service_avatar"]);
		$detail["images"] = $this->DoUrlListHandle($detail["images"]);
		$detail["platform_reply"] = $detail["platform_reply"] ? $detail["platform_reply"] : '';

		return $detail;
	}

	//服务订单结算检查
	public function ordercheck(){
		$user = $this->AuthUserInfo;

		$projectid = I("post.projectid", 0);
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

		//服务人员
		$serviceid = I("post.serviceid", 0);
		if($serviceid){
			$usermodel = D("user");
			$map = array("up.status"=>1, "u.status"=>200, "u.id"=>$serviceid);
			$serviceuser = $usermodel->alias("u")->join("left join sj_user_profile as up on u.id=up.userid")
				->field("u.id,u.nickname,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.height,up.weight,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.province,up.city,up.region,up.intro")->where($map)->find();
			if(empty($serviceuser)){
				E("您预约的服务人员不存在");
			}
			$serviceuser["avatar"] = $this->DoUrlHandle($serviceuser["avatar"]);
			$serviceuser["age"] = getAgeMonth($serviceuser["birth"]);

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
			"depositprices"=>$depositprices, "hourprices"=>$hourprices, "amount"=>$amount, "service_type"=>$service_type,
			"serviceuser"=>$serviceuser_array, "usercares"=>$usercares, "counpons"=>$counpons,
			"address"=>$address, "servicetime"=>$servicetime,'company'=>$company, "single_price"=>$single_price
		);

		return $data;
	}

	//获取可用优惠券列表
	public function ordercoupon(){
		$user = $this->AuthUserInfo;

		$amount = I("get.amount", 0);
		if(empty($amount)){
			E("订单金额不能为空");
		}

		$model = D("user_coupon");

		$time = date("Y-m-d");
		$map = array("userid"=>$user['id'], "status"=>0, "min_amount"=>array("elt", $amount), "use_end_date"=>array("egt", $time),'coupon_type'=>0);
		$list = $model->where($map)->select();
		foreach($list as $k=>$v){
			$v['title']=$v['title'].'(￥'.$v['money'].')';
			$list[$k]=$v;
		}
		return $list;
	}

	//服务订单续费结算检查
	public function orderagaincheck(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要续费的服务订单");
		}

		$ordermodel = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("续费的服务订单不存在");
		}
		
		if($order['service_userid']==0){
			E('服务人员不存在');
		}
		
		$endtime = strtotime($order["endtime"]);
		$time = strtotime("+10 minute", time());
		if($time > $endtime){
			E("距离服务结束时间已不足10分钟，无法续费");
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
		$counponmodel = D("user_coupon");
		$time = date("Y-m-d");
		$map = array("userid"=>$user['id'], "status"=>0, "min_amount"=>array("elt", $again_price), "use_end_date"=>array("egt", $time),'coupon_type'=>0);
		$counpons = $counponmodel->where($map)->select();
		foreach($counpons as $k=>$v){
			$v['title']=$v['title'].'(￥'.$v['money'].')';
			$counpons[$k]=$v;
		}
		
        //用户照护人
        $caremodel = D("user_care");
        $map = array("userid"=>$user["id"]);
        $usercares = $caremodel->where($map)->select();

		$data = array(
			"again_price"=>$again_price, "counpons"=>$counpons, "usercares"=>$usercares
		);

		return $data;
	}

	//服务订单缴费结算检查
	public function orderassesscheck(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
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
			&& $order["service_userid"] > 0 && in_array($order["execute_status"], [1,2,3]))){
			E("服务订单状态异常，缴费失败");
		}
		
		$again_price = $order["again_price"];
		if($again_price <= 0){
			E("当前服务项目未开启缴费，缴费失败");
		}

		//优惠券
		$counponmodel = D("user_coupon");
		$time = date("Y-m-d");
		$map = array("userid"=>$user['id'], "status"=>0, "min_amount"=>array("elt", $again_price), "use_end_date"=>array("egt", $time),'coupon_type'=>0);
		$counpons = $counponmodel->where($map)->select();

        //用户照护人
        $caremodel = D("user_care");
        $map = array("userid"=>$user["id"]);
        $usercares = $caremodel->where($map)->select();

		$data = array(
			"again_price"=>$again_price, "counpons"=>$counpons, "usercares"=>$usercares
		);

		return $data;
	}
	
	//订单隐私号码绑定
	public function ordermobile(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid");
		if(empty($orderid)){
			E("请选择要操作的订单");
		}

		$ordermodel = D("service_order");

		$map = array("o.userid"=>$user["id"], "o.id"=>$orderid);
		$order = $ordermodel->alias("o")->join("left join sj_user_profile as p on o.service_userid=p.userid")
			->field("o.*,p.mobile as service_mobile")->where($map)->find();
		if(empty($order)){
			E("订单不存在");
		}
		if($order["admin_status"] != 1){
			E("服务订单正在审核，无法获取服务人员联系电话");
		}
		if($order["service_userid"] <= 0){
			E("服务订单暂未分配服务人员，无法获取服务人员联系电话");
		}
		if(empty($order["mobile"])){
			E("服务用户联系号码异常，请联系客服");
		}
		if(empty($order["service_mobile"])){
			E("服务人员联系号码异常，请联系客服");
		}

		//隐私号码已绑定
		if($order["pn_status"] == 1 && $order["pn_mobile"]){
			return array("mobile"=>$order["pn_mobile"]);
		}

		$usermobile = $order["mobile"];
		$servicemobile = $order["service_mobile"];

		//绑定隐私号码
		$axbmodel = D("Common/HwAXB");

		$result = $axbmodel->Bind($usermobile, $servicemobile);
		if($result["result"] == "FAIL"){
			E("获取服务人员联系电话异常，请联系客服");
		}
		
		$pn_mobile = $result["data"]["relationNum"];
		$pn_bind_id = $result["data"]["subscriptionId"];

		$entity = array(
			"pn_mobile"=>$pn_mobile, "pn_bind_id"=>$pn_bind_id, "pn_status"=>1
		);
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$ordermodel->where($map)->save($entity);

		return array("mobile"=>$pn_mobile);
	}
	
	//线下评估表单 - 家护师
	public function orderassess3(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择查看的订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("当前服务订单为非线下评估服务订单，无法查看线下评估表单");
		}

		//服务订单线下评估表单集合
		$assessmodel = D("service_order_assess");

		$orderassess = $assessmodel->order("type asc, id asc")->select();

		$assess = array();
		$history = array();
		foreach($orderassess as $k=>$v){
			if($v["type"] == 1){
				$item = $assess[$v["category"]];
			} else if($v["type"] == 2){
				$item = $history[$v["category"]];
			}
			if(empty($item)){
				$item = [];
			}
			$item[] = $v;

			if($v["type"] == 1){
				$assess[$v["category"]] = $item;
			} else if($v["type"] == 2){
				$history[$v["category"]] = $item;
			}
		}

		//当前订单评估记录（1=是）
		$current_order_record = 1;

		//照护人评估记录
		$recordmodel = D("service_order_assess_record");

		$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
		$record = $recordmodel->where($map)->find();
		if(empty($record)){
			E("当前服务订单暂未进行线下评估");
		}

		if($record){
			$answer = $record["answer_content"];
			if($answer){
				$answer = json_decode($answer, true);
			} else{
				$answer = [];
			}
			$record["answer"] = $answer;
		}

		//护理等级评估
		$assess_list = [];
		foreach($assess as $k=>$v){
			$item = array(
				"title"=>$k,
				"score"=>0,
				"icon"=>$this->DoUrlHandle("/upload/default/".$k.".png"),
				"list"=>[]
			);

			foreach($v as $ik=>$iv){
				if(empty($record)){
					$iv["answer"] = "";
				} else{
					$answer = $record["answer"]["answer_".$iv["id"]];
					if($answer){
						$option = $answer["answer"];

						$iv["answer"] = $option;

						if($current_order_record == 1){
							$score = intval($iv["answer_".$option."_score"]);

							$item["score"] += $score;
						}
					}
				}
				
				$item["list"][] = $iv;
			}

			$assess_list[] = $item;
		}

		//过往诊断
		$history_list = [];
		foreach($history as $k=>$v){
			$item = array(
				"title"=>$k,
				"icon"=>$this->DoUrlHandle("/upload/default/".$k.".png"),
				"list"=>[]
			);

			foreach($v as $ik=>$iv){
				if(empty($record)){
					$iv["answer"] = "";
				} else{
					$answer = $record["answer"]["answer_".$iv["id"]];
					if($answer){
						$option = $answer["answer"];

						$iv["answer"] = $option;
					}
				}
				
				$item["list"][] = $iv;
			}

			$history_list[] = $item;
		}

		//照护人详情
		$caremodel = D("user_care");
		$map = array("userid"=>$order["userid"], "id"=>$order["careid"]);
		$usercare = $caremodel->where($map)->find();
        if ($usercare) {
            $usercare["age"] = getAgeMonth($usercare["birth"]);
			if(empty($usercare["height"])){
				$usercare["height"] = "";
			}
			if(empty($usercare["weight"])){
				$usercare["weight"] = "";
			}
		}

		$data = array(
			"assess"=>$assess_list, "history"=>$history_list, "user_care"=>$usercare,
			"total_score"=>0, "care_level"=>0, "care_level_name"=>"待评估护理等级",
			"image"=>"", "updatetime"=>""
		);

		if($current_order_record == 1 && $record){
			$data["total_score"] = $record["total_score"];
			$data["care_level"] = $record["assess_care_level"];

			switch($data["care_level"]){
				case 1: $data["care_level_name"] = "自理"; break;
				case 2: $data["care_level_name"] = "轻度失能"; break;
				case 3: $data["care_level_name"] = "中度失能"; break;
				case 4: $data["care_level_name"] = "重度失能"; break;
				case 5: $data["care_level_name"] = "特重护理"; break;
			}

			$data["image"] = $this->DoUrlHandle($record["image"]);

			$data["updatetime"] = $record["updatetime"];
		}

		return $data;
	}

	//线下评估 - 康复师
	public function orderassess4(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择查看的订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("当前服务订单为非线下评估服务订单，无法查看线下评估表单");
		}
		if($order["service_role"] != 4){
			E("当前服务订单为非康复师线下评估，无法查看");
		}

		//照护人详情
		$caremodel = D("user_care");
		$map = array("userid"=>$order["userid"], "id"=>$order["careid"]);
		$usercare = $caremodel->where($map)->find();
        if ($usercare) {
            $usercare["age"] = getAgeMonth($usercare["birth"]);
			if(empty($usercare["height"])){
				$usercare["height"] = "";
			}
			if(empty($usercare["weight"])){
				$usercare["weight"] = "";
			}
		}

		$data = array(
			"user_care"=>$usercare, "time_type"=>$order["time_type"], "time"=>$order["time"],
			"begintime"=>$order["begintime"], "endtime"=>$order["endtime"],
			"doctor_1"=>"", "doctor_2"=>"", "doctor_image"=>""
		);

		//照护人评估记录
		$recordmodel = D("service_order_assess_record");

		$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
		$record = $recordmodel->where($map)->order("updatetime desc")->find();
		if($record){
			$doctor = $record["answer_content"];
			if($doctor){
				$doctor = json_decode($doctor, true);
			} else {
				$doctor = array();
			}

			$data["doctor_1"] = $doctor["doctor_1"];
			$data["doctor_2"] = $doctor["doctor_2"];
			$data["doctor_image"] = $this->DoUrlListHandle($record["image"]);
		}

		return $data;
	}

}