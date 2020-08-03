<?php
namespace Admin\Controller;
use Think\Controller;
class UserClassController extends BaseController {
    
    public function listad(){
    	$userclass = D("user_class");
		$data["userclass"] = $userclass->select();
		
		$this->assign($data);
		$this->show();
    }

    public function modifyad(){
    	$id = I("get.id");
		$doinfo = I("get.doinfo");
		$userclass = D("user_class");
		if($id>0){
			$data['info'] = $userclass->where(array('id'=>$id))->find();
		}
		$data['info'] = $userclass->where(array('id'=>$id))->find();
		if($doinfo=="modify"){
			$d['title'] = I('post.title');
			$d['user_list'] = I('post.user_list');
			if($id>0){
				D('user_class')->where(array('id'=>$id))->save($d);
			}else{
				D('user_class')->add($d);
			}
			$this->redirect('listad');
		}
		$user = D('user')->field('id value,nickname title')->where(array('status'=>200))->select();
		$data['user'] = json_encode($user,JSON_UNESCAPED_UNICODE);
		
		$this->assign($data);
		$this->show();
		
    }

    public function delad(){
    	$model = D("user_class");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("UserClass/listad");
    }
}