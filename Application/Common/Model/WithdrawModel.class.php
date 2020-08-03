<?php
namespace Common\Model;

class WithdrawModel{
	public function check($user_id,$config_id=1){
		$config = D('withdraw_config')->where(array('id'=>$config_id))->find();
		
		//校验星期
		$week = date('w');
		$weekday = explode(',',$config['weekday']);
		if($week==0){
			$week = 7;
		}
		if(!in_array($week,$weekday)){
			return array('success'=>0,'info'=>$config['remark']);
		}
		$monday = date('Y-m-d H:i:s',strtotime('monday -6 day',time()));
		//校验次数
		if($config_id == 1){
			//用户端
			$map = array('add_time'=>array('gt',strtotime($monday)),'user_id'=>$user_id);
			$count = D('wallet_withdraw')->where($map)->count();
			if($count >= $config['num']){
				return array('success'=>0,'info'=>'超过本周可提现次数');
			}
		}elseif($config_id == 2){
			//服务端
			$map = array('createtime'=>array('gt',$monday),'user_id'=>$user_id);
			$count = D('service_withdraw')->where($map)->count();
			if($count >= $config['num']){
				return array('success'=>0,'info'=>'超过本周可提现次数');
			}
		}
		return array('success'=>1);
		
	}
}
