<?php
namespace Admin\Controller;
use Think\Controller;
class OrgOrderController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createtime desc";
        $param = $this->getMap();
        $map = array("status"=>array("neq", -1) );
        if(I("get.keyword")){
            $where["sn"] = array("like","%".I("get.keyword")."%");
            $where["nickname"] = array("like","%".I("get.keyword")."%");
			$where["mobile"] = array("like","%".I("get.keyword")."%");
            $where["title"] = array("like","%".I("get.keyword")."%");
            $where["_logic"] = "OR";
            $map["_complex"] = $where;
        }
		$where=array();
		if(I('get.status')!=-1 && I('get.status')!==''){
			$where['status'] = $map['status']=I('get.status');
		}
		if(I('get.pay_status')!=-1 && I('get.pay_status')!==''){
			$where['pay_status'] = $map['pay_status']=I('get.pay_status');
		}
		if(I('get.order_type')!=-1){
			$where['order_type'] = I('get.order_type');
			if($where['order_type'] == 1){
				$map['activity_id'] = 0;
			}elseif($where['order_type']==2){
				$map['activity_id'] = array('gt',0);
			}
		}
		D("pension_activity_order")->where(array('notice'=>0))->save(array('notice'=>1));
		$this->assign('where',$where);
        $data = $this->pager("pension_activity_order", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("pension_activity_order");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("OrgOrder/listad", $this->getMap());
    }
	public function next_type(){
		$model = D("pension_activity_order");
		$id = I("get.id");
		$info=$model->find($id);
		if(in_array($info['status'],[1,2])){
			$model->where(array('id'=>$id))->setInc('status',1);
		}
		$this->redirect("OrgOrder/listad", $this->getMap());
	}


    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $status = I("get.status",-1);
		$pay_status = I("get.pay_status",-1);
		$pay_type = I("get.pay_type",-1);
        $keyword = I("get.keyword");
        $map = array("p"=>$p, "type"=>$type, 'status'=>$status, "keyword"=>$keyword,'pay_status'=>$pay_status,'pay_type'=>$pay_type);
        return $map;
    }
}