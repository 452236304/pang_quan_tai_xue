<?php
namespace SApi\Controller;
use Think\Controller;
class WithdrawController extends BaseController {
	/*
	 * 提现规则
	 * 用户端 分销提现 id=1
	*/
   public function amount_list(){
	   $list = D('withdraw_config_money')->where(array('config_id'=>2))->select();
	   return $list;
   }
}