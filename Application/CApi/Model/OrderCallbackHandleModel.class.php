<?php
namespace CApi\Model;
use Think\Exception;
use Think\Log;

class OrderCallbackHandleModel{

    public function OrderHandle($attach){
        switch($attach["type"]){
            case 1: //商城订单
                $this->OrderProduct($attach);
                break;
            case 2: //机构订单
                $this->OrderOrg($attach);
                break;
            case 3: //服务订单
                $this->OrderService($attach);
                break;
            case 4: //服务续费订单
                $this->OrderServiceAgain($attach);
                break;
            case 5: //服务缴费订单
                $this->OrderServiceAssess($attach);
                break;
			case 6: //VIP购买
			    $this->OrderVip($attach);
			    break;
            case 7: //公益活动购买
                $this->publicBenefitOrder($attach);
                break;
			case 8: //新机构订单
			    $this->pensionOrder($attach);
			    break;
        }

    }

    //商城订单处理
    private function OrderProduct($attach){
        $paylogmodel = D("pay_log");

        $map = array("sn"=>$attach["logsn"], "type"=>1);
        $paylog = $paylogmodel->where($map)->find();
        if(empty($paylog)){
            return false;
        }
        if($paylog["ispaid"] == 1){
            return true;
        }

        $ordermodel = D("product_order");

        $order = $ordermodel->where(array('id'=>array('in',$paylog["orderid"])))->select();
        if(empty($order)){
            return false;
        }
		F('store',$order);

        $usermodel = D("user");

        $user = $usermodel->where("id=".$order[0]["userid"])->find();
        if(empty($user)){
            return false;
        }
		
		//订单日志更新为已支付
		$paylog_entity = array("ispaid"=>1);
		$paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
		
		foreach($order as $key=>$value){
			if($value['is_point_shop']){
				$remark['remark'] = '购买积分商城商品'.$point_shop['title'].'-'.$point_shop['subtitle'];
				$result = D('PointLog','Service')->append($value['userid'],-$value['point'],$remark);
				if($result == false){
					D('user')->where(array('id'=>$value['userid']))->setDec('point',$value['point']);
				}
			}
			
			//订单更新为已支付
			$order_entity = array("pay_status"=>3, "pay_date"=>date("Y-m-d H:i:s"));
			if(in_array($value["type"], [1,2])){
			    $order_entity["status"] = 4;
			}
			$ordermodel->where("id=".$value["id"])->save($order_entity);
			
			//标题
			$title = "一点椿-商城《".$value["title"]."》";
			
			//用户消费记录
			$consumemodel = D("user_consume");
			$consume_entity = array(
			    "userid"=>$user["id"], "orderid"=>$value["id"], "order_type"=>1, "type"=>2, "title"=>$title,
			    "amount"=>$value["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
			);
			$consumemodel->add($consume_entity);
			
			//用户订单消息
			$messagemodel = D("user_message");
			$content = '<p>';
			$content .= "【订单号】：".$value["sn"]."<br/>";
			$content .= "【订单内容】：".$title."<br/>";
			$content .= "订单购买成功。祝您身体健康，生活愉快！";
			$content .= '</p>';
			$message_entity = array(
			    "userid"=>$user["id"], "title"=>$title, "content"=>$content,
			    "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
			);
			$messagemodel->add($message_entity);
			
			//发送短信消息
			$sms = D("Common/RequestSms");
			$sms->SendProductSms($value['mobile'], $value['title'] ,$value['sn']);

            // 7陌订单提醒
            $content = D('CApi/Moor', 'Service')->orderMessage($value['id']);
            $result = D('CApi/Moor', 'Service')->createContext($user["id"]);
            $result = D('CApi/Moor', 'Service')->sendRobotTextMessage($user["id"], $content);
			
			// 分销体系
			Log::write('分销体系测试：'.json_encode($user),'INFO');
			if( $user['is_team'] ){
			    D('CApi/Brokerage', 'Service')->orderSettle(2, $value['id']);
			}
			
			//为订单的商品添加销量
			$map = array('orderid'=>$value['id']);
			$pop = D('product_order_product')->where($map)->select();
			foreach($pop as $k=>$v){
				$map = array('productid'=>$v['productid']);
				D('product')->where($map)->setInc('sales',$v['quantity']);
			}
		}
		if($order[0]['is_point_shop'] == 0){
			D('Point','Service')->append($user['id'],'buy');
		}
        
        return true;
    }

    //机构订单处理
    private function OrderOrg($attach){
        $paylogmodel = D("pay_log");

        $map = array("sn"=>$attach["logsn"], "type"=>2);
        $paylog = $paylogmodel->where($map)->find();
        if(empty($paylog)){
            return false;
        }
        if($paylog["ispaid"] == 1){
            return true;
        }

        $ordermodel = D("org_order");

        $order = $ordermodel->find($paylog["orderid"]);
        if(empty($order)){
            return false;
        }

        $usermodel = D("user");

        $user = $usermodel->where("id=".$order["userid"])->find();
        if(empty($user)){
            return false;
        }

        //订单更新为已支付
        $order_entity = array("pay_status"=>3, "status"=>4, "pay_date"=>date("Y-m-d H:i:s"));
        $ordermodel->where("id=".$order["id"])->save($order_entity);
        
        //订单日志更新为已支付
        $paylog_entity = array("ispaid"=>1);
        $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);

        //标题
        $title = "一点椿-机构照护《".$order["title"]."》";

        //用户消费记录
        $consumemodel = D("user_consume");
        $consume = array(
            "userid"=>$user["id"], "orderid"=>$order["id"], "order_type"=>2, "type"=>2, "title"=>$title,
            "amount"=>$order["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $consumemodel->add($consume);

        //用户订单消息
        $messagemodel = D("user_message");
        $content = '<p>';
        $content .= "【订单号】：".$order["sn"]."<br/>";
        $content .= "【订单内容】：".$title."<br/>";
        $content .= "订单购买成功。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$user["id"], "title"=>$title, "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);
		
		//短信通知
		$RequestSms=D('Common/RequestSms');
		$info=array('mobile'=>$user['mobile']);
		switch($order['type']){
			case 1:
				//一元参观
				$RequestSms->SendVisit($info);
				break;
			case 2:
				//机构长住
				$info['title']=$title;
				$RequestSms->SendLongStay($info);
				break;
			case 3:
				//短期入住
				$info['title'] = $title;
				$info['time'] = $order['attribute3'];
				$RequestSms->SendShortStay($info);
				break;
		}

        // 分销体系
        if( !empty($user['is_team']) ){
            D('CApi/Brokerage', 'Service')->orderSettle(1, $order['id']);
        }
		
		//机构订单支付立刻发放积分
		/*if($user['level']>0){
			//购物发放积分 1元=2分
			$data=['remark'=>'会员购买机构订单获得积分','tag'=>'shopping'];
			$point = $order['amount']*2;
			D('PointLog','Service')->append($user['id'],$point,$data);
		}else{
			//购物发放积分 1元=1.5分
			$data=['remark'=>'购买机构订单获得积分','tag'=>'shopping'];
			$point = floor($order['amount']*1.5);
			D('PointLog','Service')->append($user['id'],$point,$data);
		}*/
		D('Point','Service')->append($user['id'],'buy');
        return true;
    }

    //服务订单处理
    private function OrderService($attach){
        $paylogmodel = D("pay_log");

        $map = array("sn"=>$attach["logsn"], "type"=>3);
        $paylog = $paylogmodel->where($map)->find();
        if(empty($paylog)){
            return false;
        }
        if($paylog["ispaid"] == 1){
            return true;
        }

        $ordermodel = D("service_order");

        $order = $ordermodel->find($paylog["orderid"]);
        if(empty($order)){
            return false;
        }

        $usermodel = D("user");

        $user = $usermodel->where("id=".$order["userid"])->find();
        if(empty($user)){
            return false;
        }

        //订单更新为已支付
        $order_entity = array("pay_status"=>3, "pay_date"=>date("Y-m-d H:i:s"));
        $ordermodel->where("id=".$order["id"])->save($order_entity);
        
        //订单日志更新为已支付
        $paylog_entity = array("ispaid"=>1);
        $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);

        //标题
        $title = "一点椿-服务项目《".$order["title"]."》";

        //用户消费记录
        $consumemodel = D("user_consume");
        $consume = array(
            "userid"=>$user["id"], "orderid"=>$order["id"], "order_type"=>3, "type"=>2, "title"=>$title,
            "amount"=>$order["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $consumemodel->add($consume);

        //用户订单消息
        $messagemodel = D("user_message");
        $content = '<p>';
        $content .= "【订单号】：".$order["sn"]."<br/>";
        $content .= "【订单内容】：".$title."<br/>";
        $content .= "订单购买成功，即将等待审核，请耐心等候。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$user["id"], "title"=>$title, "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);

        /**
         * 非评估订单直接分佣
         */
        // 分销体系
        if( !empty($user['is_team']) && !$order['assess'] ){
            D('CApi/Brokerage', 'Service')->orderSettle(3, $order['id']);
        }
        // 7陌订单提醒
        $content = D('CApi/Moor', 'Service')->orderMessage($order['id'], 2);
        D('CApi/Moor', 'Service')->createContext($order['userid']);
        D('CApi/Moor', 'Service')->sendRobotTextMessage($order['userid'], $content);

		D('Point','Service')->append($user['id'],'buy');
        return true;
    }

    //服务续费订单处理
    private function OrderServiceAgain($attach){
        $paylogmodel = D("pay_log");

        $map = array("sn"=>$attach["logsn"], "type"=>4);
        $paylog = $paylogmodel->where($map)->find();
        if(empty($paylog)){
            return false;
        }
        if($paylog["ispaid"] == 1){
            return true;
        }

        //续费订单记录
        $orderagainmodel = D("service_order_again_record");
        
        $againrecord = $orderagainmodel->find($paylog["orderid"]);
        if(empty($againrecord)){
            return false;
        }

        $ordermodel = D("service_order");

        $order = $ordermodel->find($againrecord["orderid"]);
        if(empty($order)){
            return false;
        }

        $usermodel = D("user");

        $user = $usermodel->where("id=".$order["userid"])->find();
        if(empty($user)){
            return false;
        }

        //续费订单更新为已支付
        $o_entity = array("pay_status"=>3, "pay_date"=>date("Y-m-d H:i:s"));
        $orderagainmodel->where("id=".$againrecord["id"])->save($o_entity);

        //订单更新为已续费,更新服务时间(服务结束时间新增60分钟)
        $endtime = date("Y-m-d H:i:s", strtotime("+60 minute", strtotime($order["endtime"])));
        $o_entity = array("again_status"=>2, "again_count"=>($order["again_count"]+1), "again_date"=>date("Y-m-d H:i:s"), "endtime"=>$endtime);
        $ordermodel->where("id=".$order["id"])->save($o_entity);
        
        //订单日志更新为已支付
        $paylog_entity = array("ispaid"=>1);
        $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);

        //标题
        $title = "一点椿-服务项目续费《".$order["title"]."》";

        //用户消费记录
        $consumemodel = D("user_consume");
        $consume = array(
            "userid"=>$user["id"], "orderid"=>$order["id"], "order_type"=>3, "type"=>2, "title"=>$title,
            "amount"=>$order["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $consumemodel->add($consume);

        //用户订单消息
        $messagemodel = D("user_message");
        $content = '<p>';
        $content .= "【订单号】：".$order["sn"]."<br/>";
        $content .= "【订单内容】：".$title."<br/>";
        $content .= "续费订单购买成功，服务时间将延长1小时。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$user["id"], "title"=>$title, "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);

        if ($order["service_userid"] > 0) {
            //服务人员订单消息
            $content = '<p>';
            $content .= "【订单内容】：".$title."<br/>";
            $content .= "【服务地址】：".$order["province"].$order["city"].$order["region"].$order["address"]."<br/>";
            $content .= "【服务时间】：".$order["begintime"].' / '.$endtime."<br/>";
            if($order["other_remark"]){
                $content .= "【用户备注】：".$order["other_remark"]."<br/>";
            }
            if($order["platform_money"] > 0){
                $content .= "【平台补贴】：".$order["platform_money"]."元<br/>";
            }
            $content .= "以上订单客户已经完成续费，请将服务时间延长1小时，谢谢";
            $content .= '</p>';
            $message_entity = array(
                "userid"=>$order["service_userid"], "title"=>$title, "content"=>$content,
                "hybrid"=>"service", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
            );
            $messagemodel->add($message_entity);
			
			//短信通知订单续单
			$service_user=D('user_profile')->where(array('userid'=>$order['service_userid']))->find();
			$info=array('mobile'=>$service_user['mobile'],'name'=>$service_user['realname']);
			D('Common/RequestSms')->SendRenewal($info);
			
			//短信通知订单成功续费
			$info=array('mobile'=>$user['mobile'],'title'=>$title);
			D('Common/RequestSms')->SendRenew($info);
        }


        /**
         * 评估订单分佣
         */
        // 分销体系
        if( !empty($user['is_team']) && $order['assess'] ){
            D('CApi/Brokerage', 'Service')->orderSettle(3, $order['id']);
        }

        return true;
    }

    //服务缴费订单处理
    private function OrderServiceAssess($attach){
        $paylogmodel = D("pay_log");
    
        $map = array("sn"=>$attach["logsn"], "type"=>5);
        $paylog = $paylogmodel->where($map)->find();
        if(empty($paylog)){
            return false;
        }
        if($paylog["ispaid"] == 1){
            return true;
        }
    
        //缴费订单记录
        $orderagainmodel = D("service_order_again_record");
        
        $againrecord = $orderagainmodel->find($paylog["orderid"]);
        if(empty($againrecord)){
            return false;
        }
    
        $ordermodel = D("service_order");
    
        $order = $ordermodel->find($againrecord["orderid"]);
        if(empty($order)){
            return false;
        }
    
        $usermodel = D("user");
    
        $user = $usermodel->where("id=".$order["userid"])->find();
        if(empty($user)){
            return false;
        }
    
        //缴费订单更新为已支付
        $o_entity = array("pay_status"=>3, "pay_date"=>date("Y-m-d H:i:s"));
        $orderagainmodel->where("id=".$againrecord["id"])->save($o_entity);
    
        //订单更新为已缴费 且 订单更新为确认开始服务
        $o_entity = array(
            "again_status"=>2, "again_count"=>1, "again_date"=>date("Y-m-d H:i:s"),
            "execute_status"=>2, "execute_time"=>date("Y-m-d H:i:s")
        );
        $ordermodel->where("id=".$order["id"])->save($o_entity);

        //服务交互记录
		$recordmodel = D("service_order_record");
		$record_entity = array(
			"orderid"=>$order["id"], "userid"=>$user["id"], "title"=>"确认开始服务",
			"execute_status"=>2, "updatetime"=>date("Y-m-d H:i:s"), "remark"=>"缴付尾款，确认开始服务"
		);
		$recordmodel->add($record_entity);
        
        //订单日志更新为已支付
        $paylog_entity = array("ispaid"=>1);
        $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
    
        //标题
        $title = "一点椿-服务项目缴费《".$order["title"]."》";
    
        //用户消费记录
        $consumemodel = D("user_consume");
        $consume = array(
            "userid"=>$user["id"], "orderid"=>$order["id"], "order_type"=>3, "type"=>2, "title"=>$title,
            "amount"=>$order["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $consumemodel->add($consume);
    
        //用户订单消息
        $messagemodel = D("user_message");
        $content = '<p>';
        $content .= "【订单号】：".$order["sn"]."<br/>";
        $content .= "【订单内容】：".$title."<br/>";
        $content .= "服务订单评估缴费订单购买成功。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$user["id"], "title"=>$title, "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);
    
        if ($order["service_userid"] > 0) {
            //服务人员订单消息
            $content = '<p>';
            $content .= "【订单内容】：".$title."<br/>";
            $content .= "【服务地址】：".$order["province"].$order["city"].$order["region"].$order["address"]."<br/>";
            $content .= "【服务时间】：".$order["begintime"].' / '.$order["endtime"]."<br/>";
            if($order["other_remark"]){
                $content .= "【用户备注】：".$order["other_remark"]."<br/>";
            }
            if($order["platform_money"] > 0){
                $content .= "【平台补贴】：".$order["platform_money"]."元<br/>";
            }
            $content .= "以上订单客户已经完成服务订单评估缴费，谢谢";
            $content .= '</p>';
            $message_entity = array(
                "userid"=>$order["service_userid"], "title"=>$title, "content"=>$content,
                "hybrid"=>"service", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
            );
            $messagemodel->add($message_entity);
        }
    
        return true;
    }
	
	//VIP订单处理
	private function OrderVip($attach){
	    $paylogmodel = D("pay_log");
	
	    $map = array("sn"=>$attach["logsn"], "type"=>6);
	    $paylog = $paylogmodel->where($map)->find();
	    if(empty($paylog)){
	        return false;
	    }
	    if($paylog["ispaid"] == 1){
	        return true;
	    }
	
	    $ordermodel = D("vip_order");
	
	    $order = $ordermodel->find($paylog["orderid"]);
	    if(empty($order)){
	        return false;
	    }
	
	    $usermodel = D("user");
	
	    $user = $usermodel->where("id=".$order["userid"])->find();
	    if(empty($user)){
	        return false;
	    }
	
	    //订单更新为已支付
	    $o_entity = array("is_pay"=>1,"pay_time"=>date("Y-m-d H:i:s"));
	    $ordermodel->where("id=".$order["id"])->save($o_entity);
	    
	    //订单日志更新为已支付
	    $paylog_entity = array("ispaid"=>1);
	    $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
	
	    //标题
	    $title = "一点椿-VIP购买";
	
	    //用户消费记录
	    $consumemodel = D("user_consume");
	    $consume = array(
	        "userid"=>$user["id"], "orderid"=>$order["id"], "order_type"=>3, "type"=>2, "title"=>$title,
	        "amount"=>$order["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
	    );
	    $consumemodel->add($consume);
		
		//设置VIP状态
		$map = array('id'=>$order['userid']);
		$entity = array('level'=>$order['level']);
		D('user')->where($map)->save($entity);
		
		$map = array('user_id'=>$order['userid'],'over_time'=>array('gt',date('Y-m-d')));
		$vip_info=D('user_vip')->where($map)->find();
		
		//购买的会员的详情
		$map = array('level'=>$order['level']);
		$vip_rule=D('vip_rule')->where($map)->find();
		if($vip_info){
			if($vip_info['level']>$order['level']){
				
			}elseif($vip_info['level']<$order['level']){
				//升级会员 补免费次数的差价
				//剩余免费次数 当前次数 +（升级后会员的免费次数-升级前会员的免费次数）
				$free=$vip_info['free']+($vip_rule['free']);
				$map = array('user_id'=>$order['userid']);
				$entity = array('level'=>$order['level'],'free'=>$free,'birthday'=>$order['birthday'],'over_time'=>date('Y-m-d',time()+31536000));
				D('user_vip')->where($map)->save($entity);
			}else{
				//续费会员
				$map = array('user_id'=>$order['userid']);
				$entity = array('free'=>$vip_info['free']+$vip_rule['free'],'over_time'=>date('Y-m-d',strtotime($vip_info['over_time'])+31536000),'birthday'=>$order['birthday']);
				D('user_vip')->where($map)->save($entity);
			}
		}else{
			//购买会员
			$map = array('user_id'=>$order['userid']);
			D('user_vip')->where($map)->delete();
			$entity = array('user_id'=>$order['userid'],'level'=>$order['level'],'free'=>$vip_rule['free'],'over_time'=>date('Y-m-d',time()+31536000),'birthday'=>$order['birthday']);
			D('user_vip')->add($entity);
		}
		
		//根据会员等级发放对应的卷
		$map = array('type'=>3,'level'=>$order['level'],'status'=>1);
		$coupon = D('coupon')->where($map)->select();
		foreach($coupon as $k=>$v){
			for($i=1;$i<=$v['num'];$i++){
				D('common/coupon')->send_coupon($order['userid'],$v['id']);
			}
		}
	    return true;
	}

    public function publicBenefitOrder($attach){
        $paylogmodel = D("pay_log");
        $map = array("sn"=>$attach["logsn"], "type"=>7);
        $paylog = $paylogmodel->where($map)->find();
        if(empty($paylog)){
            return false;
        }
        if($paylog["ispaid"] == 1){
            return true;
        }
    
        $ordermodel = D("PublicBenefitOrder");
    
        $order = $ordermodel->find($paylog["orderid"]);
        if(empty($order)){
            return false;
        }
    
        $usermodel = D("user");
    
        $user = $usermodel->where("id=".$order["userid"])->find();
        if(empty($user)){
            return false;
        }
    
        //订单更新为已支付
        $order_entity = array("pay_status"=>1, "pay_date"=>date("Y-m-d H:i:s"));
        $ordermodel->where("id=".$order["id"])->save($order_entity);
    
        //订单日志更新为已支付
        $paylog_entity = array("ispaid"=>1);
        $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
    
        //标题
        $info = D('public_benefit')->find($order['public_benefit_id']);
        $title = "一点椿-公益活动《".$order["title"]."》";
    
        //用户消费记录
        $consumemodel = D("user_consume");
        $consume_entity = array(
            "userid"=>$user["id"], "orderid"=>$order["id"], "order_type"=>6, "type"=>2, "title"=>$title,
            "amount"=>$order["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $consumemodel->add($consume_entity);
    
        //用户订单消息
        $messagemodel = D("user_message");
        $content = '<p>';
        $content .= "【订单号】：".$order["sn"]."<br/>";
        $content .= "【订单内容】：".$title."<br/>";
        $content .= "订单购买成功。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$user["id"], "title"=>$title, "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);
    
        //发送短信消息
        $sms = D("Common/RequestSms");
        $sms->SendProductSms($order['mobile'], $title ,$order['sn']);
        return true;
    }
	
	public function pensionOrder($attach){
	    $paylogmodel = D("pay_log");
	    $map = array("sn"=>$attach["logsn"], "type"=>8);
	    $paylog = $paylogmodel->where($map)->find();
	    if(empty($paylog)){
	        return false;
	    }
	    if($paylog["ispaid"] == 1){
	        return true;
	    }
	
	    $ordermodel = D("pension_activity_order");
	
	    $order = $ordermodel->find($paylog["orderid"]);
	    if(empty($order)){
	        return false;
	    }
	
	    $usermodel = D("user");
	
	    $user = $usermodel->where("id=".$order["userid"])->find();
	    if(empty($user)){
	        return false;
	    }
	
	    //订单更新为已支付
	    $order_entity = array("status"=>1,'pay_status'=>3, "pay_date"=>date("Y-m-d H:i:s"));
	    $ordermodel->where("id=".$order["id"])->save($order_entity);
	
	    //订单日志更新为已支付
	    $paylog_entity = array("ispaid"=>1);
	    $paylogmodel->where("id=".$paylog["id"])->save($paylog_entity);
	
	
	    //用户消费记录
	    $consumemodel = D("user_consume");
	    $consume_entity = array(
	        "userid"=>$user["id"], "orderid"=>$order["id"], "order_type"=>6, "type"=>2, "title"=>$order['title'],
	        "amount"=>$order["amount"], "balance"=>0, "createdate"=>date("Y-m-d H:i:s")
	    );
	    $consumemodel->add($consume_entity);
	
	    //用户订单消息
	    $messagemodel = D("user_message");
	    $content = '<p>';
	    $content .= "【订单号】：".$order["sn"]."<br/>";
	    $content .= "【订单内容】：".$order['title']."<br/>";
	    $content .= "订单购买成功。祝您身体健康，生活愉快！";
	    $content .= '</p>';
	    $message_entity = array(
	        "userid"=>$user["id"], "title"=>$order['title'], "content"=>$content,
	        "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
	    );
	    $messagemodel->add($message_entity);
	
	    //短信通知
	    $RequestSms=D('Common/RequestSms');
	    $info=array('mobile'=>$user['mobile']);
		$info['title']=$order['title'];
		$RequestSms->SendLongStay($info);

		// 7陌客服消息
        $content = D('CApi/Moor', 'Service')->orderMessage($order["id"], 3);
        D('CApi/Moor', 'Service')->createContext($user["id"]);
        D('CApi/Moor', 'Service')->sendRobotTextMessage($user["id"], $content);
		
	    return true;
	}
}
