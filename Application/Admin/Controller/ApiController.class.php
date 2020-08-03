<?php
namespace Admin\Controller;
use Think\Controller;
class ApiController extends BaseController {
	/* 新订单通知 */
	public function order_notice(){
		//服务订单
		$map = array('notice'=>0);
		$service_order = D('service_order')->where($map)->find();
		if($service_order){
			$data = array('type'=>'1');
			// $map = array('notice'=>0);
			// $service_order = D('service_order')->where($map)->save(array('notice'=>1));
			$this->ajaxReturn($data,'json');
		}
		
		//商品订单
		$map = array('notice'=>0);
		$product_order = D('product_order')->where($map)->find();
		if($product_order){
			$data = array('type'=>'2');
			/* $map = array('notice'=>0);
			$product_order = D('product_order')->where($map)->save(array('notice'=>1)); */
			$this->ajaxReturn($data,'json');
		}
		
		//机构订单
		$map = array('notice'=>0);
		$pension_activity_order = D('pension_activity_order')->where($map)->find();
		if($pension_activity_order){
			$data = array('type'=>'3');
			// $map = array('notice'=>0);
			// $pension_activity_order = D('pension_activity_order')->where($map)->save(array('notice'=>1));
			$this->ajaxReturn($data,'json');
		}
		$this->Error('无消息');
	}
	protected function Error($message, $code = 0){
		header("HTTP/1.1 400 Error");
		$this->ajaxReturn(array("message"=>$message, "code"=>$code), "json");
	}
}