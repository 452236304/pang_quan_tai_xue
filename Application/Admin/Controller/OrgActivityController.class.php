<?php
namespace Admin\Controller;
use Think\Controller;
class OrgActivityController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("org_activity");
        $type = I('get.type', 0);
        $map = array('type'=>$type);
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
        $model = D("org_activity");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["type"] = I("get.type");
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["subtitle"] = I("post.subtitle");
            $d["thumb"] = I("post.thumb");
            $d["price"] = I("post.price", 0);
            $d["brokerage"] = I("post.brokerage", 0);
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["ordernum"] = I("post.ordernum", 0);
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['thumb'])){
				if(!is_file('.'.$d['thumb'])){
					$this->error($d["thumb"].'图片路径无效');
				}
			}		
            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("OrgActivity/listad", $this->getMap());
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
    	$model = D("org_activity");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("OrgActivity/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("org_activity");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("OrgActivity/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("OrgActivity/listad", $this->getMap()));
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