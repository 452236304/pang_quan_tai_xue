<?php
namespace Admin\Controller;
use Think\Controller;
class OrgConditionController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("org_condition");
        $type = I('get.type', 0);
        $map = array('type'=>$type);
        $data = $model->where($map)->order("ordernum asc")->select();
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
        $model = D("org_condition");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["type"] = I("get.type");
            $d["status"] = I("post.status", 1);
            $d["name"] = I("post.name");
            $d["remark"] = I("post.remark");
            $d["ordernum"] = I("post.ordernum", 0);

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("OrgCondition/listad", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("org_condition");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("OrgCondition/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("org_condition");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("OrgCondition/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("OrgCondition/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $map = array("type"=>$type,"p"=>$p);
        return $map;
    }
}