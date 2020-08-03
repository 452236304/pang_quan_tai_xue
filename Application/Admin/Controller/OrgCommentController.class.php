<?php
namespace Admin\Controller;
use Think\Controller;
class OrgCommentController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate desc";
        $param = $this->getMap();
        $map = array("orgid"=>$param["orgid"]);
        if(I("post.keyword")){
            $map["nickname"] = array("like","%".I("post.keyword")."%");
        }
        $data = $this->pager("org_comment", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("org_comment");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("OrgComment/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $orgid = I("get.orgid");
        $keyword = I("post.keyword");
        $map = array("p"=>$p, "orgid"=>$orgid, "keyword"=>$keyword);
        return $map;
    }
}