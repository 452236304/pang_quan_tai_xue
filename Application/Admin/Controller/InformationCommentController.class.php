<?php
namespace Admin\Controller;
use Think\Controller;
class InformationCommentController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate desc";
        $param = $this->getMap();
        $map = array("infoid"=>$param["infoid"], "reply"=>0);
        if($param["commentid"]){
            $map["reply"] = 1;
            $map["commentid"] = $param["commentid"];
        }
        if(I("post.keyword")){
            $where["nickname"] = array("like","%".I("post.keyword")."%");
            $where["reply_nickname"] = array("like","%".I("post.keyword")."%");
            $where["content"] = array("like","%".I("post.keyword")."%");
            $where["_logic"] = "OR";
            $map["_complex"] = $where;
        }
        $data = $this->pager("information_comment", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("information_comment");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("InformationComment/listad", $this->getMap());
    }

    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function statusad(){
        $id = I("get.id", 0);
        $d['status'] = I("get.status", 1);
        $model = D("information_comment");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }
        $this->redirect("InformationComment/listad", $this->getMap());
    }

    public function getMap(){
        $commentid = I("get.commentid");
        $infoid = I("get.infoid");
        $p = I("get.p");
        $keyword = I("post.keyword");
        $map = array("commentid"=>$commentid, "infoid"=>$infoid,"p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}