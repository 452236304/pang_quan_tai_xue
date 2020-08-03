<?php
namespace SApi\Controller;
use Think\Controller;
class OrderController extends BaseLoggedController {
	
	//我的订单
	public function lists(){
		$user = $this->AuthUserInfo;

		$ordermodel = D("service_order");

		//orderstatus：0=全部,1=待服务,2=服务中,3=已完成,4=待退款,5=已退款,6=已评价,7=待处理
		$orderstatus = I("get.orderstatus", 0);

		//剔除已删除的订单
		$map = array("so.service_userid"=>$user["id"], "so.status"=>array("not in", [-1,2]), "so.pay_status"=>3);
		switch ($orderstatus) {
			case 1:
				$map["so.status"] = 1;
				$map["so.execute_status"] = 0;
				break;
			case 2:
				$map["so.status"] = 1;
				$map["so.execute_status"] = array("in", [1,2,3]);
				break;
			case 3:
				$map["so.status"] = 4;
				$map["so.execute_status"] = 4;
				break;
			case 4:
				$map["so.status"] = 5;
				break;
			case 5:
				$map["so.status"] = 6;
				break;
			case 6:
				$map["so.status"] = 4;
				$map["so.execute_status"] = 4;
				$map["so.commentid"] = 0;
				break;
			case 7:
				$map["so.status"] = 1;
				$where['so.execute_status']=0;
				$where['soar.is_agree']=0;
				$where['sofr.record_date']=date('Y-m-d');
				$where['_logic']='or';
				$map['_complex']=$where;
				break;
		}

		//服务项目
		$projectid = I("get.projectid", 0);
		if($projectid){
			$map["so.projectid"] = $projectid;
		}

		//服务时间 0=全部,1=当天,2=最近三天,3=最近一周,4=最近1个月,5=最近3个月,6=最近半年
		$date = I("get.date", 0);
		switch($date){
			case 1:
				$time = date("Y-m-d", time());
				$map["so.begintime"] = array("egt", $time);
				break;
			case 2:
				$time = date("Y-m-d", strtotime("-3 day", time()));
				$map["so.begintime"] = array("egt", $time);
				break;
			case 3:
				$time = date("Y-m-d", strtotime("-7 day", time()));
				$map["so.begintime"] = array("egt", $time);
				break;
			case 4:
				$time = date("Y-m-d", strtotime("-1 month", time()));
				$map["so.begintime"] = array("egt", $time);
				break;
			case 5:
				$time = date("Y-m-d", strtotime("-3 month", time()));
				$map["so.begintime"] = array("egt", $time);
				break;
			case 6:
				$time = date("Y-m-d", strtotime("-6 month", time()));
				$map["so.begintime"] = array("egt", $time);
				break;
		}

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        
        $order = "so.createdate desc";
		$count = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")->where($map)->count();
        $totalpage = ceil($count/$row);
		$list = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")->join("left join sj_user_care as c on so.careid=c.id")
			->join("left join sj_service_order_assess_record as soasr on so.id=soasr.orderid")
			->field("so.*,u.avatar as user_avatar,c.level as care_level,soasr.care_level as assess_care_level")
			->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		$recordmodel = D("service_order_record");
		$caremodel = D("user_care");
		foreach($list as $k=>$v){
			
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
			if(empty($v["user_avatar"])){
				$v["user_avatar"] = "/upload/default/default_avatar.png";
			}
			$v["user_avatar"] = $this->DoUrlHandle($v["user_avatar"]);
			$v["doctor_image"] = $this->DoUrlListHandle($v["doctor_image"]);
			$v["coupon_money"] = getNumberFormat($v["coupon_money"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);
            $v["again_price"] = getNumberFormat($v["again_price"]);
			//平台补贴（优惠券金额）
			$v["platform_money"] = getNumberFormat($v["platform_money"]);

			//订单综合状态
			$v["com_status"] = $this->GetServiceOrderStatus($v);

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
			if($v["status"] = 1 && $v["execute_status"] = 0){
				$v['pending']=1;
			}elseif($v['projectid']==22){
				$map = array('orderid'=>$v['id'],'record_date'=>date('Y-m-d'));
				$record=D('service_order_form_record')->where($map)->find();
				if($record){
					$v['pending']=1;
				}else{
					$v['pending']=0;
				}
			}else{
				$map = array('orderid'=>$v['id'],'is_agree'=>0);
				$again=D('service_order_again_record')->where($map)->find();
				if($again){
					$v['pending']=1;
				}else{
					$v['pending']=0;
				}
			}
			if($orderstatus==7 && $v['pending']==0){
				unset($list[$k]);
			}
			
			$list[$k] = $v;
		}

		return $list;
	}
	
	//订单详情
	public function detail(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择要查看的订单");
		}

		//订单详情
		$ordermodel = D("service_order");
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
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

		//订单进度
		$order["progress"] = $this->GetProgress($order);
		
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
			}
		}

		//订单售后信息
		if(in_array($order["status"], [5,6])){
			$refundmodel = D("service_order_refund");
			$map = array("userid"=>$user["id"], "orderid"=>$orderid);
			$order["refund_record"] = $refundmodel->where($map)->find();
			if($order["refund_record"]){
				$order["refund_record"]["images"] = $this->DoUrlListHandle($order["refund_record"]["images"]);
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
		
		//当前服务订单是否定位中
		$current_begin_time = date("Y-m-d H:i:s", strtotime("+1 hour", time()));
		$current_end_time = date("Y-m-d H:i:s", strtotime("-1 hour", time()));
		if(($order["status"] == 1 && in_array($order["execute_status"], [1,2,3,4]) && $order["admin_status"] == 1)
			|| ($order["status"] == 1 && $order["execute_status"] == 0 && $order["begintime"]>$current_begin_time)
			|| ($order["status"] == 4 && $order["execute_status"] == 4 && $order["endtime"]>$current_end_time)){
			$order["current_location"] = 1;
		} else{
			$order["current_location"] = 0;
		}

		//照护视频录制
		$order["record"] = 0;
		if(in_array($order["execute_status"], [1,2,3,4])){
			$recordmodel = D("service_order_care_record");
			$map = array("orderid"=>$order["id"]);
			$record = $recordmodel->where($map)->find();
			if($order["assess"] == 1  || (in_array($order["time_type"], [2,4]) && $order["time"] > 1)){ //大于1天的服务项目的服务订单
				if(in_array($order["execute_status"], [1,2])){ //服务中
					if(empty($record)){
						$order["record"] = 1;
					} else{
						if(empty($record["video"])){
							$order["record"] = 1;
						} else{
							$order["record"] = 0;
						}
					}
				} else if(in_array($order["execute_status"], [3,4])){ //已完成
					if(empty($record)){
						$order["record"] = 2;
					} else{
						if(empty($record["images"]) && empty($record["video"]) && empty($record["content"])){
							$order["record"] = 2;
						} else if($record["video"]){
							$video_count = explode("|", $record["video"]);
							if($video_count == 1){//已录入开始视频
								$order["record"] = 2;
							} else{
								$order["record"] = 3;
							}
						} else{
							$order["record"] = 3;
						}
					}
				}
			} else{ //短期服务项目的服务订单
				if(in_array($order["execute_status"], [3,4])){ //已完成
					if(empty($record)){
						$order["record"] = 2;
					} else{
						$order["record"] = 3;
					}
				}
			}
		}

		//照护人详情
		$caremodel = D("user_care");
		$map = array("userid"=>$order["userid"], "id"=>$order["careid"]);
		$usercare = $caremodel->where($map)->find();
        if ($usercare) {
            $usercare['age'] = getAgeMonth($usercare['birth']);
			if($usercare['height']==0){
				$usercare['height']='';
			}
			if($usercare['weight']==0){
				$usercare['weight']='';
			}
		}
		
		//服务人员详情
		if($order["service_userid"] > 0){
			$usermodel = D("user_profile");
			$map = array("u.status"=>200, "up.status"=>1, "u.id"=>$order["service_userid"]);
			$serviceuser = $usermodel->alias("up")->join("left join sj_user as u on u.id=up.userid")
				->field("u.id,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->find();
			if ($serviceuser){
				$serviceuser['age'] = getAge($serviceuser['birth']);
				$serviceuser['avatar'] = $this->DoUrlHandle($serviceuser["avatar"]);
			}
		}

		//服务人员坐标
		$coordinatemodel = D("Common/Coordinate");
		$coordinate = $coordinatemodel->readcoordinate($order);
		
		//查询订单是否申请续费
		$map = array('orderid'=>$orderid, 'is_agree'=>0);
		$again = D('service_order_again_record')->where($map)->find();
		if($again && $order["com_status"]['com_status'] == 18){
			$again = 1;
		}else{
			$again = 0;
		}

		$data = array(
			"order"=>$order, "usercare"=>$usercare, "serviceuser"=>$serviceuser,
			"coordinate"=>$coordinate, "again"=>$again
		);

		return $data;
	}

    //送餐服务订单详情
    public function mealdetail(){
        $user = $this->AuthUserInfo;

        $orderid = I("get.orderid", 0);
        if(empty($orderid)){
            E("请选择要查看的订单");
        }

        //订单详情
        $ordermodel = D("service_order");
        $map = array("service_userid"=>$user["id"], "id"=>$orderid);
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

		//是否评论
		$order["is_comment"] = 0;
		if($order["commentid"] > 0){
			$order["is_comment"] = 1;
		}
		
		//当前服务订单是否定位中
		$current_begin_time = date("Y-m-d H:i:s", strtotime("+1 hour", time()));
		$current_end_time = date("Y-m-d H:i:s", strtotime("-1 hour", time()));
		if(($order["status"] == 1 && in_array($order["execute_status"], [1,2,3,4]) && $order["admin_status"] == 1)
			|| ($order["status"] == 1 && $order["execute_status"] == 0 && $order["begintime"]>$current_begin_time)
			|| ($order["status"] == 4 && $order["execute_status"] == 4 && $order["endtime"]>$current_end_time)){
			$order["current_location"] = 1;
		} else{
			$order["current_location"] = 0;
		}

        //服务人员详情
        if($order["service_userid"] > 0){
            $usermodel = D("user_profile");
			$map = array("u.status"=>200, "up.status"=>1, "u.id"=>$order["service_userid"]);
			$serviceuser = $usermodel->alias("up")->join("left join sj_user as u on u.id=up.userid")->join("left join sj_user_role as ur on u.id=ur.userid")
                ->field("u.id,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent,ur.role as service_role")->where($map)->find();
            if ($serviceuser){
                $serviceuser['age'] = getAge($serviceuser['birth']);
                $serviceuser['avatar'] = $this->DoUrlHandle($serviceuser["avatar"]);
			}
			
            //服务交互记录 - 配餐餐次
            $recordmodel = D("service_order_record");
            $map = array("orderid"=>$orderid, "userid"=>$order["service_userid"], "execute_status"=>3);
            $record = $recordmodel->where($map)->select();
		}
		
		//服务人员坐标
		$coordinatemodel = D("Common/Coordinate");
		$coordinate = $coordinatemodel->readcoordinate($order);

        $data = array(
            "order"=>$order, "serviceuser"=>$serviceuser, "record"=>$record, "coordinate"=>$coordinate
        );

        return $data;
	}

	//检查 线下评估/日间照护/遵照医嘱 服务订单当天是否填写表单 - 21:00至24:00
	public function ordercheckform(){
		$user = $this->AuthUserInfo;

		$data = array("record"=>0, "orderid"=>0);

		$hour = intval(date("h"));
		// if(!($hour >= 21 && $hour <=23)){
		// 	return $data;
		// }

		$ordermodel = D("service_order");
		
		$time = date("Y-m-d H:i:s");
		$map = array(
			"service_userid"=>$user["id"], "admin_status"=>1, "status"=>1, "execute_status"=>array("in",[1,2,3]),
			"formids"=>array("exp", "is not null"),	"begintime"=>array("elt", $time), "endtime"=>array("egt", $time),
			"_complex"=>array("assess"=>1, "time_type"=>4, "doctor"=>1, "_logic"=>"or")
		);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			return $data;
		}

		$recordmodel = D("service_order_form_record");

		//检查是否记录表单
		$map = array("orderid"=>$order["id"], "record_date"=>date("Y-m-d"));
		$count = $recordmodel->where($map)->count();
		if($count > 0){
			return $data;
		}

		$data = array("record"=>1, "orderid"=>$order["id"]);

		return $data;
	}

	// 线下评估/日间照护/遵照医嘱 服务订单 - 表单集合
	public function orderforms(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择查看的订单");
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

		$list = [];

		if(empty($order["formids"])){
			return $list;
		}
		$formids = explode(",", $order["formids"]);
		
		$begin = strtotime(date("Y-m-d", strtotime($order["begintime"])));
		// if($begin > time()){
		// 	return $list;
		// }
		$end = strtotime(date("Y-m-d", strtotime($order["endtime"])));
		// if($end > time()){
		// 	$end = time();
		// }

		//服务订单表单项目集合
		$formmodel = D("service_form");

		$map = array("id"=>array("in", $formids));
		$orderform = $formmodel->where($map)->select();
		foreach($orderform as $k=>$v){
			$v["recordid"] = 0;
			$v["completed"] = 0;
			$v["remark"] = "";

			$orderform[$k] = $v;
		}

		//服务订单表单项目记录集合
		$recordmodel = D("service_order_form_record");

		//起始日期
		$date = $begin;
		//相差天数
		//$day = floor(($end - $begin)/3600/24);
		$day = $order["time"];
		if($order["time_type"] == 3){
			$day = intval($order["time"]) * 30;
		}
		for($i=0;$i<=$day;$i++){
			$record_date = date("Y-m-d", strtotime("+".$i." day", $date));

			$item = array(
				"orderid"=>$orderid, "record"=>0, "date"=>$record_date, "list"=>[]
			);

			$map = array("orderid"=>$orderid, "formid"=>array("in", $formids), "record_date"=>$record_date);
			$forms = $recordmodel->alias("r")->join("left join sj_service_form as f on r.formid=f.id")
				->field("f.id,f.category,f.title,f.source,r.id as recordid,r.completed,r.remark")
				->where($map)->select();
			if(count($forms) <= 0){
				$forms = $orderform;
			} else{
				$item["record"] = 1;
			}

			$item["list"] = $forms;

			$list[] = $item;
		}

		return $list;
	}
	
	//长期照护服务订单 - 表单信息
	public function orderform(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择查看的订单");
		}
		$date = I("get.date");
		if(empty($date)){
			E("请选择查看的日期");
		}
		if(!checkDateTime($date, "Y-m-d")){
			E("日期格式不正确");
		}
		if($date > date("Y-m-d")){
			E("查看的日期不能大于当天日期");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		$begintime = date("Y-m-d", strtotime($order["begintime"]));
		$endtime = date("Y-m-d", strtotime($order["endtime"]));
		if(!($begintime <= $date && $endtime >= $date)){
			E("查询的表单日志时间必须是服务周期内");
		}

		$data = array("record"=>0, "date"=>$date, "list"=>[]);

		if(empty($order["formids"])){
			return $data;
		}
		$formids = explode(",", $order["formids"]);

		$recordmodel = D("service_order_form_record");
	
		$map = array("o.service_userid"=>$user["id"], "r.orderid"=>$orderid, "r.record_date"=>$date, "r.formid"=>array("in", $formids));
		$list = $recordmodel->alias("r")->join("left join sj_service_form as f on r.formid=f.id")->join("left join sj_service_order as o on r.orderid=o.id")
			->field("f.id,f.category,f.title,f.source,r.id as recordid,r.completed,r.remark")->where($map)->select();

		if(count($list) <= 0){
			$formmodel = D("service_form");

			$map = array("id"=>array("in", $formids));
			$form = $formmodel->where($map)->select();

			$list = [];

			foreach($form as $k=>$v){
				$v["recordid"] = 0;
				$v["completed"] = 0;
				$v["remark"] = "";

				$list[] = $v;
			}
		} else{
			$data["record"] = 1;
		}

		$data["list"] = $list;

		return $list;
	}
	
	//订单隐私号码绑定
	public function ordermobile(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid");
		if(empty($orderid)){
			E("请选择要操作的订单");
		}

		$ordermodel = D("service_order");

		$map = array("o.service_userid"=>$user["id"], "o.id"=>$orderid);
		$order = $ordermodel->alias("o")->join("left join sj_user_profile as p on o.service_userid=p.userid")
			->field("o.*,p.mobile as service_mobile")->where($map)->find();
		if(empty($order)){
			E("订单不存在");
		}
		if($order["admin_status"] != 1){
			E("服务订单正在审核，无法获取服务用户联系电话");
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
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
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
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("当前服务订单为非线下评估服务订单，无法查看线下评估表单");
		}
		if($order["service_role"] != 3){
			E("当前服务订单为非家护师线下评估，无法查看");
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
		$record = $recordmodel->where($map)->order("updatetime desc")->find();
		if(empty($record)){
			$map = array("careid"=>$order["careid"]);
			$record = $recordmodel->where($map)->order("updatetime desc")->find();

			$current_order_record = 0;
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
			"image"=>"", "submit"=>0, "updatetime"=>""
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

			//可提交状态（1=不可提交）
			if($order["assess_status"] == 2 && $order["again_status"] == 2){
				$data["submit"] = 1;
			}
		}

		return $data;
	}

	//更新线下评估护理等级和服务结束时间 - 家护师
	public function orderupdateassess3(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
		if(empty($orderid)){
			E("请选择查看的订单");
		}

		$ordermodel = D("service_order");
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("服务订单不存在");
		}
		if($order["assess"] != 1){
			E("当前服务订单为非线下评估服务订单，无法更新线下评估护理等级和服务结束时间");
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
		$record = $recordmodel->where($map)->order("updatetime desc")->find();
		if(empty($record)){
			E("当前订单暂未进行线下评估，无法更新订单护理等级信息");
		}

		$time_type = $order["time_type"];

		$time = $order["time"];

		$begintime = $order["begintime"];

		$endtime = $order["endtime"];

		$care_level_list = [
			array("title"=>"自理", "level"=>1, "index"=>"one"),
			array("title"=>"轻度失能", "level"=>2, "index"=>"two"),
			array("title"=>"中度失能", "level"=>3, "index"=>"three"),
			array("title"=>"重度失能", "level"=>4, "index"=>"four"),
			array("title"=>"特重护理", "level"=>5, "index"=>"five")
		];

		$current_care = $care_level_list[$record["assess_care_level"]-1];
		$current_care_level = array(
			"care_level_name"=>$current_care["title"], "care_level"=>$current_care["level"]
		);

		$select_care_level = [];
		for($i=$record["assess_care_level"]; $i<=5; $i++){
			$item = $care_level_list[$i-1];

			$care_level_item = array(
				"care_level_name"=>$item["title"], "care_level"=>$item["level"],
				"price"=>$depositprice[$item["index"]."_price"], "selected"=>0
			);

			if($record["care_level"] == $i){
				$care_level_item["selected"] = 1;
			}

			$select_care_level[] = $care_level_item;
		}

		$data = array(
			"time_type"=>$time_type, "time"=>$time, "begintime"=>$begintime, "endtime"=>$endtime,
			"current_care_level"=>$current_care_level, "select_care_level"=>$select_care_level
		);

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
		
		$map = array("service_userid"=>$user["id"], "id"=>$orderid);
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
			"doctor_1"=>"", "doctor_2"=>"", "doctor_image"=>"", "submit"=>0
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

			//可提交状态
			if($order["assess_status"] == 2 && $order["again_status"] == 2){
				$data["submit"] = 1;
			}
		}

		return $data;
	}

	//更新线下评估服务结束时间和缴付尾款 - 康复师
	public function orderupdateassess4(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.orderid", 0);
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

		$time_type = $order["time_type"];

		$time = $order["time"];

		$begintime = $order["begintime"];

		$endtime = $order["endtime"];

		$amount = $order["again_price"];
		if(empty($amount)){
			$amount = "";
		}

		$data = array(
			"time_type"=>$time_type, "time"=>$time, "begintime"=>$begintime, "endtime"=>$endtime, "amount"=>$amount
		);

		return $data;
	}
}