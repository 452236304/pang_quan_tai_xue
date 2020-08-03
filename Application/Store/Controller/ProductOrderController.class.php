<?php
namespace Store\Controller;
use Think\Controller;
class ProductOrderController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate desc";
        $param = $this->getMap();
        $status = $param['status'];
		
        $map = array("status"=>array("neq", -1) ,"type"=>$param["type"]);
        if ($status == 'sh') {
            $map = array("status"=>array("in", [5,6]));
        }
		$map['company_id'] = $_SESSION['businessID'];
		$status=I('get.status');
		$pay_status=I('get.pay_status',-1);
		$is_activity=I('get.is_activity',-1);
		if($pay_status!=-1){
			$where_map['pay_status']=$map['pay_status']=$pay_status;
		}
		if($is_activity!=-1){
			$where_map['is_activity']=$map['is_activity']=$is_activity;
		}
		switch($status){
			case "1":
				$where_map['status']=1;
				$map['status']=1;
				$map['pay_status']=0;
				break;
			case "2":
				$map['status']=$where_map['status']=2;
				break;
			case "3":
				$where_map['status']=3;
				$map['status']=1;
				$map['pay_status']=3;
				break;
			case "4":
				$map['status']=$where_map['status']=4;
				break;
			case "5":
				$map['status']=$where_map['status']=5;
				break;
			case "6":
				$map['status']=$where_map['status']=6;
				break;
		}
        if($param['keyword']){
            $where["sn"] = array("like","%".$param['keyword']."%");
            $where["nickname"] = array("like","%".$param['keyword']."%");
            $where["title"] = array("like","%".$param['keyword']."%");
            $where["consignee"] = array("like","%".$param['keyword']."%");
            $where["mobile"] = array("like","%".$param['keyword']."%");
            $where["shipping_name"] = array("like","%".$param['keyword']."%");
            $where["shipping_number"] = array("like","%".$param['keyword']."%");
            $where["keyword"] = array("like","%".$param['keyword']."%");
            $where["_logic"] = "OR";
            $map["_complex"] = $where;
        }
        $data = $this->pager("product_order", "10", $order, $map, $param);
		foreach($data['data'] as $k=>&$v){
			$user_info=D('user')->field('mobile')->where(array('id'=>$v['userid']))->find();
			$v['user_mobile']=$user_info['mobile'];
		}
        $this->assign($data);
		$this->assign('where',$where_map);
        $this->assign("map", $this->getMap());
        $this->display();
    }

    public function shipping(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("product_order");
        $data["info"] = $model->find($id);
		
        if($doinfo == "modify"){
            $d["shipping_name"] = I("post.shipping_name");
            $d["shipping_number"] = I("post.shipping_number");
            $d["shipping_send_date"] = I("post.time");

            if($data["info"]["shipping_status"] == 0){
                $d["shipping_status"] = 1;
            }
            
            if($data["info"]["shipping_status"] == 2){
                alert("订单已收货，操作失败", U("ProductOrder/listad", $this->getMap()));
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }
            
            $this->redirect("ProductOrder/listad", $this->getMap());
        }
		if(empty($data['info']['shipping_send_date'])){
			$data['info']['shipping_send_date']=date('Y-m-d H:i:s');
		}
        $this->assign($data);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("product_order");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ProductOrder/listad", $this->getMap());
    }
	public function examine(){
		$model = D("product_order");
		$id = I("get.id");
		$map = array('id'=>$id);
		$entity = array('examine'=>I('get.examine'));
		$model->where($map)->save($entity);
		$this->redirect("ProductOrder/listad", $this->getMap());
	}


    /**
     * 回复评论
     * [modifyad]
     * @return [type] [description]
     */
    public function comment(){
        $id = I("get.orderid", 0);
        $modelattach = D("order_attach");
        $doinfo = I("get.doinfo");
        $attachdata = $modelattach->where('orderid='.$id)->find();
        if (empty($attachdata)) {
            alert_close('数据不存在');
        }
        $model = D("product_comment");
        $map = array('orderid'=>$attachdata['orderid'], 'productid'=>$attachdata['objectid']);
        $data= $model->where($map)->find();
        if (empty($data)) {
            alert_close('评论不存在');
        }

        if($doinfo == "modify"){
            $d["platform_reply"] = I("post.platform_reply");

            if (empty($d["platform_time"])) {
                $d["platform_time"] = date("Y-m-d H:i");
            }
            $d["adminid"] = $_SESSION['storeID'];
            if($data['id'] > 0){
                $model->where("id=".$data['id'])->save($d);
                alert_back('保存成功');
            }
        }
        $this->assign("info", $data);
        $this->show();
    }
	
	
	public function change_price(){
	    $id = I("get.id", 0);
	    $doinfo = I("get.doinfo");
	    $model = D("product_order");
	    
	    if($doinfo == "modify"){
	        $d["total_amount"] = I("post.total_amount", 0);
	        $d["amount"] = I("post.amount", 0);
	    	
	        if($id > 0){
	            $model->where("id=".$id)->save($d);
	        }
	        
	        $this->redirect("ProductOrder/listad", $this->getMap());
	    }
	    
	    $data["info"] = $model->find($id);
	    $this->assign($data);
	    $this->assign("map", $this->getMap());
	    $this->display();
	}

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $status = I("get.status");
		if(I('get.keyword')){
			$keyword = I('get.keyword');
		}else{
			$keyword = iconv("gb2312","UTF-8",$_GET['keyword']);
		}
        
        $map = array("p"=>$p, "type"=>$type, "keyword"=>$keyword, "status"=>$status);
        return $map;
    }
}