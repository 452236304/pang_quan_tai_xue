<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceProjectHourPriceController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("service_project_hour_price");
        $projectid = I("get.projectid", 0);
        $map = array("plp.projectid"=>$projectid);
        $data = $model->alias("plp")->join("left join sj_service_project pp on plp.projectid=pp.id")
            ->field("plp.*,pp.title as otitle")->where($map)->order("hour asc")->select();
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
        $model = D("service_project_hour_price");

        if($doinfo == "modify"){
            $projectid = I("get.projectid");
            if(empty($projectid)){
                alert_back("请选择要关联的服务项目");
            }
            $d["projectid"] = $projectid;
            $d["status"] = I("post.status", 1);
            $hour = I("post.hour", 1);
            $map = array("projectid"=>$projectid, "hour"=>$hour);
            $checkprice = $model->where($map)->find();
            if($checkprice && empty($id)){
                alert_back("当前服务项目已经添加过".$hour."小时日间照护，请勿重复添加");
            }
            $d["hour"] = $hour;
            $d["price"] = I("post.price", 0);
            $d["remark"] = I("post.remark");

            if($id == 0){
                $d["updatetime"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("ServiceProjectHourPrice/listad", $this->getMap());
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
    	$model = D("service_project_hour_price");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ServiceProjectHourPrice/listad", $this->getMap());
    }

    public function getMap(){
        $projectid = I("get.projectid");
        $p = I("get.p");
        $map = array("projectid"=>$projectid,"p"=>$p);
        return $map;
    }
}