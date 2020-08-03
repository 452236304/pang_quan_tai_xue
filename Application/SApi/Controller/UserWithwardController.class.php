<?php
namespace SApi\Controller;
use Think\Controller;

//提现控制器
class UserWithwardController extends BaseLoggedController {
	//可提现额度
	public function money(){
		$user = $this->AuthUserInfo;
		$amount = D('service_withdraw')->where(array('status'=>0,'user_id'=>$user['id']))->field('SUM(amount) amount')->group('status')->find();
		if(empty($amount['amount'])){
			$amount['amount'] = 0;
		}
		//规则
		$config = D('withdraw_config')->where('id=2')->find();
		
		return array('money'=>$user['profile']['money'],'examine'=>$amount['amount'],'remark'=>$config['remark']);
	}
	//提现记录
	public function history(){
		$user = $this->AuthUserInfo;
		$withdrawModel = D('service_withdraw');
		
		//查询已提现额度
		$map = array('status'=>1,'user_id'=>$user['id']);
		$is_withdraw = $withdrawModel->field('SUM(amount) amount')->where($map)->group('user_id')->find();
		
		//查询提现记录(分页)
		$page = I('get.page',1);
		$row = I('get.row',10);
		$begin = ($page-1)*$row;
		
		$map = array('user_id'=>$user['id']);
		
		//状态 0全部 1待审核 2已提现
		$status = I('get.status',0);
		switch($status){
			case 1:
				$map['status'] = 0;
				break;
			case 2:
				$map['status'] = 1;
				break;
		}
		
		$count = $withdrawModel->where($map)->count();
		
		$list = $withdrawModel->field('status,amount,createtime')->where($map)->limit($begin,$row)->select();
		
		$totalpage = ceil($count/$row);
		
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		
		return array('list'=>$list,'money'=>$is_withdraw['amount']?:0);
	}
	//提现申请
	public function apply(){
		$user = $this->AuthUserInfo;
		
		$result = D('Common/Withdraw')->check($user['id'],2);
		if($result['success'] == 0){
			E($result['info']);
		}
		
		//类型 0微信 1支付宝 
		$type = I('post.type',1);
		
		if($type==1){
			//支付宝账号
			$account = I('post.account');
			if(empty($account)){
				E('请填写支付宝账号');
			}
			
			//名字
			$truename = I('post.truename');
			if(empty($truename)){
				E('请填写真实姓名');
			}
			
			if($truename != $user['profile']['realname']){
				E('请填写姓名与账号绑定姓名不一致');
			}
			
			//手机号
			$mobile = I('post.mobile');
			if($mobile != $user['mobile']){
				E('手机号码与账号手机号不一致');
			}
			if(empty($mobile)){
				E('请填写手机号');
			}
			
			//验证码
			$code = I('post.code');
			if(empty($code)){
				E('请填写验证码');
			}
			$this->CheckSmsCode($mobile,'withward',$code);
			
			//提现金额
			$amount = I('post.amount');
			
			//最低提现金额校验
			if($amount < 0.1){
				E('最低提现金额0.1元');
			}
			
			//校验是否有足够的佣金
			if($user['profile']['money'] < $amount){
				E('金额不足无法提现');
			}
			
			//记录到提现申请
			$entity = array(
				'sn'=>$this->BuildOrderSN(),'status'=>0,'user_id'=>$user['id'],'amount'=>$amount,
				'type'=>1,'openid'=>'','ali_account'=>$account,'ali_name'=>$truename,
				'createtime'=>date('Y-m-d H:i:s')
			);
			D('service_withdraw')->add($entity);
			
			D('user_profile')->where(array('userid'=>$user['id']))->setDec('money',$amount);
			
			return ;
		}else{
			//手机号
			$mobile = I('post.mobile');
			if(empty($mobile)){
				E('请填写手机号');
			}
			
			//验证码
			$code = I('post.code');
			if(empty($code)){
				E('请填写验证码');
			}
			$this->CheckSmsCode($mobile,'withward',$code);
			
			//提现金额
			$amount = I('post.amount');
			
			//最低提现金额校验
			if($amount < 0.1){
				E('最低提现金额0.1元');
			}
			
			//校验是否有足够的佣金
			if($user['profile']['money'] < $amount){
				E('金额不足无法提现');
			}
			
			E('微信提现暂未开通');
		}
	}
}