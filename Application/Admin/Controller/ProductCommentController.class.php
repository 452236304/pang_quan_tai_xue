<?php
namespace Admin\Controller;
use Think\Controller;
class ProductCommentController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate desc";
        $param = $this->getMap();
        $map = array("productid"=>$param["productid"]);
        if(I("post.keyword")){
            $map["nickname"] = array("like","%".I("post.keyword")."%");
        }
        $data = $this->pager("product_comment", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("product_comment");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ProductComment/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $productid = I("get.productid");
        $keyword = I("post.keyword");
        $map = array("p"=>$p, "productid"=>$productid, "keyword"=>$keyword);
        return $map;
    }
}