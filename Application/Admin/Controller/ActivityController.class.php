<?php
namespace Admin\Controller;
use Think\Controller;
class ActivityController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $param = $this->getMap();
		$map = array('status'=>array('neq',2));
        if($param["keyword"]){
            $where["title"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $data = $this->pager("activity", "10", "id asc", $map);
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
        $model = D("activity");

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["thumb"] = I("post.thumb");
			$d["num"] = I("post.num");
			$d["object"] = I("post.object");
			$d["qualifications"] = I("post.qualifications");
			$d["starttime"] = I("post.starttime");
			$d["endtime"] = I("post.endtime");
			$d["updatetime"] = date('Y-m-d H:i:s');
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['thumb'])){
				if(!is_file('.'.$d['thumb'])){
					$this->error($d["thumb"].'图片路径无效');
				}
			}
			
            if($id == 0){
                $d["createtime"] = date("Y-m-d H:i:s");
            }
			
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Activity/listad", $this->getMap());
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
    	$model = D("activity");
    	$id = I("get.id");
    	$model->where(array('id'=>$id))->save(array('status'=>2));
    	$this->redirect("Activity/listad", $this->getMap());
    }


    public function getMap(){
        $keyword = I("get.keyword");
        $p = I("get.p");
        $map = array("p"=>$p,"keyword"=>$keyword);
        return $map;
    }
}