<?php
namespace Admin\Controller;
use Think\Controller;
class ArticleCommentController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate asc";
        $param = $this->getMap();
        $map = array("articleid"=>$param["articleid"], "reply"=>0);
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
        $data = $this->pager("article_comment", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("article_comment");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ArticleComment/listad", $this->getMap());
    }

    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function statusad(){
        $id = I("get.id", 0);
        $d['status'] = I("get.status");
        $model = D("article_comment");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }else{
            $model->add($d);
        }
        $this->redirect("ArticleComment/listad", $this->getMap());
    }

    public function getMap(){
        $commentid = I("get.commentid");
        $articleid = I("get.articleid");
        $p = I("get.p");
        $keyword = I("post.keyword");
        $map = array("commentid"=>$commentid, "articleid"=>$articleid,"p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}