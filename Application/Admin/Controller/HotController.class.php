<?php
namespace Admin\Controller;
use Think\Controller;
class HotController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("hot_search");
		$data = $this->pager("hot_search", "10", 'weight desc', array(), array());
        $this->assign( $data);
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
        $model = D("hot_search");
        $data["info"] = $model->find($id);
        if($doinfo == "modify"){
            $d["keyword"] = I("post.keyword");
			$d["weight"] = I("post.weight");
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $id = $model->add($d);
            }
            
            $this->redirect("Hot/listad", $this->getMap());
        }

        $this->assign($data);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("hot_search");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Hot/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $map = array("p"=>$p);
        return $map;
    }
}