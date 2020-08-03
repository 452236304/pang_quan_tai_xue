<?php
namespace Admin\Controller;
use Think\Controller;
class RestaurantController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("restaurant");
        $data = $model->order("ordernum asc")->select();
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
        $model = D("restaurant");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 0);
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
            $d["images"] = I("post.images");
            $d["content"] = I("post.content");
            $d["province"] = I("post.province");
            $d["city"] = I("post.city");
            $d["region"] = I("post.region");
            $d["ordernum"] = I("post.ordernum", 0);

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i:s");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Restaurant/listad", $this->getMap());
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
    	$model = D("restaurant");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Restaurant/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("restaurant");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Restaurant/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Restaurant/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $p = I("get.p");
        $map = array("p"=>$p);
        return $map;
    }
}