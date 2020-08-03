<?php
namespace CApi\Service;

class PrizeService{
    /**
     * Notes: 抽奖
     * User: LH
     * Date: 2020/4/15
     * Time: 10:30 上午
	 * @param int $user_id
	 * @param int $activity_id
	 * return $prize 中奖的奖品信息
     */
    public function prize($user_id,$activity_id){
		$now = date('Y-m-d H:i:s'); //当前时间  抽奖时间
		$map = array('id'=>$activity_id);
		$activity=D('activity')->where($map)->find();
		if($activity['starttime']>$now){
			E('活动还未开始');
		}
		if($activity['endtime']<$now){
			E('活动已结束');
		}
		if($activity['status']!=1){
			E('活动未开放');
		}
		//查询可以获取的奖品
		$map = array('activity_id'=>$activity_id,'num'=>array('gt',0));
		$prize=D('prize')->where($map)->select();
		foreach($prize as $k=>$v){
			$prize_list[$k]=$v['probability'];
		}
		$key = $this->get_rand($prize_list);//根据中奖率随机获取一个key
		
		//减少该用户抽奖次数
		if(!$this->check($user_id,$activity_id)){
			E('抽奖次数不足');
		}
		$map = array('user_id'=>$user_id,'activity_id'=>$activity_id);
		D('user_prize')->where($map)->setDec('surplus',1);
		
		//发放奖品
		$this->send_prize($user_id,$prize[$key]['id']);
		
		$user_info=D('user')->where(array('id'=>$user_id))->field('nickname')->find();
		//中奖纪录
		$entity = array('user_id'=>$user_id,'user_name'=>$user_info['nickname'],'type'=>$prize[$key]['type'],'prize_name'=>$prize[$key]['title'],'prize_id'=>$prize[$key]['id'],'activity_id'=>$activity_id,'createtime'=>date('Y-m-d H:i:s'));
		D('prize_log')->add($entity);
		
		return $prize[$key];
	}
	/**
	 * Notes: 随机获取一个
	 * User: LH
	 * Date: 2020/4/15
	 * Time: 17:57 下午
	 * @param array $proArr 一维数组 key=>id value=>中奖权重
	 */
	public function get_rand($proArr) {
		$result = '';
		$proSum = array_sum($proArr); //概率数组的总概率精度 
		//概率数组循环 
		foreach ($proArr as $key => $proCur) { 
			$randNum = mt_rand(1, $proSum); 
			if ($randNum <= $proCur) { 
				$result = $key;
				break; 
			} else { 
				$proSum -= $proCur;
			}    
		} 
		unset ($proArr); 
		return $result; 
	} 
	/**
	 * Notes: 发放奖品
	 * User: LH
	 * Date: 2020/4/15
	 * Time: 18:27 下午
	 * @param int $user_id 
	 * @param int $prize_id 
	 */
	public function send_prize($user_id,$prize_id){
		//查看奖品类型
		$prize_info=D('prize')->find($prize_id);
		switch($prize_info['type']){
			case 0:
				//无奖品 谢谢惠顾
				break;
			case 1:
				//积分
				$map = array('id'=>$prize_id);
				D('prize')->where($map)->setDec('num',1);
				$remark['tag']='prize_point';
				$remark['remark']='抽奖活动获得积分';
				D('PointLog','Service')->append($user_id,$prize_info['point'],$remark);
				break;
			case 2:
				//优惠券
				$map = array('id'=>$prize_id);
				D('prize')->where($map)->setDec('num',1);
				$this->GrantUserCoupon($user_id,$prize['coupon_id']);
				break;
			case 3:
				//抽奖机会
				$map = array('id'=>$prize_id);
				D('prize')->where($map)->setDec('num',1);
				$map = array('user_id'=>$user_id,'activity_id'=>$prize_info['activity_id']);
				D('user_prize')->where($map)->setInc('surplus',$prize_info['prize_num']);
				break;
		}
		$map = array('id'=>$prize_id);
		D('prize')->where($map)->setInc('winning',1);
	} 
	/**
	 * Notes: 检查用户能否抽奖
	 * User: LH
	 * Date: 2020/4/16
	 * Time: 10:26 上午
	 * @param int $user_id
	 * @param int $activity_id
	 * return bool 是否剩余抽奖次数
	 */
	public function check($user_id,$activity_id){
		$map = array('user_id'=>$user_id,'activity_id'=>$activity_id);
		$user_prize=D('user_prize')->where($map)->find();
		if($user_prize['surplus']>0){
			return true;
		}
		return false;
	}
	
	/**
	 * Notes: 购买抽奖次数
	 * User: LH
	 * Date: 2020/4/16
	 * Time: 10:26 上午
	 * @param int $user_id
	 * @param int $activity_id
	 */
	public function buy($user_id,$activity_id){
		$map = array('user_id'=>$user_id,'activity_id'=>$activity_id);
		$user_prize=D('user_prize')->where($map)->find();
		if($user_prize && ($user_prize['uplimit']-$user_prize['num'])==0){
			E('抽奖次数达到上限');
		}else{
			//减少积分
			$activity_info=D('activity')->where(array('id'=>$activity_id))->find();
			$user_info=D('user')->where(array('id'=>$user_id))->find();
			if($user_info['point']<$activity_info['point']){
				E('剩余积分不足');
			}else{
				//D('user')->where(array('id'=>$user_id))->setDec('point',$activity_info['point']);
				$remark['tag']='prize';
				$remark['remark']='《'.$activity_info['title'].'》兑换抽奖次数';
				D('PointLog','Service')->append($user_id,-$activity_info['point'],$remark);
			}
			
			$this->add_num($user_id,$activity_id);
		}
		$map = array('user_id'=>$user_id,'activity_id'=>$activity_id);
		$user_prize=D('user_prize')->where($map)->find();
		return $user_prize;
	}
	
	/**
	 * Notes: 添加抽奖次数
	 * User: LH
	 * Date: 2020/4/16
	 * Time: 10:26 上午
	 * @param int $user_id
	 * @param int $activity_id
	 */
	public function add_num($user_id,$activity_id){
		$map = array('user_id'=>$user_id,'activity_id'=>$activity_id);
		$user_prize=D('user_prize')->where($map)->find();
		if($user_prize){
			D('user_prize')->where($map)->setInc('surplus',1);
			D('user_prize')->where($map)->setInc('num',1);
		}else{
			$activity_info=D('activity')->where(array('id'=>$activity_id))->find();
			$entity = array('user_id'=>$user_id,'activity_id'=>$activity_id,'uplimit'=>$activity_info['num'],'num'=>1,'surplus'=>1);
			D('user_prize')->add($entity);
		}
	}
	
	
	//发放优惠券
	private function GrantUserCoupon($user_id,$id){
	    if(empty($user_id)){
	        return;
	    }
	
	    $map = array("id"=>$id, "status"=>1, "count"=>array("gt", 0));
	    $register_coupon = D("coupon")->where($map)->select();
	    if(count($register_coupon) <= 0){
	        return;
	    }
	
	    //发放优惠券
	    $time = time();
	    foreach($register_coupon as $k=>$v){
	        $use_date = $v["use_date"];
	        $entity = array(
	            "couponid"=>$v["id"], "type"=>$v["type"], "code"=>$v["code"], "title"=>$v["title"],
	            "use_start_date"=>date("Y-m-d H:i:s", $time), "use_end_date"=>date("Y-m-d H:i:s", strtotime("+".$use_date." day", $time)),
	            "coupon_type"=>$v["coupon_type"], "status"=>0, "use_type"=>0,
	            "userid"=>$user_id, "createdate"=>date("Y-m-d H:i:s", $time)
	        );
	    	switch($v['coupon_type']){
	    		case 0:
	    			$entity['money']=$v['money'];
	    			$entity['min_amount']=$v['min_amount'];
	    			break;
	    		case 1:
	    			$entity['product_id']=$v['product_id'];
	    			break;
	    		case 2:
	    			$entity['service_id']=$v['service_id'];
	    			break;
	    		case 3:
	    			$entity['org_id']=$v['org_id'];
	    			break;
	    	}
	        D("user_coupon")->add($entity);
	        //更新优惠券余量
	        $r_entity = array("count"=>($v["count"]-1), "sales"=>($v["sales"]+1));
	        D("coupon")->where("id=".$v["id"])->save($r_entity);
	    }
	
	}
}