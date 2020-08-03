<?php
namespace Common\Model;

class CouponModel{
	public function send_coupon($user_id,$coupon_id){
		$info = $this->find($couponid);
		if($info["status"] != 1){
			return false;
		}
		$user_coupon_model = D("user_coupon");
		if($userid <= 0){
			return false;
		}
		if($info["count"] <= 0){
			return false;
		}
		$d["couponid"] = $couponid;
		$d["type"] = $info["type"];
		$d["code"] = $info["code"];
		$d["title"] = $info["title"];
		
		$use_date = $info["use_date"];
		$time = time();
		$d["use_start_date"] = date("Y-m-d H:i:s", $time);
		$d["use_end_date"] = date("Y-m-d H:i:s", strtotime("+".$use_date." day", $time));
		
		$d['coupon_type']=$info['coupon_type'];
		if($info['coupon_type']==0){
			$d["money"] = $info["money"];
			$d["min_amount"] = $info["min_amount"];
		}elseif($info['coupon_type']==1){
			$d["product_id"] = $info["product_id"];
		}elseif($info['coupon_type']==2){
			$d["service_id"] = $info["service_id"];
		}elseif($info['coupon_type']==3){
			$d["org_id"] = $info["org_id"];
		}
		
		$d["status"] = 0;
		$d["use_type"] = 0;
		$d["userid"] = $userid;
		
		$d["createdate"] = date("Y-m-d H:i");
		$entity = array(
		    "hybrid"=>'client', "status"=>0, "sendid"=>0, "sender"=>"系统消息", "userid"=>$userid,
		    "title"=>'优惠券', "content"=>'您好，您有一张新的'.$info["title"].',价值'.$info["money"].'元，有效期至'.$d['use_end_date'], "param"=>null,
		    "createdate"=>date("Y-m-d H:i:s"), "type"=>0, "systemid"=>0
		);
		D("user_message")->add($entity);
		$user_coupon_model->add($d);
		
		$entity = array("count"=>($info["count"]-1), "sales"=>($info["sales"]+1));
		$this->where("id=".$couponid)->save($entity);
		return true;
	}
}
