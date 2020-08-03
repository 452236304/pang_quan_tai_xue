<?php
namespace Admin\Controller;
use Think\Controller;
class OrgActivityRelationController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("org_activity_relation");
        $type = I("get.type", 0);
        $orgid = I("get.orgid", 0);
        $map = array("oar.type"=>$type, "oar.orgid"=>$orgid);
        $data = $model->alias("oar")->join("left join sj_org_activity oa on oar.activityid=oa.id")
            ->join("left join sj_org o on oar.orgid=o.id")->field("oar.*,oa.price,oa.title as oatitle,o.title as otitle")
            ->where($map)->order("id asc")->select();
        $this->assign("data", $data);
        $this->assign("map", $this->getMap());
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("org_activity_relation");

        $type = I("get.type", 0);
        if($doinfo == "modify"){
            $d["type"] = $type;
            $orgid = I("get.orgid");
            if(empty($orgid)){
                alert_back("请选择要关联的机构");
            }
            $d["orgid"] = $orgid;
            $d["status"] = I("post.status", 1);
            $activityid = I("post.activityid");
            if(empty($activityid)){
                alert_back("请选择要关联的活动");
            }
            $d["activityid"] = $activityid;
            $d["dis_price"] = I("post.dis_price", 0);
            $d["brokerage"] = I("post.brokerage", 0);
            $d["updatetime"] = date("Y-m-d H:i:s");

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{

                $map = array("orgid"=>$orgid, "activityid"=>$activityid);
                $check = $model->where($map)->find($map);
                if($check){
                    alert_back("当前机构已经关联所选活动，保存失败");
                }
                
                $model->add($d);
            }
            
            $this->redirect("OrgActivityRelation/listad", $this->getMap());
        }

        $map = array("type"=>$type, "status"=>1);
        $activity = D("org_activity")->where($map)->select();
        $this->assign("activity", $activity);

        $data["info"] = $model->find($id);
        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("org_activity_relation");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("OrgActivityRelation/listad", $this->getMap());
    }

    public function getMap(){
        $type = I("get.type");
        $orgid = I("get.orgid");
        $map = array("type"=>$type, "orgid"=>$orgid,"p"=>$p);
        return $map;
    }
}