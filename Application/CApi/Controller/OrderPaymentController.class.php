<?php
namespace CApi\Controller;
use Think\Controller;
class OrderPaymentController extends BaseLoggedController {
	
	//支付订单信息
	public function paymentinfo(){
		$user = $this->AuthUserInfo;

		$orderid = I("get.oderid");
		if(empty($orderid)){
			E("请选择支付的订单");
		}
		$type = I("get.type");
		if(empty($type)){
			E("请选择支付的订单类型");
		}

		switch($type){
			case "product": //商品订单
				$ordermodel = D("product_order");
				break;
			case "org": //机构订单
				$ordermodel = D("org_order");
				break;
			case "service": //服务订单
				$ordermodel = D("service_order");
				break;
            case "public_benefit": //公益活动订单
                $ordermodel = D("public_benefit_order");
                break;
			case "pension": //公益活动订单
			    $ordermodel = D("pension_activity_order");
			    break;
			default:
				E("不存在订单类型");
		}
		
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("选择支付的订单不存在");
		}
		if(empty($order['createdate'])){
			$order['createdate']=$order['createtime'];
		}
		$data = array(
			"orderid"=>$order["id"], "ordersn"=>$order["sn"], "title"=>$order["title"],
			"coupon_money"=>$order["coupon_money"], "amount"=>$order["amount"], "createdate"=>$order["createdate"]
		);

		if($type == "service"){
			//续费中状态
			if($order["again_status"] == 1){  
				if($order["time_type"] == 3){ // 待缴费
					$data["amount"] = $order["again_price"];
				} else{ // 待续费
					$data["amount"] = $order["again_price"];
				}

				$data["coupon_money"] = 0;
			}
		}

		return $data;
	}

	//商城订单支付
	public function paymentproduct(){
		$user = $this->AuthUserInfo;

		$data = I("post.");
		
		$paytype = $data["paytype"]; //1=微信支付，2=支付宝
		if(!in_array($paytype, [1,2])){
			E("请选择支付方式");
		}
		$hybrid = $data["hybrid"];
		if(!in_array($hybrid, ["xcx", "app"])){
			E("请选择请求来源");
		}
		if($hybrid == "xcx"){
			$openid = $data["openid"];
			if(empty($openid) && $paytype == 1){
				E("微信openid不能为空");
			}
		}
		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择支付的订单");
		}
		
		$paylogmodel = D("pay_log");
		
		$ordermodel = D("product_order");
		$amount = 0;
		$orderid = explode(',',$data["orderid"]);
		foreach($orderid as $k=>$v){
			$map = array("userid"=>$user["id"], "id"=>$v);
			$order = $ordermodel->where($map)->find();
			if(empty($order)){
				E("订单不存在，支付失败");
			}
			if($order["paystatus"] == 3){
				E("订单已支付，请勿重复支付");
			}
			if($order["status"] != 1 || $order["paystatus"] != 0){
				E("订单状态异常，支付失败");
			}
			
			$time = time();
			$outtime = strtotime("+30 minute", strtotime($order["createdate"]));
			if($time > $outtime){
				E("订单已超时，支付失败");
			}
			$order = D('product_order')->field('amount,logid')->where(array('id'=>$v))->find();
			if($order['is_point_shop'] == 1 && $order['point'] > $user['point']){
				//是积分订单时判断剩余积分是否足够
				E('积分不足');
			}
			$amount += $order['amount'];
		}
		
		
		$map = array("orderid"=>$data["orderid"],"type"=>1);
		$paylog = $paylogmodel->where($map)->find();
		if(empty($paylog)){
			$paylog = array(
				"sn"=>($this->BuildOrderSn()), "type"=>1, "orderid"=>$data["orderid"], "amount"=>$amount,
				"ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
			);
			$paylog["id"] = $paylogmodel->add($paylog);
		} else { 
			if($paylog["ispaid"] == 1){
				E("订单已经支付，请勿重复支付");
			}

			if(in_array($paytype, [1,2])){
				$closeordersn = $paylog["type"]."_".$paylog["sn"];

				$paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
			}
			$paylog["amount"] = $paylog_entity["amount"] = $amount;
			$paylog["isonline"] = $paylog_entity["isonline"] = 1;
			$paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
			$paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
		}

		//更新订单的日志ID
		if($order["logid"] != $paylog["id"]){
			$o_entity = array("logid"=>$paylog["id"], "pay_type"=>$paytype);
			$ordermodel->where(array('id'=>array('in',$data["orderid"])))->save($o_entity);
		}
		if($paytype == 1){ //微信支付
			$data = array(
				"attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
				"title"=>"一点椿-商城", "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
				"amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
			);
			$wxmodel = D("Payment/WxJsApi");
			$parameter = $wxmodel->WxPayUnifiedOrder($data);
			if($parameter["result_code"] == "FAIL"){
				E("调起微信支付失败，请稍后尝试");
			}
		} else if($paytype == 2){ //支付宝
			if($hybrid == "app"){
				$data = array(
					"subject"=>"一点椿-商城", "body"=>"一点椿-商城：《".$order["title"]."》",
					"amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
				);
				$alimodel = D("Payment/AlipayApp");
				$parameter = $alimodel->AlipayUnifiedOrder($data);
			}
		}

		if(empty($parameter)){
			E("请求支付异常，请重新尝试");
		}

		if($closeordersn){
			$this->closepayorder($closeordersn);
		}

		//更新订单来源
		$o_entity = array("hybrid"=>$hybrid);
		$map = array("id"=>array('in',$orderid));
		$ordermodel->where($map)->save($o_entity);
		
		return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
	}
	
	//机构订单支付
	public function paymentorg(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$paytype = $data["paytype"]; //1=微信支付，2=支付宝
		if(!in_array($paytype, [1,2])){
			E("请选择支付方式");
		}
		$hybrid = $data["hybrid"];
		if(!in_array($hybrid, ["xcx", "app"])){
			E("请选择请求来源");
		}
		if($hybrid == "xcx"){
			$openid = $data["openid"];
			if(empty($openid) && $paytype == 1){
				E("微信openid不能为空");
			}
		}
		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择支付的订单");
		}

		$ordermodel = D("org_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，支付失败");
		}
		if($order["paystatus"] == 3){
			E("订单已支付，请勿重复支付");
		}
		if($order["status"] != 1 || $order["paystatus"] != 0){
			E("订单状态异常，支付失败");
		}
		$time = time();
		$outtime = strtotime("+30 minute", strtotime($order["createdate"]));
		if($time > $outtime){
			E("订单已超时，支付失败");
		}
		
		$paylogmodel = D("pay_log");

		$map = array("orderid"=>$order["id"],"type"=>2);
		$paylog = $paylogmodel->where($map)->find();
		if(empty($paylog)){
			$paylog = array(
				"sn"=>($this->BuildOrderSn()), "type"=>2, "orderid"=>$order["id"], "amount"=>$order["amount"],
				"ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
			);
			$paylog["id"] = $paylogmodel->add($paylog);
		} else { 
			if($paylog["ispaid"] == 1){
				E("订单已经支付，请勿重复支付");
			}

			if(in_array($paytype, [1,2])){
				$closeordersn = $paylog["type"]."_".$paylog["sn"];

				$paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
			}
			$paylog["amount"] = $paylog_entity["amount"] = $order["amount"];
			$paylog["isonline"] = $paylog_entity["isonline"] = 1;
            $paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
			$paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
		}
		
		//更新订单的日志ID
		if($order["logid"] != $paylog["id"]){
			$o_entity = array("logid"=>$paylog["id"], "pay_type"=>$paytype);
			$ordermodel->where("id=".$order["id"])->save($o_entity);
		}

		if($paytype == 1){ //微信支付
			$data = array(
				"attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
				"title"=>"一点椿-机构照护", "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
				"amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
			);
			$wxmodel = D("Payment/WxJsApi");
			$parameter = $wxmodel->WxPayUnifiedOrder($data);
			if($parameter["result_code"] == "FAIL"){
				E("调起微信支付失败，请稍后尝试");
			}
		} else if($paytype == 2){ //支付宝
			if($hybrid == "app"){
				$data = array(
					"subject"=>"一点椿-机构照护", "body"=>"一点椿-机构照护：《".$order["title"]."》",
					"amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
				);
				$alimodel = D("Payment/AlipayApp");
				$parameter = $alimodel->AlipayUnifiedOrder($data);
			}
		}

		if(empty($parameter)){
			E("请求支付异常，请重新尝试");
		}

		if($closeordersn){
			$this->closepayorder($closeordersn);
		}
		
		return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
	}

	//服务订单支付
	public function paymentservice(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$paytype = $data["paytype"]; //1=微信支付，2=支付宝
		if(!in_array($paytype, [1,2])){
			E("请选择支付方式");
		}
		$hybrid = $data["hybrid"];
		if(!in_array($hybrid, ["xcx", "app"])){
			E("请选择请求来源");
		}
		if($hybrid == "xcx"){
			$openid = $data["openid"];
			if(empty($openid) && $paytype == 1){
				E("微信openid不能为空");
			}
		}
		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择支付的订单");
		}

		$ordermodel = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，支付失败");
		}
		if($order["paystatus"] == 3){
			E("订单已支付，请勿重复支付");
		}
		if($order["status"] != 1 || $order["paystatus"] != 0){
			E("订单状态异常，支付失败");
		}
		$time = time();
		$outtime = strtotime("+30 minute", strtotime($order["createdate"]));
		if($time > $outtime){
			E("订单已超时，支付失败");
		}
		
		$paylogmodel = D("pay_log");

		$map = array("orderid"=>$order["id"],"type"=>3);
		$paylog = $paylogmodel->where($map)->find();
		if(empty($paylog)){
			$paylog = array(
				"sn"=>($this->BuildOrderSn()), "type"=>3, "orderid"=>$order["id"], "amount"=>$order["amount"],
				"ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
			);
			$paylog["id"] = $paylogmodel->add($paylog);
		} else { 
			if($paylog["ispaid"] == 1){
				E("订单已经支付，请勿重复支付");
			}

			if(in_array($paytype, [1,2])){
				$closeordersn = $paylog["type"]."_".$paylog["sn"];

				$paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
			}
			$paylog["amount"] = $paylog_entity["amount"] = $order["amount"];
			$paylog["isonline"] = $paylog_entity["isonline"] = 1;
            $paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
			$paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
		}

		//更新订单的日志ID
		if($order["logid"] != $paylog["id"]){
			$o_entity = array("logid"=>$paylog["id"], "pay_type"=>$paytype);
			$ordermodel->where("id=".$order["id"])->save($o_entity);
		}

		if($paytype == 1){ //微信支付
			$data = array(
				"attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
				"title"=>"一点椿-服务项目", "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
				"amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
			);
			$wxmodel = D("Payment/WxJsApi");
			$parameter = $wxmodel->WxPayUnifiedOrder($data);
			if($parameter["result_code"] == "FAIL"){
				E("调起微信支付失败，请稍后尝试");
			}
		} else if($paytype == 2){ //支付宝
			if($hybrid == "app"){
				$data = array(
					"subject"=>"一点椿-服务项目", "body"=>"一点椿-服务项目：《".$order["title"]."》",
					"amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
				);
				$alimodel = D("Payment/AlipayApp");
				$parameter = $alimodel->AlipayUnifiedOrder($data);
			}
		}

		if(empty($parameter)){
			E("请求支付异常，请重新尝试");
		}

		if($closeordersn){
			$this->closepayorder($closeordersn);
		}
		
		return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
	}

	//服务续费订单支付
	public function paymentserviceagain(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$paytype = $data["paytype"]; //1=微信支付，2=支付宝
		if(!in_array($paytype, [1,2])){
			E("请选择支付方式");
		}
		$hybrid = $data["hybrid"];
		if(!in_array($hybrid, ["xcx", "app"])){
			E("请选择请求来源");
		}
		if($hybrid == "xcx"){
			$openid = $data["openid"];
			if(empty($openid) && $paytype == 1){
				E("微信openid不能为空");
			}
		}
		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择支付的订单");
		}

		$ordermodel = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，支付失败");
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
		if($order["again_status"] == 2){
			E("服务订单已经续费，支付失败");
		}
		if($order["again_recordid"] <= 0){
			E("服务订单暂未提交续费信息，支付失败");
		}

		//续费订单记录
		$orderagainmodel = D("service_order_again_record");

		$map = array("userid"=>$user["id"], "orderid"=>$order["id"], "id"=>$order["again_recordid"], "type"=>1);
		$againrecord = $orderagainmodel->where($map)->find();
		if(empty($againrecord)){
			E("服务订单暂未提交续费信息，支付失败");
		}
		
		if($againrecord['is_agree']==0){
			E("服务人员暂未同意缴费");
		}
		
		$paylogmodel = D("pay_log");

		$map = array("orderid"=>$againrecord["id"], "type"=>4);
		$paylog = $paylogmodel->where($map)->find();
		if(empty($paylog)){
			$paylog = array(
				"sn"=>($this->BuildOrderSn()), "type"=>4, "orderid"=>$againrecord["id"], "amount"=>$againrecord["amount"],
				"ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
			);
			$paylog["id"] = $paylogmodel->add($paylog);
		} else { 
			if($paylog["ispaid"] == 1){
				E("订单已经支付，请勿重复支付");
			}

			if(in_array($paytype, [1,2])){
				$closeordersn = $paylog["type"]."_".$paylog["sn"];

				$paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
			}
			$paylog["amount"] = $paylog_entity["amount"] = $againrecord["amount"];
			$paylog["isonline"] = $paylog_entity["isonline"] = 1;
            $paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
			$paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
		}

		//更新续费订单的日志ID
		if($againrecord["logid"] != $paylog["id"]){
			$o_entity = array("logid"=>$paylog["id"], "pay_type"=>$paytype);
			$orderagainmodel->where("id=".$againrecord["id"])->save($o_entity);
		}

		if($paytype == 1){ //微信支付
			$data = array(
				"attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
				"title"=>"一点椿-服务项目续费", "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
				"amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
			);
			$wxmodel = D("Payment/WxJsApi");
			$parameter = $wxmodel->WxPayUnifiedOrder($data);
			if($parameter["result_code"] == "FAIL"){
				E("调起微信支付失败，请稍后尝试");
			}
		} else if($paytype == 2){ //支付宝
			if($hybrid == "app"){
				$data = array(
					"subject"=>"一点椿-服务项目续费", "body"=>"一点椿-服务项目续费".$againrecord["title"]."》",
					"amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
				);
				$alimodel = D("Payment/AlipayApp");
				$parameter = $alimodel->AlipayUnifiedOrder($data);
			}
		}

		if(empty($parameter)){
			E("请求支付异常，请重新尝试");
		}

		if($closeordersn){
			$this->closepayorder($closeordersn);
		}
		
		return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
	}

	//服务缴费订单支付
	public function paymentserviceassess(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$paytype = $data["paytype"]; //1=微信支付，2=支付宝
		if(!in_array($paytype, [1,2])){
			E("请选择支付方式");
		}
		$hybrid = $data["hybrid"];
		if(!in_array($hybrid, ["xcx", "app"])){
			E("请选择请求来源");
		}
		if($hybrid == "xcx"){
			$openid = $data["openid"];
			if(empty($openid) && $paytype == 1){
				E("微信openid不能为空");
			}
		}
		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择支付的订单");
		}

		$ordermodel = D("service_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，支付失败");
		}
		if($order["type"] != 2){
			E("服务订单类型异常，缴费失败");
		}
		if($order["status"] == 4){
			E("服务订单已完成，缴费失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3 && $order["assess_status"] == 2
			&& $order["again_status"] == 1 && $order["execute_status"] == 1)){
			E("服务订单状态异常，缴费失败");
		}
		if($order["again_status"] == 2){
			E("服务订单已经缴费，支付失败");
		}
		if($order["again_recordid"] <= 0){
			E("服务订单暂未提交缴费信息，支付失败");
		}

		//缴费订单记录
		$orderagainmodel = D("service_order_again_record");

		$map = array("userid"=>$user["id"], "orderid"=>$order["id"], "id"=>$order["again_recordid"], "type"=>2);
		$againrecord = $orderagainmodel->where($map)->find();
		if(empty($againrecord)){
			E("服务订单暂未提交续费信息，支付失败");
		}
		
		$paylogmodel = D("pay_log");

		$map = array("orderid"=>$againrecord["id"], "type"=>5);
		$paylog = $paylogmodel->where($map)->find();
		if(empty($paylog)){
			$paylog = array(
				"sn"=>($this->BuildOrderSn()), "type"=>5, "orderid"=>$againrecord["id"], "amount"=>$againrecord["amount"],
				"ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
			);
			$paylog["id"] = $paylogmodel->add($paylog);
		} else { 
			if($paylog["ispaid"] == 1){
				E("订单已经支付，请勿重复支付");
			}

			if(in_array($paytype, [1,2])){
				$closeordersn = $paylog["type"]."_".$paylog["sn"];

				$paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
			}
			$paylog["amount"] = $paylog_entity["amount"] = $againrecord["amount"];
			$paylog["isonline"] = $paylog_entity["isonline"] = 1;
            $paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
			$paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
		}

		//更新缴费订单的日志ID
		if($againrecord["logid"] != $paylog["id"]){
			$o_entity = array("logid"=>$paylog["id"], "pay_type"=>$paytype);
			$orderagainmodel->where("id=".$againrecord["id"])->save($o_entity);
		}

		if($paytype == 1){ //微信支付
			$data = array(
				"attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
				"title"=>"一点椿-服务项目缴费", "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
				"amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
			);
			$wxmodel = D("Payment/WxJsApi");
			$parameter = $wxmodel->WxPayUnifiedOrder($data);
			if($parameter["result_code"] == "FAIL"){
				E("调起微信支付失败，请稍后尝试");
			}
		} else if($paytype == 2){ //支付宝
			if($hybrid == "app"){
				$data = array(
					"subject"=>"一点椿-服务项目缴费", "body"=>"一点椿-服务项目缴费".$againrecord["title"]."》",
					"amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
				);
				$alimodel = D("Payment/AlipayApp");
				$parameter = $alimodel->AlipayUnifiedOrder($data);
			}
		}

		if(empty($parameter)){
			E("请求支付异常，请重新尝试");
		}

		if($closeordersn){
			$this->closepayorder($closeordersn);
		}
		
		return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
	}
	//VIP订单支付
	public function paymentvip(){
		$user = $this->AuthUserInfo;
	
		$data = I("post.");
	
		$paytype = $data["paytype"]; //1=微信支付，2=支付宝 , 3=积分
		if(!in_array($paytype, [1,2,3])){
			E("请选择支付方式");
		}
		$hybrid = $data["hybrid"];
		if(!in_array($hybrid, ["xcx", "app"])){
			E("请选择请求来源");
		}
		if($hybrid == "xcx"){
			$openid = $data["openid"];
			if(empty($openid) && $paytype == 1){
				E("微信openid不能为空");
			}
		}
		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择支付的订单");
		}
	
		$ordermodel = D("vip_order");
	
		$map = array("id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，支付失败");
		}
		if($order["is_pay"] == 1){
			E("订单已支付，请勿重复支付");
		}
		$time = time();
		$outtime = strtotime("+30 minute", strtotime($order["createtime"]));
		if($time > $outtime){
			E("订单已超时，支付失败");
		}
		
		$paylogmodel = D("pay_log");
	
		$map = array("orderid"=>$order["id"],"type"=>6);
		$paylog = $paylogmodel->where($map)->find();
		if(empty($paylog)){
			$paylog = array(
				"sn"=>($this->BuildOrderSn()), "type"=>6, "orderid"=>$order["id"], "amount"=>$order["amount"],
				"ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
			);
			$paylog["id"] = $paylogmodel->add($paylog);
		} else { 
			if($paylog["ispaid"] == 1){
				E("订单已经支付，请勿重复支付");
			}
	
			if(in_array($paytype, [1,2,3])){
				$closeordersn = $paylog["type"]."_".$paylog["sn"];
	
				$paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
			}
			$paylog["amount"] = $paylog_entity["amount"] = $order["amount"];
			$paylog["isonline"] = $paylog_entity["isonline"] = 1;
	        $paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
			$paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
		}
	
		//更新订单的日志ID
		if($order["logid"] != $paylog["id"]){
			$o_entity = array("logid"=>$paylog["id"], "pay_type"=>$paytype);
			$ordermodel->where("id=".$order["id"])->save($o_entity);
		}
	
		if($paytype == 1){ //微信支付
			$data = array(
				"attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
				"title"=>"一点椿-VIP购买", "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
				"amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
			);
			$wxmodel = D("Payment/WxJsApi");
			$parameter = $wxmodel->WxPayUnifiedOrder($data);
			if($parameter["result_code"] == "FAIL"){
				E("调起微信支付失败，请稍后尝试");
			}
		} else if($paytype == 2){ //支付宝
			if($hybrid == "app"){
				$data = array(
					"subject"=>"一点椿-VIP购买", "body"=>"一点椿：《VIP购买》",
					"amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
				);
				$alimodel = D("Payment/AlipayApp");
				$parameter = $alimodel->AlipayUnifiedOrder($data);
			}
		}
	
		if(empty($parameter)){
			E("请求支付异常，请重新尝试");
		}
	
		if($closeordersn){
			$this->closepayorder($closeordersn);
		}
		
		return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
	}
	//支付状态
	public function paylogstatus(){
		$logid = I("get.logid", 0);
		if($logid <= 0){
			return;
		}

		$paylogmodel = D("pay_log");

		$paylog = $paylogmodel->find($logid);
		if(empty($paylog)){
			return;
		}

		//ispaid：0=未支付,1=已支付
		return array("status"=>$paylog["ispaid"]);
	}

	//关闭支付订单
	private function closepayorder($ordersn){
		if(empty($ordersn)){
			return;
		}

		//关闭微信订单
		$wxmodel = D("Payment/WxBase");
		$data = array("ordersn"=>$ordersn);
		$wxmodel->WxCloseOrder($data);

		//关闭支付宝订单
		$alimodel = D("Payment/AlipayBase");
		$data = array("ordersn"=>$ordersn);
		$alimodel->AlipayCloseOrder($data);

		return;
	}

    /**
     * Notes: 公益活动调起支付
     * User: dede
     * Date: 2020/3/24
     * Time: 4:10 下午
     * @return array
     * @throws \Think\Exception
     */
	public function publicBenefitOrderPay(){
        $user = $this->AuthUserInfo;

        $data = I("post.");

        $paytype = $data["paytype"]; //1=微信支付，2=支付宝
        if(!in_array($paytype, [1,2])){
            E("请选择支付方式");
        }
        $hybrid = $data["hybrid"];
        if(!in_array($hybrid, ["xcx", "app"])){
            E("请选择请求来源");
        }
        if($hybrid == "xcx"){
            $openid = $data["openid"];
            if(empty($openid) && $paytype == 1){
                E("微信openid不能为空");
            }
        }
        $orderid = $data["orderid"];
        if(empty($orderid)){
            E("请选择支付的订单");
        }

        $ordermodel = D("PublicBenefitOrder");

        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $order = $ordermodel->where($map)->find();
        if(empty($order)){
            E("订单不存在，支付失败");
        }
        if($order["status"] !== 0){
            E("服务订单状态异常，缴费失败");
        }

        $paylogmodel = D("pay_log");

        $map = array("orderid"=>$orderid, "type"=>7);
        $paylog = $paylogmodel->where($map)->find();
        if(empty($paylog)){
            $paylog = array(
                "sn"=>($this->BuildOrderSn()), "type"=>7, "orderid"=>$order['id'], "amount"=>$order["amount"],
                "ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
            );
            $paylog["id"] = $paylogmodel->add($paylog);
        } else {
            if($paylog["ispaid"] == 1){
                E("订单已经支付，请勿重复支付");
            }

            if(in_array($paytype, [1,2])){
                $closeordersn = $paylog["type"]."_".$paylog["sn"];

                $paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
            }
            $paylog["amount"] = $paylog_entity["amount"] = $order["amount"];
            $paylog["isonline"] = $paylog_entity["isonline"] = 1;
            $paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
            $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
        }

        $info = D('public_benefit')->find($order['public_benefit_id']);
        if($paytype == 1){ //微信支付
            $data = array(
                "attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
                "title"=>$info['title'], "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
                "amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
            );
            $wxmodel = D("Payment/WxJsApi");
            $parameter = $wxmodel->WxPayUnifiedOrder($data);
            if($parameter["result_code"] == "FAIL"){
                E("调起微信支付失败，请稍后尝试");
            }
        } else if($paytype == 2){ //支付宝
            if($hybrid == "app"){
                $data = array(
                    "subject"=>$info['title'], "body"=>$info['title'],
                    "amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
                );
                $alimodel = D("Payment/AlipayApp");
                $parameter = $alimodel->AlipayUnifiedOrder($data);
            }
        }

        if(empty($parameter)){
            E("请求支付异常，请重新尝试");
        }

        if($closeordersn){
            $this->closepayorder($closeordersn);
        }

        return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
    }
	/**
	 * Notes: 新机构订单支付
	 * User: LH
	 * Date: 2020/3/24
	 * Time: 4:10 下午
	 * @return array
	 */
	public function pensionOrderPay(){
	    $user = $this->AuthUserInfo;
	
	    $data = I("post.");
	
	    $paytype = $data["paytype"]; //1=微信支付，2=支付宝
	    if(!in_array($paytype, [1,2])){
	        E("请选择支付方式");
	    }
	    $hybrid = $data["hybrid"];
	    if(!in_array($hybrid, ["xcx", "app"])){
	        E("请选择请求来源");
	    }
	    if($hybrid == "xcx"){
	        $openid = $data["openid"];
	        if(empty($openid) && $paytype == 1){
	            E("微信openid不能为空");
	        }
	    }
	    $orderid = $data["orderid"];
	    if(empty($orderid)){
	        E("请选择支付的订单");
	    }
	
	    $ordermodel = D("pension_activity_order");
	
	    $map = array("userid"=>$user["id"], "id"=>$orderid);
	    $order = $ordermodel->where($map)->find();
	    if(empty($order)){
	        E("订单不存在，支付失败");
	    }
	    if($order["status"] != 0){
	        E("订单状态异常，支付失败");
	    }
	
	    $paylogmodel = D("pay_log");
	
	    $map = array("orderid"=>$orderid, "type"=>8);
	    $paylog = $paylogmodel->where($map)->find();
	    if(empty($paylog)){
	        $paylog = array(
	            "sn"=>($this->BuildOrderSn()), "type"=>8, "orderid"=>$order['id'], "amount"=>$order["amount"],
	            "ispaid"=>0, "isonline"=>1, "hybrid"=>$hybrid
	        );
	        $paylog["id"] = $paylogmodel->add($paylog);
	    } else {
	        if($paylog["ispaid"] == 1){
	            E("订单已经支付，请勿重复支付");
	        }
	
	        if(in_array($paytype, [1,2])){
	            $closeordersn = $paylog["type"]."_".$paylog["sn"];
	
	            $paylog["sn"] = $paylog_entity["sn"] = $this->BuildOrderSn();
	        }
	        $paylog["amount"] = $paylog_entity["amount"] = $order["amount"];
	        $paylog["isonline"] = $paylog_entity["isonline"] = 1;
	        $paylog["hybrid"] = $paylog_entity["hybrid"] = $hybrid;
	        $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
	    }
		
	    if($paytype == 1){ //微信支付
	        $data = array(
	            "attach"=>array("type"=>$paylog["type"], "logsn"=>$paylog["sn"], "logid"=>$paylog["id"], "hybrid"=>$hybrid),
	            "title"=>$order['title'], "ordersn"=>($paylog["type"]."_".$paylog["sn"]),
	            "amount"=>$paylog["amount"], "id"=>$paylog["id"], "hybrid"=>$hybrid, "openid"=>$openid
	        );
	        $wxmodel = D("Payment/WxJsApi");
	        $parameter = $wxmodel->WxPayUnifiedOrder($data);
	        if($parameter["result_code"] == "FAIL"){
	            E("调起微信支付失败，请稍后尝试");
	        }
	    } else if($paytype == 2){ //支付宝
	        if($hybrid == "app"){
	            $data = array(
	                "subject"=>$order['title'], "body"=>$order['title'],
	                "amount"=>$paylog["amount"], "out_trade_no"=>($paylog["type"]."_".$paylog["sn"])
	            );
	            $alimodel = D("Payment/AlipayApp");
	            $parameter = $alimodel->AlipayUnifiedOrder($data);
	        }
	    }
	
	    if(empty($parameter)){
	        E("请求支付异常，请重新尝试");
	    }
	
	    if($closeordersn){
	        $this->closepayorder($closeordersn);
	    }
	
	    return array("parameter"=>$parameter, "logid"=>$paylog["id"], "paytype"=>$paytype);
	}
}