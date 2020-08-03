<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceFormController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("service_form");
		$map=array();
        $data = $model->where($map)->order("id desc")->select();
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
        $model = D("service_form");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["title"] = I("post.title");
            $d["category"] = I("post.category");
			$d["source"] = '自营';
			
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("ServiceForm/listad", $this->getMap());
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
    	$model = D("service_form");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ServiceForm/listad", $this->getMap());
    }

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $map = array("p"=>$p, "type"=>$type);
        return $map;
    }
}