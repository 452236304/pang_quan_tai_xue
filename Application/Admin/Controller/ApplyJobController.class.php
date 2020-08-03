<?php
namespace Admin\Controller;
use Think\Controller;
class ApplyJobController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate desc";
        $param = $this->getMap();
        if(I("post.keyword")){
            $map["name"] = array("like","%".I("post.keyword")."%");
            $map["mobile"] = array("like","%".I("post.keyword")."%");
            $map["_logic"] = "OR";
        }
        $data = $this->pager("apply_job", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("apply_job");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ApplyJob/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $keyword = I("post.keyword");
        $map = array("p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}