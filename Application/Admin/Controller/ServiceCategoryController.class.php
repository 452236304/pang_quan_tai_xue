<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceCategoryController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("service_category");
		$map=array('type'=>I('get.type'));
        $data = $model->where($map)->order("ordernum asc")->select();
        $this->assign("data", $data);
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
        $model = D("service_category");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
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
            $d["color"] = I("post.color");
            $d["remark"] = I("post.remark");
            $d["ordernum"] = I("post.ordernum", 0);
            $d["role"] = I("post.role", 3);
            $d["hot"] = I("post.hot", 0);
			$d["type"] = I("post.type", 0);

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("ServiceCategory/listad", $this->getMap());
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
    	$model = D("service_category");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ServiceCategory/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("service_category");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("ServiceCategory/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("ServiceCategory/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $map = array("p"=>$p, "type"=>$type);
        return $map;
    }
}