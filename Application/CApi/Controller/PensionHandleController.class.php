<?php
namespace CApi\Controller;
use Think\Controller;
class PensionHandleController extends BaseLoggedController {
	//创建折扣住订单
	public function createorder(){
		$user = $this->AuthUserInfo;
		$now = date('Y-m-d H:i:s');
		//机构ID
		$id = I('post.id');
		$map = array('status'=>1,'id'=>$id);
		$pension_info=D('pension')->where($map)->find();
		
		//检查进行中的活动
		$map = array('status'=>1,'pension_id'=>$id,'starttime'=>array('lt',$now),'endtime'=>array('gt',$now));
		$activity_info=D('pension_activity')->where($map)->order('price asc')->find();
		if($activity_info){
			$info = array(
				'title'=>($pension_info['title'].'-'.$activity_info['title']),'id'=>$activity_info['id'],
				'price'=>$activity_info['price']
			);
		}else{
			$info = array(
				'title'=>$pension_info['title'],'id'=>0,'price'=>$pension_info['price']
			);
		}
		
		$order = array(
			'sn'=>$this->BuildOrderSN(),'userid'=>$user['id'],'title'=>$info['title'],'pension_id'=>$pension_info['id'],
			'activity_id'=>$info['id'],'status'=>0,'total_amount'=>$info['price'],'amount'=>$info['price'],
			'createtime'=>date('Y-m-d H:i:s'),'nickname'=>$user['nickname'],'mobile'=>$user['mobile']
		);
		
		$order['id'] = D('pension_activity_order')->add($order);

        // 7陌订单提醒
        $content = D('CApi/Moor', 'Service')->orderMessage($order['id'], 3);
        D('CApi/Moor', 'Service')->createContext($user['id']);
        D('CApi/Moor', 'Service')->sendRobotTextMessage($user['id'], $content);


		return array('orderid'=>$order['id'],'userid'=>$user['id'],'ordersn'=>$order['sn'],'amount'=>$order['amount'],'title'=>$order['title'],'createtime'=>$order['createtime']);
	}
}