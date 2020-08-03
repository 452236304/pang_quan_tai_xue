<?php
namespace CApi\Controller;
use Think\Controller;
class OrderPensionController extends BaseLoggedController {
	//活动订单列表
	public function lists(){
		$user = $this->AuthUserInfo;
		$map = array('status'=>array('neq',-1),'userid'=>$user['id']);
		
		//0全部 1待付款 2待接单 3洽谈中 4订单完成 5已取消 6售后中 7已售后 8已超时
		$status = I('orderstatus',0);
		if($status>0){
			$map['status']=$status-1;
		}
		$page = I('get.page');
		$row = I('get.row');
		$begin = ($page-1)*$row;
		$model = D('pension_activity_order');
		$count = $model->where($map)->count();
		$list = $model->where($map)->limit($begin,$row)->order('createtime desc')->select();
		foreach($list as $k=>&$v){
			$v['status']+=1;
			if($v['status']==1 && $v['createtime'] < date('Y-m-d H:i:s',(time()-1800))){
				$v['status']=8;
			}
			$v['com_status']=$this->orderstatus($v);
		}
		$totalpage = ceil($count/$row);
		$this->SetHttpHeader($totalpage,$count,$page,$row);
		return $list;
	}
	
	//取消订单
	public function cancelorder(){
		$user = $this->AuthUserInfo;
	
		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要取消的订单");
		}
	
		$model = D("pension_activity_order");
	
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["status"] == 3){
			E("订单已完成，无法取消");
		}
		if($order["pay_status"] == 3){
			E("订单已支付，无法取消");
		}
		if($order["status"] != 0 || $order["pay_status"] != 0){
			E("订单状态异常，操作失败");
		}
	
		$entity = array("status"=>4);
		$model->where($map)->save($entity);
		
		return;
	}
	
	//删除订单
	public function deleteorder(){
		$user = $this->AuthUserInfo;
	
		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要删除的订单");
		}
	
		$model = D("pension_activity_order");
	
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if($order["status"] == 3){
			E("订单已完成，无法删除");
		}
		if($order["pay_status"] == 3){
			E("订单已支付，无法删除");
		}
	
	    //检查订单为已超时才可进行删除
	    $time = time();
	    $outtime = strtotime("+30 minute", strtotime($order["createtime"]));
	    if($order["status"] == 1 && $order["pay_status"] == 0 && $outtime >= $time){
			E("订单状态异常，操作失败");
	    }
		if(!in_array($order["status"], [0]) || $order["pay_status"] != 0){
			E("订单状态异常，操作失败");
		}
	
		$entity = array("status"=>-1);
		$model->where($map)->save($entity);
	
		return;
	}
	
	//申请退款
	public function refundorder(){
		$user = $this->AuthUserInfo;
	
		$orderid = I("post.orderid", 0);
		if(empty($orderid)){
			E("请选择要申请的订单");
		}
	
		$model = D("pension_activity_order");
	
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $model->where($map)->find();
		if(empty($order)){
			E("订单不存在，操作失败");
		}
		if(!(in_array($order["status"], [3]) && $order["pay_status"] != 3)){
			E("订单状态异常，操作失败");
		}
	
		$reason = I("post.reason");
		if(empty($reason)){
			E("请输入申请退款的原因");
		}
	
		$images = I("post.images");
	
		//新增订单售后信息
		$refundmodel = D("pension_order_refund");
		$entity = array(
			"userid"=>$user["id"], "orderid"=>$order["id"], "reason"=>$reason, "images"=>$images,
			"createdate"=>date("Y-m-d H:i:s"), "status"=>1
		);
		$refundmodel->add($entity);
	
		//更新订单的售后状态
		$entity = array("status"=>5);
		$model->where($map)->save($entity);
	
		return;
	}
	
	//取消售后订单
	public function cancelrefund(){
	    $user = $this->AuthUserInfo;
	
	    $orderid = I("post.orderid", 0);
	    if(empty($orderid)){
	        E("请选择要取消的售后订单");
	    }
	    $model = D("pension_activity_order");
	
	    $map = array("userid"=>$user["id"], "id"=>$orderid);
	    $order = $model->where($map)->find();
	    if(empty($order)){
	        E("订单不存在，操作失败");
	    }
	    if(!($order["status"] == 5)){
	        E("订单状态异常，操作失败");
		}
		
	    //订单状态改变
	    $entity = array('status'=>3);
	    $map = array("userid"=>$user["id"], "id"=>$orderid);
	    $model->where($map)->save($entity);
	
	    return;
	}
	
	//机构订单评价
	public function comment(){
		$user = $this->AuthUserInfo;
	
		$data = I("post.");
	
		$orderid = $data["orderid"];
		if(empty($orderid)){
			E("请选择要评价的订单");
		}
		$pension_id = $data["pension_id"];
		if(empty($pension_id)){
			E("请选择要评价的机构");
		}
		$content = $data["content"];
		if(empty($content)){
			E("请输入机构的评价内容");
		}
		$comment1 = $data["comment1"];
		if(empty($comment1)){
			E("请对机构设施进行评价");
		}
		$comment2 = $data["comment2"];
		if(empty($comment2)){
			E("请对周边环境进行评价");
		}
		$comment3 = $data["comment3"];
		if(empty($comment3)){
			E("请对服务态度进行评价");
		}
		$comment4 = $data["comment4"];
		if(empty($comment4)){
			E("请对专业能力进行评价");
		}
		$images = $data["images"];
	
		$orgmodel = D("pension");
		$map = array("status"=>1, "id"=>$pension_id);
		$org = $orgmodel->where($map)->find();
		if(empty($org)){
			E("机构不存在");
		}
	
		$ordermodel = D("pension_activity_order");
	
		$map = array("userid"=>$user["id"], "id"=>$orderid);
		$order = $ordermodel->where($map)->find();
		if(empty($order)){
			E("订单不存在，评价失败");
		}
		if($order["status"] != 3){
			E("订单未完成，评价失败");
		}
		if($order["commentid"] > 0){
			E("订单机构已经评价，请勿重复评价");
		}
	
		$entity = array(
			"status"=>1, "userid"=>$user["id"], "nickname"=>$user["nickname"], "avatar"=>$user["avatar"],
			"pension_id"=>$org["id"], "title"=>$org["title"], "thumb"=>$org["thumb"],
			"images"=>$images, "content"=>$content, "orderid"=>$order["id"], "ordersn"=>$order["sn"],
			"createdate"=>date("Y-m-d H:i:s"),"comment1"=>$comment1, "comment2"=>$comment2,
	        "comment3"=>$comment3, "comment4"=>$comment4
		);
	
		$commentid = $entity["id"] = D("pension_comment")->add($entity);
	
		//设置订单为已评论
		$map = array("userid"=>$user["id"], "id"=>$order["id"]);
		$ordermodel->where($map)->save(array("commentid"=>$commentid));
	
		return;
	}
	//订单状态处理
	public function orderstatus($order){
		$status['cancel']=0;
		$status['refund']=0;
		$status['cancelrefund']=0;
		$status['comment']=0;
		$status['read_comment']=0;
		$status['pay']=0;
		//1待付款 2待接单 3洽谈中 4订单完成 5已取消 6售后中 7已售后 8已超时
		switch($order['status']){
			case 1:
				$status['name']='待付款';
				$status['pay']=1;
				$status['cancel']=1;
				break;
			case 2:
				$status['name']='待接单';
				break;
			case 3:
				$status['name']='洽谈中';
				break;
			case 4:
				$status['name']='订单完成';
				$status['refund']=1;
				if($order['commentid']==0){
					$status['comment']=1;
				}else{
					$status['read_comment']=1;
				}
				break;
			case 5:
				$status['name']='已取消';
				break;
			case 6:
				$status['name']='售后中';
				$status['cancelrefund']=1;
				break;
			case 7:
				$status['name']='已售后';
				break;
			case 8:
				$status['name']='已超时';
				$status['cancel']=1;
				break;
		}
		return $status;
	}
	
	//订单评价详情
	public function commentdetail(){
		$user = $this->AuthUserInfo;
	
		$model = D("pension_comment");
	
		$commentid = I("post.commentid", 0);
		if(empty($commentid)){
			E("请选择要查看的机构订单评价");
		}
	
		$map = array("userid"=>$user["id"], "id"=>$commentid);
		$detail = $model->where($map)->find();
		if(empty($detail)){
			E("当前订单还未评价，无法查看");
		}
	
		$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		$detail["images"] = $this->DoUrlListHandle($detail["images"]);
	
		return $detail;
	}
}