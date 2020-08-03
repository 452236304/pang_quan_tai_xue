<?php
namespace Admin\Controller;
use Think\Controller;
class EditionController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("edition");
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
        $model = D("edition");
        $data["info"] = $model->find($id);
    
        if($doinfo == "modify"){
			$d["system"] = I("post.system",'android');
			$d["hybrid"] = I("post.hybrid",'user');
            $d["version"] = I("post.version");
			$d["link"] = I("post.file_link");
			$d["is_new"] = I("post.is_new", 1);
			
			$map['system']=$d['system'];
			$map['hybrid']=$d['hybrid'];
			if($d['is_new']==1){
				$model->where($map)->save(array('is_new'=>0));
			}
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
				$map['version']=$d['version'];
				$check_version=$model->where($map)->find();
				if($check_version){
					alert_back("存在相同的版本号");
					$this->redirect('modifyad?id='.$id);
				}
				$d['createtime']=date('Y-m-d H:i:s');
                $id = $model->add($d);
            }
            $this->redirect('listad?type=0&add=1');
            //alert_back("保存成功");
    		
        }
    
        $this->assign($data);
    	$this->show();
    }
	public function delad(){
		$id = I("get.id", 0);
		$model = D("edition");
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