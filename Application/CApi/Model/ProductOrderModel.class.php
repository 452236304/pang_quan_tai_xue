<?php
namespace CApi\Model;

class ProductOrderModel extends CommonModel{
    //创建商品订单
    public function createproductorder($user,$hybrid,$products,$coupon,$address){
		
    	//运费模版集合
    	$freight_temp = array();
		$company_id = 0;
    	foreach($products as $k=>$v){
    		$productids[]=$v;
    		if($now == 1){ //立即购买，默认购买数量为1
    			$v["quantity"] = $quantity;
    		}
			
    		$amount = floatval($v["price"]) * floatval($v["quantity"]);
    		
    		//订单总金额
    		$totalamount = $totalamount + $amount;
    		//订单标题
    		$ordertitle[] = $v["title"];
    
    		//订单商品
    		$orderproduct[] = array(
    			"userid"=>$user["id"], "productid"=>$v["id"], "title"=>$v["title"], "thumb"=>$v["thumb"],
    			"attributeid"=>$v["attributeid"], "attributetitle"=>$v["attributetitle"],"price"=>$v["price"], "quantity"=>$v["quantity"], 'brokerage' => $v['brokerage']
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
		if($coupon['id']>0){
			foreach($products as $k=>$v){
				if($v['id']=$coupon['product_id']){
					$coupon_money=$v['price'];
				}
			}
		}else{
			$coupon_money = $coupon['money'];
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
    
    	//订单支付金额
    	$amount = $totalamount - $coupon_money;
    	$createdate=date("Y-m-d H:i:s");
    	$order = array(
    		"sn"=>$this->BuildOrderSN(), "userid"=>$user["id"], "nickname"=>$user["nickname"], "type"=>0, "title"=>$ordertitle, "status"=>1, "pay_status"=>0,'company_id'=>$company_id,
    		"consignee"=>$address["consignee"], "mobile"=>$address["mobile"], "province"=>$address["province"], "city"=>$address["city"], "region"=>$address["region"],
    		"address"=>$address["address"], "couponid"=>$coupon['id'], "coupon_money"=>$coupon_money, "freight"=>$freight, "total_amount"=>$totalamount, "amount"=>$amount,
    		"remark"=>"", "createdate"=>$createdate, "keyword"=>$ordertitle,"hybrid"=>$hybrid
    	);
    
    	//检查订单是否免费
    	if($amount <= 0){
    		$order["pay_status"] = 3;
    		$order["pay_date"] = $createdate;
    	}
    
    	$ordermodel = D("product_order");
    
    	$orderid = $order["id"] = $ordermodel->add($order);
    
        //检查订单是否免费 分销体系
        if($amount <= 0){
            D('Brokerage', 'Service')->orderSettle(2, $orderid);
        }
    
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
	//生成订单流水号
	private function BuildOrderSN(){
	    
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