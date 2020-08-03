<?php
namespace CApi\Controller;
use Think\Controller;
//委托代理
class EntrustController extends BaseLoggedController {
	public function put(){
		$user = $this->AuthUserInfo;
		$d['userid']=$user['id'];
		$d['work']=I('post.work');
		$d['entrusttime']=I('post.entrusttime');
		$d['name']=I('post.name');
		$d['mobile']=I('post.mobile');
		$d['address']=I('post.address');
		$d['createtime']=date("Y-m-d H:i:s");
		D('entrust')->add($d);
		return;
	}
}