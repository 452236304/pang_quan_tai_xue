<?php
namespace Home\Controller;
use Think\Controller;
class WxcodeController extends Controller {
	public function index(){
		$map = array('id'=>1);
		$info=D('wxcode')->where($map)->find();
		$this->assign('info',$info);
		$this->display();
	}
}