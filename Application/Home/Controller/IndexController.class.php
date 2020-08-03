<?php
namespace Home\Controller;
use Think\Controller;

require_once "Application/Payment/Weixin/Extend/log.php";

class IndexController extends Controller {

    function __construct(){
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/timer/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }
	
	public function index(){
		$this->redirect("../index");
	}

    //处理超时的商品订单 - 1分钟
    public function OrderProductHandle(){
        \Log::INFO("OrderProductHandle：处理超时的商品订单 - begin");

        $ordermodel = D("product_order");

        $time = date("Y-m-d H:i:s", strtotime("-30 minute", time()));
        $map = array("type"=>0, "timeout"=>0, "status"=>1, "pay_status"=>0, "createdate"=>array("lt", $time));
        $list = $ordermodel->where($map)->select();
        if(count($list) <= 0){
            return;
        }

        //获取所有商品订单中的商品
        $orderproductmodel = D("product_order_product");
        $productmodel = D("product");
        foreach($list as $k=>$v){
            $map = array("orderid"=>$v["id"]);
            $orderproducts = $orderproductmodel->where($map)->select();
            if(count($orderproducts) <= 0){
                continue;
            }

            //释放商品库存
            foreach($orderproducts as $ik=>$iv){
                $map = array("id"=>$iv["productid"]);
                $product = $productmodel->where($map)->find();
                if(empty($product)){
                    continue;
                }

                $entity = array("stock"=>($product["stock"]+1));
                $productmodel->where($map)->save($entity);
            }

            //待更新订单id集合
            $updateorderids[] = $v["id"];
        }

        if(empty($updateorderids) || count($updateorderids) <= 0){
            return;
        }

        //更新订单状态为超时订单
        $entity = array("timeout"=>1);
        $map = array("id"=>array("in", $updateorderids));
        $ordermodel->where($map)->save($entity);

        \Log::INFO("OrderProductHandle：处理超时的商品订单 - 超时订单(updateorderids)：".(json_encode($updateorderids)));
    }

    //验证服务订单超过服务开始时间后的半小时，订单还未开始，即为爽约订单，设置服务人员3个月内不能接单 - 30分钟
    public function OrderServiceTimeoutHandle(){

        //送餐订单 - 开始配送爽约检查
        $this->OrderServiceType1BeginTimeout();
        //送餐订单 - 完成配送爽约检查
        $this->OrderServiceType1EndTimeout();
        
        //服务订单爽约检查
        $this->OrderServiceType2Timeout();

    }

    //验证送餐订单超过服务开始时间后的半小时，订单还未开始，即为爽约订单
    private function OrderServiceType1BeginTimeout(){
        \Log::INFO("OrderServiceType1BeginTimeout：验证送餐订单超过服务开始时间，订单还未开始，即为爽约订单，设置服务人员3个月内不能接单 - begin");

        $ordermodel = D("service_order");

        $map = array(
            "type"=>1, "status"=>1, "pay_status"=>3, "admin_status"=>1, "service_userid"=>array("gt", 0),
            "execute_status"=>0
        );
        $list = $ordermodel->where($map)->select();
        if(count($list) <= 0){
            return;
        }
        
        //服务交互记录
        $recordmodel = D("service_order_record");

        $time = date("Y-m-d H:i", strtotime("-30 minute", time()));
        foreach($list as $k=>$v){
            //午餐或者晚餐时
            if(in_array($v["res_type"], [1,2]) && $v["begintime"] >= $time){
                continue;
            }
            //午餐和晚餐时
            if($v["res_type"] == 3){
                $btime = date("Y-m-d", strtotime($v["begintime"]));
                if($v["service_time"] == 0){ //第一份午餐
                    $begintime = $btime." 11:00:00";
                    if($begintime >= $time){
                        continue;
                    }
                }
                if($v["service_time"] == 1){ //第二份晚餐
                    $begintime = $btime." 17:00:00";
                    if($begintime >= $time){
                        continue;
                    }
                }
            }

            //爽约记录
            $entity = array(
                "orderid"=>$v["id"], "userid"=>$v["service_userid"], "title"=>"爽约订单",
                "execute_status"=>7, "updatetime"=>date("Y-m-d H:i:s")
            );
            $map = array("orderid"=>$v["id"], "userid"=>$v["service_userid"], "execute_status"=>7);
            $check = $recordmodel->where($map)->find($map);
            if(empty($check)){
                $recordmodel->add($entity);
                
                //爽约订单id集合
                $orderids[] = $v["id"];
                //爽约服务人员id集合
                $serviceuserids[] = $v["service_userid"];
            }
        }

        if(empty($serviceuserids) || count($serviceuserids) <= 0){
            return;
        }

        //更新服务订单的执行状态为爽约状态
        $entity = array("execute_status"=>7, "execute_time"=>date("Y-m-d H:i:s"));
        $map = array("id"=>array("in", $orderids));
        $ordermodel->where($map)->save($entity);

        //记录服务人员爽约时间，3个月内不能接单
        $profilemodel = D("user_profile");
        $entity = array("plane_time"=>date("Y-m-d H:i:s"));
        $map = array("userid"=>array("in", $serviceuserids));
        $profilemodel->where($map)->save($entity);

        \Log::INFO("OrderServiceType1BeginTimeout：验证送餐订单超过服务开始时间，订单还未开始，即为爽约订单，设置服务人员3个月内不能接单 - 爽约服务人员(serviceuserids)：".(json_encode($serviceuserids)));
    }

    //验证送餐订单超过服务结束时间后，订单还未完成，即为爽约订单
    private function OrderServiceType1EndTimeout(){
        \Log::INFO("OrderServiceType1EndTimeout：验证送餐订单超过服务结束时间，订单还未完成，即为爽约订单，设置服务人员3个月内不能接单 - begin");

        $ordermodel = D("service_order");

        $map = array(
            "type"=>1, "status"=>1, "pay_status"=>3, "admin_status"=>1, "service_userid"=>array("gt", 0),
            "execute_status"=>array("in", [1,2])
        );
        $list = $ordermodel->where($map)->select();
        if(count($list) <= 0){
            return;
        }
        
        //服务交互记录
        $recordmodel = D("service_order_record");

        $time = date("Y-m-d H:i");
        foreach($list as $k=>$v){
            //午餐或者晚餐时
            if(in_array($v["res_type"], [1,2]) && $v["endtime"] >= $time){
                continue;
            }
            //午餐和晚餐时
            if($v["res_type"] == 3){
                $etime = date("Y-m-d", strtotime($v["endtime"]));
                if($v["service_time"] == 0){ //第一份午餐
                    $endtime = $etime." 13:00:00";
                    if($endtime >= $time){
                        continue;
                    }
                }
                if($v["service_time"] == 1){ //第二份晚餐
                    $endtime = $etime." 19:00:00";
                    if($endtime >= $time){
                        continue;
                    }
                }
            }

            //爽约记录
            $entity = array(
                "orderid"=>$v["id"], "userid"=>$v["service_userid"], "title"=>"爽约订单",
                "execute_status"=>7, "updatetime"=>date("Y-m-d H:i:s")
            );
            $map = array("orderid"=>$v["id"], "userid"=>$v["service_userid"], "execute_status"=>7);
            $check = $recordmodel->where($map)->find($map);
            if(empty($check)){
                $recordmodel->add($entity);
                
                //爽约订单id集合
                $orderids[] = $v["id"];
                //爽约服务人员id集合
                $serviceuserids[] = $v["service_userid"];
            }
        }

        if(empty($serviceuserids) || count($serviceuserids) <= 0){
            return;
        }

        //更新服务订单的执行状态为爽约状态
        $entity = array("execute_status"=>7, "execute_time"=>date("Y-m-d H:i:s"));
        $map = array("id"=>array("in", $orderids));
        $ordermodel->where($map)->save($entity);

        //记录服务人员爽约时间，3个月内不能接单
        $profilemodel = D("user_profile");
        $entity = array("plane_time"=>date("Y-m-d H:i:s"));
        $map = array("userid"=>array("in", $serviceuserids));
        $profilemodel->where($map)->save($entity);

        \Log::INFO("OrderServiceType1EndTimeout：验证送餐订单超过服务结束时间，订单还未完成，即为爽约订单，设置服务人员3个月内不能接单 - 爽约服务人员(serviceuserids)：".(json_encode($serviceuserids)));
    }

    //验证服务订单超过服务开始时间后的半小时，订单还未开始，即为爽约订单
    private function OrderServiceType2Timeout(){
        \Log::INFO("OrderServiceType2Timeout：验证服务订单超过服务结束时间，订单还未开始，即为爽约订单，设置服务人员3个月内不能接单 - begin");

        $ordermodel = D("service_order");

        $time = date("Y-m-d H:i", strtotime("-30 minute", time()));
        $map = array(
            "type"=>2, "status"=>1, "pay_status"=>3, "admin_status"=>1, "service_userid"=>array("gt", 0),
            "execute_status"=>0, "begintime"=>array("lt", $time)
        );
        $list = $ordermodel->where($map)->select();
        if(count($list) <= 0){
            return;
        }
        
        //服务交互记录
        $recordmodel = D("service_order_record");

        foreach($list as $k=>$v){
            //爽约记录
            $entity = array(
                "orderid"=>$v["id"], "userid"=>$v["service_userid"], "title"=>"爽约订单",
                "execute_status"=>7, "updatetime"=>date("Y-m-d H:i:s")
            );
            $map = array("orderid"=>$v["id"], "userid"=>$v["service_userid"], "execute_status"=>7);
            $check = $recordmodel->where($map)->find($map);
            if(empty($check)){
                $recordmodel->add($entity);
                
                //爽约订单id集合
                $orderids[] = $v["id"];
                //爽约服务人员id集合
                $serviceuserids[] = $v["service_userid"];
            }
        }

        if(empty($serviceuserids) || count($serviceuserids) <= 0){
            return;
        }

        //更新服务订单的执行状态为爽约状态
        $entity = array("execute_status"=>7, "execute_time"=>date("Y-m-d H:i:s"));
        $map = array("id"=>array("in", $orderids));
        $ordermodel->where($map)->save($entity);

        //记录服务人员爽约时间，3个月内不能接单
        $profilemodel = D("user_profile");
        $entity = array("plane_time"=>date("Y-m-d H:i:s"));
        $map = array("userid"=>array("in", $serviceuserids));
        $profilemodel->where($map)->save($entity);

        \Log::INFO("OrderServiceType2Timeout：验证服务订单超过服务结束时间，订单还未开始，即为爽约订单，设置服务人员3个月内不能接单 - 爽约服务人员(serviceuserids)：".(json_encode($serviceuserids)));
    }

    //服务订单结束前10分钟，提醒用户是否续单，续单服务时间为60分钟 - 10分钟
    public function OrderServiceWarnHandle(){
        \Log::INFO("OrderServiceWarnHandle：服务订单结束前10分钟，提醒用户是否续单，续单服务时间不超过60分钟 - begin");

        $ordermodel = D("service_order");

        $begintime = date("Y-m-d H:i:s", strtotime("+20 minute", time()));
        $endtime = date("Y-m-d H:i:s", strtotime("+30 minute", time()));
        $map = array(
            "type"=>2, "status"=>1, "pay_status"=>3, "admin_status"=>1, "service_userid"=>array("gt", 0),
            "execute_status"=>array("in", [1,2,3]), "endtime"=>array(array("lt", $endtime), array("egt", $begintime), "and")
        );
        $list = $ordermodel->where($map)->select();
        if(count($list) <= 0){
            return;
        }

        //消息推送提醒用户续单
        $usermodel = D("user");
        $msgpush = D("Common/IGeTuiMessagePush");
        foreach($list as $k=>$v){
            $userid = $v["userid"];
            $user = $usermodel->find($userid);
            if(empty($user)){
                continue;
            }

            $clientid = $user["clientid"];
            $system = $user["system"];
            $title = "服务订单提醒";
            $content = "您购买的服务《".$v["title"]."》即将结束，点击续单可继续服务，续单时长为60分钟。";
            $msgpush->PushMessageToSingle($clientid, $system, $title, $content);

            $warnusers[] = array("userid"=>$userid, "orderid"=>$v["id"]);
        }
        
        \Log::INFO("OrderServiceWarnHandle：服务订单结束前10分钟，提醒用户是否续单，续单服务时间为60分钟 - 提醒用户(warnusers)：".(json_encode($warnusers)));
    }

    //12小时内用户或者服务人员没有点击完成订单，系统自动确认完成订单 - 30分钟
    public function OrderServiceAutoCompletedHandle(){
        \Log::INFO("OrderServiceAutoCompletedHandle：12小时内用户或者服务人员没有点击完成订单，系统自动确认完成订单 - begin");

        $ordermodel = D("service_order");

        $time = date("Y-m-d H:i:s", strtotime("-12 hour", time()));
        $map = array(
            "status"=>1, "pay_status"=>3, "admin_status"=>1, "service_userid"=>array("gt", 0),
            "execute_status"=>array("in", [2,3]), "endtime"=>array("lt", $time)
        );
        $list = $ordermodel->where($map)->select();
        if(count($list) <= 0){
            return;
        }

        //服务交互记录
        $recordmodel = D("service_order_record");

        foreach($list as $k=>$v){
            $orderids[] = $v["id"];
            
            $entity = array(
                "orderid"=>$v["id"], "userid"=>0, "title"=>"完成订单",
                "execute_status"=>4, "updatetime"=>date("Y-m-d H:i:s"), "remark"=>"系统自动确认完成订单"
            );
            $recordmodel->add($entity);
        }

         //更新订单为已完成状态
         $entity = array("status"=>4, "execute_status"=>4, "execute_time"=>date("Y-m-d H:i:s"));
         $map = array("id"=>array("in", $orderids));
         $ordermodel->where($map)->save($entity);
		 
		 //完成服务发放积分
		 $user = D('user')->where(array('id'=>$order['userid']))->find();
		 if($user['level']>0){
		 	//购物发放积分 1元=2分
		 	$data=['remark'=>'会员购买服务获得积分','tag'=>'shopping'];
		 	$point = $order['amount']*2;
		 	D('PointLog','Service')->append($user['id'],$point,$data);
		 }else{
		 	//购物发放积分 1元=1.5分
		 	$data=['remark'=>'购买服务获得积分','tag'=>'shopping'];
		 	$point = floor($order['amount']*1.5);
		 	D('PointLog','Service')->append($user['id'],$point,$data);
		 }
		 
		//记录到服务人员的佣金表
		$withdrawal = $order['amount']*0.8;
		$commission = array(
			'status'=>0,'user_id'=>$order['service_userid'],'achievement'=>$order['amount'],
			'withdrawal'=>$withdrawal,'order_id'=>$order['id'],'order_sn'=>$order['sn'],
			'title'=>$order['title'],'subsidy'=>$order['platform_money'],
			'createtime'=>date('Y-m-d H:i:s')
		);
		
		D('service_commission')->add($commission);
		
		$money = $withdrawal + $order['platform_money'];
		$map = array('userid'=>$order['service_userid']);
		D('user_profile')->where($map)->setInc('money',$money);
		
		
        \Log::INFO("OrderServiceAutoCompletedHandle：12小时内用户或者服务人员没有点击完成订单，系统自动确认完成订单 - 系统确认订单(orderids)：".(json_encode($orderids)));
    }

    //7天内未评价订单设置为默认好评 - 1天
    public function OrderServiceAutoCommentHandle(){
        \Log::INFO("OrderServiceAutoCommentHandle：7天内未评价订单设置为默认好评 - begin");

        $ordermodel = D("service_order");

        $time = date("Y-m-d H:i:s", strtotime("-7 day", time()));
        $map = array(
            "so.status"=>4, "so.pay_status"=>3, "so.admin_status"=>1, "so.service_userid"=>array("gt", 0),
            "so.execute_status"=>4, "so.commentid"=>0, "so.execute_time"=>array("lt", $time)
        );
        $list = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")->field("so.*,u.avatar")->where($map)->select();
        if(count($list) <= 0){
            return;
        }

        //服务订单评价
        $commentmodel = D("service_comment");

        //系统默认好评
        foreach($list as $k=>$v){
            $entity = array(
                "status"=>1, "userid"=>$v["userid"], "nickname"=>$v["nickname"], "avatar"=>$v["avatar"],
                "service_userid"=>$v["service_userid"], "service_realname"=>$v["service_realname"], "service_avatar"=>$v["service_avatar"],
                "content"=>"好评", "comment1"=>5, "comment2"=>5, "comment3"=>5, "score"=>100,
                "orderid"=>$v["id"], "ordersn"=>$v["sn"], "createdate"=>date("Y-m-d H:i:s"), "system"=>1
            );

            $commentid=$commentmodel->add($entity);
			$map = array('id'=>$v["id"]);
			$entity = array('commentid'=>$commentid);
			$ordermodel->where($map)->save($entity);
            $orderids[] = $v["id"];
        }
		
        \Log::INFO("OrderServiceAutoCommentHandle：7天内未评价订单设置为默认好评 - 系统默认好评(orderids)：".(json_encode($orderids)));
    }
	//7天内未收货自动确认收货 - 1天
	public function receiveorderAutoComplate(){
		$ordermodel = D("product_order");
		
		$time = date("Y-m-d H:i:s", strtotime("-7 day", time()));
		$map = array('status'=>1,'pay_status'=>3,'shipping_status'=>1,'type'=>0,'shipping_send_date'=>array('lt',$time));
		$order=D('product_order')->field('id')->where($map)->select();
		foreach($order as $k=>$v){
			$this->receiveorder($v['id']);
		}
	}
	
    //45天的周期验证服务人员的服务等级降级验证 - 1天
    public function ServiceUserLevelCheckHandle(){
        \Log::INFO("ServiceUserLevelCheckHandle：45天的周期验证服务人员的服务等级降级验证 - begin");
        
        $rolemodel = D("user_role");

        $map = array("status"=>1, "role"=>array("in", [2,3,4,5,6]));
        $users = $rolemodel->where($map)->select();
        
        $profilemodel = D("user_profile");

        foreach($users as $k=>$v){
            if(!in_array($v["userid"], $userids)){
                $userids[] = $v["userid"];
            }
        }

        $map = array("userid"=>array("in", $userids));
        $profiles = $profilemodel->where($map)->select();

        $ordermodel = D("service_order");
        $commentmodel = D("service_comment");

        foreach($profiles as $k=>$v){
            //当前服务人员的服务等级低等于1级，无需进行降级验证
            $service_level = $v["service_level"];
            if($service_level <= 1){
                continue;
            }

            $begintime = $v["service_level_check_time"];
            if(empty($begintime) || strpos($begintime, "0000-00-00") !== false){
                //等级验证时间或等级变化时间不存在即更新到用户配置信息中
                $entity = array("service_level_check_time"=>date("Y-m-d H:i:s"));
                if(empty($v["service_level_update_time"]) || strpos($v["service_level_update_time"], "0000-00-00") !== false){
                    $entity["service_level_update_time"] = date("Y-m-d H:i:s");
                }
                $map = array("userid"=>$v["userid"]);
                $profilemodel->where($map)->save($entity);

                continue;
            } else if(empty($v["service_level_update_time"]) || strpos($v["service_level_update_time"], "0000-00-00") !== false){
                $entity["service_level_update_time"] = date("Y-m-d H:i:s");
                $map = array("userid"=>$v["userid"]);
                $profilemodel->where($map)->save($entity);
            }

            //验证是否满足45天的周期
            $time = date("Y-m-d H:i:s", strtotime("-45 day", time()));
            if($begintime > $time){
                continue;
            }

            //45天内的订单
            $map = array("status"=>4, "service_userid"=>$v["userid"], "execute_time"=>array("egt", $begintime), "commentid"=>array("gt", 0));
            $orders = $ordermodel->where($map)->select();
            foreach($orders as $k=>$v){
                $commentids[] = $v["commentid"];
            }
            if(count($commentids) <= 0){
                continue;
            }

            //45天内的订单评价
            $map = array("service_userid"=>$v["userid"], "id"=>array("in", $commentids));
            $comments = $commentmodel->where($map)->select();
            $commentcount = count($comments);

            $total_score = 0;
            foreach ($comments as $k=>$v) {
                $total_score += $v["score"];
            }

            //平均分
            $score = 0;
            if($commentcount > 0){
                $score = $total_score/$commentcount;
            }
            
            //记录当前的服务等级验证时间
            $entity = array("service_level_check_time"=>date("Y-m-d H:i:s"));
            //平均分低于70分,降低当前服务人员的服务等级
            if($score < 70){
                $entity["service_level"] = ($service_level - 1);
            }
            $map = array("userid"=>$v["userid"]);
            $profilemodel->where($map)->save($entity);
        }

        \Log::INFO("ServiceUserLevelCheckHandle：45天的周期验证服务人员的服务等级降级验证 - 服务等级降级验证(userids)：".(json_encode($userids)));
    }

    //服务开始1小时前发送短信通知 - 30分钟
    public function ServiceLastHour(){
    	$runlog=F('runlogstart');
    	$runlog[]=time();
    	F('runlogstart',$runlog);
    	$ordermodel=D('service_order');
    	$map=array('sms'=>0,'status'=>1);
    	$map['service_userid']=array('gt',0);
    	$map['execute_status']=0;
    	$map['begintime']=array('lt',date("Y-m-d H:i:s", strtotime("+1 hour", time())));
    	$list=$ordermodel->where($map)->select();
    	$RequestSms=D('Common/RequestSms');
    	$usermodel=D('user_profile');
    	foreach($list as $k=>$v){
    		$service_info=$usermodel->field('mobile')->where(array('userid'=>$v['service_userid']))->find();
    		$info=array('mobile'=>$service_info['mobile'],'title'=>$v['title']);
    		$result=$RequestSms->SendLastHour($info);
    		$smsResult=F('smsresult');
    		$smsResult[]=$result;
    		F('smsresult',$smsResult);
    		$ordermodel->where('id='.$v['id'])->save(array('sms'=>1));
    	}
    	$runlog=F('runlogend');
    	$runlog[]=time();
    	F('runlogend',$runlog);
    }
	//确认收货
	public function receiveorder($orderid){
		$user = $this->AuthUserInfo;
	
	
		$model = D("product_order");
	
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
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
}