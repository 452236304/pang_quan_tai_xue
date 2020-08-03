<?php
namespace CApi\Controller;
use Think\Controller;
//推荐活动
class RecommendActivityController extends BaseLoggedController {
	
	/**
	 * Notes: 到店列表
	 * User: LH
	 * Date: 2020/05/06
	 * Time: 16:06
	 */
	public function store_list(){
		$user = $this->AuthUserInfo;
		$id = I('get.id');//依然是活动的ID
		$map = array('id'=>$id,'status'=>1);
		$info = D('recommend_activity')->field('id,starttime,endtime,store_id,createtime')->where($map)->find();
		if(empty($info)){
			E('活动已下架');
		}
		if($info['starttime']>date('Y-m-d H:i:s')){
			E('活动未开始');
		}
		if($info['endtime']<date('Y-m-d H:i:s')){
			E('活动未开始');
		}
		$map = array('status'=>1,'id'=>array('in',$info['store_id']));
		$list=D('store')->where($map)->select();
		return $list;
	}
	/**
	 * Notes: 确认订单
	 * User: LH
	 * Date: 2020/05/06
	 * Time: 17:20
	 */
	public function ordercheck(){
		$user = $this->AuthUserInfo;
		$id = I('get.id');//依然是活动的ID
		$type = I('get.type',1);//1送货上门  2到店自取
		$map = array('id'=>$id,'status'=>1);
		$info = D('recommend_activity')->field('id,title,subtitle,starttime,endtime,thumb,price,freight,post_image,examine_image,store_id,createtime,product_id,attribute_id')->where($map)->find();
		if(empty($info)){
			E('活动已下架');
		}
		if($info['starttime']>date('Y-m-d H:i:s')){
			E('活动未开始');
		}
		if($info['endtime']<date('Y-m-d H:i:s')){
			E('活动未开始');
		}
		$info['thumb'] = $this->DoUrlHandle($info['thumb']);
		$info['post_image'] = $this->DoUrlHandle($info['post_image']);
		$info['examine_image'] = $this->DoUrlHandle($info['examine_image']);
		$order = $info;
		$order['amount'] = $info['price'];
		if($type == 2){
			$order['freight'] = 0;
		}else{
			$order['freight'] = $info['freight'];
		}
		$order['total_amount'] = $order['amount']+$order['freight'];
		$map = array('id'=>$info['product_id']);
		$product=D('product')->field('title,subtitle')->where($map)->find();
		$map = array('id'=>$info['attribute_id']);
		$attribute=D('product_attribute')->field('price,thumb')->where($map)->find();
		$order['product']['title']=$product['title'];
		$order['product']['subtitle']=$product['subtitle'];
		$order['product']['price']=$attribute['price'];
		$order['product']['thumb']=$this->DoUrlHandle($attribute['thumb']);
		return $order;
	}
	
	/**
	 * Notes: 创建订单
	 * User: LH
	 * Date: 2020/05/06
	 * Time: 17:20
	 */
	public function ordercreate(){
		$user = $this->AuthUserInfo;
		$data = I("post.");
		$type = $data['type']?:1;//1送货上门  2到店自取
		
		//检查是否参与过活动
		$map = array('activity_id'=>$data['id'],'userid'=>$user['id'],'is_activity'=>1,'status'=>array('neq',2));
		$is_order=D('product_order')->where($map)->find();
		if($is_order){
			E('已参与活动 请勿重复报名。');
		}
		$map = array('id'=>$data['id'],'status'=>1);
		$info = D('recommend_activity')->field('id,product_id,attribute_id,title,subtitle,starttime,endtime,thumb,price,freight,post_image,examine_image,store_id,createtime')->where($map)->find();
		if(empty($info)){
			E('活动已下架');
		}
		if($info['starttime']>date('Y-m-d H:i:s')){
			E('活动未开始');
		}
		if($info['endtime']<date('Y-m-d H:i:s')){
			E('活动未开始');
		}
		
		
		
		//订单来源
		$hybrid = $this->GetHttpHeader("platform");
		if($type==1){
			$addressid = $data["addressid"];
			if(empty($addressid)){
				E("请选择收货地址");
			}
			//收货地址
			$addressmodel = D("user_address");
			$map = array("userid"=>$user["id"], "id"=>$addressid);
			$address = $addressmodel->where($map)->find();
			if(empty($address)){
				E("收货地址不存在");
			}
			//运费
			$freight = $info['freight'];
			$extract = 0;
		}else{
			$store_id = $data["store_id"];
			if(empty($store_id)){
				E("请选择门店");
			}
			//运费
			$freight = 0;
			$extract = 1;
		}
		
		//购买数量
		$quantity = 1;
		//订单总金额
		$totalamount = 0;
		
		//商品列表
		$productids = array();
		$productid = $info["product_id"];
		$attributeid = $info["attribute_id"];
		$productmodel = D("product");
		$map = array("p.status"=>1, "p.id"=>$productid, "a.id"=>$attributeid);
		$products = $productmodel->alias("p")->join("left join sj_product_attribute as a on a.productid=p.id")
			->field("p.id,p.status,p.title,p.subtitle,p.thumb,a.id as attributeid,a.title as attributetitle,a.price,a.stock,p.freightid,p.brokerage")->where($map)->select();
		
		if(count($products) <= 0){
			E("您选择购买的商品不存在");
		}
		
		//运费模版集合
		$freight_temp = array();
		
		foreach($products as $k=>$v){
			$productids[]=$v;
			$v["quantity"] = $quantity;
		
			if($v["status"] != 1){
				E($v["title"]."商品已失效，下单失败");
			}
		
			if($v["stock"] <= 0 || $v["stock"] < $v["quantity"]){
				E($v["title"]."库存不足，下单失败");
			}
		
			$amount = $info['amount'];
			
			//订单总金额
			$totalamount = $totalamount + $amount;
		
			//订单商品
			$orderproduct[] = array(
				"userid"=>$user["id"], "productid"=>$v["id"], "title"=>$v["title"], "thumb"=>$v["thumb"],
				"attributeid"=>$v["attributeid"], "attributetitle"=>$v["attributetitle"],"price"=>$v["price"], "quantity"=>$v["quantity"], 'brokerage' => $v['brokerage']
			);
		
			$products[$k] = $v;
		}
		
		//订单标题
		$ordertitle = $info['title'];
		
		//订单总金额
		$totalamount += $freight;
		
		//订单支付金额
		$amount = $totalamount - 0;
		$createdate=date("Y-m-d H:i:s");
		$order = array(
			"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>0, "title"=>$ordertitle, "status"=>1, "pay_status"=>0,
			"consignee"=>$address["consignee"], "mobile"=>$address["mobile"], "province"=>$address["province"], "city"=>$address["city"], "region"=>$address["region"],
			"address"=>$address["address"], "couponid"=>0, "coupon_money"=>0, "freight"=>$freight, "total_amount"=>$totalamount, "amount"=>$amount,
			"remark"=>"", "createdate"=>$createdate, "keyword"=>$ordertitle,"hybrid"=>$hybrid,'is_activity'=>1,
			'examine'=>0,'extract'=>$extract,'store_id'=>$store_id,'activity_id'=>$data['id']
		);
		
		//检查订单是否免费
		if($amount <= 0){
			$order["pay_status"] = 3;
			$order["pay_date"] = $createdate;
		}
		
		$ordermodel = D("product_order");
		
		$orderid = $order["id"] = $ordermodel->add($order);
		
		
		$orderproductmodel = D("product_order_product");
		//新增订单商品
		foreach($orderproduct as $k=>$v){
			$v["orderid"] = $orderid;
		
			$orderproductmodel->add($v);
		
			$this->updateproductstock($v["attributeid"],$v['quantity']);
		
			//订单免费时，同步更新商品销量
			if($amount <= 0){
				$this->updateproductsales($v);
			}
		}
		
		return array("orderid"=>$order["id"], "ordersn"=>$order["sn"], "amount"=>$amount,'coupon_money'=>$coupon_money,'freight'=>$freight,'createdate'=>date('Y/m/d'),"title"=>$ordertitle);
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
}