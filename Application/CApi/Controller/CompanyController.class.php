<?php
namespace CApi\Controller;
use Think\Controller;
class CompanyController extends BaseLoggedController {
	//申请商家入驻
	public function apply(){
		$user = $this->AuthUserInfo;
		$id = I('post.id',0);
		
		//企业名称
		$name = I('post.name');
		if(empty($name)){
			E('请填写企业名称');
		}
		//营业执照
		$license = I('post.license');
		if(empty($license)){
			E('请填写营业执照号');
		}
		//营业执照照片
		$license_link = I('post.license_link');
		if(empty($license_link)){
			E('请填写营业执照照片');
		}
		//入住板块
		$settled = I('post.settled');
		if($settled!=0 && $settled!=1 && $settled!=2){
			E('请选择入住板块');
		}
		//联系人姓名
		$contact = I('post.contact');
		if(empty($contact)){
			E('请填写联系人姓名');
		}
		//联系人电话
		$mobile = I('post.mobile');
		if(empty($mobile)){
			E('请填写联系人电话');
		}
		if(!isMobile($mobile)){
			E("手机号码格式不正确");
		}
		$entity = array(
			'userid'=>$user['id'],'name'=>$name,'license'=>$license,
			'license_link'=>$license_link,'settled'=>$settled,'status'=>0,
			'contact'=>$contact,'mobile'=>$mobile,'updatetime'=>date('Y-m-d H:i:s')
		);
		
		if($id){
			D('company')->where(array('id'=>$id))->save($entity);
		}else{
			$entity['createtime'] = date("Y-m-d H:i:s");
			D('company')->add($entity);
		}
		
		
		
		return ;
	}
	//判断是否有提交过申请
	public function is_apply(){
		$user = $this->AuthUserInfo;
		$map = array('userid'=>$user['id']);
		$company = D('company')->where($map)->find();
		return array('result'=>is_array($company));
	}
	//申请记录
	public function apply_list(){
		$user = $this->AuthUserInfo;
		$map = array('userid'=>$user['id']);
		$list = D('company')->where($map)->select();
		return $list;
	}
	//申请记录详情
	public function apply_detail(){
		$user = $this->AuthUserInfo;
		$id = I('get.id');
		$map = array('id'=>$id,'userid'=>$user['id']);
		$info = D('company')->where($map)->find();
		$info['license_link'] = $this->DoUrlHandle($info['license_link']);
		return $info;
	}
}