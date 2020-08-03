<?php
namespace Admin\Controller;
use Think\Controller;
class InformationController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "id desc";
        $param = $this->getMap();
        $map = array("type"=>$param["type"]);
        if($param["keyword"]){
            $where["category"] = array("like","%".$param["keyword"]."%");
            $where["title"] = array("like","%".$param["keyword"]."%");
            $where["subtitle"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $data = $this->pager("information", "10", $order, $map, $param);
        $this->assign($data);
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
        $model = D("information");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["type"] = I("get.type");
            $d["status"] = I("post.status", 1);
            $d["category"] = I("post.category");
            $d["title"] = I("post.title");
			$d["intro"] = I("post.intro");
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
            $d["time"] = I("post.time");
            $d["address"] = I("post.address");
            $d["video"] = I("post.video");
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["source"] = I("post.source");
            $d["source_logo"] = I("post.source_logo");
            $d["top"] = I("post.top", 0);
            $d["ordernum"] = I("post.ordernum", 0);

            if($id == 0){
                $d["newstime"] = date("Y-m-d H:i:s");
                $d["createdate"] = date("Y-m-d H:i:s");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Information/listad", $this->getMap());
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
    	$model = D("information");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Information/listad", $this->getMap());
    }

    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function topad(){
        $id = I("get.id", 0);
        $d['top'] = I("get.top", 0);
        $model = D("information");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }
        $this->redirect("Information/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("information");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Information/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Information/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("type"=>$type,"p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}