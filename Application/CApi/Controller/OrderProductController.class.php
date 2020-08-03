<?php
namespace CApi\Controller;
use Think\Controller;
class OrderProductController extends BaseLoggedController {
	
	//商品订单列表
	public function productorder(){
		$user = $this->AuthUserInfo;

		$ordermodel = D("product_order");

		//orderstatus：0=全部,1=待发货,2=待收货,3=待付款,4=已取消,5=已完成,6=售后,7=待评价,8=待审核,9=待自取
		$orderstatus = I("get.orderstatus", 0);

		//剔除已删除的订单
		$map = array("userid"=>$user["id"], "status"=>array("neq", -1));
		switch ($orderstatus) {
			case 1:
				$map["status"] = 1;
				$map["pay_status"] = 0;
				$map["examine"] = 1;
				break;
			case 2:
				$map["status"] = 1;
				$map["pay_status"] = 3;
				$map["type"] = 0;
				$map["shipping_status"] = 0;
				$map["examine"] = 1;
				break;
			case 3:
				$map["status"] = 1;
				$map["pay_status"] = 3;
				$map["type"] = 0;
				$map["shipping_status"] = 1;
				break;
			case 4:
				$map["status"] = 2;
				$map["pay_status"] = 0;
				break;
			case 5:
				$map["status"] = 4;
				$map["pay_status"] = 3;
				$map["examine"] = 1;
				break;
			case 6:
				$map["status"] = array("in", [5,6]);
                $map["pay_status"] = 3;
				break;
			case 7:
				$map["status"] = 4;
				$map["pay_status"] = 3;
				$map["is_comment"] = 0;
				break;
			case 8:
				$map["status"] = 1;
				$map["examine"] = 0;
			case 9:
				$map["status"] = 1;
				$map["pay_status"] = 3;
				$map["extract"] = 1;
		}

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        
        $order = "createdate desc";
        $count = $ordermodel->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $ordermodel->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		$orderproductmodel = D("product_order_product");
		$orderattachmodel = D("order_attach");

		foreach($list as $k=>$v){
            if ($v['type'] == 0) {
				$map = array("userid"=>$user["id"], "orderid"=>$v["id"]);
				$products = $orderproductmodel->alias("pop")->join("left join sj_product as p on pop.productid=p.id")
					->join("left join sj_attribute as a1 on p.attribute_cpid=a1.id")->join("left join sj_attribute as a2 on p.attribute_czid=a2.id")
					->field("pop.*,a1.name as cpname,a2.name as czname")->where($map)->select();
                foreach($products as $ik=>$iv){
					$map = array('id'=>$iv['attributeid']);
					$attribute=D('product_attribute')->where($map)->find();
                    $iv["thumb"] = $this->DoUrlHandle($attribute["thumb"]);
                    $iv["amount"] = floatval($iv["price"]) * floatval($iv["quantity"]);
                    $products[$ik] = $iv;
                }
                $v["products"] = $products;
            }else{
				$map = array("orderid"=>$v["id"]);
				$attach = $orderattachmodel->where($map)->find();
				if($attach){
					$attach["thumb"] = $this->DoUrlHandle($attach["thumb"]);
					$attach["amount"] = getNumberFormat($v['amount']);
					$attach["price"] = getNumberFormat($v['total_amount']);
					$attach['productid'] = $attach['objectid'];
					$attach['quantity'] = '1';

					$v["products"][] = $attach;
				}
			}
			
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);

			//订单综合状态
			$v["com_status"] = $this->GetProductOrderStatus($v);

			$list[$k] = $v;
		}

		return $list;
	}

    //商品订单详情
    public function productorderdetail(){
        $user = $this->AuthUserInfo;

        $ordermodel = D("product_order");

        $orderid = I("get.orderid", 0);
        if(empty($orderid)){
            E("请选择要查看的订单");
        }
        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $order = $ordermodel->where($map)->find();
        if(empty($order)){
            E("订单不存在");
        }

        $orderproductmodel = D("product_order_product");
		$orderattachmodel = D("order_attach");

		if ($order["type"] == 0) {
			$map = array("userid"=>$user["id"], "orderid"=>$orderid);
			$products = $orderproductmodel->where($map)->select();
			foreach($products as $ik=>$iv){
				$map = array('id'=>$iv['attributeid']);
				$attribute=D('product_attribute')->where($map)->find();
				$iv["thumb"] = $this->DoUrlHandle($attribute["thumb"]);
				$iv["amount"] =getNumberFormat(floatval($iv["price"]) * floatval($iv["quantity"]),1);
				$iv["price"]=getNumberFormat($iv["price"],1);
				$products[$ik] = $iv;
			}
			$order["products"] = $products;
		} else{
			$map = array("orderid"=>$orderid);
			$attach = $orderattachmodel->where($map)->find();
			if($attach){
				$attach["thumb"] = $this->DoUrlHandle($attach["thumb"]);
				$attach["amount"] = getNumberFormat($order['total_amount']);
				$attach["price"] = getNumberFormat($order['total_amount']);
				$attach['productid'] = $attach['objectid'];
				$attach['quantity'] = '1';
	
				$order["products"][] = $attach;
			}
		}

		$order["total_amount"] = getNumberFormat($order["total_amount"]);
		$order["amount"] = getNumberFormat($order["amount"]);
		$order["freight"] = getNumberFormat($order["freight"]);

        //订单综合状态
		$order["com_status"] = $this->GetProductOrderStatus($order);

		//订单售后信息
		if(in_array($order["status"], [5,6])){
			$refundmodel = D("product_order_refund");
			$map = array("userid"=>$user["id"], "orderid"=>$orderid);
			$order["refund_record"] = $refundmodel->where($map)->find();
			if($order["refund_record"]){
				$order["refund_record"]["images"] = $this->DoUrlListHandle($order["refund_record"]["images"]);
			}
		}
		if($order['extract']==1){
			$map = array('id'=>$order['store_id']);
			$order['store']=D('store')->where($map)->find();
		}
        return $order;
    }

	//商品订单评价列表
	public function productcomment(){
		$user = $this->AuthUserInfo;

		$model = D("product_order");

		//类型：0=待评价，1=已评价
		$type = I("get.type", 0);

		$map = array("userid"=>$user["id"], "is_comment"=>$type, "status"=>4, "pay_status"=>3);
		
		$page = I("get.page", 1);
        $row = I("get.row", 10);
		$begin = ($page-1)*$row;
		
		$order = "createdate desc";
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
		$orders = $model->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		//评论列表
		$list = [];

		//订单商品
		$orderproductmodel = D("product_order_product");
		//订单定制改造
		$orderattachmodel = D("order_attach");

		foreach($orders as $k=>$v){
			if($v["type"] == 0){
				$map = array("userid"=>$user["id"], "orderid"=>$v["id"]);
				if($type == 0){
					$map["commentid"] = array("elt", 0);
				} else{
					$map["commentid"] = array("gt", 0);
				}
				$products = $orderproductmodel->where($map)->select();
				foreach($products as $ik=>$iv){
					$iv["type"] = 0;
					$map = array('id'=>$iv['attributeid']);
					$attribute=D('product_attribute')->where($map)->find();
					$iv["thumb"] = $this->DoUrlHandle($attribute["thumb"]);
					$iv["amount"] = floatval($iv["price"]) * floatval($iv["quantity"]);
					$iv["createdate"] = $v["createdate"];
					$iv["sn"] = $v["sn"];
					$iv["is_comment"] = $v["is_comment"];

					$list[] = $iv;
				}
			} else{
				$map = array("orderid"=>$v["id"], "type"=>$v["type"]);
				$attach = $orderattachmodel->where($map)->find();
				if($attach){
					$item = array(
						"userid"=>$user["id"], "orderid"=>$v["id"], "productid"=>$attach["objectid"],
						"title"=>$attach["title"], "thumb"=>$this->DoUrlHandle($attach["thumb"]),
						"price"=>$v["total_amount"], "quantity"=>1, "amount"=>$v["total_amount"],
						"sn"=>$v["sn"], "createdate"=>$v["createdate"], "type"=>$v["type"],
						"commentid"=>$attach["commentid"], "is_comment"=>$v["is_comment"]
					);

					$list[] = $item;
				}
			}
		}
		
		return $list;
	}

	//商品订单评价详情
	public function productcommentdetail(){
		$user = $this->AuthUserInfo;

		$model = D("product_comment");

		$commentid = I("get.commentid", 0);
		$type = I("get.type", 0);
		if(empty($commentid)){
			E("请选择要查看的商品订单评价");
		}
		$map = array("pc.userid"=>$user["id"], "pc.id"=>$commentid);
        if ($type == 0) {
            $detail = $model->alias("pc")->join("left join sj_product_order_product as pop on pop.commentid=pc.id")
                ->join("left join sj_product_order as po on pop.orderid=po.id")
                ->field("po.createdate as ordercreatedate,pop.price,pop.quantity,pc.*,pop.attributeid")->where($map)->find();
        }else{
            //定制、改造订单
            $detail = $model->alias("pc")->join("left join sj_order_attach as oa on oa.commentid=pc.id")
                ->join("left join sj_product_order as po on oa.orderid=po.id")
                ->field("po.createdate as ordercreatedate,po.total_amount as price,pc.*")->where($map)->find();
        }


        if(empty($detail)){
			E("当前订单还未评价，无法查看");
		}
        if ($type != 0) {
            //定制、改造订单
            $detail['quantity'] = '1';
        }
        $detail["type"]  = $type;
		if($type==0){
			$map = array('id'=>$detail['attributeid']);
			$attribute=D('product_attribute')->where($map)->find();
			$detail["thumb"] = $this->DoUrlHandle($attribute["thumb"]);
		}else{
			$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		}
		
		$detail["images"] = $this->DoUrlListHandle($detail["images"]);

		return $detail;
	}

	//购物车列表
	public function shoppingcart(){
		$user = $this->AuthUserInfo;

		$model = D("shopping_cart");

		$map = array("userid"=>$user["id"]);
		$list = $model->alias("sc")->join("left join sj_product as p on sc.productid=p.id")
			->join("left join sj_product_attribute as pa on sc.attributeid=pa.id")
			->field("sc.id,sc.quantity,p.id as productid,p.status,p.title,p.subtitle,pa.thumb,pa.price,pa.stock,pa.title as attributetitle,sc.attributeid")
			->where($map)->order("sc.updatetime asc")->select();

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			$list[$k] = $v;
		}

		return $list;
	}

	//商品订单结算检查
	public function ordercheck(){
		$user = $this->AuthUserInfo;

		//立即购买（1=是）
		$now = I("get.now");
		//购买数量
		$quantity = I("get.quantity", 1);

		//订单总金额
		$totalamount = 0;

		//商品列表
		$productids = array();
		if($now == 1){ //立即购买
			$productid = I("get.productid", 0);
			if(empty($productid)){
				E("请选择要购买的商品");
			}
			$attributeid = I("get.attributeid", 0);
			if(empty($attributeid)){
				E("请选择购买商品的套餐");
			}

			$productmodel = D("product");
			$map = array("p.status"=>1, "p.id"=>$productid, "pa.id"=>$attributeid);
			$products = $productmodel->alias("p")->join("left join sj_product_attribute as pa on pa.productid=p.id")
				->field("p.id,p.status,p.title,p.subtitle,p.thumb,p.freightid,pa.price,pa.stock,pa.title as attributetitle,pa.id as attributeid")
				->where($map)->select();
		} else{
			//购物车id集合
			$sids = I("get.sids");
			if(empty($sids)){
				E("请选择要购买的商品");
			}
			$sids = explode(",", $sids);

			$cartmodel = D("shopping_cart");
			$map = array("sc.userid"=>$user["id"], "sc.id"=>array("in", $sids));
			$products = $cartmodel->alias("sc")->join("left join sj_product as p on sc.productid=p.id")
				->join("left join sj_product_attribute as pa on pa.id=sc.attributeid")
				->field("sc.id as cartid,sc.quantity,p.id,p.status,p.title,p.subtitle,pa.thumb,p.freightid,pa.price,pa.stock,pa.title as attributetitle,pa.id as attributeid")
				->where($map)->select();
		}

		if(count($products) <= 0){
			E("您选择购买的商品不存在");
		}

		//运费模版集合
		$freight_temp = array();
		foreach($products as $k=>$v){
			$productids[]=$v['id'];
			if($now == 1){ //立即购买，默认购买数量为1
				$v["quantity"] = $quantity;
			}
			
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["amount"] = floatval($v["price"]) * floatval($v["quantity"]);

			$totalamount = $totalamount + $v["amount"];

			$products[$k] = $v;

			$temp = $freight_temp["temp_".$v["freightid"]];
			if($temp){
				$temp["amount"] += floatval($v["amount"]);
			} else{
				$temp = array("id"=>$v["freightid"], "amount"=>floatval($v["amount"]));
			}
			$freight_temp["temp_".$v["freightid"]] = $temp;
		}

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

		//收货地址
		$addressmodel = D("user_address");
		$map = array("type"=>0, "userid"=>$user["id"]);
		$address = $addressmodel->where($map)->order("is_default desc")->find();

		//优惠券
		$counponmodel = D("user_coupon");
		$time = date("Y-m-d");
		$map = array(
			array("userid"=>$user['id'], "status"=>0, "min_amount"=>array("elt", $totalamount), "use_end_date"=>array("egt", $time),'coupon_type'=>0),
			array("userid"=>$user['id'], "status"=>0, "use_end_date"=>array("egt", $time),'coupon_type'=>1,'product_id'=>array('in',$productids)),
			'_logic'=>'or'
		);
		/* $map = array("userid"=>$user['id'], "status"=>0, "use_end_date"=>array("egt", $time),'coupon_type'=>1,'product_id'=>array('in',$productids)); */
		$counpons = $counponmodel->where($map)->select();
		foreach($counpons as $k=>$v){
			if($v['coupon_type']==1){
				foreach($products as $key=>$value){
					if($value['id']==$v['product_id']){
						$v['money']=$value['price'];
					}
				}
			}
			$v['title']=$v['title'].'(￥'.$v['money'].')';
			$counpons[$k]=$v;
		}
		$data = array(
			"totalamount"=>getNumberFormat($totalamount), "freight"=>getNumberFormat($freight), "products"=>$products, "address"=>$address, "coupons"=>$counpons
		);

		return $data;
	}

    //获取商品价格对应的优惠劵
    public function coupon(){
        $user = $this->AuthUserInfo;
        $amount = I('get.amount',0);
        $counponmodel = D("user_coupon");
        $time = date("Y-m-d H:i:s");
        $map = array("userid"=>$user['id'], "status"=>0, "min_amount"=>array("elt", $amount), "use_end_date"=>array("gt", $time));
        $counpons = $counponmodel->where($map)->select();

        return $counpons;
	}
	
    //餐饮类商品订单结算检查
    public function mealordercheck(){
        $user = $this->AuthUserInfo;

        $productid = I("get.productid");
        if(empty($productid)){
            E("请选择要购买的配餐");
        }

        //商品
        $productmodel = D("product");
        $map = array('id'=>$productid, 'status'=>1);
        $product = $productmodel->where($map)->find();
        if(empty($product)){
            E("您选择购买的配餐不存在");
        }

        //配餐餐次列表
        $productmeal = D("product_meal_level_price");
        $map = array("productid"=>$product['id'], 'status'=>1);
        $meallsit = $productmeal->where($map)->order('meal_level asc')->select();

        $totalamount = $meallsit[0]['price'] ? $meallsit[0]['price'] : 0 ;

        //收货地址
        $addressmodel = D("user_address");
        $map = array("userid"=>$user["id"]);
        $address = $addressmodel->where($map)->order("is_default desc")->find();

        //优惠券
        $counponmodel = D("user_coupon");
        $time = date("Y-m-d H:i:s");
        $map = array("userid"=>$user['id'], "status"=>0, "min_amount"=>array("elt", $totalamount), "use_end_date"=>array("gt", $time));
        $counpons = $counponmodel->where($map)->select();
		foreach($counpons as $k=>$v){
			$v['title']=$v['title'].'(￥'.$v['money'].')';
			$counpons[$k]=$v;
		}
		
        $product["thumb"] = $this->DoUrlHandle($product["thumb"]);
        $product["amount"] = $totalamount;

        $data = array(
            "totalamount"=>$totalamount, "product"=>$product, "address"=>$address, "coupons"=>$counpons, "meallsit"=>$meallsit
        );
        return $data;
	}
	
	//积分商品订单结算检查
	public function pointordercheck(){
		$user = $this->AuthUserInfo;
	
		//立即购买（1=是）
		$now = I("get.now",1);
		//购买数量
		$quantity = I("get.quantity", 1);
	
		//订单总金额
		$totalamount = 0;
	
		//商品列表
		$productids = array();
		$productid = I("get.productid", 0);
		if(empty($productid)){
			E("请选择要购买的商品");
		}
		$attributeid = I("get.attributeid", 0);
		if(empty($attributeid)){
			E("请选择购买商品的套餐");
		}

		$productmodel = D("product");
		$map = array("p.status"=>1, "p.id"=>$productid, "pa.id"=>$attributeid);
		$products = $productmodel->alias("p")->join("left join sj_product_attribute as pa on pa.productid=p.id")
			->field("p.id,p.status,p.title,p.subtitle,p.thumb,p.freightid,pa.price,pa.stock,pa.title as attributetitle,pa.id as attributeid")
			->where($map)->select();
		
	
		if(count($products) <= 0){
			E("您选择购买的商品不存在");
		}
	
		//运费模版集合
		$freight_temp = array();
		foreach($products as $k=>$v){
			$productids[]=$v['id'];
			if($now == 1){ //立即购买，默认购买数量为1
				$v["quantity"] = $quantity;
			}
			
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			
			//判断商品积分购买是否需要现金
			$v["amount"] = floatval($v["price"]) * floatval($v["quantity"]);
	
			$totalamount = $totalamount + $v["amount"];
	
			$products[$k] = $v;
	
			$temp = $freight_temp["temp_".$v["freightid"]];
			if($temp){
				$temp["amount"] += floatval($v["amount"]);
			} else{
				$temp = array("id"=>$v["freightid"], "amount"=>floatval($v["amount"]));
			}
			$freight_temp["temp_".$v["freightid"]] = $temp;
		}
	
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
	
		//收货地址
		$addressmodel = D("user_address");
		$map = array("type"=>0, "userid"=>$user["id"]);
		$address = $addressmodel->where($map)->order("is_default desc")->find();

		$data = array(
			"totalamount"=>getNumberFormat($totalamount), "freight"=>getNumberFormat($freight), "products"=>$products, "address"=>$address
		);
	
		return $data;
	}
}