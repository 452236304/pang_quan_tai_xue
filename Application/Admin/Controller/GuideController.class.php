<?php
namespace Admin\Controller;
use Think\Controller;
class GuideController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createtime desc";
        $param = $this->getMap();
        $map = array();
		$map['type']=I('get.type');
        $data = $this->pager("guide", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }
	public function modifyad(){
	    $id = I("get.id", 0);
		$doinfo = I("get.doinfo");
	    $model = D("guide");
	    $data["info"] = $model->find($id);
	
	    if($doinfo == "modify"){
	        $d["title"] = I("post.title");
	        $d["subtitle"] = I("post.subtitle");
	        $d["thumb"] = I("post.thumb");
			
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['thumb'])){
				if(!is_file('.'.$d['thumb'])){
					$this->error($d["thumb"].'图片路径无效');
				}
			}
	        $d["ordernum"] = I("post.ordernum", 0);
	        if($id == 0){
	            $d["createtime"] = date("Y-m-d H:i:s");
	        }
	        if($id > 0){
	            $model->where("id=".$id)->save($d);
	        }else{
				$d["type"] = I("get.type");
	            $model->add($d);
	        }
	        
	        $this->redirect("Guide/listad?type=".I('get.type'));
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
    	$model = D("guide");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Guide/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $keyword = I("post.keyword");
		$type=I('get.type');
        $map = array("p"=>$p, "keyword"=>$keyword,'type'=>$type);
        return $map;
    }
}