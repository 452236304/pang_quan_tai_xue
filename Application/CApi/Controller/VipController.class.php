<?php
namespace CApi\Controller;
use Think\Controller;
//会员
class VipController extends BaseLoggedController {
	public function home(){
		$user = $this->AuthUserInfo;
		$map = array('uv.user_id'=>$user['id'],'uv.over_time'=>array('gt',date('Y-m-d')));
		$vip_info = D('user_vip')->field('uv.*,vr.price')->alias('uv')->where($map)->join('left join sj_vip_rule as vr on uv.level=vr.level')->find();
		if($vip_info){
			//当用户是vip时计算剩余时间
			$over_time = strtotime($vip_info['over_time']);
			$last_time = $over_time - time();
			$last_day = ceil($last_time/86400);//剩余天数
			$vip_rule=D('vip_rule')->order('level desc')->select();
			foreach($vip_rule as $k=>$v){
				if($v['level'] == $vip_info['level']){
					$v['pay_price'] = $v['price'];
				}elseif($v['level'] < $vip_info['level']){
					$v['pay_price'] = $v['price'] - (($vip_info['price'] / 365) * $last_day);
				}else{
					$v['pay_price'] = 0;
				}
				$vip_rule[$k]=$v;
			}
		}else{
			//非vip直接展示每个VIP的价格
			$vip_rule=D('vip_rule')->field('id,level,name,price pay_price,discount,free')->order('level desc')->select();
			$vip_info['user_id']=$user['id'];
			$vip_info['level']=0;
			$vip_info['free']=0;
			$vip_info['over_time']=0;
		}
		
		foreach($vip_rule as $k=>$v){
			//会员权益
			$map = array('status'=>1,'level'=>$v['level']);
			$equity=D('vip_equity')->where($map)->select();
			$v['equity']=$equity;
			$vip_rule[$k]=$v;
		}
		
		//秒杀服务
		$map = array('sp.status'=>1,"lp.status"=>1,'sp.vip_price'=>array('gt',0));
		$seckill = D('service_project')->alias('sp')->join('left join sj_service_project_level_price as lp on sp.id=lp.projectid')->field('sp.*')->group('sp.id')->where($map)->order('createdate desc')->limit(0,4)->select();
		foreach($seckill as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
		}
		
		//积分商品
		$map = array('ps.status'=>1,'ps.type'=>0);
		$point_shop=D('point_shop')->alias('ps')->field('ps.*,pa.price market_price')->where($map)->join('left join sj_product_attribute pa on ps.attribute_id = pa.id')->limit(0,10)->select();
		foreach($point_shop as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
		}
		
		//公益活动/精品课
		$active = array(
			array(
				'title'=>'免费活动',
				'list'=>array(array('title'=>'12小时招呼','level'=>'A级家护师','total_quota'=>10,'quota'=>7,'surplus_quota'=>3,'thumb'=>''),array('title'=>'24小时招呼','level'=>'A级家护师','total_quota'=>10,'quota'=>7,'surplus_quota'=>3,'thumb'=>''))
			),
			array(
				'title'=>'0元大咖',
				'list'=>array(array('title'=>'48小时招呼','level'=>'A级家护师','total_quota'=>10,'quota'=>7,'surplus_quota'=>3,'thumb'=>''),array('title'=>'72小时招呼','level'=>'A级家护师','total_quota'=>10,'quota'=>7,'surplus_quota'=>3,'thumb'=>''))
			),
		);
		
		//免费礼包
		if($vip_info){
			$map = array('is_vip'=>1,'userid'=>$user['id'],'coupon_type'=>array('gt',0));
			$free_project = D('user_coupon')->where($map)->select();
		}else{
			$map = array('status'=>1,'type'=>3,'level'=>array('gt',0));
			$free_project = D('coupon')->where($map)->select();
		}
		
		foreach($free_project as $k=>&$v){
			switch($v['coupon_type']){
				case 1:
					//商品
					$map = array('id'=>$v['product_id']);
					$v = D('product')->where($map)->find();
					$v['type'] = 1;
					break;
				case 2:
					//服务
					$map = array('id'=>$v['service_id']);
					$v = D('service_project')->where($map)->find();
					$v['type'] = 2;
					break;
				case 3:
					//机构
					$map = array('id'=>$v['org_id']);
					$v = D('pension')->where($map)->find();
					$v['type'] = 3;
					break;
			}
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
		}
		
		//获取配置
		$vip_config = D('vip_config')->find();
		
		return array(
			'vip_info'=>$vip_info,'vip_rule'=>$vip_rule,'equity'=>$equity, 
			'pointshop'=>$pointshop,'seckill'=>$seckill,'free_project'=>$free_project,
			'active'=>$active,'free_title'=>$vip_config['free_title'],
			'free_switch'=>$vip_config['free_switch'],'active_switch'=>$vip_config['active_switch'],
			'strategy'=>'升级攻略','point_switch'=>$vip_config['point_switch'],
			'seckill_switch'=>$vip_config['seckill_switch'],'banner'=>$vip_config['banner'],
			'content_switch'=>$vip_config['content_switch'],
			'content'=>$vip_config['content']
		);
	}
	
	//确认升级会员
	public function check_levelup(){
		$user = $this->AuthUserInfo;
		$level=I('get.level');//需要升级到的会员等级
		
		//检查当前会员等级
		$map = array('uv.user_id'=>$user['id'],'uv.over_time'=>array('gt',date('Y-m-d')));
		$vip_info = D('user_vip')->field('uv.*,vr.price,vr.point')->alias('uv')->where($map)->join('left join sj_vip_rule as vr on uv.level=vr.level')->find();
		//目标的会员等级信息
		$map = array('level'=>$level);
		$vip_rule=D('vip_rule')->where($map)->find();
		if($vip_info){
			if($vip_info['level']>$level){
				E('当前会员等级比购买的会员等级高');
			}elseif($vip_info['level']<$level){
				//升级会员
				//$price = ($vip_rule['price']-$vip_info['price'])*($last_day/365);
				$price = $vip_rule['price'] - (($vip_info['price'] / 365) * $last_day);
			}else{
				//续费会员
				$price = $vip_rule['price'];
				$point = $vip_rule['point'];
			}
		}else{
			//购买会员
			$price = $vip_rule['price'];
			$point = $vip_rule['point'];
		}
		return array('price'=>$price,'point'=>$point);
	}
	
	//创建订单
	public function createorder(){
		$user = $this->AuthUserInfo;
		$level = I('get.level');//需要升级到的会员等级
		$birthday = I('get.birthday');
		$mobile = I('get.mobile',0);
		$create_user = $user['id'];
		if($mobile){
			$user = D('user')->where(array('account'=>$mobile))->find();
			if(empty($user)){
				E('该好友还未注册');
			}
		}
		//检查当前会员等级
		$map = array('uv.user_id'=>$user['id'],'uv.over_time'=>array('gt',date('Y-m-d H:i:s')));
		$vip_info = D('user_vip')->field('uv.*,vr.price,vr.point')->alias('uv')->where($map)->join('left join sj_vip_rule as vr on uv.level=vr.level')->find();
		//目标的会员等级信息
		$map = array('level'=>$level);
		$vip_rule=D('vip_rule')->where($map)->find();
		if($vip_info){
			if($vip_info['level']>$level){
				E('当前会员等级比购买的会员等级高');
			}elseif($vip_info['level']<$level){
				//升级会员
				//$price = ($vip_rule['price']-$vip_info['price'])*($last_day/365);
				$price = $vip_rule['price'] - (($vip_info['price'] / 365) * $last_day);
			}else{
				//续费会员
				$price = $vip_rule['price'];
			}
		}else{
			//购买会员
			$price = $vip_rule['price'];
		}
		$sn = $this->BuildOrderSN();
		$entity = array(
			'status'=>0,'userid'=>$user['id'],'username'=>$user['realname'],'sn'=>$sn,'total_amount'=>$price,
			'amount'=>$price,'total_point'=>$point,'point'=>0,'is_pay'=>0,'createtime'=>date('Y-m-d H:i:s'),'level'=>$level,'birthday'=>$birthday,'create_user'=>$create_user,'mobile'=>$mobile
		);
		$orderid=D('vip_order')->add($entity);
		return array('orderid'=>$orderid,'ordersn'=>$sn);
	}
}