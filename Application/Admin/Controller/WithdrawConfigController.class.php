<?php
namespace Admin\Controller;
use Think\Controller;

class WithdrawConfigController extends BaseController {
	public function index(){
		$order = '';
		$map = array();
		$param = null;
		$data = $this->pager("withdraw_config", "10", $order, $map, $param);

		$this->assign($data);
		$this->assign("map", $this->getMap());
		$this->show();
	}
	public function update(){
	    $id = I('id', 0, 'intval');
	    if( IS_AJAX ){
	        $data = [
	            'num' => I('num', 0, 'intval'),
				'weekday' => implode(',',I('weekday')),
	            'remark' => I('remark'),
	        ];
	        D()->startTrans();
			if($id){
				$res = D('withdraw_config')->where('id='.$id)->save($data);
			}else{
				$res = D('withdraw_config')->add($data);
			}
	        
			if( $res ){
				D()->commit();
				$this->success();
			}
	        D()->rollback();
	        $this->error('操作失败！');
	    }
	
	    $data = D('withdraw_config')->find($id);
		$data['weekday'] = explode(',',$data['weekday']);
		$number = array('one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6,'seven'=>7);
		$this->assign($number);
	    $this->assign('data', $data);
	    $this->display();
	}
	public function listad(){
		$config_id = I('get.config_id');
		$order = 'amount asc';
		$map = array('config_id'=>$config_id);
		$param = null;
		$data = $this->pager("withdraw_config_money", "10", $order, $map, $param);
	
		$this->assign($data);
		$this->assign("map", $this->getMap());
		$this->show();
	}
	public function money_update(){
	    $id = I('id', 0, 'intval');
	    if( !$id ){
	        $this->error('非法操作！');
	    }
	    if( IS_AJAX ){
	        $data = [
	            'amount' => I('amount', 0, 'intval'),
	        ];
	        D()->startTrans();
	        $res = D('withdraw_config_money')->where('id='.$id)->save($data);
			if( $res ){
				D()->commit();
				$this->success();
			}
	        D()->rollback();
	        $this->error('操作失败！');
	    }
	
	    $data = D('withdraw_config_money')->find($id);
	    $this->assign('data', $data);
	    $this->display();
	}
	public function getMap(){
	    $config_id = I("get.config_id");
	    $p = I("get.p");
	    $map = array("p"=>$p, "config_id"=>$config_id);
	    return $map;
	}
}