<?php
namespace Admin\Controller;
use Think\Controller;
class OrgController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "ordernum asc";
        $param = $this->getMap();
        if(I("post.keyword")){
            $map["title"] = array("like","%".I("post.keyword")."%");
            $map["subtitle"] = array("like","%".I("post.keyword")."%");
            $map["_logic"] = "or";
        }
        $data = $this->pager("org", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("org");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["type"] = I("post.type", 0);
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
            $d["province"] = I("post.province");
            $d["city"] = I("post.city");
            $d["region"] = I("post.region");
            $d["address"] = I("post.address");
            $d["attribute1"] = I("post.attribute1");
            $d["attribute2"] = I("post.attribute2");
            $d["attribute3"] = I("post.attribute3");
            $d["content1"] = htmlspecialchars_decode(I("post.content1"));
            $d["content2"] = htmlspecialchars_decode(I("post.content2"));
            $d["content3"] = htmlspecialchars_decode(I("post.content3"));
            $d["content4"] = htmlspecialchars_decode(I("post.content4"));
            $d["content5"] = htmlspecialchars_decode(I("post.content5"));
            $d["query1"] = I("post.query1");
            $d["query2"] = I("post.query2");
            $d["query3"] = I("post.query3");
            $d["query4"] = I("post.query4");
            $d["query5"] = I("post.query5");
            $d["query6"] = I("post.query6");
            $d["query7"] = I("post.query7");
            $d["ordernum"] = I("post.ordernum", 0);
            $d["top"] = I("post.top", 0);
            $d["tel"] = I("post.tel");

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Org/listad", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("org");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Org/listad", $this->getMap());
    }

    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function topad(){
        $id = I("get.id", 0);
        $d['top'] = I("get.top", 0);
        $model = D("org");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }else{
            $model->add($d);
        }
        $this->redirect("Org/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("org");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Org/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Org/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $p = I("get.p");
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}