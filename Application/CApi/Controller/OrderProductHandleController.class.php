<?php
namespace CApi\Controller;
use Think\Controller;
class OrderProductHandleController extends BaseLoggedController {
	
	//商品订单评价
	public function productcomment(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择要评价的订单");
		}
		$productid = $data["productid"];
		if(empty($productid)){
			E("请选择要评价的商品");
		}
		$comment1 = $data["comment1"];
		if(empty($comment1)){
			E("请对商品符合度进行评价");
		}
		$comment2 = $data["comment2"];
		if(empty($comment2)){
			E("请对店家服务态度进行评价");
		}
		$comment3 = $data["comment3"];
		if(empty($comment3)){
			E("请对物流发货速度进行评价");
		}
		$comment4 = $data["comment4"];
		if(empty($comment4)){
			E("请对配送员服务态度进行评价");
		}
		$content = $data["content"];
		if(empty($content)){
			E("请输入商品的评价内容");
		}
		$images = $data["images"];

		$ordermodel = D("product_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，评价失败");
		}
		if($order["status"] != 4){
			E("订单未完成，评价失败");
		}

		if($order["type"] == 0) { //商品订单
			$attributeid = $data["attributeid"];
			if(empty($attributeid)){
				E("请选择评论的商品套餐");
			}

            $orderproductmodel = D("product_order_product");
            $map = array("userid"=>$user["id"], "orderid"=>$order["id"], "productid"=>$productid, "attributeid"=>$attributeid);
            $orderproduct = $orderproductmodel->where($map)->find();
            if (empty($orderproduct)) {
                E("订单商品不存在，评价失败");
            }
            if ($orderproduct["commentid"] > 0) {
                E("订单商品已经评价，请勿重复评价");
            }
        } else{ //定制或改造订单
		    $orderattachmodel = D("order_attach");
		    $map = array("orderid"=>$order["id"], "objectid"=>$productid);
		    $orderproduct = $orderattachmodel->where($map)->find();
		    if(empty($orderproduct)){
                E("方案不存在，评价失败");
            }
            if($orderproduct["commentid"] > 0){
                E("方案已经评价，请勿重复评价");
            }
        }

		$entity = array(
			"status"=>1, "userid"=>$user["id"], "nickname"=>$user["nickname"], "avatar"=>$user["avatar"],
			"productid"=>$productid, "title"=>$orderproduct["title"], "thumb"=>$orderproduct["thumb"],
			"images"=>$images, "content"=>$content, "comment1"=>$comment1, "comment2"=>$comment2,
            "comment3"=>$comment3, "comment4"=>$comment4,
			"orderid"=>$order["id"], "ordersn"=>$order["sn"], "createdate"=>date("Y-m-d H:i:s")
		);
		if($order["type"] == 0){ //商品订单
			$entity["attributeid"] = $orderproduct["attributeid"];
			$endtime["attributetitle"] = $orderproduct["attributetitle"];
		}

		//计算评分
        $score1 = intval($comment1)/5*100; //商品符合度
        $score2 = intval($comment2)/5*100; //店家服务态度
        $score3 = intval($comment3)/5*100; //物流发货速度
        $score4 = intval($comment4)/5*100; //配送员服务态度
        $score = ceil((($score1+$score2+$score3+$score4)/400)*100); //评分

        $entity["score"] = $score;

		$commentid = D("product_comment")->add($entity);

		if($order["type"] == 0) {
            //赋值评价id
            $orderproductmodel->where('id=' . $orderproduct['id'])->save(array('commentid' => $commentid));
        } else{
            //赋值评价id
            $orderattachmodel->where('id=' . $orderproduct['id'])->save(array('commentid' => $commentid));
        }

		//设置订单为已评论
		if($order["is_comment"] == 0){
			$map = array("userid"=>$user["id"], "id"=>$order["id"]);
			$ordermodel->where($map)->save(array("is_comment"=>1));
		}

		return;
	}

	//加入购物车商品
	public function shoppingcart(){
		$user = $this->AuthUserInfo;

		$data = I("post.");
		//状态1为增加
        $status = $data["status"];

		$productid = $data["productid"];
		if(empty($productid)){
			E("请选择加入购物车的商品");
		}
		$attributeid = $data["attributeid"];
		if(empty($attributeid)){
			E("请选择加入购物车的商品套餐");
		}
		$quantity = $data["quantity"];
		if(empty($quantity)){
			E("请输入加入购物车的数量");
		}
		if($quantity <= 0){
			E("购买的商品数量不能小于0");
		}

		$attributemodel = D("product_attribute");

		$map = array("productid"=>$productid, "id"=>$attributeid);
		$attribute = $attributemodel->where($map)->find();
		if(empty($attribute)){
			E("商品不存在，无法购买");
		}
		if($attribute["stock"] < $quantity){
			E("库存不足，无法购买");
		}

		$cartmodel = D("shopping_cart");

		$entity = array(
			"userid"=>$user["id"], "productid"=>$attribute["productid"], "attributeid"=>$attributeid,
			"quantity"=>$quantity, "updatetime"=>date("Y-m-d H:i:s")
		);

		$map = array("userid"=>$user["id"], "productid"=>$productid, "attributeid"=>$attributeid);
		$shoppingcart = $cartmodel->where($map)->find();
		if($shoppingcart){
            if ($status == 1) {
                //增加操作
                $total = $shoppingcart["quantity"] + $quantity;
                if ($attribute["stock"] < $total) {
                    E("库存不足，无法购买");
                }
                $entity["quantity"] = $total;
            } else{
				//减少操作
                $total = $shoppingcart["quantity"] - $quantity;
                if ($total <= 0) {
                    $total = 1;
                }
                $entity["quantity"] = $total;
			}
			$cartmodel->where($map)->save($entity);
		} else{
			$cartmodel->add($entity);
		}
		
		//购物车商品数量
		$map = array("userid"=>$user["id"]);
        $count = $cartmodel->where($map)->count();

		return array("count"=>$count);
	}

	//删除购物车商品
	public function deleteshoppingcart(){
		$user = $this->AuthUserInfo;

		//购物车id集合
		$ids = I("post.ids");
		if(empty($ids)){
			E("请选择要移出购物车的商品");
		}

		$ids = explode(",", $ids);

		$model = D("shopping_cart");

		$map = array("userid"=>$user["id"], "id"=>array("in", $ids));
		$model->where($map)->delete();

		return;
	}

	//创建商品订单
	public function createproductorder(){
		$user = $this->AuthUserInfo;

		$data = I("post.");
		
		//订单来源
		$hybrid = $this->GetHttpHeader("platform");
		
		$addressid = $data["addressid"];
		if(empty($addressid)){
			E("请选择收货地址");
		}

		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}

		//立即购买（1=是）
		$now = $data["now"];
		//购买数量
		$quantity = $data["quantity"];
		if(empty($quantity)){
			$quantity = 1;
		}

		//收货地址
		$addressmodel = D("user_address");
		$map = array("userid"=>$user["id"], "id"=>$addressid);
		$address = $addressmodel->where($map)->find();
		if(empty($address)){
			E("收货地址不存在");
		}

		//订单总金额
		$totalamount = 0;

		//商品列表
		$productids = array();
		if($now == 1){ //立即购买
			$productid = $data["productid"];
			if(empty($productid)){
				E("请选择要购买的商品");
			}
			$attributeid = $data["attributeid"];
			if(empty($attributeid)){
				E("请选择购买商品的套餐");
			}
			$productmodel = D("product");
			$map = array("p.status"=>1, "p.id"=>$productid, "a.id"=>$attributeid);
			$products = $productmodel->alias("p")->join("left join sj_product_attribute as a on a.productid=p.id")->field("p.id,p.company_id,p.status,p.title,p.subtitle,p.thumb,a.id as attributeid,a.title as attributetitle,a.price,a.stock,p.freightid,p.brokerage")->where($map)->select();
		} else{
			//购物车id集合
			$sids = $data["sids"];
			if(empty($sids)){
				E("请选择要购买的商品");
			}
			$sids = explode(",", $sids);

			$cartmodel = D("shopping_cart");
			$map = array("s.userid"=>$user["id"], "s.id"=>array("in", $sids));
			$products = $cartmodel->alias("s")->join("left join sj_product as p on s.productid=p.id")
				->join("left join sj_product_attribute as a on a.id=s.attributeid")
				->field("s.quantity,p.company_id,p.id,p.status,p.title,p.subtitle,p.thumb,a.id as attributeid,a.title as attributetitle,a.price,a.stock,p.freightid,p.brokerage")
				->where($map)->select();
		}
		
		if(count($products) <= 0){
			E("您选择购买的商品不存在");
		}
		
		//店铺列表 商品按照店铺拆分 然后再分别创建订单
		$company = [];
		foreach($products as $k=>$v){
			if($now == 1){ //立即购买，默认购买数量为1
				$v["quantity"] = $quantity;
			}
		    
			if($v["status"] != 1){
				E($v["title"]."商品已失效，下单失败");
			}
		    
			if($v["stock"] <= 0 || $v["stock"] < $v["quantity"]){
				E($v["title"]."库存不足，下单失败");
			}
			$company[$v['company_id']][]=$v;
		}
		//检查是否使用优惠券
		$coupon_money=0;
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
			if($coupon['coupon_type']==1){
				//指定商品免费优惠券
				foreach($products as $k=>$v){
					if($v['id']=$coupon['product_id']){
						$coupon['money']=$v['price'];
					}
				}
			}
			//优惠券金额
			$coupon_money = $coupon["money"];
		}
		$orderid = [];
		$ordersn = [];
		$amount = 0;
		$freight = 0;
		
		if($coupon['coupon_type'] == 1){
			$coupon_arr = array('money'=>$coupon_money,'product_id'=>$coupon['product_id'],'id'=>$couponid);
		}else{
			$company_num = count($company);
			$coupon_arr = array('money'=>$coupon_money/$company_num,'id'=>$couponid); 
		}
		
		foreach($company as $k=>$v){
			$order_return = D('ProductOrder')->createproductorder($user,$hybrid,$v,$coupon_arr,$address);
			$orderid[] = $order_return['orderid'];
			$ordersn[] = $order_return['ordersn'];
			$amount += $order_return['amount'];
			$freight += $order_return['freight'];
			$ordertitle .= $order_return['ordertitle'];

            // 7陌订单提醒
            $content = D('Moor', 'Service')->orderMessage($order_return['orderid']);
            D('Moor', 'Service')->createContext($user["id"]);
            D('Moor', 'Service')->sendRobotTextMessage($user["id"], $content);
		}
		
		//删除购物车商品
		if($now != 1){
			$map = array("userid"=>$user["id"], "id"=>array("in", $sids));
			$cartmodel->where($map)->delete();
		}
		
		//更新优惠券信息
		$use_order = array('id'=>$orderid,'userid'=>$user['id']);
		$this->updatecouponstatus($coupon, $use_order);
		$orderid = implode(',',$orderid);
		$ordersn = implode(',',$ordersn);

		
		return array("orderid"=>$orderid, "ordersn"=>$ordersn, "amount"=>$amount,'coupon_money'=>$coupon_money,'freight'=>$freight,'createdate'=>date('Y/m/d'),"title"=>$ordertitle);
	}
	
	//更新商品库存
	private function updateproductstock($attributeid,$quantity){
		$productmodel = D("product_attribute");

		$product = $productmodel->find($attributeid);
		if(empty($product)){
			return;
		}

		$stock = $product["stock"];
		if($stock > 0){
			$stock = $stock - $quantity;
		}
		$entity = array("stock"=>$stock);
		$map = array("id"=>$attributeid);
		$productmodel->where($map)->save($entity);
	}

	//创建定制订单
	public function createcustomorder(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$productid = $data["productid"];
		if(empty($productid)){
			E("请选择要定制的套餐");
		}
		
		//订单来源
		$hybrid = $this->GetHttpHeader("platform");
		
		$time = $data["time"];
		if(empty($time)){
			E("请选择预约上门时间");
		}
		$province = $data["province"];
		$city = $data["city"];
		$region = $data["region"];
		if(empty($province) || empty($city) || empty($region)){
			E("省市区不能为空");
		}
		$address = $data["address"];
		if(empty($address)){
			E("详细地址不能为空");
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
		$remark = $data["remark"];

		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}

		$productmodel = D("product");
		$map = array("status"=>1, "type"=>1, "id"=>$productid);
		$product = $productmodel->where($map)->find();
		if(empty($product)){
			E("您选择定制的套餐不存在");
		}
		// if($product["stock"] <= 0){
		// 	E("您选择定制的套餐库存不足");
		// }

		//订单标题
		$ordertitle = $product["title"];

		//订单总金额
		$totalamount = $product["price"];

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
			if($coupon['coupon_type']==1){
				//指定商品免费优惠券
				foreach($products as $k=>$v){
					if($v['id']=$coupon['product_id']){
						$coupon['money']=$v['price'];
					}
				}
			}
			//优惠券金额
			$coupon_money = $coupon["money"];
		}

		//订单支付金额
		$amount = $totalamount - $coupon_money;

		$order = array(
			"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>1, "title"=>$ordertitle, "status"=>1, "pay_status"=>0,
			"couponid"=>$couponid, "coupon_money"=>$coupon_money, "total_amount"=>$totalamount, "amount"=>$amount,
			"remark"=>"", "createdate"=>date("Y-m-d H:i:s"), "keyword"=>$ordertitle,'hybrid'=>$hybrid
		);

		//检查订单是否免费
		if($amount <= 0){
			$order["pay_status"] = 3;
            $order["status"] = 4;
			$order["pay_date"] = date("Y-m-d H:i:s");
		}

		$ordermodel = D("product_order");

		$orderid = $order["id"] = $ordermodel->add($order);

		$attach = array(
			"orderid"=>$orderid, "type"=>1, "objectid"=>$product["id"], "title"=>$product["title"], "thumb"=>$product["thumb"],
			"time"=>$time, "province"=>$province, "city"=>$city, "region"=>$region, "address"=>$address, "contact"=>$contact, "mobile"=>$mobile,
			"remark"=>$remark, "createdate"=>date("Y-m-d H:i:s")
		);

		$attachmodel = D("order_attach");
		
		$attachmodel->add($attach);

		//更新优惠券信息
		$this->updatecouponstatus($coupon, $order);

		//订单免费时，同步更新商品销量
		if($amount <= 0){
			$this->updateproductsales($product);
		}

		return array("orderid"=>$order["id"], "ordersn"=>$order["sn"], "amount"=>$amount,'coupon_money'=>$coupon_money,'createdate'=>date('Y/m/d'),"title"=>$ordertitle);
	}

	//创建改造订单
	public function createbuildorder(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$productid = $data["productid"];
		if(empty($productid)){
			E("请选择要改造的方案");
		}
		
		//订单来源
		$hybrid = $this->GetHttpHeader("platform");
		
		$usercareid = $data["usercareid"];
		if(empty($usercareid)){
			E("请选择照护人");
		}
		$map = array("userid"=>$user["id"], "id"=>$usercareid);
		$checkusercare = D("user_care")->where($map)->find();
		if(empty($checkusercare)){
			E("您选择的照护人不存在");
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
		$home_remark = $data["home_remark"];
		if(empty($home_remark)){
			E("请输入居家环境说明");
		}
		$device_remark = $data["device_remark"];
		if(empty($device_remark)){
			E("请输入现有设施说明");
		}
		$f_percent = $data["f_percent"];
		if(!in_array($f_percent, [1,2,3,4,5])){
			E("请评估跌倒风险");
		}
		$s_percent = $data["s_percent"];
		if(!in_array($s_percent, [1,2,3,4,5])){
			E("请评估安全风险系数");
		}
		$remark = $data["remark"];

		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}

		$productmodel = D("product");
		$map = array("status"=>1, "type"=>2, "types"=>1, "id"=>$productid);
		$product = $productmodel->where($map)->find();
		if(empty($product)){
			E("您选择改造的方案不存在");
		}
		// if($product["stock"] <= 0){
		// 	E("您选择改造的套餐库存不足");
		// }

		//订单标题
		$ordertitle = $product["title"];

		//订单总金额
		$totalamount = $product["price"];

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
			if($coupon['coupon_type']==1){
				//指定商品免费优惠券
				foreach($products as $k=>$v){
					if($v['id']=$coupon['product_id']){
						$coupon['money']=$v['price'];
					}
				}
			}
			//优惠券金额
			$coupon_money = $coupon["money"];
		}

		//订单支付金额
		$amount = $totalamount - $coupon_money;

		$order = array(
			"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>2,  "types"=>1, "title"=>$ordertitle, "status"=>1, "pay_status"=>0,
			"couponid"=>$couponid, "coupon_money"=>$coupon_money, "total_amount"=>$totalamount, "amount"=>$amount,
			"remark"=>"", "createdate"=>date("Y-m-d H:i:s"), "keyword"=>$ordertitle,'hybrid'=>$hybrid
		);

		//检查订单是否免费
		if($amount <= 0){
			$order["pay_status"] = 3;
            $order["status"] = 4;
			$order["pay_date"] = date("Y-m-d H:i:s");
		}

		$ordermodel = D("product_order");

		$orderid = $order["id"] = $ordermodel->add($order);

		$attach = array(
			"orderid"=>$orderid, "type"=>2, "objectid"=>$product["id"], "title"=>$product["title"], "thumb"=>$product["thumb"],
			"careid"=>$usercareid, "contact"=>$contact, "mobile"=>$mobile, "home_remark"=>$home_remark, "device_remark"=>$device_remark,
			"f_percent"=>$f_percent, "s_percent"=>$s_percent, "remark"=>$remark, "createdate"=>date("Y-m-d H:i:s")
		);

		$attachmodel = D("order_attach");
		
		$attachmodel->add($attach);

		//更新优惠券信息
		$this->updatecouponstatus($coupon, $order);

		//订单免费时，同步更新商品销量
		if($amount <= 0){
			$this->updateproductsales($product);
		}

		return array("orderid"=>$order["id"], "ordersn"=>$order["sn"], "amount"=>$amount,'coupon_money'=>$coupon_money,'createdate'=>date('Y/m/d'),"title"=>$ordertitle);
	}

	//创建送餐服务订单
	public function createserviceorder(){
		$user = $this->AuthUserInfo;

		$data = I("post.");
		
		//订单来源
		$hybrid = $this->GetHttpHeader("platform");

		//配餐套餐
		$productid = $data["productid"];
        $mealid = $data["mealid"];
		if(empty($productid)){
			E("请选择要购买的配餐");
		}
        if(empty($mealid)){
            E("请选择要购买的餐次");
        }

		$productmodel = D("product");
		$map = array("status"=>1, "id"=>$productid);
		$product = $productmodel->where($map)->find();
		if(empty($product)){
			E("配餐不存在");
		}
        //商品餐次
        $productmealmodel = D("product_meal_level_price");
        $map = array('id'=>$mealid, 'productid'=>$productid, 'status'=>1);
        $productmeal = $productmealmodel->where($map)->find();
        if(empty($productmeal)){
            E("配餐餐次不存在");
        }

		//预约上门时间
		$begintime = $data["begintime"];
		if(empty($begintime)){
			E("请选择预约上门时间");
		}
		if(!checkDateTime($begintime,'Y-m-d')){
			E("请选择正确的时间格式");
		}
        //时间判断
        $time = time();
        $begintime_st = strtotime($begintime);
		
        $b = date("Y-m-d", $begintime_st);
        $c = date("Y-m-d", $time);
        if ($b < $c) {
            E("预约上门时间必须大于当前日期");
		}
        if ($b == $c) {//判断是否同一天
            $present_hours = date('H', $time);//当前小时
            if ($productmeal["meal_level"] == 1 || $productmeal["meal_level"] == 3) {
                if ($present_hours >= 10) {
                    E('如您需当天的午餐或者午餐+晚餐服务，请您在当天上午10：00am前预约');
                }
            } else if($productmeal["meal_level"] == 2){
                if ($present_hours >= 16) {
                    E('如仅需当天晚餐服务，请您在下午16：00pm前预约；或者您也可以选择预约第二天的送餐服务，谢谢！');
                }
            }
        }

        $maxtime = strtotime("+3 day", strtotime(date("Y-m-d", $time)));
        if ($begintime_st > $maxtime) {
            E('预约时间不能大于三天');
        }

		//联系人
		$contact = $data["contact"];
		if(empty($contact)){
			E("请输入联系姓名");
		}
		//联系电话
		$mobile = $data["mobile"];
		if(empty($mobile)){
			E("请输入联系手机号码");
		}
		if(!isMobile($mobile)){
			E("手机号码格式不正确");
		}
		//其他要求
		$other_remark = $data["other_remark"];
		//省份
		$province = $data["province"];
		if(empty($province)){
			E("请选择省份");
		}
		//城市
		$city = $data["city"];
		if(empty($city)){
			E("请选择城市");
		}
		//区县
		$region = $data["region"];
		if(empty($region)){
			E("请选择区县");
		}
		//详细地址
		$address = $data["address"];
		if(empty($address)){
			E("请输入详细地址");
		}
		//经度
		$longitude = $data["longitude"];
		//纬度
		$latitude = $data["latitude"];
		if(empty($longitude) || empty($latitude)){
			E("请获取服务地址的经纬度");
		}

		//优惠券
		$couponid = $data["couponid"];
		if(empty($couponid)){
			$couponid = 0;
		}

		//订单标题
		$ordertitle = $product["title"];

		//订单总金额
		$totalamount = $productmeal["price"];

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

		//服务次数
		$service_time = 0;

        $begintime = date("Y-m-d", strtotime($begintime));
        $endtime = $begintime;
		switch ($productmeal["meal_level"]) {
			case 1: //中餐
				$begintime .= " 11:00:00";
				$endtime .= " 13:00:00";
				$service_time = 1;
				break;
			case 2: //晚餐
				$begintime .= " 17:00:00";
				$endtime .= " 19:00:00";
				$service_time = 1;
				break;
			case 3: //中晚
				$begintime .= " 11:00:00";
				$endtime .= " 19:00:00";
				$service_time = 2;
				break;
		}
        if (!checkDateTime($endtime, "Y-m-d H:i:s")) {
            E('餐类有误');
        }
		$order = array(
			"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>1, "service_role"=>2,
			"projectid"=>$product["id"], "title"=>$ordertitle, "thumb"=>$product["thumb"], "res_type"=>$productmeal["meal_level"], "service_time"=>$service_time,
			"begintime"=>$begintime, "endtime"=>$endtime, "contact"=>$contact, "mobile"=>$mobile, "other_remark"=>$other_remark,
			"province"=>$province, "city"=>$city, "region"=>$region, "address"=>$address, "longitude"=>$longitude, "latitude"=>$latitude,
			"status"=>1, "admin_status"=>1, "execute_status"=>0, "pay_status"=>0, "hybrid"=>$hybrid,
			"couponid"=>$couponid, "coupon_money"=>$coupon_money, "total_amount"=>$totalamount, "amount"=>$amount,
			"remark"=>"", "createdate"=>date("Y-m-d H:i:s"), "keyword"=>$ordertitle, "mealid"=>$mealid
		);

		//检查订单是否免费
		if($amount <= 0){
			$order["pay_status"] = 3;
			$order["pay_date"] = date("Y-m-d H:i:s");
		}

		$ordermodel = D("service_order");

		$orderid = $order["id"] = $ordermodel->add($order);

		//更新优惠券信息
		if($coupon){
			$entity = array("orderid"=>$orderid, "status"=>1, "use_type"=>3);
			$map = array("userid"=>$user["id"], "id"=>$coupon["id"]);
			$couponmodel->where($map)->save($entity);
		}

		return array("orderid"=>$order["id"], "ordersn"=>$order["sn"], "amount"=>$amount,'coupon_money'=>$coupon_money,'createdate'=>date('Y/m/d'),"title"=>$ordertitle);
	}

	//更新优惠券状态
	private function updatecouponstatus($coupon, $order){
		if(empty($coupon) || empty($order)){
			return false;
		}

		$couponmodel = D("user_coupon");

		$entity = array("orderid"=>$order["id"], "status"=>1, "use_type"=>1);
		$map = array("userid"=>$order["userid"], "id"=>$coupon["id"]);
		$couponmodel->where($map)->save($entity);

		return true;
	}

	//更新免费订单商品销量
	private function updateproductsales($product){
		if(empty($product)){
			return false;
		}

		if($product["productid"]){
			$productid = $product["productid"];
		} else{
			$productid = $product["id"];
		}

		$quantity = 1;
		if($product["quantity"]){
			$quantity = $product["quantity"];
		}

		$model = D("product");

		$map = array("status"=>1, "id"=>$productid);
		$product = $model->where($map)->find();
		if(empty($product)){
			return false;
		}

		$entity = array("sales"=>($product["sales"]+$quantity));
		$model->where($map)->save($entity);

		return true;
	}

	//取消订单
	public function cancelorder(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要取消的订单");
		}

		$model = D("product_order");

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

		$model = D("product_order");

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
	
	//申请售后
	public function refundorder(){
		$user = $this->AuthUserInfo;

		//1=退货,2=退款
		$type = I("post.type", 0);
		if(!in_array($type, [1,2])){
			E("请选择售后的方式");
		}

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要售后的订单");
		}

		$productids = I("post.productids");
		if(empty($productids)){
			E("请选择要进行售后的商品");
		}

		$model = D("product_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if(!(in_array($order["status"], [1,4]) && $order["pay_status"] == 3)){
			E("订单状态异常，操作失败");
		}
		if($type == 1 && !in_array($order["shipping_status"], [1,2])){
			E("订单还未发货，不能申请退货");
		}

		//匹配订单和订单商品
        $orderproductmodel = D("product_order_product");

        $map = array("userid"=>$user["id"], "orderid"=>$orderid, 'productid' => array('in',$productids),'status'=>array('neq',$type));
        $productidlist = $orderproductmodel->where($map)->getField('id',true);
        if (empty($productidlist)) {
            E("商品有误，操作失败");
        }

		if($type == 1){
			$shipping_name = I("post.shipping_name");
			if(empty($shipping_name)){
				E("请输入快递名称");
			}
			$shipping_number = I("post.shipping_number");
			if(empty($shipping_number)){
				E("请输入快递单号");
			}
			$shipping_date = I("post.shipping_date");
			if(empty($shipping_date)){
				E("请输入发货时间");
			}
		} else if($type ==2){
			$refund_money = I("post.refund_money", 0);
			if(empty($refund_money)){
				E("请输入退款金额");
			}
            if ($refund_money > $order['amount']) {
                E("退款金额不能大于交易金额");
            }
		}

		$reason = I("post.reason");
		if(empty($reason)){
			E("请输入申请售后的原因");
		}

		$images = I("post.images");

		//新增订单售后信息
		$refundmodel = D("product_order_refund");
		$entity = array(
			"userid"=>$user["id"], "orderid"=>$order["id"], "type"=>$type,
			"reason"=>$reason, "images"=>$images, "createdate"=>date("Y-m-d H:i:s"), "status"=>1
		);
		if($type == 1){
			$entity["shipping_name"] = $shipping_name;
			$entity["shipping_number"] = $shipping_number;
			$entity["shipping_date"] = $shipping_date;
		} else if($type == 2){
			$entity["refund_money"] = $refund_money;
		}
		$refundmodel->add($entity);

		//更新订单的售后状态
        $map = array("userid"=>$user["id"], "id"=>$orderid);
		$entity = array("status"=>5);
		$model->where($map)->save($entity);

		//更新商品的售后状态
        $map = array("id" => array('in',$productidlist));
        $entity = array("status"=>$type);
        $orderproductmodel->where($map)->save($entity);

		return;
	}

	//确认收货
	public function receiveorder(){
		$user = $this->AuthUserInfo;

		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要收货的订单");
		}

		$model = D("product_order");

		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if(!($order["status"] == 1 && $order["pay_status"] == 3 && $order["shipping_status"] == 1)){
			E("订单状态异常，操作失败");
		}

		$entity = array("shipping_receive_date"=>date("Y-m-d H:i:s"), 'shipping_status'=>2, 'status'=>4);
		$res = $model->where($map)->save($entity);
		if( $res ){
		    D('Brokerage', 'Service')->receive($orderid);
        }
		
		//确认收货发放积分
		$user = D('user')->where(array('id'=>$user['id']))->find();
		if($user['level']>0){
			//购物发放积分 1元=2分
			$data=['remark'=>'会员购买商品获得积分','tag'=>'shopping'];
			$point = $order['amount']*2;
			D('PointLog','Service')->append($user['id'],$point,$data);
		}else{
			//购物发放积分 1元=1.5分
			$data=['remark'=>'购买商品获得积分','tag'=>'shopping'];
			$point = floor($order['amount']*1.5);
			D('PointLog','Service')->append($user['id'],$point,$data);
		}
		
		return;
	}

	//取消售后订单
    public function cancelrefund(){
        $user = $this->AuthUserInfo;

        $orderid = I("post.orderid", 0);
        if(empty($orderid)){
            E("请选择要取消的售后订单");
        }
        $model = D("product_order");

        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $order = $model->where($map)->find();
        if(empty($order)){
            E("订单不存在，操作失败");
        }
        if(!($order["status"] == 5 && $order["pay_status"] == 3 && $order["type"] == 0)){
            E("订单状态异常，操作失败");
        }
        //订单状态改变
        $entity = array("status"=>4);
        if($order["type"] == 0 && $order["shipping_status"] != 2) {
            //已收货
        	$entity = array("status"=>1);
        }
        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $model->where($map)->save($entity);

        //更新商品的售后状态
        $map = array("orderid" => array('in',$order['id']));
        $entity = array("status"=>0);
        D("product_order_product")->where($map)->save($entity);

        return;
    }
}