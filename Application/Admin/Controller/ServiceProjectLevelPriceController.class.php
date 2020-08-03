<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceProjectLevelPriceController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("service_project_level_price");
        $projectid = I("get.projectid", 0);
        $map = array("plp.projectid"=>$projectid);
        $data = $model->alias("plp")->join("left join sj_service_project pp on plp.projectid=pp.id")
            ->field("plp.*,pp.title as otitle")->where($map)->order("service_level asc")->select();
        $this->assign("data", $data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("service_project_level_price");

        if($doinfo == "modify"){
            $projectid = I("get.projectid");
            if(empty($projectid)){
                alert_back("请选择要关联的服务项目");
            }
            $d["projectid"] = $projectid;
            $d["status"] = I("post.status", 1);
            $service_level = I("post.service_level", 1);
            $map = array("projectid"=>$projectid, "service_level"=>$service_level);
            $checkprice = $model->where($map)->find();
            if($checkprice && empty($id)){
                alert_back("当前服务项目已经添加过".$service_level."星，请勿重复添加");
            }
            $d["service_level"] = $service_level;
            $d["price"] = I("post.price", 0);
            $d["again_price"] = I("post.again_price", 0);
            $d["remark"] = I("post.remark");

            if($id == 0){
                $d["updatetime"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("ServiceProjectLevelPrice/listad", $this->getMap());
        }

        $data["info"] = $model->find($id);
        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("service_project_level_price");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ServiceProjectLevelPrice/listad", $this->getMap());
    }

    public function getMap(){
        $projectid = I("get.projectid");
        $p = I("get.p");
        $map = array("projectid"=>$projectid,"p"=>$p);
        return $map;
    }
}