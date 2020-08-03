<?php
namespace Admin\Controller;
use Think\Controller;
class VipactivityController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("vipactivity");
        $map = $this->getMap();
        $data = $model->where($map)->select();
        $this->assign("data", $data);
        $this->assign("map", $map);
        $this->display();
    }
    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("vipactivity");
        $data["info"] = $model->find($id);
    
        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["thumb"] = I("post.thumb");
            $d["images"] = I("post.images");
    
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
				$d["createtime"] = date("Y-m-d H:i:s");
                $id = $model->add($d);
            }
            $this->redirect('listad');
            //alert_back("保存成功");
    		
        }
        $this->assign($data);
    	$this->show();
    } 
	public function applylist(){
	    $model = D("vipactivity_enroll");
	    $map = array('activity_id'=>I('get.id'));
	    $data = $model->where($map)->select();
	    $this->assign("data", $data);
	    $this->assign("map", $map);
	    $this->display();
	}
	
	public function delad(){
		$id = I("get.id", 0);
		$model = D("vipactivity");
		$model->delete($id);
		
		$this->redirect('listad',$this->getMap().'&add=1');
	}
    public function getMap(){
        $type = I("get.type");
        $add = I("get.add", 0);
        $map = array("type"=>$type, "add"=>$add);
        return $map;
    }
}