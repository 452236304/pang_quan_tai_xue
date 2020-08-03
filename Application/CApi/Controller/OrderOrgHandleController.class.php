<?php
namespace CApi\Controller;
use Think\Controller;
class OrderOrgHandleController extends BaseLoggedController {
	
	//机构订单评价
	public function orgcomment(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择要评价的订单");
		}
		$orgid = $data["orgid"];
		if(empty($orgid)){
			E("请选择要评价的机构");
		}
		$content = $data["content"];
		if(empty($content)){
			E("请输入机构的评价内容");
		}
		$comment1 = $data["comment1"];
		if(empty($comment1)){
			E("请对机构设施进行评价");
		}
		$comment2 = $data["comment2"];
		if(empty($comment2)){
			E("请对周边环境进行评价");
		}
		$comment3 = $data["comment3"];
		if(empty($comment3)){
			E("请对服务态度进行评价");
		}
		$comment4 = $data["comment4"];
		if(empty($comment4)){
			E("请对专业能力进行评价");
		}
		$images = $data["images"];

		$orgmodel = D("org");
		$map = array("status"=>1, "id"=>$orgid);
		$org = $orgmodel->where($map)->find();
		if(empty($org)){
			E("机构不存在");
		}

		$ordermodel = D("org_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，评价失败");
		}
		if($order["status"] != 4){
			E("订单未完成，评价失败");
		}
		if($order["commentid"] > 0){
			E("订单机构已经评价，请勿重复评价");
		}

		$entity = array(
			"status"=>1, "userid"=>$user["id"], "nickname"=>$user["nickname"], "avatar"=>$user["avatar"],
			"orgid"=>$org["id"], "title"=>$org["title"], "thumb"=>$org["thumb"],
			"images"=>$images, "content"=>$content, "orderid"=>$order["id"], "ordersn"=>$order["sn"],
			"createdate"=>date("Y-m-d H:i:s"),"comment1"=>$comment1, "comment2"=>$comment2,
            "comment3"=>$comment3, "comment4"=>$comment4
		);

		$commentid = $entity["id"] = D("org_comment")->add($entity);

		//设置订单为已评论
		$map = array("userid"=>$user["id"], "id"=>$order["id"]);
		$ordermodel->where($map)->save(array("commentid"=>$commentid));

		return;
	}
	
	//创建机构订单
	public function createorgorder(){
		$user = $this->AuthUserInfo;

		$data = I("post.");
		
		//订单来源
		$hybrid = $this->GetHttpHeader("platform");

		// 1=预约参观,2=机构长住,3=短期入住
		$type = $data["type"];
		if(!in_array($type, [1,2,3])){
			E("请选择创建机构订单的类型");
		}
		
		$objectid = $data["objectid"];
		if(empty($objectid)){
			if($type != 3){
				E("请选择预约参观或机构长住的活动");
			} else{
				E("请选择入住机构");
			}
		}

		if($type != 3){
			$activitymodel = D("org_activity");
			$map = array("status"=>1, "id"=>$objectid);
			$object = $activitymodel->where($map)->find();
			if(empty($object)){
				E("您选择的预约参观或机构长住的活动不存在");
			}
		} else{
			$orgmodel = D("org");
			$map = array("status"=>1, "id"=>$objectid);
			$object = $orgmodel->where($map)->find();
			if(empty($object)){
				E("您选择的入住机构不存在");
			}

			$orgpriceid = $data["orgpriceid"];
			if(empty($orgpriceid)){
				E("请选择短期入住的周期");
			}

			$orgpricemodel = D("org_price");
			$map = array("status"=>1, "type"=>1, "orgid"=>$objectid, "id"=>$orgpriceid);
			$orgprice = $orgpricemodel->where($map)->find();
			if(empty($orgprice)){
				E("您选择的短期入住周期不存在");
			}

			$object["price"] = $orgprice["price"];
			$object["date"] = $orgprice["date"];

			$attribute1 = $data["attribute1"];
			if(empty($attribute1)){
				E("请选择床位需求");
			}
			$attribute2 = $orgprice["date"]."天";
			$attribute3 = $data["attribute3"];
			if(empty($attribute3)){
				E("请选择入住时间");
			}
			if(time() > strtotime($attribute3)){
				E("入住时间不能小于当前时间");
			}
			$attribute4 = $data["attribute4"];
			if(empty($attribute4)){
				E("请选择护理级别");
			}

			$usercareid = $data["usercareid"];
			if(empty($usercareid)){
				E("请选择照护人");
			}
			$map = array("userid"=>$user["id"], "id"=>$usercareid);
			$checkusercare = D("user_care")->where($map)->find();
			if(empty($checkusercare)){
				E("您选择的照护人不存在");
			}
            if (getAge($checkusercare['birth']) < 55) {
                E("对不起，您选择的照护人年龄<55岁，非服务对象，谢谢关注！");
            }
			$contact = $data["contact"];
			if(empty($contact)){
				E("请输入联系姓名");
			}
			$mobile = $data["mobile"];
			if(empty($mobile)){
				E("请输入联系手机号码");
			}
			if(!isMobile($mobile)){
				E("手机号码格式不正确");
			}
			$other_remark = $data["other_remark"];
		}
		

		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}

		//订单标题
		$ordertitle = $object["title"];

		//订单总金额
		$totalamount = $object["price"];

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
			if($coupon["use_end_date"] < date("Y-m-d H:i:s")){
				E("您选择的优惠券已失效");
			}

			if($coupon["min_amount"] > $totalamount){
				E("订单总金额小于优惠券的最低使用金额：".$coupon["min_amount"]."元");
			}

			//优惠券金额
			$coupon_money = $coupon["money"];
		}

		//订单支付金额
		$amount = $totalamount - $coupon_money;

		$order = array(
			"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>$type,
			"title"=>$ordertitle, "thumb"=>$object["thumb"], "objectid"=>$objectid, "status"=>1, "pay_status"=>0,
			"couponid"=>$couponid, "coupon_money"=>$coupon_money, "total_amount"=>$totalamount, "amount"=>$amount,
			"remark"=>"", "createdate"=>date("Y-m-d H:i:s"), "keyword"=>$ordertitle,"hybrid"=>$hybrid
		);

		//短期入住
		if($type == 3){
			$order["title"] = "短期入住";
			$order["objectid"] = 0;
			$order["keyword"] = $order["title"].",".$order["keyword"];
			$order["orgid"] = $objectid;
			$order["org_name"] = $object["title"];
			$order["attribute1"] = $attribute1;
			$order["attribute2"] = $attribute2;
			$order["attribute3"] = $attribute3;
			$order["attribute4"] = $attribute4;
			$order["careid"] = $usercareid;
			$order["contact"] = $contact;
			$order["mobile"] = $mobile;
			$order["other_remark"] = $other_remark;
		}

		//检查订单是否免费
		if($amount <= 0){
			$order["pay_status"] = 3;
			$order["status"] = 4;
			$order["pay_date"] = date("Y-m-d H:i:s");
			//免费时发送短信
			$RequestSms=D('Common/RequestSms');
			$info=array('mobile'=>$user['mobile']);
			switch($type){
				case 1:
					//一元参观
					$RequestSms->SendVisit($info);
					break;
				case 2:
					//机构长住
					$info['title']=$ordertitle;
					$RequestSms->SendLongStay($info);
					break;
				case 3:
					//短期入住
					$info['title'] = $ordertitle;
					$info['time'] = $attribute3;
					$RequestSms->SendShortStay($info);
					break;
			}
			
		}

		$ordermodel = D("org_order");

		$orderid = $order["id"] = $ordermodel->add($order);

        //检查订单是否免费 分销体系
        if($amount <= 0){
            D('Brokerage', 'Service')->orderSettle(1, $orderid);
        }

		//更新优惠券信息
		if($coupon){
			$entity = array("orderid"=>$orderid, "status"=>1, "use_type"=>2);
			$map = array("userid"=>$user["id"], "id"=>$coupon["id"]);
			$couponmodel->where($map)->save($entity);
		}

		return array("orderid"=>$order["id"], "ordersn"=>$order["sn"], "amount"=>$amount,'coupon_money'=>$coupon_money,'createdate'=>date('Y/m/d'),"title"=>$ordertitle);
	}

	//取消订单
	public function cancelorder(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要取消的订单");
		}

		$model = D("org_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["status"] == 4){
			E("订单已完成，无法取消");
		}
		if($order["pay_status"] == 3){
			E("订单已支付，无法取消");
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

		$model = D("org_order");

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
			E("请选择要申请的订单");
		}

		$model = D("org_order");

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
		$refundmodel = D("org_order_refund");
		$entity = array(
			"userid"=>$user["id"], "orderid"=>$order["id"], "reason"=>$reason, "images"=>$images,
			"createdate"=>date("Y-m-d H:i:s"), "status"=>1
		);
		$refundmodel->add($entity);

		//更新订单的售后状态
		$entity = array("status"=>5);
		$model->where($map)->save($entity);

		return;
	}

	//取消售后订单
    public function cancelrefund(){
        $user = $this->AuthUserInfo;

        $orderid = I("post.orderid", 0);
        if(empty($orderid)){
            E("请选择要取消的售后订单");
        }
        $model = D("org_order");

        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $order = $model->where($map)->find();
        if(empty($order)){
            E("订单不存在，操作失败");
        }
        if(!($order["status"] == 5 && $order["pay_status"] == 3)){
            E("订单状态异常，操作失败");
		}
		
        //订单状态改变
        $entity = array('status'=>4);
        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $model->where($map)->save($entity);

        return;
    }

}