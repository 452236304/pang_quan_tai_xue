<?php
namespace Admin\Controller;
use Think\Controller;
class StoreController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $param = $this->getMap();
		
        if($param["keyword"]){
            $where["title"] = array("like","%".$param["keyword"]."%");
            $where["mobile"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $count = D("store")->where($map)->count();
        $model = D("store");
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", "id asc", $map);
        $this->assign($data);
        $this->assign("map", $param);
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("store");

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["mobile"] = I("post.mobile");
			$d["province"] = I("post.province");
			$d["city"] = I("post.city");
			$d["region"] = I("post.region");
			$d["address"] = I("post.address");
           
            if($id == 0){
                $d["createtime"] = date("Y-m-d H:i:s");
            }
			
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Store/listad", $this->getMap());
        }
		
        $data["info"] = $model->find($id);
        $this->assign($data);

        $this->assign("map", $this->getMap());
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("store");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Store/listad", $this->getMap());
    }
    public function getMap(){
        $keyword = I("get.keyword");
        $p = I("get.p");
        $map = array("p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}