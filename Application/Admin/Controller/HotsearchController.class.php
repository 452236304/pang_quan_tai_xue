<?php
namespace Admin\Controller;
use Think\Controller;
class HotsearchController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("article_hot_search");
        $data = $model->select();
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
        $model = D("article_hot_search");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $id = $model->add($d);
            }
            
            $this->redirect("Hotsearch/listad", $this->getMap());
        }

        $this->assign($data);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("article_hot_search");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Hotsearch/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $map = array("p"=>$p);
        return $map;
    }
}