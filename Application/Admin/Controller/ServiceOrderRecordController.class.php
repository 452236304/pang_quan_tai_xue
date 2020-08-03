<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceOrderRecordController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $userid = I("get.userid",0);
        $order = "sor.updatetime desc";
        $param = $this->getMap();
        $map = array('sor.execute_status'=>7, 'sor.userid'=>$userid);
        if($param["keyword"]){
            $where["so.sn"] = array("like","%".$param["keyword"]."%");
            $where["so.mobile"] = array("like","%".$param["keyword"]."%");
            $where["so.contact"] = array("like","%".$param["keyword"]."%");
            $where["so.title"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $count = D("service_order_record")->alias("sor")->join("right join sj_service_order as so on so.id=sor.orderid")->where($map)->group('so.id')->count();
        $model = D("service_order_record")->alias("sor")->join("right join sj_service_order as so on so.id=sor.orderid")->field("so.*,sor.updatetime as plane_time")->group('so.id');
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map,$param);

        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
        $model = D("service_order");
        $doinfo = I("get.doinfo");
        $data = $model->where('id='.$id)->find();
        if($doinfo == "modify"){
        }
        if ($data) {
            $care = D("user_care")->find($data['careid']);
            $comment = D("service_comment")->where('orderid='.$data['id'])->find();
        }
        $param = $this->getMap();
        $param['type'] = $data['type'];

        $this->assign("care", $care);
        $this->assign("comment", $comment);
        $this->assign("info", $data);
        $this->assign("map", $param);
        $this->show();
    }


    public function getMap(){
        $p = I("get.p");
        $keyword = I("post.keyword");
        $userid = I("get.userid");
        $map = array("p"=>$p, "keyword"=>$keyword, "userid"=>$userid);
        return $map;
    }
}