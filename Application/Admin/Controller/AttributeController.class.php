<?php
namespace Admin\Controller;
use Think\Controller;
class AttributeController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("attribute");
        $type = I('get.type',0);
        $map = array('type'=>$type);
        $data = $model->where($map)->order("ordernum asc")->select();
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
        $model = D("attribute");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["type"] = I("get.type", 0);
            $d["name"] = I("post.name");
            $d["ordernum"] = I("post.ordernum");
            $d["remark"] = I("post.remark");

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Attribute/listad", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("attribute");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Attribute/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("attribute");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Attribute/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Attribute/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }


    public function getMap(){
        $p = I("get.p");
        $type = I("get.type");
        $map = array("p"=>$p, "type"=>$type);
        return $map;
    }
}