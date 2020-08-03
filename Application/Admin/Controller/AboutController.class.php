<?php
namespace Admin\Controller;
use Think\Controller;
class AboutController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("about");
        $map = $this->getMap();
		$map['id']=array('neq',4);
        $data = $model->where($map)->select();
        $this->assign("data", $data);
        $this->assign("map", $map);
		if($map['type']==1){
			$this->display();
		}elseif($map['type']==2){
			$this->display('listac');
		}else{
			$this->display('listab');
		}
        
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("about");
        $data["info"] = $model->find($id);
    
        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["type"] = I("get.type", 0);
            $d["title"] = I("post.title");
            $d["subtitle"] = I("post.subtitle");
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["remark"] = I("post.remark");
            $d["updatetime"] = date("Y-m-d H:i");
    
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $id = $model->add($d);
            }
            $this->redirect('listad?type=0&add=1');
            //alert_back("保存成功");
    		
        }
    
        $this->assign($data);
    	$this->show();
    } 
	
	public function insurance(){
	    $id = I("get.id", 0);
		$doinfo = I("get.doinfo");
	    $model = D("about");
	    $data["info"] = $model->find($id);
	
	    if($doinfo == "modify"){
	        $d["status"] = I("post.status", 1);
	        $d["type"] = I("get.type", 2);
	        $d["title"] = I("post.title");
			$d["content"] = htmlspecialchars_decode(I("post.content"));
	        $d["updatetime"] = date("Y-m-d H:i");
	
	        if($id > 0){
	            $model->where("id=".$id)->save($d);
	        }else{
	            $id = $model->add($d);
	        }
	        $this->redirect('listad?type=2&add=1');
	        //alert_back("保存成功");
			
	    }
	
	    $this->assign($data);
		$this->show();
	} 

    public function serviceinfo(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("about");
        $data["info"] = $model->find($id);

        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    public function service(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("about");
        $data["info"] = $model->find($id);

        $this->assign($data);
    	$this->show();
    }
	public function delad(){
		$id = I("get.id", 0);
		$model = D("about");
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