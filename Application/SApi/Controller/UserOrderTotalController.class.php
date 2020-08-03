<?php
namespace SApi\Controller;
use Think\Controller;
class UserOrderTotalController extends BaseLoggedController {
	
	//统计分析
	public function ordertotal(){
		$user = $this->AuthUserInfo;

		$ordermodel = D("service_order");

		$map = array("service_userid"=>$user["id"], "status"=>array("not in", [-1,2]), "pay_status"=>3);

		//统计时间 0=全部,1=当天,2=最近三天,3=最近一周,4=最近1个月,5=最近3个月,6=最近半年
		$date = I("get.date", 0);
		switch($date){
			case 1:
				$time = date("Y-m-d", time());
				$map["begintime"] = array("egt", $time);
				break;
			case 2:
				$time = date("Y-m-d", strtotime("-3 day", time()));
				$map["begintime"] = array("egt", $time);
				break;
			case 3:
				$time = date("Y-m-d", strtotime("-7 day", time()));
				$map["begintime"] = array("egt", $time);
				break;
			case 4:
				$time = date("Y-m-d", strtotime("-1 month", time()));
				$map["begintime"] = array("egt", $time);
				break;
			case 5:
				$time = date("Y-m-d", strtotime("-3 month", time()));
				$map["begintime"] = array("egt", $time);
				break;
			case 6:
				$time = date("Y-m-d", strtotime("-6 month", time()));
				$map["begintime"] = array("egt", $time);
				break;
		}

		//服务项目
		$projectid = I("get.projectid", 0);
		if($projectid){
			$map["projectid"] = $projectid;
		}

		$list = $ordermodel->where($map)->select();

		$data = array(
			"total"=>count($list), "completed"=>0, "to_be_service"=>0,
			"in_service"=>0, "to_user_service"=>0, "refund"=>0, "refunded"=>0
		);

		foreach($list as $k=>$v){
			$order = $v;

			if($order["status"] == 4){
				$data["completed"] += 1;
			} else if($order["status"] == 1 && $order["execute_status"] == 0){
				$data["to_be_service"] += 1;
			} else if($order["status"] == 1 && in_array($order["execute_status"], [1,2])){
                $data["in_service"] += 1;
            } else if($order["status"] == 1 && $order["execute_status"] == 3){
                $data["to_user_service"] += 1;
            } else if($order["status"] == 5){
				$data["refund"] += 1;
			} else if($order["status"] == 6){
				$data["refunded"] += 1;
			}

		}

		return $data;
	}

	//我的业绩
	public function orderrecord(){
		$user = $this->AuthUserInfo;
		
		$ordermodel = D("service_order");

		$order = "so.createdate desc";
		$map = array("so.service_userid"=>$user["id"], "so.status"=>4);
		$list = $ordermodel->alias("so")->join("left join sj_service_comment as sc on so.id=sc.orderid")
			->field("so.*,sc.score")->where($map)->order($order)->select();

		$data = array(
			"total_amount"=>0, "total_platform_amount"=>0,"money"=>$user['profile']['money'],
			"current_amount"=>0, "current_platform_amount"=>0, "list"=>[],
			"comment"=>$user["profile"]["comment_percent"], "service_level"=>$user["profile"]["service_level"]
		);

		//月初
		$time = date("Y-m-01", time());

        $commentmodel = D("service_comment");

		foreach($list as $k=>$v){
			$order = $v;

			//总业绩
			$data["total_amount"] += getNumberFormat($order["total_amount"]);
			if($order["again_count"] > 0){
				$data["total_amount"] += getNumberFormat($order["again_price"]);
			}
			//总补贴
			$data["total_platform_amount"] += getNumberFormat($order["platform_money"]);

            $begintime = date("Y-m-d", strtotime($order["begintime"]));
			if(strtotime($begintime) >= strtotime($time)){
				//本月业绩
				$data["current_amount"] += getNumberFormat($order["total_amount"]);
				if($order["again_count"] > 0){
					$data["current_amount"] += getNumberFormat($order["again_price"]);
				}
				//本月补贴
				$data["current_platform_amount"] += getNumberFormat($order["platform_money"]);
			}

			if(count($data["list"]) <= 10){
				$order["thumb"] = $this->DoUrlHandle($order["thumb"]);
				$order["coupon_money"] = getNumberFormat($order["coupon_money"]);
				$order["total_amount"] = getNumberFormat($order["total_amount"]);
				$order["amount"] = getNumberFormat($order["amount"]);

				$begintime = strtotime($order["begintime"]);
				$order["begintime"] = date("Y/m/d H:i", $begintime);
				$endtime = strtotime($order["endtime"]);
				if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
					$order["endtime"] = date("H:i", $endtime);
				} else{
					$order["endtime"] = date("Y/m/d H:i", $endtime);
				}

				//是否评论
				$order["isscore"] = 0;
				if ($order["commentid"] > 0 && $order["score"] > 0) {				
					$order["isscore"] = 1;
				} else{
					$order["score"] = 0;
				}

				//平台补贴（优惠券金额）
				$order["platform_money"] = getNumberFormat($order["platform_money"]);

				$data["list"][] = $order;
			}
		}

		return $data;
	}

	//业绩明细
	public function orderrecorddetail(){
		$user = $this->AuthUserInfo;

		$profile = $user["profile"];

		//类型：0=全部，1=本月业绩，2=本月补贴，3=降级指标，4=升级指标
		$type = I("get.type", 0);

		$ordermodel = D("service_order");

		$order = "so.createdate desc";
		$map = array("so.status"=>4, "so.service_userid"=>$user["id"]);
		if($type == 1){
			$date = date("Y-m-01", time());
			$map["so.begintime"] = array("egt", $date);
		} else if($type == 2){
			$date = date("Y-m-01", time());
			$map["so.begintime"] = array("egt", $date);
			$map["so.platform_money"] = array("gt", 0);
		} else if($type == 3){
			$check_time = $profile["service_level_check_time"];
			$map["so.begintime"] = array("egt", $check_time);
			$map["so.commentid"] = array("gt", 0);
			$map["sc.score"] = array("gt", 0);
		} else if($type == 4){
			$update_time = $profile["service_level_update_time"];
			$map["so.begintime"] = array("egt", $update_time);
			$map["so.commentid"] = array("gt", 0);
			$map["sc.score"] = array("gt", 0);
		}
		$list = $ordermodel->alias("so")->join("left join sj_service_comment as sc on so.id=sc.orderid")
			->field("so.*,sc.score")->where($map)->order($order)->select();

		$ordercount = count($list);

		//累计业绩
		$total_amount = 0;
		//累计补贴
		$total_platform_amount = 0;
		//总评分
		$total_score = 0;
		$total_comment = 0;

		foreach($list as $k=>$v){
			$v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["coupon_money"] = getNumberFormat($v["coupon_money"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);

			$begintime = strtotime($v["begintime"]);
            $v["begintime"] = date("Y/m/d H:i", $begintime);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                $v["endtime"] = date("H:i", $endtime);
            } else{
                $v["endtime"] = date("Y/m/d H:i", $endtime);
            }

			//是否评论
			$v["isscore"] = 0;
			
            if ($v["commentid"] > 0 && $v["score"] > 0) {
				//评分累加
				$total_score += $v["score"];
				$total_comment += 1;
				
				$v["isscore"] = 1;
			} else{
				$v["score"] = 0;
			}

			//平台补贴（优惠券金额）
			$v["platform_money"] = getNumberFormat($v["platform_money"]);
			
			$list[$k] = $v;

			//累计业绩
			$total_amount += $v["total_amount"];
			if($v["again_count"] > 0){
				$total_amount += getNumberFormat($v["again_price"]);
			}
			//累计补贴（优惠券金额）
			$total_platform_amount += $v["platform_money"];
		}
		
		//平均评分
		$score = 0;
		if($total_score > 0 && $total_comment > 0){
			$score = intval($total_score/$total_comment);
		}

		$data = array(
			"list"=>$list, "ordercount"=>$ordercount, "total_amount"=>getNumberFormat($total_amount),
			"total_platform_amount"=>getNumberFormat($total_platform_amount), "score"=>$score
		);

		return $data;
	}
	
}